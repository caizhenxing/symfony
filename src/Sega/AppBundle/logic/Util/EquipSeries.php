<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class EquipSeries extends \Dcs\Arpg\Logic{
	private static $FLD = ['std_id','name','lv2','lv3','lv4','lv5'];

	/**
	 * シリーズの論理IDチェック
	 * @param int $std_id
	 * @return boolean
	 */
	public static function check($std_id){
		$std_id = intval($std_id);
		return 590000 <= $std_id && $std_id < 600000;
	}
	/**
	 * データ取得する
	 * @param int $std_id
	 * @return NULL|object 存在しない場合 null 存在する場合以下の構造を返す
	 * [
	 * 		'std_id' => 論理ID
	 * 		'name' => 名前
	 * 		'lv2'～'lv5' => [
	 * 			アドオンタイプ => power値, ...
	 * 		]
	 * ]
	 */
	public function getData($std_id){
		$std_id = intval($std_id);
		if($std_id == 0)
			return null;
		$c = $this->cache();
		$key = 'Arpg.Logic.Util.EquipSeries.getData.';
		$ret = $c->get(\Dcs\Cache::TYPE_APC,$key.$std_id);
		if($ret == null){
			$ret = null;
			$rs = $this->getHs(false)->select(
					new Table('equip_series',self::$FLD),
					new Query(['>'=>0],-1)
			);
			foreach($rs as $row){
				$lstd_id = intval($row[0]);
				$ret = [
					'std_id'=> $lstd_id,
					'name' =>$row[1],
					'lv2'=>[],
					'lv3'=>[],
					'lv4'=>[],
					'lv5'=>[],
				];
				for($i=2;$i<6;++$i){
					$sep = explode(',',$row[$i]);
					$addons=[];
					foreach($sep as $s){
						$s = explode(':',$s);
						if(count($s)<2) continue;
						$type = intval($s[0]);
						$pow = $s[1]+0;
						$addons[$type] = $pow;
					}
					$ret['lv'.$i] = $addons;
				}
				$c->set(\Dcs\Cache::TYPE_APC,$key.$lstd_id,$ret);
			}
		}
		return $ret;
	}
}

?>