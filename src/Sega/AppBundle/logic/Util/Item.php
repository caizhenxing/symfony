<?php
/**
*********************************************************
*********************************************************
*********************************************************
*********************************************************
*********************************************************
*********************************************************
*削除予定
*削除予定
*削除予定
*削除予定
*削除予定
*削除予定
*削除予定
*削除予定
*削除予定
*削除予定
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class Item extends \Dcs\Arpg\Logic{
	
	/**
	 * データを取得する
	 * @param int $std_id
	 * @return string
	 */
	public function getData($std_id){
		$std_id = intval($std_id);
		$rs = $this->selectHsCache(
				new Table("item_data",["name","nb_equip","info","rarity","sell"]),
				new Query(["="=>$std_id])
		);
		foreach($rs as $row){
			return [
				"std_id"=>$std_id,
				"name"=>$row[0],
				"nb_equip"=>$row[1],
				"info"=>$row[2],
				"rarity"=>$row[3],
				"sell"=>$row[4],
			];
		}
		return null;
	}
}

?>