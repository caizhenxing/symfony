<?php

namespace Dcs;

use \Dcs\Security\Mode as Mode;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Response;

/*
 * どこかのコントローラーでMaintenanceかどうかを受けるとき class XXXContoller extends Controller { private $service = null; public function isMaintenance() { if($service == null){ $service = $this->get('Dcs.MaintenanceSchedule'); } return $service->isMaintenance(); } } GAIAのユーザー別のホワイトリストは実装していないので、個別にチェックすること。
 */
class MaintenanceSchedule{
	const file_name = 'mr';
	
	// 受け入れ可能なアドレス文字列の配列
	// Resources/service/Dcs.MaintenanceRecive.yml参照
	private $accept_ips = null;
	private $maintenance_file = null; // ymlから取得 'maintenance/mr';
	private $cache_limit = 0; // ymlから取得
	private $cont = null;
	/**
	 * コンストラクタ
	 */
	public function __construct($accept_ips_,$file_path_root,$mnt_cache_limit,$container){
		$this->accept_ips = $accept_ips_;
		
		$this->maintenance_file = $file_path_root.'/'.self::file_name;
		$this->cache_limit = intval( $mnt_cache_limit );
		$this->cont = $container;
	}
	/**
	 * サービスを取得する
	 * 
	 * @param string $id
	 *        	サービスID
	 * @return サービスコンテナ
	 */
	public function get($id){
		return $this->cont->get( $id );
	}
	public function getRequest(){
		return $this->cont->get( 'request' );
	}
	public function onKernelRequest(GetResponseEvent $event){
		if($this->isMaintenance()){
			if($this->existsFile())
				$event->setResponse( new Response( 'Maintenance', 503 ) );
			elseif(!$this->isManagementToolAction($event->getRequest()))
				$event->setResponse( new Response( 'Maintenance', 503 ) );
		}
	}
	public function start(){
		if($this->checkAdress()){
			$this->createFiles();
			return;
		}
		throw new \Exception( '作成できませんでした。' );
	}
	public function finish(){
		if($this->checkAdress( $controller )){
			$this->deleteFiles();
			return;
		}
		throw new \Exception( '削除できませんでした。' );
	}
	public function isMaintenance(){
		// \Logic\ErrorLogic::error_log('isMaintenance');
		if($this->existsFile()){
			return true;
		}
		
		if($this->whiteList()){
			return false;
		}
		
		if($this->isMaintenanceTime()){
			return true;
		}
		return false;
	}
	public function isMaintenanceByUser($uid){
		if($this->existsFile()){
			return true;
		}
		
		if($this->whiteListByUser( $uid )){
			return false;
		}
		
		if($this->isMaintenanceTime()){
			return true;
		}
		return false;
	}
	public function checkAdress(){
		$ipaddress = $this->getRequest()->getClientIp();
		
		if(in_array( $ipaddress, $this->accept_ips )){
			return true;
		}
		return false;
	}
    /**
     * 管理ツール機能かどうかを判定する
     */
    protected function isManagementToolAction($request)
    {
        $controller = $request->attributes->get('_controller');
        return (strstr($controller, 'mng_tool.controller'));
    }
	function createFiles(){
		if($this->existsFile()){
			return;
		}
		error_log( $this->maintenance_file );
		for( $count = 0;$count<10;++$count ){
			if(touch( $this->maintenance_file )){
				return;
			}
		}
		throw new \Exception( '作成できませんでした。' );
	}
	function deleteFiles(){
		if($this->existsFile()==false){
			return;
		}
		for( $count = 0;$count<10;++$count ){
			if(unlink( $this->maintenance_file )){
				return;
			}
		}
		throw new \Exception( '削除できませんでした。' );
	}
	function existsFile(){
		return is_readable( $this->maintenance_file );
	}
	
