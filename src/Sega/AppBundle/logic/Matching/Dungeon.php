<?php
namespace Logic\Matching;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\ResError as ResError;
use \Logic\Util\ActorStatus as ActorStatus;
use \Dcs\DetailTimeLog as DTL;
use \Logic\Util\Quest as Quest;
/**
 * マッチング後ダンジョンデータ
 */
class Dungeon extends \Dcs\Arpg\Logic{
	public $ticket;
	public $time;
	
	public $stage;
	public $attack;
	public $enemy;
	public $effect;
//	public $lv_data;
//	public $multi_ghost;
	
	public function __sleep(){
		return [
				'ticket',
				'time',
				'stage',
				'enemy',
				'attack',
				'effect',
//				'lv_data'
		];
	}
	/**
	 * 初期化
	 * @param int $did ダンジョンID
	 * @param int $ticket ダンジョン招待チケットID
	 * @param int $pid ダンジョンを生成するユーザーID
	 * @param int $pid ダンジョンを生成するパブリックユーザーID
	 * @return multitype:number string |NULL
	 */
	public function init($did,$ticket,$uid,$pid){
		DTL::Lap('Matching.Dungeon.init start');

		$info = $this->get('Arpg.Logic.Util.Quest')->getDungeonInfoByStdID($did);
		if(empty($info))
			throw new ResError("dont find dungeon data did: $did",100);
		$info = $info[0];
		if($info->type == Quest::TOWER){
			// タワー階層作成
			$lv = $this->get('Arpg.Logic.Util.Quest')->getData($uid,$did);
			if($lv == null)
				$lv = 1;
			else
				$lv = $lv['nb_clear']+1;
			$max = count($info->tower);
			if($lv > $max) $lv = $max;
			if(isset($info->tower[$lv])){
				$did = $info->tower[$lv];
	
				$info = $this->get('Arpg.Logic.Util.Quest')->getDungeonInfoByStdID($did);
				if(empty($info))
					throw new ResError("dont find floor data did: $did",100);
				$info = $info[0];
			}
		}
		
		
		$key = 'Arpg.Logic.Matching.Dungeon:'.$did;

		$cache = null;
		$mem = $this->cache();
		// キャッシュ取得
		$cache = $mem->get(\Dcs\Cache::TYPE_MEMCACHE,$key);

		DTL::Lap('get cache');
		
		if($cache == null){
			$config = $info->config;
			$rs = $this->getHs(false)->select(
					new Table('dungeon_config',['stage','time']),
					new Query(['='=>$config])
			);
			if(empty($rs)){
				throw new ResError("dont find dungeon config did: $did",100);
			}
			$rs = $rs[0];

			DTL::Lap('fetch dungeon_config');
			
			$stage_ids = explode(',',$rs[0]);
			$this->time = intval($rs[1]);
			$this->stage = [];
			$local_id = 1;
			
			$enm = [];
			$eff = [];
			
			foreach($stage_ids as $sid){
				$sid = intval($sid);
				$stage = $this->get('Arpg.Logic.Matching.Stage');
				$list = $stage->init($sid,$local_id);
				$this->stage[] = $stage;
				$enm = array_merge($enm,$list['enm']);
				$eff = array_merge($eff,$list['eff']);
			}

			DTL::Lap('create stages data');
			
			// 敵データを収集
			$buff = [];
			
			foreach($enm as $id){
				$buff[$id] = true;
			}
			
			$elist=[];
			$alist=[];
			foreach($buff as $eid=>$bool){
				$e = $this->get('Arpg.Logic.Matching.Enemy');
				$effect_list = $e->init($eid,$alist,$elist);
				if($effect_list !== null){
					$eff = array_merge($eff,$effect_list);
				}
			}
			$this->enemy=[];
			foreach($elist as $e){
				$this->enemy[] = $e;
			}
			$this->attack=[];
			
			foreach($alist as $a){
				$this->attack[] = $a;
			}

			DTL::Lap('create enemy data');
			
			// エフェクトデータ収集
			$Effect = $this->get('Arpg.Logic.Util.Effect');
			$buff = [];
			$effquerys = [];
			foreach($eff as $id){
				$buff[$id] = true;
			}
			$buff[self::std_eff_boxN] = true;
			$buff[self::std_eff_boxR] = true;
			$buff[self::std_eff_boxS] = true;
			$buff[self::std_eff_boxSS] = true;
			$this->effect=[];
			foreach($buff as $id=>$bool){
				if($id > 0)
					$this->effect[] = $Effect->getData($id);
			}
			
			DTL::Lap('create effect data');
			
			$mem->set(\Dcs\Cache::TYPE_MEMCACHE,$key,$this);
			
			DTL::Lap('create cache');
		}else{
			$this->time = $cache->time;
			
			$this->stage = $cache->stage;
			$this->enemy = $cache->enemy;
			$this->attack = $cache->attack;
			$this->effect = $cache->effect;

			DTL::Lap('create from cache');
		}
		$now = new \Dcs\Arpg\Time();
		$now_sql = $now->getMySQLDateTime();
		// 乱数値を固定化
		$sql = null;
		$adder = [];
		$args = [];
		for($si=0;$si<count($this->stage);++$si){
			$s = $this->stage[$si];
			for($oi=0;$oi<count($s->order);++$oi){
				$o = $s->order[$oi];
				for($gi=0;$gi<count($o->obj);++$gi){
					$g = $o->obj[$gi];
					// ガチャ取得
					if($g->type == Stage::ENEMY || $g->type == Stage::BOX){
						$g->__ga = intval($g->__ga);
						if($g->__ga < 1){
							$g->__ef = 0;
							$g->__ga = 0;
						}elseif($g->rate >= mt_rand(0,1000) || $g->type == Stage::BOX){
							$tbox = $this->tboxData($g->__ga);
							if(isset($tbox[0]) && isset($tbox[1]) && $tbox[1] > 0){
								$g->__ef = $tbox[0];
								$g->__ga = $tbox[1];
								$args[] = $ticket;
								$args[] = $tbox[1];
								$args[] = $this->tboxIndex($g->__ef);
								$args[] = $now_sql;
								$args[] = 0;
								if($sql == null)
									$sql = 'insert into action_gacha(local_dungeon_id, gacha_func_id,tbox_id,create_date,type) values(?,?,?,?,?)';
								else
									$sql .= ',(?,?,?,?,?)';
								$adder[] = $g->local_id;
							}else{
								$g->__ef = 0;
								$g->__ga = 0;
							}
						}else{
							$g->__ef = 0;
							$g->__ga = 0;
						}
						
						// イベントガチャ追加
						if($g->__ga == 0 && $g->type == Stage::ENEMY){
							$tbox = $this->eventGacha($did,$g->__em);
							if(isset($tbox[0]) && isset($tbox[1]) && $tbox[1] > 0){
								$g->__ef = $tbox[0];
								$g->__ga = $tbox[1];
								$args[] = $ticket;
								$args[] = $tbox[1];
								$args[] = $this->tboxIndex($g->__ef);
								$args[] = $now_sql;
								$args[] = 1;
								if($sql == null)
									$sql = 'insert into action_gacha(local_dungeon_id, gacha_func_id,tbox_id,create_date,type) values(?,?,?,?,?)';
								else
									$sql .= ',(?,?,?,?,?)';
								$adder[] = $g->local_id;
							}else{
								$g->__ef = 0;
								$g->__ga = 0;
							}
						}
					}
					// 敵のランダム位置を固定
					if($g->type == Stage::ENEMY){
						$range = $g->radius * (mt_rand(0,1000)/1000);
						$rotate = M_PI*2*mt_rand(0,1000)/1000;
						$g->pos = (object)$g->pos;
						$g->pos->x = $g->pos->x + $range*sin($rotate);
						$g->pos->z = $g->pos->z + $range*cos($rotate);
						$g->__emra = mt_rand(0,359);
					}
					// 確率を固定
					if($g->type == Stage::RATE){
						$g->radius = $g->radius*10000 >= mt_rand(0,10000)? 1: 0;
					}
					$o->obj[$gi] = $g;
				}
				$s->order[$oi]=$o;
			}
			$this->stage[$si]=$s;
		}

		DTL::Lap('fix random');
		
		if($sql != null){
			// ガチャIDを登録
			$this->useTransaction();
			$start_id = intval($this->sql('action_gacha',$sql)->insert($args));	
			$lid2ga=[];
			foreach($adder as $lid){
				$lid2ga[intval($lid)] = $start_id;
				++$start_id;
			}
			foreach($this->stage as &$s){
				foreach($s->order as &$o){
					foreach($o->obj as $g){
						if(isset($lid2ga[$g->local_id]))
							$g->__ga = $lid2ga[$g->local_id];
					}
					unset($g);
				}
				unset($o);
			}
			unset($s);
			DTL::Lap('create gacha ID');
		}
		
		$this->ticket = $ticket;
		DTL::Lap('Matching.Dungeon.init end');
	}
	const RATE_MAX = 1000000;
	const ONCE_CHECK = true;
	private function eventGacha($dungeon_std_id,$enemy_id){
		$dungeon_std_id = intval($dungeon_std_id);
		$enemy_id = intval($enemy_id);
		$area_std_id = $dungeon_std_id - ($dungeon_std_id%100);
		$world_std_id = $dungeon_std_id - ($dungeon_std_id%10000);
		$edata = $this->get('Arpg.Logic.Matching.Enemy');
		$edata->initBase($enemy_id);
		$enemy_rank = $edata->boss;
		$all_rank = 100;
		$all_enemy = 0;
		$all_dungeon = 0;
		
		$time = new \Dcs\Arpg\Time();
		$now = $time->get();
		
		$key = 'Arpg.Logic.Matching.Dungeon.eventGacha';
		$mem = $this->cache();
		$data = $mem->get(\Dcs\Cache::TYPE_APC,$key);
		if($data == null){
			$data = [];
			$rs = $this->getHs(false)->select(new Table('item_drop_event',['dungeon_id','enemy_rank','enemy_id','rate','gacha_id','tbox','effective_from','effective_to']), new Query(['>='=>0],-1));
			
			foreach($rs as $row){
				$did = intval($row[0]);
				$rank = intval($row[1]);
				$eid = intval($row[2]);
				$rate = $row[3]+0;
				$gid = intval($row[4]);
				$tbox = intval($row[5]);
				$time->setMySQLDateTime($row[6]);
				$from = $time->get();
				$time->setMySQLDateTime($row[7]);
				$to = $time->get();
				if($rate <= 0) continue;
				if($from >= $to) continue;
				if($to < $now) continue;
				if(!isset($data[$did]))
					$data[$did] = [];
				if(!isset($data[$did][$rank]))
					$data[$did][$rank] = [];
				if(!isset($data[$did][$rank][$eid]))
					$data[$did][$rank][$eid] = [];

				$data[$did][$rank][$eid][] = [
					'rate' => intval(($rate/100)*self::RATE_MAX),
					'gacha' => $gid,
					'tbox' => $tbox,
					'from' => $from,
					'to' => $to,
				];
			}
			$mem->set(\Dcs\Cache::TYPE_APC,$key, $data);
		}
		
		$check_list = [
				[$dungeon_std_id,	$enemy_rank,	$enemy_id],
				[$dungeon_std_id,	$enemy_rank,	$all_enemy],
				[$dungeon_std_id,	$all_rank,		$enemy_id],
				[$dungeon_std_id,	$all_rank,		$all_enemy],

				[$area_std_id,		$enemy_rank,	$enemy_id],
				[$world_std_id,		$enemy_rank,	$enemy_id],
				[$all_dungeon,		$enemy_rank,	$enemy_id],
				
				[$area_std_id,		$enemy_rank,	$all_enemy],
				[$world_std_id,		$enemy_rank,	$all_enemy],
				[$all_dungeon,		$enemy_rank,	$all_enemy],

				[$area_std_id,		$all_rank,	$enemy_id],
				[$world_std_id,		$all_rank,	$enemy_id],
				[$all_dungeon,		$all_rank,	$enemy_id],

				[$area_std_id,		$all_rank,	$all_enemy],
				[$world_std_id,		$all_rank,	$all_enemy],
				[$all_dungeon,		$all_rank,	$all_enemy],
		];
		for($i=0,$len=count($check_list);$i<$len;++$i){
			$c3 = $check_list[$i];
			$c1 = $c3[0];
			$c2 = $c3[1];
			$c3 = $c3[2];
			if(isset($data[$c1][$c2][$c3])){
				foreach($data[$c1][$c2][$c3] as $info){
					$rate = mt_rand(0,self::RATE_MAX);
					$info = $data[$c1][$c2][$c3];
					if($now < $info['from'] || $info['to'] < $now) continue;
					if($info['rate'] > $rate){
						return [$info['tbox'],$info['gacha']];
					}
				}
				if(self::ONCE_CHECK) return [0,0];
			}
		}
		
		return [0,0];
	}

// 	private function getLvData($list, $lv){
// 		$mlv = 0;
// 		$ret = new LvData();
// 		foreach($list as $row){
// 			$nlv = intval($row[0]);
// 			if($nlv == $lv){
// 				$ret->init($row);
// 				break;
// 			}elseif($nlv < $lv && $mlv < $nlv){
// 				$mlv = $nlv;
// 				$ret->init($row);
// 			}
// 		}
// 		return $ret;
// 	}
	private function tboxIndex($effect){
		$effect = intval($effect);
		$key = 'Arpg.Logic.Matching.Dungeon.tboxIndex';

		$cache = null;
		$mem = $this->cache();
		$cache = $mem->get(\Dcs\Cache::TYPE_APC,$key);
		if($cache == null){
			$cache = [];
			$rs = $this->getHs(false)->select(new Table('rarity_tbox',['effect_id','icon']), new Query(['>'=>0],-1));
			foreach($rs as $row){
				$cache[intval($row[0])] = intval($row[1]);
			}
			$mem->set(\Dcs\Cache::TYPE_APC,$key, $cache);
		}
		if(isset($cache[$effect]))
			return $cache[$effect];
		return 0;
	}
	
