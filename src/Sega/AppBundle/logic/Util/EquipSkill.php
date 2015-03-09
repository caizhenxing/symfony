<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class EquipSkill extends \Dcs\Arpg\Logic{
	
	
	/**
	 * スキル名取得
	 * @param int $std_id
	 * @return string
	 */
	public function getName($std_id){
		$data = $this->getData($std_id);
		if($data == null) return '';
		return $data['name'];
	}
	
	/**
	 * スキル効力取得
	 * @param int $std_id
	 * @return string
	 */
	public function getInfo($std_id){
		$data = $this->getData($std_id);
		if($data == null) return '';
		return $data['info'];
	}

	private static $INFO_KEY = [
			'id','name','info','effect','effect2',
			'text','attribute','power','mp','type',
			'range','offset','angle','delay','data',
			'data2','data3','status','st_rate','st_time',
			'st_data','attack'
	];
	/**
	 * 論理IDの装備品データを取得する
	 * @param int $std_id
	 * @return NULL|array select * from magic FETCH_ASSOC型の結果 intとか数値は正しい値に変換される
	 */
	public function getData($std_id){
		$std_id = intval($std_id);
		
		$cache = $this->cache();
		$key = 'Arpg.Logic.Util.EquipSkill.getData.'.$std_id;
		$ret = $cache->get($cache::TYPE_APC,$key);
		if($ret == null){
			$rs = $this->getHs(false)->select(
				new Table('magic',self::$INFO_KEY),
				new Query(['='=>$std_id])
			);
			if(empty($rs)) return null;
			$rs = $rs[0];
			$ret = [];
			
			for($i=0,$len=count(self::$INFO_KEY);$i<$len;++$i){
				$dat = $rs[$i];
				if(is_numeric($dat)){
					$it = intval($dat);
					$ft = $dat+0;
					if($it == $ft)
						$dat = $it;
					else
						$dat = $ft;
				}
				$ret[self::$INFO_KEY[$i]] = $dat;
			}
			// リスト修正
			$sep = explode(',',$ret['attribute'].'');
			$attrs = [];
			foreach($sep as $attr){
				$attrs[] = intval($attr);
			}
			$ret['attribute'] = $attrs;
			$cache->set($cache::TYPE_APC,$key,$ret);
		}
		return $ret;
	}
	/**
	 * 論理IDのスキルデータをマジックデータの形で取得する
	 * @param int $std_id
	 * @return array FittingRoom.Magic
	 */
	public function getFittingMagic($std_id){
		$info = $this->getData($std_id);
		$Effect = $this->get('Arpg.Logic.Util.Effect');
		return [
				'id' => $info['id'],
				'name' => $info['name'],
				'effect' => $Effect->getData($info['effect']),
				'effect2' => $Effect->getData($info['effect2']),
				'text' => $info['text'],
				'attribute' => $info['attribute'],
				'power' => $info['power'],
				'mp' => $info['mp'],
				'type' => $info['type'],
				'range' => $info['range'],
				'offset' => $info['offset'],
				'angle' => $info['angle'],
				'delay' => $info['delay'],
				'data' => $info['data'],
				'data2' => $info['data2'],
				'data3' => $info['data3'],
				'status' => $info['status'],
				'st_rate' => $info['st_rate'],
				'st_time' => $info['st_time'],
				'st_data' => $info['st_data'],
				'attack' => $info['attack'],
		];
	}
}

?>