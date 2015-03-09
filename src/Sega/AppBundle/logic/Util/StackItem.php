<?php
/**
 * メモ
 * 地道に高速化１を実装ずみ
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class StackItem extends \Dcs\Arpg\Logic{
	const TYPE_NONE 	= 0;
	const TYPE_MATERIAL	= 1;
	const TYPE_UPGRADE	= 2;
	const TYPE_TICKET	= 3;
	const TYPE_DRAG		= 4;
	const TYPE_MDRAG	= 5;
	const TYPE_BOOST	= 6;
	const TYPE_SPECIAL	= 7;
	const TYPE_EVENT	= 8;
	const TYPE_NEVENT	= 9;
	
	const MAX_STACK_ID = 91;
	/**
	 * スタックアイテムかチェック
	 * @param int $std_id 論理ID
	 */
	public static function check($std_id){
		$sid = intval($std_id);
		return 100000 <= $sid && $sid < 300000;
	}
	
	/**
	 * スタックアイテムのタイプを取得
	 * @param int $std_id
	 * @return int
	 */
	public static function std2type($std_id){
		$std_id = intval($std_id);
		if($std_id < 100000)
			return self::TYPE_NONE;
		if($std_id < 101000)
			return self::TYPE_MATERIAL;
		if($std_id < 110000)
			return self::TYPE_UPGRADE;
		if($std_id < 200000)
			return self::TYPE_TICKET;
		if($std_id < 201000)
			return self::TYPE_DRAG;
		if($std_id < 202000)
			return self::TYPE_MDRAG;
		if($std_id < 203000)
			return self::TYPE_BOOST;
		if($std_id < 220000)
			return self::TYPE_SPECIAL;
		if($std_id < 260000)
			return self::TYPE_EVENT;
		if($std_id < 300000)
			return self::TYPE_NEVENT;
		
		return self::TYPE_NONE;
	}

	public static $STACK_FIELD = ['name','nb_equip','info','rarity','sell','delete_date'];
	/**
	 * データを取得する
	 * @param int $std_id
	 * @return string
	 */
	public function getData($std_id){
		$std_id = intval($std_id);
		
		$cache = $this->cache();
		$key = 'Arpg.Logic.Util.StackItem.getData.'.$std_id;
		$ret = $cache->get($cache::TYPE_APC,$key);
		if($ret == null){
			$rs = $this->getHs(false)->select(
					new Table('item_data',self::$STACK_FIELD),
					new Query(['='=>$std_id])
			);
			if(empty($rs)) return null;
			$rs = $rs[0];
			$ret = [];
				
			for($i=0,$len=count(self::$STACK_FIELD);$i<$len;++$i){
				$dat = $rs[$i];
				if(is_numeric($dat)){
					$it = intval($dat);
					$ft = $dat+0;
					if($it == $ft)
						$dat = $it;
					else
						$dat = $ft;
				}
				$ret[self::$STACK_FIELD[$i]] = $dat;
			}
			$ret['std_id'] = $std_id;
			$cache->set($cache::TYPE_APC,$key,$ret);
		}
		return $ret;
	}
	
	/**
	 * アイテムを新規生成する存在する場合、生成しない
	 * @param string $uid
	 * @param int $std_id
	 * @param int $num
	 */
	public function create($uid, $std_id, $num){
		if(!self::check($std_id)){
			\Dcs\Log::w("$std_id is not stack item.",true);
			return;
		}
		$this->useTransaction();
		if($this->stmt_new == null)
			$this->stmt_new = $this->sql('box_stack_item','insert ignore into box_stack_item (uid,std_id,num) values(?,?,?)');
	
		$this->stmt_new->insert([$uid,$std_id,$num]);
	}
	private $stmt_new = null;

	/**
	 * アイテムを複数行生成する存在する場合、生成しない
	 * @param array $list [[uid,std_id,num]...]の形式であること
	 */
	public function createMulti($list){
		if(empty($list)) return;
		$sql = null;
		$args = [];
		foreach($list as $line){
			if(!self::check($line[1])){
				\Dcs\Log::w($line[1].' is not stack item.',true);
				continue;
			}
			if($sql == null)
				$sql = 'insert ignore into box_stack_item (uid,std_id,num) values(?,?,?)';
			else
				$sql .= ',(?,?,?)';
			$args[] = intval($line[0]);
			$args[] = intval($line[1]);
			$args[] = intval($line[2]);
		}
		
		$this->useTransaction();
		$this->sql('box_stack_item',$sql)->insert($args);
	}
	
	/**
	 * アイテムを追加する
	 * 減らす時に足りないとExceptionを出す
	 * @param string $uid
	 * @param int $std_id
	 * @param int $num
	 */
	public function add($uid, $std_id, $num){
		if(!self::check($std_id)){
			\Dcs\Log::w("$std_id is not stack item.",true);
			return;
		}
		if(intval($num) == 0) return;
		$this->useTransaction();
		if($num < 0){
			if($this->stmt_sub == null)
				$this->stmt_sub = $this->sql('box_stack_item','update box_stack_item set num = num-? where uid=? and std_id=?');
			
			if($this->stmt_sub->update([-$num,$uid,$std_id]) < 1)
				throw new \Exception('update value is out of range');
		}else{
			if($this->stmt_add == null){
				$max_stack = $this->get('Arpg.Logic.Util.DevParam')->param(self::MAX_STACK_ID);
				$this->stmt_add = $this->sql('box_stack_item',"insert into box_stack_item (uid,std_id,num) values(?,?,?) on duplicate key update num = if(num+values(num)<$max_stack,num+values(num),$max_stack)");
			}
			$this->stmt_add->insert([$uid,$std_id,$num]);
		}
	}
	private $stmt_add = null;
	private $stmt_sub = null;

	/**
	 * ステータスを複数行追加する
	 * 同じパラを複数回追加してもOK
	 * 減らす時に足りないとExceptionを出す
	 * @param array $list [[$uid,std_id,num]...]の形式であること
	 */
	public function addMulti($list){
		$hlist = [];
		foreach($list as $line){
			$key = intval($line[0]).','.intval($line[1]);
			if(array_key_exists($key,$hlist)){
				$hlist[$key] += intval($line[2]);
			}else{
				$hlist[$key] = intval($line[2]);
			}
		}
	
		$add_sql = null;
		$sub_sql = null;
		$sub_sql2 = null;
		$add_arg = [];
		$sub_arg = [];
		$sub_arg2 = [];
		foreach($hlist as $key=>$val){
			if($val == 0) continue;
				
			list($uid,$sid) = explode(',',$key);
			if(!self::check($sid)){
				\Dcs\Log::w($sid.' is not stack item.',true);
				continue;
			}
			if($val > 0){
				if($add_sql == null){
					$add_sql = 'insert into box_stack_item (uid,std_id,num) values(?,?,?)';
				}else{
					$add_sql .= ',(?,?,?)';
				}
				$add_arg[]=$uid;
				$add_arg[]=$sid;
				$add_arg[]=$val;
			}else{
				if($sub_sql == null){
					$sub_sql = 'update box_stack_item set num = case when uid=? and std_id=? then num-?';
				}else{
					$sub_sql .= ' when uid=? and std_id=? then num-?';
				}
				$sub_arg[]=$uid;
				$sub_arg[]=$sid;
				$sub_arg[]=-intval($val);
	
				if($sub_sql2 == null){
					$sub_sql2 = ' where uid in (?';
				}else{
					$sub_sql2 .= ',?';
				}
				$sub_arg2[] = $uid;
			}
		}
		if($add_sql != null){
			$max_stack = $this->get('Arpg.Logic.Util.DevParam')->param(self::MAX_STACK_ID);
			$add_sql .= " on duplicate key update num = if(num+values(num)<$max_stack,num+values(num),$max_stack)";
			$this->useTransaction();
			$this->sql('box_stack_item',$add_sql)->insert($add_arg);
		}
		if($sub_sql != null){
			$sub_sql .= ' else num end' .$sub_sql2.')';
			$sub_arg = array_merge($sub_arg,$sub_arg2);
			$this->useTransaction();
			$this->sql('box_stack_item',$sub_sql)->update($sub_arg);
		}
	}
	
	
	
	/**
	 * アイテムを設定する
	 * @param string $uid
	 * @param int $std_id
	 * @param int $num
	 */
	public function set($uid, $std_id, $num){
		if(!self::check($std_id)){
			\Dcs\Log::w("$std_id is not stack item.",true);
			return;
		}
		if($this->stmt_set == null){
			$max_stack = $this->get('Arpg.Logic.Util.DevParam')->param(self::MAX_STACK_ID);
			$this->stmt_set = $this->sql('box_stack_item',"insert into box_stack_item (uid,std_id,num) values(?,?,?) on duplicate key update num = if(values(num)<$max_stack,$values(num),$max_stack)");
		}
		
		$this->useTransaction();
		$this->stmt_set->insert([$uid,$std_id,$num]);
	}
	private $stmt_set = null;	/**
	 * アイテムを複数行設定する
	 * @param array $list [[uid,std_id,num]...]の形式であること
	 */
	public function setMulti($list){
		if(empty($list)) return;
		$sql = null;
		$args = [];
		foreach($list as $line){
			if(!self::check($line[1])){
				\Dcs\Log::w($line[1].' is not stack item.',true);
				continue;
			}
			if($sql == null)
				$sql = 'insert into box_stack_item (uid,std_id,num) values(?,?,?)';
			else
				$sql .= ',(?,?,?)';
			$args[] = intval($line[0]);
			$args[] = intval($line[1]);
			$args[] = intval($line[2]);
		}
		if($sql != null){
			$max_stack = $this->get('Arpg.Logic.Util.DevParam')->param(self::MAX_STACK_ID);
			$sql .= " on duplicate key update num = if(values(num)<$max_stack,values(num),$max_stack)";
			$this->useTransaction();
			$this->sql('box_stack_item',$sql)->insert($args);
		}
	}

	/**
	 * ブースト値を取得
	 * @param int $std_id
	 * @return array ['name' => ブースト名, 'exp'=>経験値ブースト, 'flr'=>フロラブースト,'time' => 使用時間]
	 */
	public function boostData($std_id){
		$key = 'Arpg.Logic.Util.StackItem.boostRate';
		$dat = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($dat == null){
			$dat = [];
			$rs = $this->getHs(false)->select(new Table('item_boost',['std_id','exp','flora','time','name']),new Query(['>'=>0],-1));
			foreach($rs as $row){
				$sid = intval($row[0]);
				$d = ['exp' => $row[1]+0,'flr'=>$row[2]+0,'time' => intval($row[3]),'name'=>$row[4]];
				$d['exp'] = $d['exp'] < 1?1:$d['exp'];
				$d['flr'] = $d['flr'] < 1?1:$d['flr'];
				$dat[$sid] = $d;
			}
			$this->cache()->get(\Dcs\Cache::TYPE_APC,$key,$dat);
		}
		if(isset($dat[$std_id]))
			return $dat[$std_id];
		return ['exp'=>1,'flr'=>1,'time'=>0,'name'=>''];
	}
	
	/**
	 * 数を取得する
	 * @param int $uid
	 * @param int $std_id
	 * @return number
	 */
	public function getNum($uid, $std_id){
		$rs = [];
		if($this->isTransactionActive()){
			if($this->stmt_get == null)
				$this->stmt_get = $this->sql('box_stack_item','select num from box_stack_item where uid=? and std_id=?');
			$ret = 0;
			$rs = $this->stmt_get->selectAll([$uid,$std_id],\PDO::FETCH_NUM);
		}else{
			$rs = $this->getHs()->select(
					new Table('box_stack_item',['num']),
					new Query(['='=>[$uid,$std_id]])
			);
		}
		if(empty($rs)){
			return 0;
		}
		return intval($rs[0][0]);
		
	}
	private $stmt_get = null;
	
	/**
	 * 数を取得する
	 * @param int $uid
	 * @param array $std_ids
	 * @return array [std_id => num , ...]
	 */
	public function getNumMulti($uid,array $std_ids){
		if(empty($std_ids))
			return [];
		if($this->isTransactionActive()){
			$list=[];
			foreach($std_ids as $i){
				$list[] = '?';
			}
			$stmt = $this->sql('box_stack_item','select std_id, num from box_stack_item where std_id in('.implode(',',$list).') and uid = ?');
			$list = $std_ids;
			$list[] = $uid;
			$rs = $stmt->selectAll($list,\PDO::FETCH_NUM);
		
			$ret = [];
			foreach($rs as $row){
				$ret[intval($row[0])] = intval($row[1]);
			}
			foreach($std_ids as $std_id){
				$std_id = intval($std_id);
				if(!array_key_exists($std_id,$ret))
					$ret[$std_id] = 0;
			}
			return $ret;
		}else{
			$qus=[];
			foreach($std_ids as $std_id){
				$qus[]=new Query(['='=>[$uid,$std_id]]);
			}
			$rss = $this->getHs()->selectMulti(
					new Table('box_stack_item',['std_id','num']),
					$qus
			);

			$ret = [];
			foreach($rss as $rs)foreach($rs as $row){
				$ret[intval($row[0])] = intval($row[1]);
			}
			foreach($std_ids as $std_id){
				$std_id = intval($std_id);
				if(!array_key_exists($std_id,$ret))
					$ret[$std_id] = 0;
			}
			return $ret;
		}
	}
}

?>