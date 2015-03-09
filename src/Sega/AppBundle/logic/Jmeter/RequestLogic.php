<?php

namespace Logic\Jmeter;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Dcs\Jmeter;
use \Dcs\Security as sec;
use Logic\Util\Equip;

class RequestLogic extends \Dcs\Arpg\Logic implements \Dcs\JmeterRequestInterface{
	use Scene\Action;
	use Scene\Common;
	use Scene\EquipnItem;
	use Scene\Evo;
	use Scene\Factory;
	use Scene\Friend;
	use Scene\Gacha;
	use Scene\Menu;
	use Scene\Message;
	use Scene\Mission;
	use Scene\NewUser;
	use Scene\Present;
	use Scene\StartUp;
	use Scene\Takeover;
	use Scene\Trade;
	use Scene\Train;
	
	const MODE_DEF = 0; //
	const MODE_NEW = 1;
	const MODE_NUONLY = 2;
	
	/*
	 * メモ 全送メールを大量に登録しておく 初期アイテムに CPとフロラを大量に追加しておく 進化素材を大量に用意しておく
	 */
	const SCENE_NONE = 0;
	const SCENE_EQUIP = 1;
	const SCENE_KUSURI = 2;
	const SCENE_MAGIC = 3;
	const SCENE_TRAIN = 4;
	const SCENE_EVO = 5;
	const SCENE_MISSION = 6;
	const SCENE_GACHA = 7;
	const SCENE_FRIEND = 8;
	const SCENE_SHOP = 9;
	const SCENE_RSTONE = 10;
	const SCENE_ETRADE = 11;
	const SCENE_MINFO = 12;
	const SCENE_PURCHASE = 13;
	const SCENE_STORY = 14;
	const SCENE_PREMES = 15;
	const SCENE_ATOMCODE = 16;
	const SCENE_TAKEOVER = 17;
	const SCENE_SETUSER = 18;
	const SCENE_MAIL = 19;
	const SCENE_PRESENT = 20;
	const SCENE_ESELL = 21;
	const SCENE_SINGLE = 100;
	const NameBase = 'jMet';
	
	const START_SCENE = 1;
	const USE_POP = 0;
	const USE_BATCH = 1;
	const USE_RAND = 2;
	/**
	 * 次リクエスト生成ロジックを実行する
	 *
	 * @param Jmeter $jm Jmeterインスタンス
	 */
	public function run(Jmeter $jm){
		
		$mode = self::MODE_DEF;
		if(strcmp($jm->getMode(),'new_user')==0){
			$mode = self::MODE_NEW;
		}elseif(strcmp($jm->getMode(),'new_user_only')==0){
			$mode = self::MODE_NUONLY;
		}
		$ajm = $jm->getAjmData();
		$scene = self::SCENE_NONE;
		if(isset($ajm['scene'])){
			$scene = intval($ajm['scene']);
		}
		/*
		if(!isset($ajm['ajm_num'])){
			$ajm['ajm_num'] = 0;
		}else{
			$ajm['ajm_num'] = $ajm['ajm_num'] + 1;
		}
		$jm->setAjmData($ajm);
		*/
		$route = $jm->getRequest()->get('_route');
		if(strlen($route)>0)
			$this->$route($jm,$mode,$scene);
		else
			echo '[route is empty]';
	}
	/**
	 * Jmeter起動時リクエスト
	 */
	private function dcs_jmeter(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
				{
					\Dcs\Log::i('### Jmeter NewUserOnly Start ###');
					$jm->setNext('dcs/create_account',15,'rsa',[
						'info' => 'jmeter test OS',
						'pem' => $jm->getPem(),
						'type' => 0
					],$jm->getAjmData());
					break;
				}
			default:
				{
					if(mt_rand(0,99)<10){
						\Dcs\Log::i('### Jmeter NewUser Start ###');
						$jm->newMode('new_user');
						$jm->setNext('stdconnect/read/game_info',15,'xor',null,$jm->getAjmData());
					}else{
						$udata = $this->GetUserIDS();
						if($udata[0] != null){
							\Dcs\Log::i('### Jmeter Default Start '.$uuid.' ###');
							$jm->newUuid($udata[0]);
							$jm->newMode('default');
							$ajm = $jm->getAjmData();
							$ajm['uid'] = $udata[1];
							$jm->setNext('stdconnect/read/game_info',15,'xor',null,$ajm);
						}else{
							\Dcs\Log::i('### Jmeter Dont Start ###');
						}
					}
					break;
				}
		}
	}
	private function GetUserIDS(){
		$uuid = null;
		$uid = null;
		$count = 0;
		while($uuid == null && $count < 30){
			$count++;
			set_time_limit(60);
			$fp = fopen('/var/www/html/symfony/app/logs/jmeter.uid','c+');
			flock($fp,LOCK_EX);
			$uid = intval(trim(fread($fp,1024)));
			if($uid < \Dcs\Arpg\Config::JmeterMinUID) $uid = \Dcs\Arpg\Config::JmeterMinUID;
			ftruncate( $fp, 0 );
			fseek($fp,0);
			$mes = ($uid+1>\Dcs\Arpg\Config::JmeterMaxUID?\Dcs\Arpg\Config::JmeterMinUID:$uid+1);
			fwrite($fp,$mes);
			flock($fp,LOCK_UN);
			fclose($fp);
			
			$rs = $this->getHs()->select(
					new Table('GAIA_USER_ACCOUNT',['uuid','os_type_id']),
					new Query(['='=>$uid])
			);
			if(!empty($rs) && intval($rs[0][1]) == 0){
				$uuid = $rs[0][0];
				$rs = $this->getHs()->select(
						new Table('box_actor',['actor_id'],'UID'),
						new Query(['='=>$uid])
				);
				if(empty($rs)){
					$uuid = null;
				}
			}
		}
		return [$uuid,$uid];
	}
	
	/**
	 * シーン入れ替えしつつバッチ系をはしらせる
	 *
	 * @param Jmeter $jm        	
	 * @param unknown $scene        	
	 * @param string $is_batch        	
	 */
	private function changeScene(Jmeter $jm,$scene,$usetype = self::USE_RAND){
		$ajm = $jm->getAjmData();
		$ajm['scene'] = $scene;
		$path = '';
		switch(intval($usetype)){
			case self::USE_POP:
				$path = 'stdconnect/read/player_home_popup';
				break;
			case self::USE_BATCH:
				$path = 'stdconnect/read/player_home_batch';
				break;
			case self::USE_RAND:
			default:
				if(mt_rand(0,99)<50)
					$path = 'stdconnect/read/player_home_popup';
				else
					$path = 'stdconnect/read/player_home_batch';
				break;
		}
		$jm->setNext($path,15,'xor',self::skey($jm),$ajm);
	}
	private function sqlCon(){
		return $this->get('doctrine')->getConnection();
	}
	
	private function cleanAjm(Jmeter $jm){
		$base = [ // 消したくないキーをリスト化
			'uid' => true,		// ユーザーID
			'pid' => true,		// パブリックユーザーID
			'aid' => true,		// アクターID
			'scene' => true,	// Jmeterシーン名
		];
		$ajm = $jm->getAjmData();
		$swap = [];
		foreach($ajm as $key => $val){
			if(isset($base[$key]))
				$swap[$key] = $val;
		}
		$jm->setAjmData($swap);
	}
	
	/**
	 * Jmeterオブジェクトからセッションキーオブジェクトを生成する
	 *
	 * @param Jmeter $jm        	
	 * @return array
	 */
	static private function skey(Jmeter $jm){
		return [
			'mSid' => $jm->getSid()
		];
	}
}
?>