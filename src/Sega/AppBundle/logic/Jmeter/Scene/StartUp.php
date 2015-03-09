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
trait StartUp{
	private function arpg_readonly_game_info(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					$jm->setNext('dcs/create_account',15,'rsa',[
							'info' => 'jmeter test OS',
							'pem' => $jm->getPem(),
							'type' => 0
							],$jm->getAjmData());
					break;
				}
			default:
				{
					$jm->setNext('dcs/login',15,'rsa',[
							'info' => 'jmeter test OS',
							'type' => 0,
							'lkey' => [
							'mUuid' => $jm->getUuid()
							]
							],$jm->getAjmData());
				}
		}
	}
	private function dcs_login(Jmeter $jm,$mode,$scene){
		$rpc = $jm->getRpc();
		if($rpc->err != null || strlen($rpc->data['mSid']) < 1){
			throw new \Exception('Jmeter dcs_login failed -> HttpStatus 500');
		}
		$jm->newSid($rpc->data['mSid']);
	
		switch($mode){
			case self::MODE_NEW:
			default:
				{
				$ajm = $jm->getAjmData();
					\Dcs\Log::i('Jmeter done login uid:'.$ajm['uid'].' uuid:'.$jm->getUuid().' sid:'.$rpc->data['mSid']);
					$jm->setNext('stdconnect/read/init_data',15,'xor',null,$ajm);
				}
		}
	}
	private function arpg_readonly_init_data(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/seq_tutorial',15,'xor',null,$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readonly_seq_tutorial(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/chara_create_data',15,'xor',null,$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_chara_create_data(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/player_basic',15,'xor',self::skey($jm),$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readonly_player_basic(Jmeter $jm,$mode,$scene){
		$ajm = $jm->getAjmData();
		$ajm['pid'] = $jm->getRpc()->data->id;
		switch($mode){
			case self::MODE_NUONLY:
				{
					// aid 取得のため
					$jm->setNext('stdconnect/read/player_actor',15,'xor',self::skey($jm),$ajm);
					break;
				}
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/trade_menu',15,'xor',null,$ajm);
				}
		}
	}
	private function arpg_readonly_trade_menu(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/player_home_pub',15,'xor',null,$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_player_home_pub(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/player_current_actor',15,'xor',self::skey($jm),$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_player_current_actor(Jmeter $jm,$mode,$scene){
		$ajm = $jm->getAjmData();
		$ajm['aid'] = $this->get('Arpg.Logic.Util.ActorStatus')->getActorId($ajm['uid']);
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/player_actor',15,'xor',self::skey($jm),$ajm);
				}
		}
	}
	private function arpg_readonly_player_actor(Jmeter $jm,$mode,$scene){
		$ajm = $jm->getAjmData();
		if(intval($ajm['aid'])==0){
			$data = $jm->getRpc()->data;
			if(!empty($data)){
				$ajm['aid'] = $data[0]->id;
			}
		}
		switch($mode){
			case self::MODE_NUONLY:
				{
					$jm->setNext('stdconnect/read/get_init_actor_parts',15,'xor',self::skey($jm),$ajm);
					break;
				}
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/master_data',15,'xor',null,$ajm);
				}
		}
	}
	private function arpg_readonly_master_data(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/player_item_stock',15,'xor',self::skey($jm),$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_player_item_stock(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					$ajm['adv'] = 0;
					$req = [
					'skey' => self::skey($jm)
					];
					$req['did'] = 1000000;
					$req['type'] = 3;
					$jm->setNext('stdconnect/read/adv_data',15,'xor',$req,$ajm);
					break;
				}
			default:
				{
					$jm->setNext('stdconnect/read/item_base',15,'xor',[
							10000,
							10001,
							10003,
							203008,
							210001
							],$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readonly_item_base(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/player_state',15,'xor',self::skey($jm),$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_player_state(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					$jm->setNext('stdconnect/read/get_init_actor_parts',15,'xor',self::skey($jm),$jm->getAjmData());
					break;
				}
			default:
				{
					$jm->setNext('stdconnect/write/login_bonus',15,'xor',self::skey($jm),$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readwrite_login_bonus(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/player_card_stock',15,'xor',self::skey($jm),$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_player_card_stock(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/player_factory',15,'xor',[
							'type' => 1,
							'skey' => self::skey($jm)
							],$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_player_factory(Jmeter $jm,$mode,$scene){
		$rpc = $jm->getRpc();
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$data = (array)$rpc->data;
					if(intval($data['type'])==1){
						$jm->setNext('stdconnect/read/player_factory',15,'xor',[
								'type' => 3,
								'skey' => self::skey($jm)
								],$jm->getAjmData());
					}else{
						$jm->setNext('stdconnect/write/bg_batch',15,'xor',self::skey($jm),$jm->getAjmData());
					}
					break;
				}
		}
	}
	private function arpg_readwrite_bg_batch(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::START_SCENE,self::USE_BATCH);
				}
		}
	}
}
?>