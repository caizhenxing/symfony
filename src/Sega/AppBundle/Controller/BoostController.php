<?php

namespace Sega\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;

class BoostController extends \Dcs\DcsController{
	/**
	 * ブースト情報を取得
	 * 
	 * リクエストデータ構造
	 * data: string セッションID
	 * 
	 * レスポンスデータ構造
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
		
			$dat = $this->get('Arpg.Logic.PlayerData.Factory');
			$dat->init($user->getUid(),$type);
			return $dat;
		});
	}

}
?>