<?php
namespace Logic\GameData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class Train extends \Dcs\Arpg\Logic{
	public static $DBKEY = ['grow_type','lv','rate','exp','price'];
	
	public $growFuncs=[];
	
	public $levelRate;
	public $attrRate;
	
	/**
	 * データ初期化
	 */
	public function init() {
		$GParam = $this->get('Arpg.Logic.Util.GameParam');
		
		$rs = $this->selectHsCache(
				new Table('equip_grow_func', self::$DBKEY),
				new Query(['>'=>0],-1)
		);
		$buff = [];
		foreach($rs as $row){
			$type = $row[0];
			if(!array_key_exists($type,$buff)){
				$buff[$type] = [
					'type' => $type,
					'exp' => [],
					'price' => [],
				];
				$buff[$type]['exp'][0] = 0;
				$buff[$type]['price'][0] = 0;
			}
			$buff[$type]['exp'][$row[1]] = $row[3];
			$buff[$type]['price'][$row[1]] = $row[4];
		}
		$this->growFuncs = [];
		foreach($buff as $line){
			$this->growFuncs[] = $line;
		}
		
		$this->levelRate = $GParam->getParam($GParam::TRAIN_EXP_RATE);
		$this->attrRate = $GParam->getParam($GParam::TRAIN_ATTR_BONUS);
	}
}
?>