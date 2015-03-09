<?php
namespace Sega\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Security as Security;

class CmnAccountController extends \Dcs\DcsController{
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

		$lkey = $data['lkey'];
		$ostype = intval($data['type']);
		$acc = $this->get('Dcs.CmnAccount');
		$ret = $acc->login($lkey['mUuid'],$ostype);

		$rpc = new \Dcs\Rpc();
		
		if($ret == \Dcs\CmnAccount::SUCCESS){
			$rpc->data = array(
				'mSid'=>$acc->getSid()
			);
			$this->addSuccessLog($acc->getUid(),'{"sid":"'.$acc->getSid().'"}',['Dcs','CmnAccount','login']);
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
		
		$rpc = new \Dcs\Rpc();
		
		$acc = $this->get('Dcs.CmnAccount');
		$con = $this->get('doctrine')->getConnection();
		try{
			$con->beginTransaction();
			$ret = $acc->create($data['type'],$data['info']);
			$con->commit();$con=null;
		}catch(\Exception $e){
			\Dcs\Log::e($e,true);
			if($con != null)$con->rollBack();
			$ret = \Dcs\CmnAccount::DBERROR;
		}
		if($ret == \Dcs\CmnAccount::SUCCESS){
			$rpc->data = array(
				'mL' => array('mUuid'=>$acc->getUuid()),
				'mS' => array('mSid'=>$acc->getSid()),
			);
			$this->addSuccessLog($acc->getUid(),'{"uuid":"'.$acc->getUuid().'","sid":"'.$acc->getSid().'"}',['Dcs','CmnAccount','create']);
		}elseif($ret == \Dcs\CmnAccount::FAILED)
			$rpc->err = 1;
		elseif($ret == \Dcs\CmnAccount::DBERROR)
			$rpc->err = 2;

		return new Response($rpc->toJson(Security\Mode::RSA(),$data['pem']));
	}
}
?>