	// DB参照の場合
	// return bool
	//
	function isMaintenanceTime(){
		$key = 'Dcs.MaintenanceSchedule.isMaintenanceTime';
		$cache = $this->get( 'Dcs.Cache' );
		$list = $cache->get( \Dcs\Cache::TYPE_APC, $key );
		if($list===null){
			$get_data = $this->cache_limit*10;
			$from = time();
			$to = $from+$get_data;
			
			$from = (new \DateTime())->setTimestamp( $from-3600 );
			$to = (new \DateTime())->setTimestamp( $to );
			
			$list = $this->get( 'gaia.maintenance.maintenance_service' )->getInformationInPeriod( $from, $to );
			$cache->set( \Dcs\Cache::TYPE_APC, $key, $list, $this->cache_limit );
		}
		
		$now = time();
		foreach( $list as $line ){
			$from = \DateTime::createFromFormat( 'Y-m-d H:i:s', $line['start_time'] )->getTimestamp();
			$to = \DateTime::createFromFormat( 'Y-m-d H:i:s', $line['end_time'] )->getTimestamp();
			if($from<=$now&&$now<=$to) return true;
		}
		return false;
	}
	
	/*
	 * //現在のメンテナンスの予定時間を取得
	 */
	public function getMaintenanceNow(){
		$service = $this->get( 'gaia.maintenance.maintenance_service' );
		// 現在時刻
		$now_time = time();
		// 今日の日時文字列
		$nowday = date( 'Y-m-d H:i:s', $now_time );
		$today_datetime = new \DateTime( $nowday );
		
		$data = $service->getInformationAt( $today_datetime );
		if($data==false){
			return null;
		}
		return $data;
	}
	public function getMaintenanceInformation($spanday){
		$service = $this->get( 'gaia.maintenance.maintenance_service' );
		
		// 現在時刻
		$now_time = time();
		// 今日の日時文字列
		$nowday = date( 'Y-m-d H:i:s', $now_time );
		// 情報表示期間
		$effective_to = strtotime( $nowday.' +'.$spanday.' days ' );
		$effective_today = date( 'Y-m-d H:i:s', $effective_to );
		
		$today_datetime = new \DateTime( $nowday );
		$effective_today_datetime = new \DateTime( $effective_today );
		
		$array = $service->getInformationInPeriod( $today_datetime, $effective_today_datetime );
		
		if(count( $array )>0&&isset( $array[0]['maintenance_information_id'] )){
			return $array;
		}
		return null;
	}
	public function whiteList(){
		// DCS_IP_WHITE_LISTに登録されているIPアドレス
		$ipaddress = $this->getRequest()->getClientIp();
		
		$key = 'Dcs.MaintenanceSchedule.whiteList';
		$cache = $this->get( 'Dcs.Cache' );
		$list = $cache->get( \Dcs\Cache::TYPE_APC, $key );
		if($list===null){
			try{
				$rs = $this->get( 'gaia.handler_socket.default_connection' )->select( new Table( 'DCS_IP_WHITE_LIST', [
					'ip_address'
				] ), new Query( [
					'>' => 0
				], -1 ) );
				$list = [];
				foreach( $rs as $row ){
					$list[] = $row[0];
				}
				$cache->set( \Dcs\Cache::TYPE_APC, $key, $list, $this->cache_limit );
			}catch( \Exception $e ){
				error_log( 'Maintenance whiteList Error!!!! :'.$e->getMessage() );
				return false;
			}
		}
		
		foreach( $list as $line ){
			if(strstr( $line, $ipaddress )) return true;
		}
		return false;
	}
	public function whiteListByUser($uid){
		// GAIA_USER_WHITE_LISTに登録されているユーザーかチェック
		$uid = intval( $uid );
		$key = 'Dcs.MaintenanceSchedule.whiteListByUser';
		$cache = $this->get( 'Dcs.Cache' );
		$list = $cache->get( \Dcs\Cache::TYPE_APC, $key );
		if($list===null){
			try{
				$rs = $this->get( 'gaia.handler_socket.default_connection' )->select( new Table( 'GAIA_USER_WHITE_LIST', [
					'user_id'
				] ), new Query( [
					'>' => 0
				], -1 ) );
				$list = [];
				foreach( $rs as $row ){
					$list[] = intval( $row[0] );
				}
				$cache->set( \Dcs\Cache::TYPE_APC, $key, $list, $this->cache_limit );
			}catch( \Exception $e ){
				error_log( 'Maintenance whiteList Error!!!! :'.$e->getMessage() );
				return false;
			}
		}
		
		foreach( $list as $line ){
			if(strstr( $line, $uid )) return true;
		}
		return false;
	}
}
?>