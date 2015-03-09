<?php

namespace Logic\Jmeter\Scene;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Dcs\Jmeter;
use \Dcs\Security as sec;
use Logic\Util\Equip;

/**
 * Logic\Jmeter\RequestLogicの内容を分割
 * @author takeda_yoshihiro
 *
 */
trait Common{
	private function arpg_readonly_player_home_batch(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/player_home_popup',15,'xor',self::skey($jm),$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_player_home_popup(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					switch($scene){
						case self::SCENE_EQUIP:
							{
								$jm->setNext('arpg/Fitting/model',15,'xor','{"is_male":false,"skin":2,"face":603002,"eye":0,"hair":601000,"hair_color":2,"costume":370087,"weapon":310038,"head_gear":360067,"no_cache":true}',$jm->getAjmData());
								break;
							}
						case self::SCENE_KUSURI:
							{
								$jm->setNext('stdconnect/read/game_navi_chara',15,'xor',[
									'type' => 1,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_MAGIC:
							{
								$jm->setNext('stdconnect/read/game_navi_chara',15,'xor',[
									'type' => 3,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_TRAIN:
							{
								$jm->setNext('stdconnect/read/game_navi_chara',15,'xor',[
									'type' => 4,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_EVO:
							{
								$jm->setNext('stdconnect/read/game_navi_chara',15,'xor',[
									'type' => 5,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_MISSION:
							{
								$jm->setNext('stdconnect/read/game_navi_chara',15,'xor',[
									'type' => 8,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_GACHA:
							{
								$jm->setNext('stdconnect/read/gacha_getter',15,'xor',self::skey($jm),$jm->getAjmData());
								break;
							}
						case self::SCENE_FRIEND:
							{
								$jm->setNext('stdconnect/read/player_friend',15,'xor',self::skey($jm),$jm->getAjmData());
								break;
							}
						case self::SCENE_SHOP:
							{
								$jm->setNext('stdconnect/read/game_navi_chara',15,'xor',[
									'type' => 9,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_RSTONE:
							{
								$jm->setNext('stdconnect/read/game_navi_chara',15,'xor',[
									'type' => 10,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_ETRADE:
							{
								$jm->setNext('stdconnect/read/game_navi_chara',15,'xor',[
									'type' => 11,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_MINFO:
							{
								$ajm = $jm->getAjmData();
								$jm->setNext('stdconnect/read/player_detail',15,'xor',[
									'uid' => $ajm['pid'],
									'skey' => self::skey($jm)
								],$ajm);
								break;
							}
						case self::SCENE_PURCHASE:
							{
								$jm->setNext('stdconnect/read/game_navi_chara',15,'xor',[
									'type' => 13,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_STORY:
							{
								$jm->setNext('stdconnect/read/story_view',15,'xor',self::skey($jm),$jm->getAjmData());
								break;
							}
						case self::SCENE_PREMES:
							{
								$jm->setNext('stdconnect/read/fetch_msg_setting_preset_msgs',15,'xor',self::skey($jm),$jm->getAjmData());
								break;
							}
						case self::SCENE_ATOMCODE:
							{
								$jm->setNext('atom/inviteCode',15,'xor',[
									'sid' => $jm->getSid()
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_TAKEOVER:
							{
								$jm->setNext('stdconnect/read/takeover_info',15,'xor',[
									'aid' => $jm->getPem(),
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_SETUSER:
							{
								$jm->setNext('stdconnect/write/set_mail',15,'xor',[
									'mail' => 'jmt@ex.txt.jpg',
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_MAIL:
							{
								$jm->setNext('stdconnect/read/message_heads',15,'xor',self::skey($jm),$jm->getAjmData());
								break;
							}
						case self::SCENE_PRESENT:
							{
								$jm->setNext('stdconnect/read/fetch_present_box_items',15,'xor',self::skey($jm),$jm->getAjmData());
								break;
							}
						case self::SCENE_ESELL:
							{
								$ajm = $jm->getAjmData();
								$ptmt = $this->sqlCon()->prepare('SELECT id FROM box_equip where uid = ? and state = 0 and `lock` = 0');
								$ptmt->execute([
									$ajm['uid']
								]);
								$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
								$cid = -1;
								foreach($rs as $row){
									if($cid<0||mt_rand(0,10)==1){
										$cid = $row[0];
									}
								}
								$ajm['item'] = $cid;
								$jm->setNext('stdconnect/write/set_equip_lock',15,'xor',[
									'unlock' => [],
									'lock' => [
										$cid
									],
									'skey' => self::skey($jm)
								],$ajm);
								break;
							}
						case self::SCENE_SINGLE:
							{
								$jm->setNext('stdconnect/write/recover_stamina',15,'xor',self::skey($jm),$jm->getAjmData());
								break;
							}
					}
				}
		}
	}
	private function arpg_readonly_game_navi_chara(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					switch($scene){
						case self::SCENE_KUSURI:
							{
								$jm->setNext('stdconnect/read/game_factory_item',15,'xor',[
									'type' => 1
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_MAGIC:
							{
								$jm->setNext('stdconnect/read/game_factory_item',15,'xor',[
									'type' => 3
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_TRAIN:
							{
								$jm->setNext('stdconnect/read/game_train',15,'xor',self::skey($jm),$jm->getAjmData());
								break;
							}
						case self::SCENE_EVO:
							{
								
								$ajm = $jm->getAjmData();
								$ptmt = $this->sqlCon()->prepare('SELECT id,std_id FROM box_equip where uid = ? and state = 0 and `lock` = 0');
								$ptmt->execute([
									$ajm['uid']
								]);
								$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
								$cid = -1;
								foreach($rs as $row){
									$type = Equip::std2type($row[1]);
									$wtype = Equip::std2wtype($row[1]);
									if($type==Equip::TYPE_WEAPON&&$wtype!=Equip::WEAPON_NONE){
										$c = intval($row[0]);
									}elseif($type==Equip::TYPE_HEADGEAR){
										$c = intval($row[0]);
									}elseif($type==Equip::TYPE_COSTUME){
										$c = intval($row[0]);
									}
									if($cid<0||mt_rand(0,10)==1){
										$cid = $c;
									}
								}
								
								$jm->setNext('stdconnect/write/try_evolution',15,'xor',[
									'cid' => $cid,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_MISSION:
							{
								$jm->setNext('stdconnect/read/mission_list',15,'xor',self::skey($jm),$jm->getAjmData());
								break;
							}
						case self::SCENE_SHOP:
							{
								$jm->setNext('stdconnect/read/trade_item_list',15,'xor',[
									'type' => 1,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_RSTONE:
							{
								$jm->setNext('stdconnect/read/trade_item_list',15,'xor',[
									'type' => 2,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_ETRADE:
							{
								$jm->setNext('stdconnect/read/trade_item_list',15,'xor',[
									'type' => 3,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_PURCHASE:
							{
								$jm->setNext('purchase/find_age_limit',15,'xor',['sid' => $jm->getSid()],$jm->getAjmData());
								break;
							}
					}
				}
		}
	}
	
}
?>