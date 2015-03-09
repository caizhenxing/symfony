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
trait Message{
	private function arpg_readwrite_accept_messages(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_PRESENT);
				}
		}
	}
	private function arpg_readonly_message_body(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$ajm = $jm->getAjmData();
					$id = $ajm['mailid'];
					unset($ajm['mailid']);
					$jm->setNext('stdconnect/write/accept_messages',15,'xor',[
						'dids' => [
							$id
						],
						'aids' => [],
						'skey' => self::skey($jm)
					],$ajm);
				}
		}
	}
	private function arpg_readonly_message_heads(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$dat = $jm->getRpc();
					$dat = $dat->data;
					$id = null;
					foreach($dat as $mail){
						if($mail!=null&&!$mail->isReaded){
							$id = $mail->id;
						}
					}
					if($id==null){
						$this->changeScene($jm,self::SCENE_PRESENT);
					}else{
						$ajm = $jm->getAjmData();
						$ajm['mailid'] = $id;
						$jm->setNext('stdconnect/read/message_body',15,'xor',[
							'id' => $id,
							'skey' => self::skey($jm)
						],$ajm);
					}
				}
		}
	}
	
}
?>