<?php 
namespace Logic;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Component\HttpFoundation\Response;
use Dcs\Arpg\ResError;
use Dcs\Arpg\SimpleResError;
use \Dcs\DetailTimeLog as DTL;

trait DcsControllerFilter{
	/**
	 * Runの最初に呼ばれる
	 */
	protected function runStart(){
		$env = $this->container->getParameter('kernel.environment');
		if(strcasecmp($env,'prod') == 0 && !\Dcs\Arpg\Config::Debug){
			$header = getallheaders();
			if(!isset($header['SGNAppConnect']))
				throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
		}
		$key = 'Arpg.Logic.DcsControllerFilter.runStart.Appver';
		$app_ver = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($app_ver == null){
			$rs = $this->getHs(false)->select(
				new Table('game_info',['data']),
				new Query(['='=>'app_version'])
			);
			if(!empty($rs)){
				$app_ver = $rs[0][0] + 0;
				$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$app_ver,600);	// 10分ごとにチェックしなおし
			}else{
				$app_ver = 0;
			}
		}
		if(isset($header['SGNAppVer']) && is_numeric($header['SGNAppVer'])){
			$aver = $header['SGNAppVer'] + 0;
			if($aver < $app_ver)
				throw new \Symfony\Component\HttpKernel\Exception\HttpException(426,'AppVersion is old');
		}
		if(isset($header['SGNAppTimeOut']) && is_numeric($header['SGNAppTimeOut'])){
			$timeout = intval($header['SGNAppTimeOut']);
			if($timeout < 15)
				$timeout = 15;
			if($timeout > 120)
				$timeout = 120;
			set_time_limit($timeout);
		}
	}
	/**
	 * Runの最後に呼ばれる
	 */
	protected function runEnd(){
		
	}
	
	/**
	 * Exceptionが発生したとき用のrpc->errのデータを生成
	 * @return object
	 */
	protected function makeErrObj(\Exception $e){
		if($e instanceof \Dcs\Arpg\ResError){
			$code = intval($e->getCode());
			$rs = $this->selectHsCache(
					new Table('lang_err_'.\Dcs\Arpg\Config::Lang,['mes','action']),
					new Query(['='=>$code])
			);
			if(empty($rs)){
				return [
					'code' => $code,
					'mes' => 'DBエラー',
					'action' => 1
				];
			}else{
				$form = $e->getFormat();
				$mes = $rs[0][0];
				foreach($form as $key=>$value){
					$mes = str_replace('%'.$key.'%',$value,$mes);
				}
				return [
					'code' => $code,
					'mes' => $mes,
					'action' => intval($rs[0][1])
				];
			}
				
		}elseif($e instanceof \Dcs\Arpg\SimpleResError){
			return [
				'code' => intval($e->getCode()),
				'mes' => $e->getMessage(),
				'action' => intval($e->getAction())
			];
			
		}
		return [
			'code' => 2,
			'mes' => 'DBエラー',
			'action' => 1
		];
	}
	/**
	 * 基本的な実装をまとめた関数
	 * @param bool $use_transaction trueの時トランザクションが有効になる
	 * @param \Dcs\Security\Mode $response_security レスポンス時の暗号化モード
	 * @param function $logic レスポンスデータを返すfunction(&$out_pem=null) 例外が投げられると、DBをロールバックする $pemは必要な時だけ
	 * @return \Symfony\Component\HttpFoundation\Response レスポンスデータ
	 */
	protected function run(\Dcs\Security\Mode $response_security,callable $logic){
		$this->runStart();
		// RPCを生成
		$rpc = new \Dcs\Rpc();
		
		// ロジックを実行
		$sql = $this->get('doctrine')->getConnection();
		$skey = null;
		$ret = null;
		$locker= $this->get('Dcs.RequestLock');
		try{
			$rpc->data = $logic($skey);
			
			$ret = $rpc->toJson($response_security,$skey);

			if($sql->isTransactionActive()){
				$locker->unlock($ret);
				$sql->commit();
				$sql = null;
			}elseif($locker->isLocked()){
				\Dcs\Log::w('this request is locked. but not need lock.');
				$this->get('Dcs.RequestLock')->delete();
			}
		}catch(\Exception $e){
			if($sql != null && $sql->isTransactionActive()){
				$sql->rollBack();
			}
			$this->get('Dcs.RequestLock')->delete();
			if($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface){
				\Dcs\Log::e($e,true);
				throw $e;
			}elseif($e instanceof \Dcs\RequestLock\AlreadyResponseException){
				return new Response($e->response);
			}
			\Dcs\Log::e($e,true);
			$rpc->data = null;
			$rpc->err = $this->makeErrObj($e);
			$ret = $rpc->toJson($response_security,$skey);
		}
		$this->runEnd();
		return new Response($ret);
	}
	
	
	
	// Project Original
	/**
	 * ログインチェックを行う
	 * ログインしてない場合、$rpcにエラーを書き込む
	 * @param \Dcs\CmnAccount $user	アカウントデータ
	 * @param array $skey	セッションキーデータ
	 * @param int $lock_lv リクエストロックするレベルを指定する 0以下で無効
	 * @throws \Exception
	 */
	protected function checkLogin(\Dcs\CmnAccount $user,array $skey,$lock_lv=\Dcs\RequestLock::LOCK_NONE){
		$mes = $user->loginCheck($skey['mSid']);
		if($mes == $user::DBERROR)
			throw new ResError('login db error',2);
		elseif($mes == $user::FAILED)
			throw new ResError('dont login sid:'.$skey['mSid'],1);
		$ban = $user->getBanData();
		if($ban != null)
			throw new SimpleResError(json_encode($ban),1000,1000);
		if($lock_lv > 0){
			$this->get('Dcs.RequestLock')->lock($user->getUid(),$lock_lv);
			$this->useTransaction();
		}
		DTL::Lap('check login');
	}
	
	/**
	 * 最終アクション時間を保存する
	 * @param int $uid ユーザーID
	 */
	public function updateActionDate($uid){
		DTL::Lap('updateActionDate start');
		$std_last_action = 1004;
		try{
			$time = new \Dcs\Arpg\Time();
			if($this->isTransactionActive()){
				$stmt = $this->sql('box_player', 'update box_player set last_action = ? where uid = ?');
				$stmt->update([$time->getMySQLDateTime(),$uid]);
				DTL::Lap('update box_player');
				$stmt = $this->sql('box_player_status', 'update box_player_status set num = ? where uid = ? and std_id = ?');
				$stmt->update([$time->get(),$uid,$std_last_action]);
				DTL::Lap('update box_player_status');
			}else{
				$this->getHs()->update(
						new Table('box_player',['last_action']),
						new Query(['='=>$uid]),
						[$time->getMySQLDateTime()]
				);
				DTL::Lap('update box_player');
				$this->getHs()->update(
						new Table('box_player_status',['num']),
						new Query(['='=>[$uid,$std_last_action]]),
						[$time->get()]
				);
				DTL::Lap('update box_player_status');
			}
		}catch(\Exception $e){
			\Dcs\Log::e($e,true);
		}
		DTL::Lap('updateActionDate end');
	}
}
?>