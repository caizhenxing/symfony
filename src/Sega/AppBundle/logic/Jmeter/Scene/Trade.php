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
trait Trade{

	private function arpg_readonly_trade_item_list(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					switch($scene){
						case self::SCENE_SHOP:
							{
								$jm->setNext('stdconnect/write/trade_item',15,'xor',[
										'skey' => self::skey($jm),
										'tid' => 801111
										],$jm->getAjmData());
								break;
							}
						case self::SCENE_RSTONE:
							{
								$jm->setNext('stdconnect/write/trade_item',15,'xor',[
										'skey' => self::skey($jm),
										'tid' => 802001
										],$jm->getAjmData());
								break;
							}
						case self::SCENE_ETRADE:
							{
								$jm->setNext('stdconnect/write/trade_item',15,'xor',[
										'skey' => self::skey($jm),
										'tid' => 803006
										],$jm->getAjmData());
								break;
							}
					}
				}
		}
	}
	private function arpg_readwrite_trade_item(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					switch($scene){
						case self::SCENE_SHOP:
							{
								$this->changeScene($jm,self::SCENE_RSTONE);
								break;
							}
						case self::SCENE_RSTONE:
							{
								$this->changeScene($jm,self::SCENE_ETRADE);
								break;
							}
						case self::SCENE_ETRADE:
							{
								$this->changeScene($jm,self::SCENE_MINFO,self::USE_BATCH);
								break;
							}
					}
				}
		}
	}
}
?>