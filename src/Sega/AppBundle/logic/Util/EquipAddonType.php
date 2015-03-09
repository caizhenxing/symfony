<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class EquipAddonType extends \Dcs\Arpg\Logic{

	private static $FLD = ['id','name','detail'];
	/**
	 *
	 * @param unknown $id
	 * @return array 存在しない場合null 存在する場合以下の構造
	 * [
	 * 		'id' => タイプID
	 * 		'name' => 名前
	 * 		'detail' => 説明文
	 * ]
	 */
	public function getData($id){
		$id = intval($id);

		$c = $this->cache();
		$key = 'Arpg.Logic.Util.EquipAddonType.getData.';
		$ret = $c->get(\Dcs\Cache::TYPE_APC,$key.$id);
		if($ret == null){
			$rs = $this->getHs(false)->select(
				new Table('equip_addon_type',self::$FLD),
				new Query(['>='=>0],-1)
			);
			foreach($rs as $row){
				$dat = [
					'id' => intval($row[0]),
					'name' => $row[1],
					'detail' => $row[2],
				];
				if($dat['id'] == $id)
					$ret = $dat;
				$c->set(\Dcs\Cache::TYPE_APC,$key.$dat['id'],$dat);
			}
		}
		return $ret;
	}
	/**
	 * 説明文のpowerを置換した文字列を返す
	 * @param int $id
	 * @param float $power
	 * @return string
	 */
	public function getDetail($id,$power){
		$dat = $this->getData($id);
		if($dat == null){
			return "";
		}
		return str_replace('[power]',$power,$dat['detail']);
	}
}

?>