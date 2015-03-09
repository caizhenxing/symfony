<?php
namespace Logic\Matching;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

/**
 * マッチング後ダンジョンデータ
 */
class Stage extends \Dcs\Arpg\Logic{
	const ENEMY		= 0;
	const BUTTON	= 1;
	const DELETE	= 2;
	const TIMER		= 3;
	const FENCE		= 4;
	const WARP		= 5;
	const BOX		= 6;
	const ALLKILL	= 7;
	const RATE		= 8;
	const MONUMENT	= 9;
	const WARNING	= 10;
	const COUNT		= 11;
	
	public $model;
	public $minimap;
	public $start;
	public $angle;
	public $order;
	public $light_probes;
	public $env_sound;

	public function __sleep(){
		return [
			'model',
			'minimap',
			'start',
			'angle',
			'order',
			'light_probes',
			'env_sound',
		];
	}
	/**
	 * 初期化
	 * @param int $sid
	 * @param int $local_id ローカルオブジェクトID用インデックス
	 * @return object [
	 * 		'enm'=> array 敵IDリスト
	 * 		'eff'=> array エフェクトIDリスト
	 * ]
	 */
	public function init($sid,&$local_id){
		$rs = $this->selectSqlCache(
				'select sm.env_sound as env_sound, sm.`light_probes` as light_probes, sm.`name` as model, sm.minimap as minimap, sc.start_x as start_x, sc.start_y as start_y, sc.start_z as start_z, sc.start_angle as start_angle  from stage_config as sc left join stage_model as sm on sc.model = sm.id where sc.id = ? limit 1',
				[$sid]
		);
		$rs = $rs[0];
		$this->model = $rs['model'];
		$this->minimap = $rs['minimap'];
		$this->light_probes = $rs['light_probes']+0;
		$this->env_sound = $rs['env_sound'];
		$this->start = [
			'x'=>($rs['start_x']+0),
			'y'=>($rs['start_y']+0),
			'z'=>($rs['start_z']+0),
		];
		$this->angle = $rs['start_angle'];
		
		$rs = $this->selectHsCache(
				new Table('enemy_place',['order','target','level','enemies','type','x','y','z','radius','effect','gacha','rate','level2','level3'],'STAGE_INDEX'),
				new Query(['='=>$sid],-1)
		);
		$ret = ['enm'=>[],'eff'=>[]];
// 		$ret = ['enm'=>[],'eff'=>[],'lv'=>[],'lv2'=>[],'lv3'=>[]];
		
		$orders = [];
		foreach($rs as $row){
			$odr = intval($row[0]);
			if(!array_key_exists($odr,$orders))
				$orders[$odr] = new Order($odr);
			$enemies = explode(',',$row[3]);
			// 敵を追加
			if(intval($row[4]) == 0){
				foreach($enemies as $eid){
					$eid = intval($eid + 0);
					if($eid < 1) continue;
					$obj = new Obj($row, $local_id, $eid);
					++$local_id;
					$ret['enm'][] = $eid;
// 					$ret['lv'][] = intval($row[2]);
// 					$ret['lv2'][] = intval($row[12]);
// 					$ret['lv3'][] = intval($row[13]);
					$orders[$odr]->obj[] = $obj;
					$ret['eff'][] = intval($row[9]+0);
				}
			}else{
				// ギミックとか追加
				$obj = new Obj($row, $local_id, 0);
				++$local_id;
				$ret['eff'][] = intval($row[9]+0);
				$orders[$odr]->obj[] = $obj;
			}
		}

		$this->order = [];
		foreach($orders as $odr){
			$this->order[] = $odr;
		}
		
		return $ret;
	}
	
}

class Order{
	public $index;
	public $obj;
	public function __construct($index){
		$this->index = $index;
		$this->obj = [];
	}
}
class Obj{
	public $local_id;
	public $target;
	public $level;
	public $level2;
	public $level3;
	public $__ef;
	public $type;
	public $pos;
	public $radius;
	public $__em;
	public $__ga;
	public $__emra;
	public $rate;
	public function __construct($row, $lid, $eid){
		$this->local_id = $lid;
		$this->target = intval($row[1]);
		$this->level = intval($row[2]);
		$this->level2 = intval($row[12]);
		$this->level3 = intval($row[13]);
		$this->__em = $eid;
		$this->type = intval($row[4]);
		$this->pos = new Vec3(($row[5]+0),($row[6]+0),($row[7]+0));
		$this->radius = $row[8]+0;
		$this->__ef = intval($row[9]);
		$this->__ga = intval($row[10]);
		$this->rate = intval($row[11]);
		$this->__emra = 0;
	}
}
class Vec3{
	public $x;
	public $y;
	public $z;
	public function __construct($x,$y,$z){
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}
}

?>