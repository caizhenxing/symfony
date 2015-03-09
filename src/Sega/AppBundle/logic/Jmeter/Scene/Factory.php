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
trait Factory{
	private function arpg_readonly_game_factory_item(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					switch($scene){
						case self::SCENE_KUSURI:
							{
								$jm->setNext('stdconnect/write/upgrade_factory',15,'xor',[
									'type' => 1,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_MAGIC:
							{
								$jm->setNext('stdconnect/write/upgrade_factory',15,'xor',[
									'type' => 3,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
					}
				}
		}
	}
	private function arpg_readwrite_upgrade_factory(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					switch($scene){
						case self::SCENE_KUSURI:
							{
								$jm->setNext('stdconnect/write/try_factory',15,'xor',[
									'iid' => 200001,
									'type' => 1,
									'num' => 3,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_MAGIC:
							{
								$jm->setNext('stdconnect/write/try_factory',15,'xor',[
									'iid' => 201011,
									'type' => 3,
									'num' => 5,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
					}
				}
		}
	}
	private function arpg_readwrite_try_factory(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					switch($scene){
						case self::SCENE_KUSURI:
							{
								$jm->setNext('stdconnect/write/use_spirit',15,'xor',[
									'type' => 1,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_MAGIC:
							{
								$jm->setNext('stdconnect/write/use_spirit',15,'xor',[
									'type' => 3,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
					}
				}
		}
	}
	private function arpg_readwrite_use_spirit(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					switch($scene){
						case self::SCENE_KUSURI:
							{
								$jm->setNext('stdconnect/write/complete_factory',15,'xor',[
									'type' => 1,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case self::SCENE_MAGIC:
							{
								$jm->setNext('stdconnect/write/complete_factory',15,'xor',[
									'type' => 3,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
					}
				}
		}
	}
	private function arpg_readwrite_complete_factory(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$ajm = $jm->getAjmData();
					$sc = null;
					switch($scene){
						case self::SCENE_KUSURI:
							{
								$sc = self::SCENE_MAGIC;
								break;
							}
						case self::SCENE_MAGIC:
							{
								$sc = self::SCENE_TRAIN;
								break;
							}
					}
					$this->changeScene($jm,$sc);
				}
		}
	}
	
}
?>