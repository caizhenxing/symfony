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
trait Present{
	private function arpg_readonly_fetch_present_box_items(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$dat = $jm->getRpc();
					$dat = $dat->data;
					$id = -1;
					foreach($dat as $item){
						if($id<0||mt_rand(0,10)==1) $id = $item->mId;
					}
					if($id<0){
						$this->getAllPresent($jm);
					}else{
						$ajm = $jm->getAjmData();
						$jm->setNext('stdconnect/write/accepts_one_present_box_item',15,'xor',[
							'pid' => $ajm['pid'],
							'skey' => self::skey($jm)
						],$ajm);
					}
				}
		}
	}
	private function arpg_readwrite_accepts_one_present_box_item(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->getAllPresent($jm);
				}
		}
	}
	private function getAllPresent(Jmeter $jm){
		$jm->setNext('stdconnect/write/accepts_all_present_box_items',15,'xor',self::skey($jm),$jm->getAjmData());
	}
	private function arpg_readwrite_accepts_all_present_box_items(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_ESELL);
				}
		}
	}
}
?>