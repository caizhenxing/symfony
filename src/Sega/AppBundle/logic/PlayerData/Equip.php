<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\ResError as ResError;
use \Logic\CardData as CardData;

class Equip extends \Dcs\Arpg\Logic{
	public $equipCardID=[];
	public $equipItem=[];
	/**
	 * データ初期化
	 * @param int $uid
	 */
	public function init($uid){
		$eq = $this->get('Arpg.Logic.Util.Equip')->getEquipedByUsers([$uid])[$uid];
		
		$this->equipCardID = $eq['card'];
		$this->equipItem = [];
		$inum = [];
		foreach($eq['item'] as $i){
			if($i == null) continue;
			$inum[$i->stdId] = $i->num;
		}
		for($i=0;$i<\Logic\Util\Equip::MAX_SUPPLIES;++$i){
			$it = null;
			if(isset($eq['item'][$i]))
				$it = $eq['item'][$i];
			$std_id = 0;
			$num = 0;
			if($it != null){
				$std_id = $it->stdId;
				$num = $inum[$std_id];
				$num = $num>$it->nbEquip?$it->nbEquip:$num;
				$inum[$std_id] -= $num;
			}
			$this->equipItem[$i] = ['id'=>$std_id,'num'=>$num];
		}
	}
}

?>