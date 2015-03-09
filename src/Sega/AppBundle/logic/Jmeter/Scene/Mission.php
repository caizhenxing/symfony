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
trait Mission{
	private function arpg_readonly_mission_list(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$key = 'Jmeter.Mission_list';
					$rs = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
					if($rs==null){
						$rs = $this->getHs(false)->select(new Table('mission',[
								'std_id',
								'slot'
								]),new Query([
										'>' => 0
										],-1));
						$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$rs);
					}
						
					$mid = -1;
					foreach($rs as $row){
						if(intval($row[1])>0) continue;
						if($mid<0||mt_rand(0,10)==1){
							$mid = intval($row[0]);
						}
					}
					$jm->setNext('stdconnect/write/mission_complete',15,'xor',[
							'skey' => self::skey($jm),
							'mid' => $mid
							],$jm->getAjmData());
				}
		}
	}
	private function arpg_readwrite_mission_complete(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_GACHA);
				}
		}
	}
}
?>