<?php
namespace Dcs;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class Jmeter extends \Dcs\Arpg\Logic{
	/**
	 * レスポンス暗号化用のPEMデータ
	 * @return string
	 */
	public function getPem(){
		return $this->mPem;
	}
	/**
	 * Jmeterモード
	 * @return string
	 */
	public function getMode(){
		return $this->mMode;
	}
	/**
	 * GaiaセッションID
	 * @return string
	 */
	public function getSid(){
		return $this->mSid;
	}
	/**
	 * Gaiaログイン用ID
	 * @return string
	 */
	public function getUuid(){
		return $this->mUuid;
	}
	/**
	 * 前リクエスト時に設定した ajm_data
	 * @return array
	 */
	public function getAjmData(){
		return $this->mData;
	}
	public function setAjmData($ajm){
		$this->mData = $ajm;
	}
	/**
	 * リクエストデータ
	 * @return Request
	 */
	public function getRequest(){
		return $this->mReq;
	}
	/**
	 * レスポンスに使用したRpcオブジェクト
	 * @return Rpc
	 */
	public function getRpc(){
		return self::$mRpc;
	}
	
	/**
	 * 新しいModeを設定する
	 * @param string $mode
	 */
	public function newMode($mode){
		$this->mMode = $mode;
	}
	/**
	 * 新しいセッションIDを設定する
	 * @param string $sid
	 */
	public function newSid($sid){
		$this->mSid = $this->mNewSid = $sid;
	}
	/**
	 * 新しいUUIDを設定する
	 * @param string $uuid
	 */
	public function newUuid($uuid){
		$this->mUuid = $this->mNewUuid = $uuid;
	}
	
	/**
	 * 次リクエストを設定する
	 * @param string $path		ex 'stdconnect/read/init_data'
	 * @param int $timeout		ex 15
	 * @param 'none'|'xor'|'rsa' $reqtype
	 * @param mixed $reqdata 送信するデータ		ex ['skey'=>['mSid'=>'aaaaaaaaaaaa']]
	 * @param array $ajm_data セッションごとに保存されるデータ。大きくならないように	ex ['sescount' => 12]
	 */
	public function setNext($path,$timeout,$reqtype,$reqdata,array $ajm_data){
		$this->mNext = [
			'path' => $path,
			'timeout' => intval($timeout),
			'reqtype' => $reqtype,
			'request' => $reqdata,
			'ajm_data' => $ajm_data,
		];
	}
	/**
	 * イベント関数 通常これをよびだすことはない
	 * @param FilterResponseEvent $event
	 */
	public function onKernelResponse(FilterResponseEvent $event){
		if(!\Dcs\config::JmeterMode)
			return;
		//$start = microtime(true);
		
		$res = $event->getResponse();
		if($res->getStatusCode() != 200)
			return;
		$header = getallheaders();
		
		$this->mMode = isset($header['AJM_MODE'])?$header['AJM_MODE']:"";
		$this->mPem = isset($header['AJM_PEM'])?$header['AJM_PEM']:null;
		$this->mReq = $event->getRequest();
		$this->mSid = isset($header['AJM_SID'])?$header['AJM_SID']:"";
		$this->mUuid = isset($header['AJM_UUID'])?$header['AJM_UUID']:"";
		$this->mData = isset($header['AJM_DATA'])?json_decode($header['AJM_DATA'],true):[];
		
		if($this->mPem == null)
			return;	// Jmeterからきてないっぽい
		
		$this->get('Dcs.JmeterRequest')->run($this);
		$res->headers->set('ajm_next',json_encode($this->mNext));
		$res->headers->set('ajm_mode', $this->mMode);
		if($this->mNewSid != null) 
			$res->headers->set('ajm_sid',$this->mNewSid);
		if($this->mNewUuid != null)
			$res->headers->set('ajm_uuid',$this->mNewUuid);
		/*if(!\Dcs\config::RequestAggregate && isset($this->mNext['path']))
			error_log($this->mNext['path'].','.((microtime(true)-$start)*1000)."\n",3,'../app/logs/RequestAggregate.log');
			*/
	}
	static public function setRpc(\Dcs\Rpc $rpc){
		self::$mRpc = $rpc;
	}
	
	private $mPem;
	private $mMode;
	private $mSid;
	private $mUuid;
	private $mData;
	private $mReq;
	static private $mRpc;
	private $mNext = [];
	private $mNewSid = null;
	private $mNewUuid = null;
}
?>