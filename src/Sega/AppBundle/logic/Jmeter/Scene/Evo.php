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
trait Evo{
	private function arpg_readwrite_try_evolution(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_MISSION);
				}
		}
	}
}
?>