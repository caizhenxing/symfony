<?php
namespace Sega\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;
use \Logic\PlayerData\Mission as Mission;
use \Dcs\Arpg\Time as Time;

class MissionController extends \Dcs\DcsController{
	/**
	 * ミッション情報を取得
	 *
	 * リクエストデータ構造
	 * data: string セッションID
	 *
	 * レスポンスデータ構造
	 * array<Arpg.Logic.Util.Mission>
	 */
	public function listAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$Mission = $this->get('Arpg.Logic.Util.Mission');
			$cleared = $Mission->getCleared($uid);
			return $Mission->getList($uid,$cleared);
		});
	}

	/**
	 * ミッションコンプリート
	 *
	 * リクエストデータ構造
	 * data:[
	 *  	skey: string セッションID
	 *  	mid: int ミッションID
	 *  ]
	 *
	 * レスポンスデータ構造
	 * array<Arpg.Logic.Util.Mission>
	 */
	public function completeAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$mid = intval($data['mid']);
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();
			$Mission = $this->get('Arpg.Logic.Util.Mission');

			$mis = $Mission->complete($uid,$mid);

			$reward = $this->get('Arpg.Logic.GameData.Reward')->add($uid,[[$mis->rewardStdId,$mis->rewardNum]],10003);
			return [
				'mission'=>$Mission->getList($uid,[$mis->id => (new Time())->get()]),
				'cleared'=>[],
				'reward'=>$reward
			];
		});
	}
	
	
	/**
	 * ミッション情報を取得
	 *
	 * リクエストデータ構造
	 * data: string セッションID
	 *
	 * レスポンスデータ構造
	 * array<Arpg.Logic.Util.Mission> 現在の全ミッション
	 */
	public function acceptAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();
			$this->updateActionDate($uid);

			$Mission = $this->get('Arpg.Logic.Util.Mission');
			$Equip = $this->get('Arpg.Logic.Util.Equip');
			$Stack = $this->get('Arpg.Logic.Util.StackItem');
			$PStatus = $this->get('Arpg.Logic.Util.PlayerStatus');

			$now = new Time();
			$now = $now->get();
			
			$cleared = $Mission->getCleared($uid);
			$list = $Mission->getList($uid,$cleared);
			
			$reward_data=[];
			$sub_clear=[];
			foreach($list as $mis){
				if($mis == null) continue;
				if($mis->stat != $mis::STATE_CLEAR) continue;

				$reward_data[]=[$mis->rewardStdId,$mis->rewardNum];
				$cleared[$mis->id] = $now;
				$sub_clear[] = $mis;
			}
			$reward = $this->get('Arpg.Logic.GameData.Reward')->add($uid,$reward_data,10003);
			
			$Mission->clear($uid,$sub_clear);
			
			return [
				'mission'=>$Mission->getList($uid,$cleared),
				'cleared'=>$sub_clear,
				'reward'=>$reward
			];
		});
	}
}
?>