	/**
	 * 
	 * @param unknown $tbox_id
	 * @return array [effect_id,gacha_id]
	 */
	private function tboxData($tbox_id){
		$tbox_id = intval($tbox_id);
		$key = 'Arpg.Logic.Matching.Dungeon.tboxData';
		
		$cache = null;
		$mem = $this->cache();
		$cache = $mem->get(\Dcs\Cache::TYPE_APC,$key);
		if($cache == null){
			$cache = [];
			$rs = $this->getHs(false)->select(new Table('tbox',['id','tbox1_rate','tbox1_gacha','tbox2_rate','tbox2_gacha','tbox3_rate','tbox3_gacha','tbox4_rate','tbox4_gacha']), new Query(['>'=>0],-1));
			foreach($rs as $row){
				$row[1] += 0;
				$row[3] += 0;
				$row[5] += 0;
				$row[7] += 0;
				$total = $row[1]+$row[3]+$row[5]+$row[7];
				if($total == 0) continue;
				
				$cache[intval($row[0])] = [
					[$row[1]/$total,intval($row[2])],
					[($row[1]+$row[3])/$total,intval($row[4])],
					[($row[1]+$row[3]+$row[5])/$total,intval($row[6])],
					[1,intval($row[8])],
				];
			}
			$mem->set(\Dcs\Cache::TYPE_APC,$key, $cache);
		}
		if(isset($cache[$tbox_id])){
			$data = $cache[$tbox_id];
			$r = mt_rand(0,1000000)/1000000;
			if($r <= $data[0][0]){
				return [self::std_eff_boxN, $data[0][1]];
			}elseif($r <= $data[1][0]){
				return [self::std_eff_boxR, $data[1][1]];
			}elseif($r <= $data[2][0]){
				return [self::std_eff_boxS, $data[2][1]];
			}else{
				return [self::std_eff_boxSS,$data[3][1]];
			}
		}
		return [0,0];
	}
	const std_player_level = 1;
	const std_eff_boxN = 117;
	const std_eff_boxR = 118;
	const std_eff_boxS = 119;
	const std_eff_boxSS = 120;
}
// class LvData{
// 	public $lv=1;
// 	public $hp=1;
// 	public $atk=1;
// 	public $matk=1;
// 	public $def=1;
// 	public $mdef=1;
// 	public function init($row){
// 		$this->lv = intval($row[0]);
// 		$this->atk = $row[1]+0;
// 		$this->matk = $row[2]+0;
// 		$this->def = $row[3]+0;
// 		$this->mdef = $row[4]+0;
// 		$this->hp = $row[5]+0;
// 	}
// }

?>