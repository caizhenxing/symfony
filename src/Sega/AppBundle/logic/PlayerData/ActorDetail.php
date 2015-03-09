<?php
/**
 * メモ
 * 地道に高速化１を実装ずみ
 */
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\Util\Equip as EquipUtil;

class ActorDetail extends \Dcs\Arpg\Logic{
	public $id;
	public $name;
	public $atk;
	public $def;
	public $matk;
	public $mdef;
	public $weaponStdId;
	
	/**
	 * データ初期化
	 * @param \Dcs\CmnAccount $account
	 */
	public function init($aid,$name,$lv,array $cards){
		$AStatus = $this->get('Arpg.Logic.Util.ActorStatus');
		$this->id = $aid;
		$this->name = $name;
		$this->atk = 0;
		$this->matk = 0;
		$this->def = 0;
		$this->mdef = 0;
		foreach($cards as $card){
			$this->atk += $card->phisicalAttack;
			$this->matk += $card->magicalAttack;
			$this->def += $card->phisicalDefence;
			$this->mdef += $card->magicalDefence;
			if($card->type == EquipUtil::TYPE_WEAPON)
				$this->weaponStdId = $card->stdId;
		}
	}
}

?>