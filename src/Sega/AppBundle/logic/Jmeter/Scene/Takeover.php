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
trait Takeover{
	private function arpg_readonly_takeover_info(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/write/takeover_offer',15,'xor',[
						'aid' => $jm->getPem(),
						'skey' => self::skey($jm)
					],$jm->getAjmData());
				}
		}
	}
	private function arpg_readwrite_takeover_offer(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$ajm = $jm->getAjmData();
					$rpc = $jm->getRpc();
					$ajm['topass'] = $rpc->data['password'];
					$jm->setNext('stdconnect/read/takeover_check',15,'xor',$ajm['topass'],$ajm);
				}
		}
	}
	private function arpg_readonly_takeover_check(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$ajm = $jm->getAjmData();
					$pass = $ajm['topass'];
					unset($ajm['topass']);
					$jm->setNext('stdconnect/write/takeover_accept',15,'rsa',[
							'uuid' => $jm->getUuid(),
							'pass' => $pass,
							'type' => 0,
							'info' => 'jmeter test OS takeover'
					],$ajm);
				}
		}
	}
	private function arpg_readwrite_takeover_accept(Jmeter $jm,$mode,$scene){
		$ajm = $jm->getAjmData();
		$jm->newSid($this->get('gaia.user.user_service')->updateSession($ajm['uid']));
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_SETUSER);
				}
		}
	}
}
?>