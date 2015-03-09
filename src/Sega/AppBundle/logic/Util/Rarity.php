<?php

namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Cache as Cache;

class Rarity extends \Dcs\Arpg\Logic{
	
	/**
	 * 名前を取得する
	 * @param int $rarity
	 * @return string
	 */
	public function name($rarity){
		$rarity = intval($rarity);
		$dat = $this->getData();
		if(!isset($dat[$rarity])) return '';
		return $dat[$rarity]->name;
	}
	
	/**
	 * エフェクトレベルを生成する
	 * @param int $rarity
	 */
	public function effect($rarity){
		$rarity = intval($rarity);
		$dat = $this->getData();
		if(!isset($dat[$rarity])) return 0;
		$rand = mt_rand(0,10000) / 100;
		
		$ret = 0;
		for($i=self::RARITY_EFFECT_SIZE-1;$i>=0;--$i){
			$rate = $dat[$rarity]->effect[$i];
			if($rand <= $rate) return $i;
			$rand -= $rate;
		}
		return 0;
	}
	
	private function getData(){
		$key='Sega.AppBundle.Dao.Util.Rarity';
		$dat = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($dat == null){
			$rs = $this->getHs(false)->select(
					new Table('rarity',['id','name',
							'gacha_effect0','gacha_effect1','gacha_effect2','gacha_effect3','gacha_effect4',
							'gacha_effect5','gacha_effect6','gacha_effect7','gacha_effect8','gacha_effect9',
							'gacha_effect10','gacha_effect11','gacha_effect12','gacha_effect13','gacha_effect14',
							'gacha_effect15','gacha_effect16','gacha_effect17']),
					new Query(['>'=>0],-1)
			);
			$dat = [];
			foreach($rs as $row){
				$d = new RarityInner();
				$d->name = $row[1];
				$total = 0;
				for($i=0;$i<self::RARITY_EFFECT_SIZE;++$i){
					$rate = $row[$i+2]+0;
					$d->effect[] = $rate;
					$total += $rate;
				}
				if($total < 1) $total = 1;
				foreach($d->effect as &$rate){
					$rate = $rate/$total*100;
				}
				unset($rate);
				
				$dat[intval($row[0])] = $d;
			}
			$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$dat);
		}
		return $dat;
	}
	const RARITY_EFFECT_SIZE = 18;
}
class RarityInner{
	public $name;
	public $effect=[];
}

?>