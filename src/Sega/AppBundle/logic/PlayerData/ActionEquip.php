<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\Util\Equip as EquipUtil;
use \Logic\CardData as CardData;
use \Dcs\Arpg\ResError as ResError;

class ActionEquip extends \Dcs\Arpg\Logic{
	public $c=[];
	public $i=[];
	
	/**
	 * データ初期化
	 * @param int $uid
	 */
	public function init($aid){
		$Util = $this->get('Arpg.Logic.Util.Equip');
		$eq = $Util->getEquipedByActors([$aid])[$aid];
		$this->c = $eq['card'];
		$this->i = [];
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
			$this->items[$i] = ['id'=>$std_id,'num'=>$num];
		}
	}
}

?>