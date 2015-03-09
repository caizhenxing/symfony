<?php
/**
 * メモ
 * 地道に高速化１を実装ずみ
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\Time as Time;

class World{
	public $world_id;
	public $title;
	public $file_icon_on;
	public $file_icon_off;
	public $file_map;
	public $width;
	public $height;
	public static $FIELD = [
			'id',	'title',	'file_icon_on',	'file_icon_off',	'file_map',
			'width','height'
	];
	public function __construct($row){
		$this->world_id = $row[0];
		$this->title = $row[1];
		$this->file_icon_on = $row[2];
		$this->file_icon_off = $row[3];
		$this->file_map = $row[4];
		$this->width = $row[5];
		$this->height = $row[6];
	}
	
}
class Area{
	public $world_id;
	public $area_id;
	public $title;
	public $type;
	public $file_icon;
	public $main_spirit;
	public $file_bg;
	public $file_eff;
	public $pos_x;
	public $pos_y;
	public $limit_lv;

	public static $FIELD = [
			'world_id',		'id',		'title',	'type',		'file_icon',
			'main_spirit',	'file_bg',	'pos_x',	'pos_y',	'limit_lv',
			'file_eff',
	];
	public function __construct($row){
		$this->world_id = intval($row[0]);
		$this->area_id = intval($row[1]);
		$this->title = $row[2];
		$this->type = intval($row[3]);
		$this->file_icon = $row[4];
		$this->main_spirit = intval($row[5]);
		$this->file_bg = $row[6];
		$this->pos_x = $row[7]+0;
		$this->pos_y = $row[8]+0;
		$this->limit_lv = intval($row[9]);
		$this->file_eff = $row[10];
	}
	
}
class Dungeon{
	public $world_id;
	public $area_id;
	public $dungeon_id;
	public $priority;
	public $attribute;
	public $title;
	public $enemy_level;
	public $start_type;
	public $type;
	public $file_icon;
	public $try_stp;
	public $player_exp;
	public $config;
	public $str_message;
	public $str_info;
	public $file_item;
	public $parent_dungeon;
	public $effective_from;
	public $effective_to;
	public $clear_money;
	public $kill_border;
	public $time_border;
	public $comp_open1;
	public $comp_stdid1;
	public $comp_stdid2;
	public $comp_num1;
	public $comp_num2;
	public $comp_max;
	public $limit_lv;
	public $limit_attr;
	public $limit_weapon;
	public $party_mode;
	public $tower;
	public $kbox_icon;
	public $kbox_rate;
	public $kbox_nokey_rate;
	public $kbox_nokey_gacha;
	public $kbox_key_gacha;
	public $kbox_title;

	public static $FIELD = [
			'world_id',		'area_id',		'id',				'title',		'enemy_level',
			'type',			'file_icon',		'try_stp',		'player_exp',	'config',
			'str_message',		'str_info',		'nb_item',		'file_item1',	'file_item2',	
			'file_item3',		'file_item4',	'file_item5',	'file_item6',	'parent_dungeon',	
			'effective_from',	'effective_to',	'clear_money',	'kill_border',	'comp_open1',	
			'comp_stdid1',		'comp_stdid2',	'comp_max',		'limit_lv',		'limit_attr',	
			'limit_weapon',		'party_mode',	'attribute',	'priority',		'start_type',
			'kbox_icon',		'kbox_rate',	'kbox_nokey_rate','kbox_nokey_gacha','kbox_key_gacha',
			'kbox_title',		'comp_num1',	'comp_num2',	'open_week',	'time_border'
	];
	public function std_id(){
		return 1000000+$this->world_id*10000+$this->area_id*100+$this->dungeon_id;
	}
	public function __construct($row){
		$this->world_id = intval($row[0]);
		$this->area_id = intval($row[1]);
		$this->dungeon_id = intval($row[2]);
		$this->title = $row[3];
		$this->enemy_level = intval($row[4]);
		$this->type = intval($row[5]);
		$this->file_icon = $row[6];
		$this->try_stp = intval($row[7]);
		$this->player_exp = intval($row[8]);
		$this->config = intval($row[9]);
		$this->str_message = $row[10];
		$this->str_info = $row[11];
		$this->file_item = [];
		$num = intval($row[12]);
		for($i=0;$i<$num;++$i){
			$this->file_item[] = $row[13+$i];
		}
		$pdun = explode(',',$row[19]);
		$this->parent_dungeon = [];
		foreach($pdun as $d){
			if(is_numeric($d))
				$this->parent_dungeon[] = intval($d);
		}
		$this->effective_from = $row[20];
		$this->effective_to = $row[21];
		$this->clear_money = intval($row[22]);
		$this->kill_border = intval($row[23]);
		$this->comp_open1 = intval($row[24]);
		$this->comp_stdid1 = intval($row[25]);
		$this->comp_stdid2 = intval($row[26]);
		$this->comp_num1 = intval($row[41]);
		$this->comp_num2 = intval($row[42]);
		$this->comp_num1 = $this->comp_num1<1?1:$this->comp_num1;
		$this->comp_num2 = $this->comp_num2<1?1:$this->comp_num2;
		$this->comp_max = intval($row[27]);
		if($this->comp_max < 1)
			$this->comp_max = 1;
		$this->limit_lv = intval($row[28]);
		$this->limit_attr = intval($row[29]);
		$this->limit_weapon = intval($row[30]);
		if($this->world_id == 99)
			$this->party_mode = 2;
		elseif($this->world_id == 98)
			$this->party_mode = intval($row[31]);
		else
			$this->party_mode = 1;
		if($this->party_mode < 1 || 2 < $this->party_mode)
			$this->party_mode = 1;
		
		$this->attribute = intval($row[32]);
		$this->priority = intval($row[33]);
		$this->start_type = intval($row[34]);
		$this->kbox_icon = $row[35];
		$this->kbox_rate = intval($row[36]);
		$this->kbox_nokey_rate = intval($row[37]);
		$this->kbox_nokey_gacha = intval($row[38]);
		$this->kbox_key_gacha = intval($row[39]);
		$this->kbox_title = $row[40];
		$sep = explode(',',$row[43]);
		$ow = [];
		foreach($sep as $wd){
			if(!is_numeric($wd)) continue;
			$wd = intval($wd);
			if($wd < 0 || 7 <= $wd) continue;
			$ow[$wd] = true;
		}
		if(empty($ow)){
			$ow=[true,true,true,true,true,true,true];
		}else{
			for($i=0;$i<7;++$i){
				if(!isset($ow[$i])){
					$ow[$i] = false;
				}
			}
		}
		$this->open_week = $ow;
		$this->tower = [];
		
		$this->time_border = intval($row[44]);
	}
	private function allDay(){
		foreach($this->open_week as $ow){
			if($ow) continue;
			return false;
		}
		return true;
	}
	private $m_from = null;
	public function from(){
		if($this->m_from !== null)
			return $this->m_from;
		$time = new Time();
		$now = $time->get();
		$ret = $time->setMySQLDateTime($this->effective_from)->get();
		if(!$this->allDay()){
			$w = new Time();
			$w->set($now);
			$dt = $w->getDateTime();
			$w = intval($dt->format('w'));
			$today = \DateTime::createFromFormat('Y-m-d H:i:s',$dt->format('Y-m-d 00:00:00'));
			$today = $today->getTimestamp();
			if($this->open_week[$w]){
				$pre = 0;
				for($i=1;$i<7;++$i){
					$a = $w-$i;
					$ap = $a < 0?$a+7:$a;
					if($this->open_week[$ap])
						$pre = $i;
					else
						break;
				}
				$ter = new Time();
				$ter->setUnixTime($today-$pre*self::one);
				$ret = $ter->get();
			}else{
				$post = 0;
				for($i=1;$i<7;++$i){
					$ap = $i % 7;
					if($this->open_week[$ap]){
						$post = $i;
						break;
					}
				}

				$ter = new Time();
				$ter->setUnixTime($today+$post*self::one);
				$ret = $ter->get();
			}
		}
		$this->m_from = $ret;
		return $ret;
	}
	private $m_to = null;
	public function to(){
		if($this->m_to !== null)
			return $this->m_to;
		$time = new Time();
		$now = $time->get();
		$ret = $time->setMySQLDateTime($this->effective_to)->get();
		if(!$this->allDay()){
			$w = new Time();
			$w->set($now);
			$dt = $w->getDateTime();
			$w = intval($dt->format('w'));
			$today = \DateTime::createFromFormat('Y-m-d H:i:s',$dt->format('Y-m-d 00:00:00'));
			$today = $today->getTimestamp();
			if($this->open_week[$w]){
				$post = 0;
				for($i=1;$i<7;++$i){
					$ap = ($w+$i) % 7;
					if($this->open_week[$ap]){
						$post = $i;
					}else{
						break;
					}
				}
				$ter = new Time();
				$ter->setUnixTime($today+(1+$post)*self::one);
				$buf = $ter->get();
				
			}else{
				$post = 0;
				for($i=0;$i<7;++$i){
					$ap =($w+$i) % 7;
					if($post > 0 && !$this->open_week[$ap]){
						break;
					}elseif($this->open_week[$ap]){
						$post = $i;
					}
				}

				$ter = new Time();
				$ter->setUnixTime($today+(1+$post)*self::one);
				$buf = $ter->get();
			}
			if($buf < $ret)
				$ret = $buf;
		}
		$this->m_to = $ret;
		return $ret;
	}
	
	public function enable(){
		$time = new Time();
		$now = $time->get();
		$from = $this->from();
		$to = $this->to();
		if($now < $from || $to <= $now) return false;
		return true;
	}
	const one = 86400;//一日の秒数
	const zero = 259200;// 開始日の秒数20100101からの秒数 月曜を開始日にしてる
}

class Quest extends \Dcs\Arpg\Logic{
	/**
	 * ダンジョンタイプ メイン
	 * @var int
	 */
	const MAIN = 1;
	/**
	 * ダンジョンタイプ サブシナリオ
	 * @var int
	 */
	const SUB = 2;
	/**
	 * ダンジョンタイプ タワー
	 * @var int 
	 */
	const TOWER = 3;
	
	/**
	 * 更新用フラグ クエストクリア
	 * @var int
	 */
	const FLAG_CLEAR = 0;
	/**
	 * 更新用フラグ クエスト開始
	 * @var int
	 */
	const FLAG_BEGIN = 1;
	/**
	 * 更新用フラグ クエストクリアかつ、メインを取得
	 * @var int
	 */
	const FLAG_GETMAIN = 2;
	
	public static function check($std_id){
		$std_id = intval($std_id);
		return 1000000 <= $std_id && $std_id < 2000000;
	}

	/**
	 * ワールド情報を取得する
	 * @param int $world_id ワールドID 0の場合全ダンジョン	default: 0
	 * @return array Logic\Util\Worldの配列
	 */
	public function getWorldInfo($world_id=0){
		$world_id = intval($world_id);

		$key = 'Sega.AppBundle.Dao.Util.Query.GetWInfo';
		$cache = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($cache == null){
			$rs = $rs = $this->getHs(false)->select(
					new Table('quest_world',World::$FIELD),
					new Query(['>'=>0],-1)
			);
			$cache = [];
			foreach($rs as $row){
				$cache[] = new World($row);
			}
			$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$cache);
		}

		$ret = [];
		if($world_id < 1){
			$ret = $cache;
		}else{
			foreach($cache as $w){
				if($w->world_id == $world_id){
					$ret[] = $w;
				}
			}
		}
		return $ret;
	}
	/**
	 * エリア情報を取得する
	 * @param int $world_id ワールドID 0の場合全ダンジョン	default: 0
	 * @param int $area_id エリアID 0の場合 指定ワールドの全ダンジョン	default: 0
	 * @return array Logic\Util\Areaの配列
	 */
	public function getAreaInfo($world_id = 0, $area_id = 0){
		$world_id = intval($world_id);
		$area_id = intval($area_id);

		$key = 'Sega.AppBundle.Dao.Util.Query.GetAInfo';

		$cache = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($cache == null){
			$rs = $this->getHs(false)->select(
					new Table('quest_area',Area::$FIELD),
					new Query(['>'=>0],-1)
			);
			$cache=[];
			foreach($rs as $row){
				$cache[] = new Area($row);
			}
			$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$cache);
		}
		$ret = [];
		if($world_id < 1){
			$ret = $cache;
		}elseif($area_id < 1){
			foreach($cache as $area){
				if($area->world_id == $world_id){
					$ret[] = $area;
				}
			}
		}else{
			foreach($cache as $area){
				if($area->world_id == $world_id && $area->area_id == $area_id){
					$ret[] = $area;
				}
			}
		}
		
		return $ret;
	}
	/**
	 * エリア情報を論理IDで取得する
	 * 論理IDの書式
	 * 1000000 以下の場合全ダンジョンを取得する
	 * 1234567と入力した場合、world:23 area:45 の情報を取得する
	 * 1230000と入力した場合、world:23  下の全エリア情報を取得する
	 * @param int $std_id
	 * @return array Logic\Util\Areaの配列
	 */
	public function getAreaInfoByStdID($std_id=0){
		$std_id = intval($std_id);
		$world_id = 0;
		$area_id = 0;
	
		if($std_id > 1000000){
			$area_id = intval($std_id/100) % 100;
			$world_id = intval($std_id/10000) % 100;
		}
		return $this->getAreaInfo($world_id, $area_id);
	}
	
	/**
	 * ダンジョン情報を取得する
	 * @param int $world_id ワールドID 0の場合全ダンジョン	default: 0
	 * @param int $area_id エリアID 0の場合 指定ワールドの全ダンジョン	default: 0
	 * @param int $dungeon_id ダンジョンID 0の場合、指定エリアの全ダンジョン	default: 0
	 * @return array Logic\Util\Dungeonの配列
	 */
	public function getDungeonInfo($world_id = 0, $area_id = 0, $dungeon_id = 0){
		$world_id = intval($world_id);
		$area_id = intval($area_id);
		$dungeon_id = intval($dungeon_id);
		
		$key = 'Sega.AppBundle.Dao.Util.Query.GetDInfo';
		$cache = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($cache == null){
			$rs = $this->getHs(false)->select(
					new Table('quest_dungeon',Dungeon::$FIELD),
					new Query(['>'=>0],-1)
			);
			$qus = [];
			$dic = [];
			foreach($rs as $row){
				$dun = new Dungeon($row);
				if($dun->type == self::TOWER){
					$qus[] = new Query(['='=>$dun->std_id()],-1);
				}
				$dic[$dun->std_id()] = $dun;
			}
			
			$rss = $this->getHs(false)->selectMulti(
					new Table('quest_tower',['std_id','lv','floor']),
					$qus
			);
			foreach($rss as $rs)foreach($rs as $row){
				$dic[intval($row[0])]->tower[intval($row[1])] = intval($row[2]);
			}
			$cache = [];
			foreach($dic as $d){
				$cache[] = $d;
			}
			$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$cache);
		}
		$ret = [];
		if($world_id < 1){
			$ret = $cache;
		}elseif($area_id < 1){
			foreach($cache as $dun){
				if($dun->world_id == $world_id){
					$ret[] = $dun;
				}
			}
		}elseif($dungeon_id < 1){
			foreach($cache as $dun){
				if($dun->world_id == $world_id && $dun->area_id == $area_id){
					$ret[] = $dun;
				}
			}
		}else{
			foreach($cache as $dun){
				if($dun->world_id == $world_id && $dun->area_id == $area_id && $dun->dungeon_id == $dungeon_id){
					$ret[] = $dun;
				}
			}
		}
		return $ret;
	}
	/**
	 * ダンジョン情報を論理IDで取得する
	 * 論理IDの書式
	 * 1000000 以下の場合全ダンジョンを取得する
	 * 1234567と入力した場合、world:23 area:45 dungeon:67 の情報を取得する
	 * 1234500と入力した場合、world:23 area:45 下の全ダンジョン情報を取得する
	 * @param int $std_id
	 * @return array Logic\Util\Dungeonの配列
	 */
	public function getDungeonInfoByStdID($std_id=0){
		$std_id = intval($std_id);
		$world_id = 0;
		$area_id = 0;
		$dungeon_id = 0;
		
		if($std_id > 1000000){
			$dungeon_id = $std_id % 100;
			$area_id = intval($std_id/100) % 100;
			$world_id = intval($std_id/10000) % 100;
		}
		return $this->getDungeonInfo($world_id, $area_id,$dungeon_id);
	}
	
	/**
	 * ステータスを新規生成する存在する場合、生成しない
	 * @param int $uid
	 * @param int $std_id
	 */
	public function create($uid, $std_id){
		$this->useTransaction();
		if($this->stmt_new == null)
			$this->stmt_new = $this->sql('box_quest','insert ignore into box_quest (uid,world_id,area_id,dungeon_id) values(?,?,?,?)');
		$std_id = intval($std_id);
		$wid = intval($std_id/10000) % 100;
		$aid = intval($std_id/100) % 100;
		$did = $std_id % 100;

		$this->stmt_new->insert([$uid,$wid,$aid,$did]);
	}
	private $stmt_new = null;
	
	/**
	 * ステータスを新規作成する存在する場合、生成しない
	 * @param array $list 設定値リスト[[uid,dungeon_id], ... ] 内部をforeachで回すのでkeyはなんでもOK
	 */
	public function createMulti($list){
		if(empty($list)) return;
		$arg = [];
		$sql = null;
		$this->useTransaction();
		foreach($list as $line){
			if($sql == null){
				$sql = 'insert ignore into box_quest (uid,world_id,area_id,dungeon_id) values(?,?,?,?)';
			}else{
				$sql .= ',(?,?,?,?)';
			}

			$arg[] = intval($line[0]);
			$std_id = intval($line[1]);
			$arg[] = intval($std_id/10000) % 100;
			$arg[] = intval($std_id/100) % 100;
			$arg[] = $std_id % 100;
		}
		$this->sql('box_quest',$sql)->insert($arg);
	}
	private static $DATA_FLD = [
			'uid','world_id','area_id','dungeon_id','nb_try','nb_clear','nb_get_main','hunt'
	];
	/**
	 * ユーザーのデータを取得
	 * @param int $uid
	 * @param int $std_id
	 * @return array box_questを連想配列で取得したもの
	 */
	public function getData($uid, $std_id){
		$std_id = intval($std_id);
		$wid = intval($std_id/10000) % 100;
		$aid = intval($std_id/100) % 100;
		$did = $std_id % 100;
		$rs = $this->getHs()->select(
				new Table('box_quest',self::$DATA_FLD),
				new Query(['='=>[$uid,$wid,$aid,$did]])
		);
		if(empty($rs)) return null;
		
		$rs = $rs[0];
		$ret = [];
		
		for($i=0,$len=count(self::$DATA_FLD);$i<$len;++$i){
			$dat = $rs[$i];
			if(is_numeric($dat)){
				$it = intval($dat);
				$ft = $dat+0;
				if($it == $ft)
					$dat = $it;
				else
					$dat = $ft;
			}
			$ret[self::$DATA_FLD[$i]] = $dat;
		}
		$ret['std_id'] = $std_id;
		return $ret;
	}
	/**
	 * ステータスを更新する
	 * @param int $uid 
	 * @param int $std_id
	 * @param int $state  FLAG_XX系定数
	 * @param int $add_hunt 追加到達値クリア系でないと実装されない default: 0
	 */
	public function update($uid, $std_id,$state,$add_hunt=0){
		$std_id = intval($std_id);
		$wid = intval($std_id/10000) % 100;
		$aid = intval($std_id/100) % 100;
		$did = $std_id % 100;
		
		$sql = null;
		$args = [];
		if($state == self::FLAG_CLEAR){
			$sql = 'insert into box_quest (uid,world_id,area_id,dungeon_id,nb_clear,hunt) values(?,?,?,?,1,?) on duplicate key update nb_clear=nb_clear+1,hunt=hunt+values(hunt)';
			$args = [$uid,$wid,$aid,$did,$add_hunt];
		}elseif($state == self::FLAG_BEGIN){
			$sql = 'insert into box_quest (uid,world_id,area_id,dungeon_id,nb_try) values(?,?,?,?,1) on duplicate key update nb_try=nb_try+1';
			$args = [$uid,$wid,$aid,$did];
		}elseif($state == self::FLAG_GETMAIN){
			$sql = 'insert into box_quest (uid,world_id,area_id,dungeon_id,nb_clear,nb_get_main,hunt) values(?,?,?,?,1,1,?) on duplicate key update nb_clear=nb_clear+1,nb_get_main=nb_get_main+1,hunt=hunt+values(hunt)';
			$args = [$uid,$wid,$aid,$did,$add_hunt];
		}
		if($sql == null) return;
		
		$this->useTransaction();
		$this->sql('box_quest',$sql)->insert($args);
	}
	
	/**
	 * 拡張ボーナスチェック
	 * @param int $std_id ダンジョンSTDID
	 * @return ['std_id'=> , 'type'=> ,'rate'=>, 'from'=>int, 'to'=>int]
	 */
	public function checkExt($std_id){
		$std_id = intval($std_id);
		$key = 'Arpg.Logic.Util.Quest.checkExt';
		$cache = $this->cache();
		$list = $cache->get($cache::TYPE_APC,$key);
		if($list == null){
			$now = new Time();
			$rs = $this->getHs(false)->select(
					new Table('quest_bonus',['std_id','type','rate','effective_from','effective_to'],'ETO'),
					new Query(['>='=>$now->getMySQLDateTime()],-1)
			);
			$list = [];
			foreach($rs as $row){
				if(intval($row[1])>0)
					$list[] = $this->createExtData($row);
			}
			$cache->set($cache::TYPE_APC,$key,$list);
		}
		
		$now = new Time();
		$now = $now->get();
		foreach($list as $line){
			if($std_id != $line['std_id']) continue;
			if($now < $line['from'] || $line['to'] < $now) continue;
			return $line;
			break;
		}
		return null;
	}
	private function createExtData($row){
		$ret = [];
		$ret['std_id'] = intval($row[0]);
		$ret['type'] = intval($row[1]);
		$ret['rate'] = $row[2]+0;
		$time = new Time();
		$time->setMySQLDateTime($row[3]);
		$ret['from'] = $time->get();
		$time->setMySQLDateTime($row[4]);
		$ret['to'] = $time->get();
		$Text = $this->get('Arpg.Logic.Util.Text');
		switch($ret['type']){
			case 1:{
				$rate = intval($ret['rate']*10);
				if($rate % 10 == 0)
					$rate = intval($rate/10);
				else
					$rate = intval($rate/10).'.'.($rate%10);
				$ret['info']= $Text->getText(10210,['[rate]'=>$rate]);
				break;
			}
			case 2:{
				$rate = 100-intval($ret['rate']*100);
				$ret['info']= $Text->getText(10211,['[rate]'=>$rate]);
				break;
			}
			case 3:{
				$rate = intval($ret['rate']*10);
				if($rate % 10 == 0)
					$rate = intval($rate/10);
				else
					$rate = intval($rate/10).'.'.($rate%10);
				$ret['info']= $Text->getText(10212,['[rate]'=>$rate]);
				break;
			}
		}
		
		return $ret;
	}
}

?>