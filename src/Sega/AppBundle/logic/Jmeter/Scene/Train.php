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
trait Train{
	private function arpg_readonly_game_train(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->do_try_train($jm,$jm->getAjmData());
				}
		}
	}
	private function do_try_train(Jmeter $jm,array $ajm){
		$req = [
			'skey' => self::skey($jm),
			'mats' => []
		];
		
		$ptmt = $this->sqlCon()->prepare('SELECT id,std_id FROM gaia.box_equip where uid = ? and state = 0 and `lock` = 0');
		$ptmt->execute([
			$ajm['uid']
		]);
		$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
		
		$req['mats'][] = $rs[mt_rand(0,count($rs)-1)][0];
		foreach($rs as $row){
			$type = Equip::std2type($row[1]);
			$wtype = Equip::std2wtype($row[1]);
			if($type==Equip::TYPE_WEAPON&&$wtype!=Equip::WEAPON_NONE&&(!isset($req['cid'])||mt_rand(0,10)==1)){
				$req['cid'] = $row[0];
			}
		}
		
		$jm->setNext('stdconnect/write/try_train',15,'xor',$req,$ajm);
	}
	private function arpg_readwrite_try_train(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$ajm = $jm->getAjmData();
					$ajm['lcount'] = isset($ajm['lcount'])?$ajm['lcount']+1:0;
					if($ajm['lcount']>3){
						unset($ajm['lcount']);
						$this->changeScene($jm,self::SCENE_EVO);
					}else{
						$this->do_try_train($jm,$ajm);
					}
				}
		}
	}
	
}
?>