<?php
namespace Sega\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Logic\GameData as GameData;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\DetailTimeLog as DTL;

class TrainController extends \Dcs\DcsController{

	/**
	 * 強化設定情報を取得
	 * リクエストデータ構造
	 * data: null
	 * RPC構造
	 * data:{
	 *		gettingTrainExpBase : int
	 *		payTrainMoneyBase : int
	 *		gettingAddonRateBase : int
	 *		payAddonMoneyBase : int
	 * }
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getTrainAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$dat = $this->get('Arpg.Logic.GameData.Train');
			$dat->init();

			return $dat;
		});
	}

	/**
	 * 強化実行
	 * リクエストデータ構造
	 * data: {
	 * 		skey : {mSid:string セッションID},
	 * 		cid : カードユニークID,
	 * 		mats : 素材装備倉庫内IDリスト
	 * }
	 * RPC構造
	 * data:Arpg.Logic.OrderData.Train
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function tryTrainAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$cid = intval($data['cid']);
			$mats = $data['mats'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();
			$this->updateActionDate($uid);

			$stmt = $this->sql('box_actor','select actor_id from box_actor where uid = ? and state = 0');
			$rs = $stmt->selectAll([$uid],\PDO::FETCH_NUM);
			$aids = [];
			foreach($rs as $row){
				$aids[] = $row[0];
			}

			$eq = $this->get('Arpg.Logic.Util.Equip')->getEquipedByActors($aids,true);
			$eqs = [];
			foreach($eq as $l){
				$l = $l['card'];
				foreach($l as $c){
					$eqs[$c]=true;
				}
			}
			foreach($mats as $m){
				$m = intval($m);
				if(isset($eqs[$m])){
					throw new ResError('equiped card use to train.',100);
				}
			}
			$dat = $this->get('Arpg.Logic.OrderData.Train');

			$dat->run($uid,$cid,$mats);
			return $dat;
		});
	}

	/**
	 * 進化実行
	 * リクエストデータ構造
	 * data: {
	 * 		skey : {mSid:string セッションID},
	 * 		cid : カードユニークID,
	 * }
	 * RPC構造
	 * data:Arpg.Logic.OrderData.Evo
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function tryEvolutionAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$cid = $data['cid'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$dat = $this->get('Arpg.Logic.OrderData.Evo');
			$dat->run($user->getUid(),$cid);
			$this->updateActionDate($user->getUid());
			return $dat;
		});
	}

	/**
	 * アドオン合成実行
	 * リクエストデータ構造
	 * data: {
	 * 		skey : {mSid:string セッションID},
	 * 		cid : カードユニークID,
	 * 		mid : 素材カードユニークID,
	 * 		snum : 鉱石数
	 * 		aidx: アドオンインデックス
	 * }
	 * RPC構造
	 * data:Arpg.Logic.OrderData.Addon
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function tryAddonAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('tryAddonAction start');
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$cid = intval($data['cid']);
			$mid = intval($data['mid']);
			$snum = intval($data['snum']);
			$aidx = intval($data['aidx']);
			// ADDON変更対象スロット
			$slot = intval($data['slot']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);

			$dat = $this->get('Arpg.Logic.OrderData.Addon');
			$dat->run($user->getUid(),$cid,$mid,$snum,$aidx,$slot);
			DTL::Lap('end run');
			$this->updateActionDate($user->getUid());
			DTL::Lap('tryAddonAction end');

			return $dat;
		});
	}
}
?>