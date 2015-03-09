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
trait Gacha{
	private function arpg_readonly_gacha_getter(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					$jm->setNext('stdconnect/read/act_tutorial',15,'xor',null,$jm->getAjmData());
					break;
				}
			default:
				{
					$jm->setNext('stdconnect/write/try_gacha',15,'xor',[
							'skey' => self::skey($jm),
							'id' => 701020
							],$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readwrite_try_gacha(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/gacha_banner',15,'xor',self::skey($jm),$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_gacha_banner(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/gacha_info',15,'xor',null,$jm->getAjmData());
				}
		}
	}
	private function arpg_readonly_gacha_info(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_FRIEND,self::USE_BATCH);
				}
		}
	}
}
?>