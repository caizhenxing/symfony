<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class EquipAddon extends \Dcs\Arpg\Logic{

	private static $INFO_KEY = ['std_id','type','power','switch','name','rarity'];
	private static $SWITCH_KEY = ['id','stone0','stone1','stone2','stone3','stone4','stone5','price'];

	private function getEquipAddon($std_id){
		$std_id = intval($std_id);
		$key = 'Arpg.Logic.Util.EquipAddon.getEquipAddon.';
		$c = $this->cache();
		$ret = $c->get(\Dcs\Cache::TYPE_APC,$key.$std_id);
		if($ret == null){
			$rs = $this->getHs(false)->select(
					new Table('equip_addon',self::$INFO_KEY),
					new Query(['>' => 0],-1)
			);

			foreach($rs as $row){
				$row[0] = intval($row[0]);
				$row[1] = intval($row[1]);
				$row[2] = $row[2]+0;
				$row[3] = intval($row[3]);
				$row[5] = intval($row[5]);
				if($row[0] == $std_id){
					$ret = $row;
				}
				$c->set(\Dcs\Cache::TYPE_APC,$key.$row[0],$row);
			}
		}
		return $ret;
	}
	private function getAddonSwitch($std_id){
		$id = $this->getSwitch($std_id);
		$key = 'Arpg.Logic.Util.EquipAddon.getAddonSwitch.';
		$c = $this->cache();
		$ret = $c->get(\Dcs\Cache::TYPE_APC,$key.$id);
		if($ret == null){
			$rs = $this->getHs(false)->select(
					new Table('equip_addon_switch',self::$SWITCH_KEY),
					new Query(['>=' => 0],-1)
			);
			foreach($rs as $row){
				$row[0] = intval($row[0]);
				$row[1] = intval($row[1]);
				$row[2] = intval($row[2]);
				$row[3] = intval($row[3]);
				$row[4] = intval($row[4]);
				$row[5] = intval($row[5]);
				$row[6] = intval($row[6]);
				$row[7] = intval($row[7]);
				if($row[0] == $id)
					$ret = $row;
				$c->set(\Dcs\Cache::TYPE_APC,$key.$row[0],$row);
			}
		}
		return $ret;
	}
	/**
	 * アドオンの論理IDチェック
	 * @param int $std_id
	 * @return boolean
	 */
	public static function check($std_id){
		$std_id = intval($std_id);
		return 500000 <= $std_id && $std_id < 590000;
	}

	/**
	 * アドオンタイプを取得
	 * @param int $std_id
	 * @return int
	 */
	public function getType($std_id){
		if($std_id < 1) return 0;
		return intval($this->getEquipAddon($std_id)[1]);
	}

	/**
	 * アドオン力を取得
	 * @param int $std_id
	 * @return int
	 */
	public function getPower($std_id){
		if($std_id < 1) return 0;
		return $this->getEquipAddon($std_id)[2]+0;
	}
	/**
	 * アドオン合成タイプ
	 * @param int $std_id
	 * @return int
	 */
	public function getSwitch($std_id){
		if($std_id < 1) return 0;
		return intval($this->getEquipAddon($std_id)[3]);
	}
	/**
	 * アドオン合成率取得
	 * @param int $std_id
	 * @return array 0~5個までの確率
	 */
	public function getSwitchRates($std_id){
		$row = $this->getAddonSwitch($std_id);
		return [$row[1]+0.0,$row[2]+0.0,$row[3]+0.0,$row[4]+0.0,$row[5]+0.0,$row[6]+0.0];
	}

	/**
	 * アドオン合成価格
	 * @param int $std_id
	 * @return int
	 */
	public function getSwitchCost($std_id){
		return intval($this->getAddonSwitch($std_id)[7]);
	}

	/**
	 * アドオン名取得
	 * @param int $std_id
	 * @return string
	 */
	public function getName($std_id){
		if($std_id < 1) return '';
		return $this->getEquipAddon($std_id)[4];
	}
	/**
	 * レアりてぃ取得
	 * @param int $std_id
	 * @return int
	 */
	public function getRarity($std_id){
		if($std_id < 1) return '';
		return intval($this->getEquipAddon($std_id)[5]);
	}
	/**
	 * 詳細情報取得
	 * @param int $std_id
	 * @return int
	 */
	public function getDetail($std_id){
		if($std_id < 1) return '';
		$dat = $this->getEquipAddon($std_id);

		return $this->get('Arpg.Logic.Util.EquipAddonType')->getDetail($dat[1],$dat[2]);
	}
}

?>