<?php
namespace Logic\Matching;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

/**
 * マッチング後ダンジョン用データ
 */
class Enemy extends \Dcs\Arpg\Logic{
	public $id;
	public $name;
	public $data;
	public $scale;
	public $attr;
	public $ai;
	public $collision_type;
	public $attack_select;
	public $hp;					// HP
	public $counter; 
	public $move;				// move_speed
	public $turn;					// turn_speed
	public $guard_lv;				// guard_level
	public $fire_lv;				// fire_level
	public $def;				// def
	public $mdef;				// mdef
	public $shield;
	public $mshield;
	public $distance;
	public $start_dist;
	public $yoroke;
	public $hate;
	public $down_damage;
	public $down_time;
	public $damage_time;
	public $attw;
	public $atts;
	public $attnc;
	public $attwc;
	public $attsc;
	public $stiff_rate;
	public $stiff_time;
	public $poison_rate;
	public $poison_time;
	public $anim_walk;			// anim_walk
	public $anim_guard;			// anim_guard
	public $anim_damaged;			// anim_damaged
	public $anim_dead;			// anim_dead
	public $collider_size; //vector3
	public $collider_center_z;
	public $model_offset_y;

	public $boss;
	public $wander;
	public $find_angle;
	public $miss_time;
	public $counter_prob;
	public $anim_idle;
	public $anim_find;
	public $anim_down;
	public $anim_stiff;
	
	public $hpb_vpos;
	
	public $__de;
	public $__ai;
	
	public static $EFLD = [
		'data','name','scale','ai','collision_type',	// 0-4
		'attack_select','hp','counter','move_speed','turn_speed',	// 5-9
		'guard_level','fire_level','def','mdef','shield',	// 10-14
		'mshield','distance','start_dist','yoroke','hate',	// 15-19
		'down_damage','down_time','damage_time','attw','atts',	// 20-24
		'attnc','attwc','attsc','stiff_rate','stiff_time',	// 25-29
		'poison_rate','poison_time','anim_walk','anim_guard','anim_damaged',	// 30-34
		'anim_dead','collider_size_x','collider_size_y','collider_size_z','collider_center_z',// 35-39
		'model_offset_y','boss','wander','find_angle','miss_time',// 40-44
		'down_effect','counter_prob','anim_idle','anim_find','anim_down',// 45-49
		'anim_stiff','attack_data','hpb_vpos','attr'// 50-54
	];
	
