<?php
namespace Logic\CardData;

class Addon extends \Dcs\Arpg\Logic{

	public $type;
	public $rarity;
	public $name;
	public $switchRates;
	public $switchCost;
	public $detail;
	
	/**
	 * データ初期化
	 * @return bool 成否
	 */
	public function init($addon_id) {
		if(!is_numeric($addon_id)) return false;
		$addon_id = intval($addon_id);
		$Addon = $this->get('Arpg.Logic.Util.EquipAddon');
		$this->name = $Addon->getName($addon_id);
		$this->type = $Addon->getType($addon_id);
		$this->rarity = $Addon->getRarity($addon_id);
		$this->switchRates = $Addon->getSwitchRates($addon_id);
		$this->switchCost = $Addon->getSwitchCost($addon_id);
		$this->pow = $Addon->getPower($addon_id);
		$this->detail = $Addon->getDetail($addon_id);
		return true;
	}
	
	public function pow(){
		return $this->pow;
	}
	private $pow = 0;
}
?>