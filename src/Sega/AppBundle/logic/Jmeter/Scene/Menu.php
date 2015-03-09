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
trait Menu{
	private function arpg_readwrite_set_user_comment(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_PURCHASE,self::USE_BATCH);
				}
		}
	}
	private function arpg_readonly_player_detail(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/write/set_user_comment',15,'xor',[
						'mes' => 'i am jmeterUser. this is test message.',
						'skey' => self::skey($jm)
					],$jm->getAjmData());
				}
		}
	}
	private function acme_purchase_could_buy_payment(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_STORY);
				}
		}
	}
	private function acme_purchase_list_android(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('purchase/could_buy_payment',15,'xor',[
						'os_type' => 'Android',
						'product_id' => 'com.sega.arpgtest2.100cp',
						'sid' => $jm->getSid()
					],$jm->getAjmData());
				}
		}
	}
	private function acme_purchase_find_age_limit(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('purchase/list_android',15,'xor',['sid' => $jm->getSid()],$jm->getAjmData());
				}
		}
	}
	private function acme_purchase_update_age_limit(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_MAIL);
				}
		}
	}
	private function arpg_readonly_get_cp(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('purchase/update_age_limit',15,'xor',[
						'month' => 1,
						'year' => 1980,
						'sid' => $jm->getSid()
					],$jm->getAjmData());
				}
		}
	}

	private function arpg_readonly_adv_data(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					$adv = 0;
					if(isset($ajm['adv'])){
						$adv = intval($ajm['adv']);
					}
					switch($adv){
						case 0:
							{
								$jm->setNext('stdconnect/read/player_state',15,'xor',self::skey($jm),$ajm);
								break;
							}
						case 1:
							{
								$jm->setNext('arpg/Fitting/model_by_actor',15,'xor',$ajm['aid'],$ajm);
								break;
							}
						case 2:
							{
								// new user end
								break;
							}
					}
					break;
				}
			default:
				{
					$this->changeScene($jm,self::SCENE_PREMES);
					break;
				}
		}
	}
	private function arpg_readonly_story_view(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/adv_data',15,'xor',[
						'did' => 1000000,
						'type' => 3,
						'skey' => self::skey($jm)
					],$jm->getAjmData());
				}
		}
	}
	private function arpg_readwrite_set_msg_setting_autowords(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_ATOMCODE);
				}
		}
	}
	private function arpg_readwrite_set_msg_setting_preset_msgs(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/write/set_msg_setting_autowords',15,'xor',[
						'words' => [
							[
								'id' => 0,
								'title' => null,
								'msg' => 'jmt',
								'toggle' => true
							]
						],
						'skey' => self::skey($jm)
					],$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_fetch_msg_setting_autowords(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/write/set_msg_setting_preset_msgs',15,'xor',[
						'msgs' => [
							[
								'id' => 0,
								'msg' => 'yoro'
							]
						],
						'skey' => self::skey($jm)
					],$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_fetch_msg_setting_preset_msgs(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					
					break;
				}
			default:
				{
					switch($scene){
						case self::SCENE_SINGLE:
							{
								$jm->setNext('stdconnect/read/player_friend',15,'xor',self::skey($jm),$jm->getAjmData());
								break;
							}
						default:
							{
								$jm->setNext('stdconnect/read/fetch_msg_setting_autowords',15,'xor',self::skey($jm),$jm->getAjmData());
								break;
							}
					}
					break;
				}
		}
	}
	private function acme_atom_invite_code(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_TAKEOVER);
				}
		}
	}
	private function arpg_readwrite_set_mail(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/get_cp',15,'xor',self::skey($jm),$jm->getAjmData());
				}
		}
	}
}
?>