	/**
	 * ベースデータでのみ初期化しアタックリストを取得
	 * @param unknown $eid
	 * @return NULL|unknown
	 */
	public function initBase($eid){
		$cache = $this->cache();
		$cache_key = 'DaoMatchingEnemy:'.$eid;
		$row = $cache->get(\Dcs\Cache::TYPE_APC,$cache_key);
		if($row == null){
			$rs = $this->getHs(false)->select(
					new Table('enemy',self::$EFLD),
					new Query(['='=>$eid])
			);
			if(empty($rs)) return null;
			$row = $rs[0];
				
			// モデル生成
			$rs = $this->selectHsCache(
					new Table('enemy_data',['name']),
					new Query(['='=>intval($row[0])])
			);
			if(empty($rs)) return null;
			$this->data = $rs[0][0];
				
			$this->id = $eid;
			$this->name = $row[1];
			$this->scale = $row[2]+0;
			$this->ai = intval($row[3]);
			$this->collision_type = intval($row[4]);
			$this->attack_select = intval($row[5]);
			$this->hp = intval($row[6]);
			$this->counter = $row[7]+0;
			$this->move = $row[8]+0;
			$this->turn = $row[9]+0;
			$this->guard_lv = intval($row[10]);
			$this->fire_lv = intval($row[11]);
			$this->def = intval($row[12]);
			$this->mdef = intval($row[13]);
			$this->shield = $row[14]+0;
			$this->mshield = $row[15]+0;
			$this->distance = $row[16]+0;
			$this->start_dist = $row[17]+0;
			$this->yoroke = intval($row[18]);
			$this->hate = intval($row[19]);
			$this->down_damage = intval($row[20]);
			$this->down_time = $row[21]+0;
			$this->damage_time = $row[22]+0;
			$this->attw = intval($row[23]);
			$this->atts = intval($row[24]);
			$this->attnc = $row[25]+0;
			$this->attwc = $row[26]+0;
			$this->attsc = $row[27]+0;
			$this->stiff_rate = $row[28]+0;
			$this->stiff_time = $row[29]+0;
			$this->poison_rate = $row[30]+0;
			$this->poison_time = $row[31]+0;
			$this->anim_walk = $row[32];			// anim_walk
			$this->anim_guard = $row[33];			// anim_guard
			$this->anim_damaged = $row[34];			// anim_damaged
			$this->anim_dead = $row[35];			// anim_dead
			$this->collider_size = [
			'x'=>($row[36]+0),
			'y'=>($row[37]+0),
			'z'=>($row[38]+0),
			];
			$this->collider_center_z = $row[39]+0;
			$this->model_offset_y = $row[40]+0;
				
			$this->boss = intval($row[41]);
			$this->wander = $row[42]+0;
			$this->find_angle = intval($row[43]);
			$this->miss_time = $row[44]+0;
			$this->__de = intval($row[45]);
			$this->counter_prob = intval($row[46]);
			$this->anim_idle = $row[47];
			$this->anim_find = $row[48];
			$this->anim_down = $row[49];
			$this->anim_stiff = $row[50];
			$this->__ai = intval($row[51]);
			$this->hpb_vpos = $row[52]+0;
			$this->attr = intval($row[53]);
				
			$atk_list = $this->getHs(false)->select(
					new Table(Attack::TABLE,Attack::$COLMNS,Attack::SELECT_INDEX),
					new Query(['='=>$this->__ai],-1)
			);
			$cache->set(\Dcs\Cache::TYPE_APC,$cache_key,[json_encode($this),$atk_list]);
		}else{
			$dat = json_decode($row[0],true);
				
			$this->id = $dat['id'];
			$this->name = $dat['name'];
			$this->data = $dat['data'];
			$this->scale = $dat['scale'];
			$this->ai = $dat['ai'];
			$this->collision_type = $dat['collision_type'];
			$this->attack_select = $dat['attack_select'];
			$this->hp = $dat['hp'];
			$this->counter = $dat['counter'];
			$this->move = $dat['move'];
			$this->turn = $dat['turn'];
			$this->guard_lv = $dat['guard_lv'];
			$this->fire_lv = $dat['fire_lv'];
			$this->def = $dat['def'];
			$this->mdef = $dat['mdef'];
			$this->shield = $dat['shield'];
			$this->mshield = $dat['mshield'];
			$this->distance = $dat['distance'];
			$this->start_dist = $dat['start_dist'];
			$this->yoroke = $dat['yoroke'];
			$this->hate = $dat['hate'];
			$this->down_damage = $dat['down_damage'];
			$this->down_time = $dat['down_time'];
			$this->damage_time = $dat['damage_time'];
			$this->attw = $dat['attw'];
			$this->atts = $dat['atts'];
			$this->attnc = $dat['attnc'];
			$this->attwc = $dat['attwc'];
			$this->attsc = $dat['attsc'];
			$this->stiff_rate = $dat['stiff_rate'];
			$this->stiff_time = $dat['stiff_time'];
			$this->poison_rate = $dat['poison_rate'];
			$this->poison_time = $dat['poison_time'];
			$this->anim_walk = $dat['anim_walk'];
			$this->anim_guard = $dat['anim_guard'];
			$this->anim_damaged = $dat['anim_damaged'];
			$this->anim_dead = $dat['anim_dead'];
			$this->collider_size = $dat['collider_size'];
			$this->collider_center_z = $dat['collider_center_z'];
			$this->model_offset_y = $dat['model_offset_y'];
				
			$this->boss = $dat['boss'];
			$this->wander = $dat['wander'];
			$this->find_angle = $dat['find_angle'];
			$this->miss_time = $dat['miss_time'];
			$this->__de = $dat['__de'];
			$this->counter_prob = $dat['counter_prob'];
			$this->anim_idle = $dat['anim_idle'];
			$this->anim_find = $dat['anim_find'];
			$this->anim_down = $dat['anim_down'];
			$this->anim_stiff = $dat['anim_stiff'];
			$this->__ai = $dat['__ai'];
			$this->hpb_vpos = $dat['hpb_vpos'];
			$this->attr = $dat['attr'];
				
			$atk_list = $row[1];
		}
		return $atk_list;
	}
	/**
	 * 初期化
	 * @param int $eid 敵ID
	 * @return array 使用するエフェクトidのリスト 敵情報が変な場合nullを返す
	 */
	public function init($eid,&$alist,&$elist){
		
		$eid = intval($eid);
		if($eid < 1) return null;
		if(isset($elist[$eid]))return self::$retCache[$eid];
		$elist[$eid] = true;// 枠だけ確保
		
		$is_new = false;
		$atk_list = $this->initBase($eid);
		
		$ret = [$this->__de];
		$atk_buff = [];
		foreach($atk_list as $line){
			$atk = $this->get('Arpg.Logic.Matching.Attack');
			$ret = array_merge($ret,$atk->init($this->__ai,$line));
			if(!isset($alist[$atk->id])){
				$alist[$atk->id] = $atk;
				$atk_buff[] = $atk;
			}
		}
		self::$retCache[$eid] = $ret;// 再帰前に一旦キャッシュ作成
		foreach($atk_buff as $atk){
			if($atk->type != 521) continue;
			
			$e = $this->get('Arpg.Logic.Matching.Enemy');
			$ret = array_merge($ret,$e->init($atk->__g,$alist,$elist));
		}
		$elist[$eid] = $this;
		self::$retCache[$eid] = $ret;
		return $ret;
	}
	private static $retCache = [];
	public function __sleep(){
		return [
			'id',
			'name',
			'data',
			'scale',
			'attr',
			'ai',
			'collision_type',
			'attack_select',
			'hp',					// HP
			'counter', 
			'move',				// move_speed
			'turn',					// turn_speed
			'guard_lv',				// guard_level
			'fire_lv',				// fire_level
			'def',				// def
			'mdef',				// mdef
			'shield',
			'mshield',
			'distance',
			'start_dist',
			'yoroke',
			'hate',
			'down_damage',
			'down_time',
			'damage_time',
			'attw',
			'atts',
			'attnc',
			'attwc',
			'attsc',
			'stiff_rate',
			'stiff_time',
			'poison_rate',
			'poison_time',
			'anim_walk',			// anim_walk
			'anim_guard',			// anim_guard
			'anim_damaged',			// anim_damaged
			'anim_dead',			// anim_dead
			'collider_size', //vector3
			'collider_center_z',
			'model_offset_y',
			
			'boss',
			'wander',
			'find_angle',
			'miss_time',
			'counter_prob',
			'anim_idle',
			'anim_find',
			'anim_down',
			'anim_stiff',
			'hpb_vpos',
			'__de',
			'__ai',
		];
	}
}

?>