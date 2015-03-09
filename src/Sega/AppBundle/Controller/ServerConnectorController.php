<?php
namespace Sega\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Security as Security;

class ServerConnectorController extends \Dcs\DcsController{
     /**
       ローカルから一度経由してきた。
       ゲームバージョンをもとに、アドレスを返す
       
	 * レスポンス
	 * xor暗号化
	 * Rpc
	 * 	data
	 * 	 game_ver bundle_version
	 *	err 1:失敗 2:DBエラー
       */
  public function connectorAction(Request $request , $data) {
    $data = json_decode(Security::decrypt(Security\Mode::RSA(),$data),TRUE);
    //var_dump($data);
    $rpc = new \Dcs\Rpc();

    $server_connector = new \Dcs\ServerConnector();
    $ret = $server_connector->connectorToGameVersion($this , $request , $data['game_version'] , $data['platform']);

    if($ret == \Dcs\ServerConnector::SUCCESS){
      $rpc->data = array('url' => $server_connector->getReturnUrl());
    }else{
      if($ret == \Dcs\ServerConnector::FAILED)
        $rpc->err = 1;
      if($ret == \Dcs\ServerConnector::DBERROR)
        $rpc->err = 2;
    }
    return new Response($rpc->toJson(Security\Mode::X_OR()));
  }
  
	/**
	 * レスポンス
	 * xor暗号化
	 * Rpc
	 * 	data
	 * 		sid セッションID
	 *	err 1:ログイン失敗 2:DBエラー
	 * 		
	 */
	public function loginAction($data){
		$data = json_decode(Security::decrypt(Security\Mode::RSA(),$data),TRUE);

		
		$acc = new \Dcs\CmnAccount($this->get('gaia.user.user_service'));
		$ret = $acc->login($data['mUuid']);
		$rpc = new \Dcs\Rpc();
		
		if($ret == \Dcs\CmnAccount::SUCCESS){
			$rpc->data = array(
				'mSid'=>$acc->getSid()
			);
		}else{
			if($ret == \Dcs\CmnAccount::FAILED)
				$rpc->err = 1;
			if($ret == \Dcs\CmnAccount::DBERROR)
				$rpc->err = 2;
		}
		
		return new Response($rpc->toJson(Security\Mode::X_OR()));
	}
	
	/**
	 * レスポンス
	 * xor暗号化
	 * Rpc
	 * 	data
	 * 		uuid ログイン用ID
	 * 		sid 一時ID
	 *	err 1:ログイン失敗 2:DBエラー
	 * 		
	 */
	public function createaccountAction($data){
		$data = json_decode(Security::decrypt(Security\Mode::RSA(),$data),TRUE);
		$memcache = $this->getMemcached();
		
		$rpc = new \Dcs\Rpc($memcache);
		
		// キャッシュあるならそっちから
		$cache = $rpc->getCache();
		if($cache !== FALSE){
			return new Response($cache);
		}
		
		$acc = new \Dcs\CmnAccount($this->get('gaia.user.user_service'));
		$ret = $acc->create();
		
		if($ret == \Dcs\CmnAccount::SUCCESS){
			$rpc->data = array(
				'mL' => array('mUuid'=>$acc->getUuid()),
				'mS' => array('mSid'=>$acc->getSid()),
			);
		}else{
			if($ret == \Dcs\CmnAccount::FAILED)
				$rpc->err = 1;
			if($ret == \Dcs\CmnAccount::DBERROR)
				$rpc->err = 2;
		}
		
		return new Response($rpc->toJson(Security\Mode::RSA(),$data['pem']));
		
	}
}
?>