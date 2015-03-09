<?php

namespace Sega\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\DetailTimeLog as DTL;

class FactoryController extends \Dcs\DcsController{
	/**
	 * 工場情報を取得
	 * リクエストデータ構造
	 * data: {
	 * 		skey: {mSid:string セッションID}
	 * 		type: int 工場タイプ
	 * }
	 * RPC構造
	 * data:Arpg.Logic.PlayerData.Factoryコンテナ
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('getAction start');
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$type = intval($data['type']);
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			DTL::Lap('eheck login');
		
			$dat = $this->get('Arpg.Logic.PlayerData.Factory');
			$dat->init($user->getUid(),$type);
			DTL::Lap('end');
			return $dat;
		});
	}

	/**
	 * アイテム生成実行
	 * リクエストデータ構造
	 * data: {
	 * 		skey : {mSid:string セッションID},
	 * 		type : 工場タイプ,
	 * 		iid : アイテム論理ID,
	 * 		num : 生成アイテム数
	 * }
	 * RPC構造
	 * data:Arpg.Logic.PlayerData.Factory
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function tryFactoryAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){

			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			
			$skey = $data['skey'];
			$type = intval($data['type']);
			$iid = intval($data['iid']);
			$num = intval($data['num']);
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();
			$this->updateActionDate($uid);
			
			$Util = $this->get('Arpg.Logic.Util.Factory');
			$Util->make($uid,$type,$iid,$num);

			$dat = $this->get('Arpg.Logic.PlayerData.Factory');
			$dat->init($uid,$type);
		
			return $dat;
		});
	}


	/**
	 * 工場生成物取得
	 * リクエストデータ構造
	 * data: {
	 * 		skey : {mSid:string セッションID},
	 * 		type : 工場タイプ,
	 * }
	 * RPC構造
	 * data:Arpg.Logic.PlayerData.Factory
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function completeFactoryAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			
			$skey = $data['skey'];
			$type = intval($data['type']);
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();
			
			$Util = $this->get('Arpg.Logic.Util.Factory');
			$reward = $Util->move($uid,$type,$item,$cards,$present);
			
			$dat = $this->get('Arpg.Logic.PlayerData.Factory');
			$dat->init($uid,$type);
		
			return [
				'factory' => $dat,
				'reward' => $reward
			];
		});
	}


	/**
	 * 工場生成キャンセル
	 * リクエストデータ構造
	 * data: {
	 * 		skey : {mSid:string セッションID},
	 * 		type : 工場タイプ,
	 * }
	 * RPC構造
	 * data:Arpg.Logic.PlayerData.Factory
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function cancelFactoryAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			
			$skey = $data['skey'];
			$type = intval($data['type']);
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();
			
			$Util = $this->get('Arpg.Logic.Util.Factory');
			$Util->cancel($uid,$type);
			
			$dat = $this->get('Arpg.Logic.PlayerData.Factory');
			$dat->init($uid,$type);
		
			return $dat;
		});
	}
	
	/**
	 * 工場拡張
	 * リクエストデータ構造
	 * data: {
	 * 		skey : {mSid:string セッションID},
	 * 		type : 工場タイプ,
	 * }
	 * RPC構造
	 * data:Arpg.Logic.PlayerData.Factory
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function upgradeFactoryAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			
			$skey = $data['skey'];
			$type = intval($data['type']);
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();
			
			$Util = $this->get('Arpg.Logic.Util.Factory');
			$Util->upgrade($uid,$type);
			
			$dat = $this->get('Arpg.Logic.PlayerData.Factory');
			$dat->init($uid,$type);
		
			return $dat;
		});
	}

	/**
	 * 妖精使用
	 * リクエストデータ構造
	 * data: {
	 * 		skey : {mSid:string セッションID},
	 * 		type : 工場タイプ,
	 * }
	 * RPC構造
	 * data:Arpg.Logic.PlayerData.Factory
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function useSpiritAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			
			$skey = $data['skey'];
			$type = intval($data['type']);
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();
			
			$Util = $this->get('Arpg.Logic.Util.Factory');
			$Util->useSpirit($uid,$type);
			
			$dat = $this->get('Arpg.Logic.PlayerData.Factory');
			$dat->init($uid,$type);
		
			return $dat;
		});
	}
}
?>