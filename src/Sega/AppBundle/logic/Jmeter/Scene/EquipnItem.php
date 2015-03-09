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
trait EquipnItem{
	private function arpg_fitting_model(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					
					$ajm = $jm->getAjmData();
					$sheet = $ajm['asheet'];
					unset($ajm['asheet']);
					$jm->setNext('stdconnect/write/entry_new_actor',15,'xor',[
						'faceType' => $sheet['face'],
						'hairColor' => $sheet['hair_color'],
						'bodyColor' => $sheet['skin'],
						'gender' => $sheet['is_male'],
						'eyeColor' => $sheet['eye'],
						'name' => self::NameBase.mt_rand(0,99),
						'hairStyle' => $sheet['hair'],
						'skey' => self::skey($jm)
					],$ajm);
					
					break;
				}
			default:
				{
					$ajm = $jm->getAjmData();
					$req = [
						'set_id' => 0,
						'skey' => self::skey($jm)
					];
					$ptmt = $this->sqlCon()->prepare('SELECT id,std_id FROM gaia.box_equip where uid = ? and state = 0');
					$ptmt->execute([
						$ajm['uid']
					]);
					$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
					$Equip = $this->get('Arpg.Logic.Util.Equip');
					$cost = [
						10000,
						10000,
						10000
					];
					$sheet = [
						0,
						0,
						0,
						0,
						0
					];
					foreach($rs as $row){
						$type = Equip::std2type($row[1]);
						$wtype = Equip::std2wtype($row[1]);
						if($type==Equip::TYPE_WEAPON&&$wtype!=Equip::WEAPON_NONE){
							$c = $Equip->getData($row[1])['cost'];
							if($cost[0]>$c) $sheet[0] = intval($row[0]);
						}elseif($type==Equip::TYPE_HEADGEAR){
							$c = $Equip->getData($row[1])['cost'];
							if($cost[1]>$c) $sheet[1] = intval($row[0]);
						}elseif($type==Equip::TYPE_COSTUME){
							$c = $Equip->getData($row[1])['cost'];
							if($cost[2]>$c) $sheet[2] = intval($row[0]);
						}
					}
					$req['sheet'] = [
						[
							'sid' => 0,
							'eq' => $sheet,
							'it' => [
								0,
								0,
								0
							],
							'unset' => null
						]
					];
					
					$jm->setNext('stdconnect/write/try_equip',15,'xor',$req,$ajm);
					break;
				}
		}
	}
	

	private function arpg_readwrite_try_equip(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_KUSURI);
				}
		}
	}

	private function arpg_readwrite_sell_card(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/write/request_eq_warehouse_expanding',15,'xor',self::skey($jm),$jm->getAjmData());
				}
		}
	}
	private function arpg_readwrite_set_equip_lock(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$ajm = $jm->getAjmData();
					$cid = $ajm['item'];
					if(isset($ajm['unlock'])){
						unset($ajm['unlock']);
						unset($ajm['item']);
						$jm->setNext('stdconnect/write/sell_card',15,'xor',[
							'ids' => [
								$cid
							],
							'skey' => self::skey($jm)
						],$ajm);
					}else{
						$ajm['unlock'] = true;
						$jm->setNext('stdconnect/write/set_equip_lock',15,'xor',[
							'unlock' => [
								$cid
							],
							'lock' => [],
							'skey' => self::skey($jm)
						],$ajm);
					}
				}
		}
	}
	private function arpg_readwrite_request_eq_warehouse_expanding(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$ajm = $jm->getAjmData();
					$ptmt = $this->sqlCon()->prepare('SELECT std_id FROM box_stack_item where uid = ? and std_id > 101000 and std_id < 110000 and num > 0');
					$ptmt->execute([
							$ajm['uid']
							]);
					$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
					$iid = -1;
					foreach($rs as $row){
						if($iid<0||mt_rand(0,10)==1){
							$iid = $row[0];
						}
					}
					$jm->setNext('stdconnect/write/sell_item',15,'xor',[
							'iid' => $iid,
							'num' => 1,
							'skey' => self::skey($jm)
							],$ajm);
				}
		}
	}
	private function arpg_readwrite_sell_item(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_SINGLE);
				}
		}
	}
}
?>