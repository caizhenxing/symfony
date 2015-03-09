<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\Time as Time;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\Cache as Cache;


class ActorStatus extends \Dcs\Arpg\Logic{
	
	/**
	 * アクターステータスかチェック
	 * @param int $std_id 論理ID
	 */
	public static function check($std_id){
		$sid = intval($std_id);
		return 50000 <= $sid && $sid < 100000;
	}
	
	static private $PACache=[];

	/* APC使用しない
	static private $APCache=[];
	private function setPA($aid,$uid){
		self::$APCache[$aid] = $uid;
		self::$PACache[$uid] = $aid;
	}
	private function setAP($aid,$uid){
		self::$APCache[$aid] = $uid;
	}
	private function getP($aid){
		if(isset(self::$APCache[$aid]))
			return self::$APCache[$aid];
		return null;
	}
	private function getA($uid){
		if(isset(self::$PACache[$uid]))
			return self::$PACache[$uid];
		return null;
	}
	//*/
	
	//* APC使用
	const AP = 'Arpg.Logic.Util.ActorStatus.AP.';
	const APTIME = 3600;
	private function setPA($aid,$uid){
		$c = $this->cache();
		$c->set(Cache::TYPE_APC,self::AP.$aid,$uid,self::APTIME);
		self::$PACache[$uid] = $aid;
	}
	private function setAP($aid,$uid){
		$c = $this->cache();
		$c->set(Cache::TYPE_APC,self::AP.$aid,$uid,self::APTIME);
	}
	private function getP($aid){
		$c = $this->cache();
		return $c->get(Cache::TYPE_APC,self::AP.$aid);
	}
	private function getA($uid){
		if(isset(self::$PACache[$uid]))
			return self::$PACache[$uid];
		return null;
	}
	//*/
	/**
	 * ユーザーIDからログイン中のActorIDを取得する
	 * @param int $uid
	 * @return int 
	 */
	public function getActorId($uid){
		$ret = 0;
		$uid = intval($uid);
		if(($ret = $this->getA($uid)) != null){
			return $ret;
		}elseif($this->isTransactionActive()){
			$stmt = $this->sql('box_player','select current_actor from box_player where uid = ? limit 1');
			$stmt->select([$uid]);

			while($row = $stmt->fetch(\PDO::FETCH_NUM)){
				$ret = intval($row[0]);
			}
		}else{
			$rs = $this->getHs()->select(
					new Table('box_player',['current_actor']),
					new Query(['='=>$uid])
			);
			foreach($rs as $row){
				$ret = intval($row[0]);
				break;
			}
		}
		$this->setAP($ret,$uid);
		return $ret;
	}
	
	/**
	 * ユーザーIDからログイン中のActorIDを取得する
	 * @param array $uids [ユーザーID, ...]
	 * @return array [ユーザーID => アクターID, ... ]
	 */
	public function getActorIdMulti($uids){
		$ret = [];
		$swap =[];
		foreach($uids as $uid){
			$uid = intval($uid);
			if(($aid = $this->getA($uid)) != null){
				$ret[$uid] = $aid;
			}else{
				$swap[] = $uid;
			}
		}
		$uids = $swap;
		if($this->isTransactionActive()){
			$sql = null;
			$arg = [];
			foreach($uids as $uid){
				if($sql == null)
					$sql = 'select uid,current_actor from box_player where uid in (?';
				else
					$sql .= ',?';
				$arg[] = $uid;
			}
			if($sql != null){
				$sql .= ')';
				$stmt = $this->sql('box_player',$sql);
				$stmt->select($arg);
				while($row = $stmt->fetch(\PDO::FETCH_NUM)){
					$ret[intval($row[0])] = intval($row[1]);
				}
			}
		}else{
			$qus = [];
			foreach($uids as $uid){
				$qus[] = new Query(['='=>$uid]);
			}
			if(!empty($qus)){
				$rss = $this->getHs()->selectMulti(
						new Table('box_player',['uid','current_actor']),
						$qus
				);
				foreach($rss as $rs)foreach($rs as $row){
					$ret[intval($row[0])] = intval($row[1]);
				}
			}
		}
		foreach($uids as $uid){
			if(isset($ret[$uid])){
				$this->setAP($ret[$uid],$uid);
				continue;
			}
			$ret[$uid] = 0;
		}
		return $ret;
	}
	/**
	 * アクターIDからユーザーIDを取得
	 */
	public function getUserId($aid){
		$ret = 0;
		$aid = intval($aid);
		if(($ret = $this->getP($aid)) != null){
			return $ret;
		}elseif($this->isTransactionActive()){
			$stmt = $this->sql('box_actor', 'select uid from box_actor where actor_id=?');
			$stmt->select([intval($aid)]);

			if($row = $stmt->fetch(\PDO::FETCH_NUM)){
				$ret = intval($row[0]);
				break;
			}
		}else{
			$rs = $this->getHs()->select(
					new Table('box_actor',['uid']),
					new Query(['='=>$aid])
			);
			foreach($rs as $row){
				$ret = intval($row[0]);
				break;
			}
		}
		$this->setPA($aid,$ret);
		
		return $ret;
	}
	
	/**
	 * アクターIDからユーザーIDを取得する
	 * @param array $aids [アクターID, ...]
	 * @return array [アクターID => ユーザーID, ... ]
	 */
	public function getUserIdMulti($aids){
		$cache = $this->cache();
		$ret = [];
		$swap =[];
		foreach($aids as $aid){
			$aid = intval($aid);
			if(($uid = $this->getP($aid)) != null){
				$ret[$aid] = $uid;
			}else{
				$swap[] = $aid;
			}
		}
		$aids = $swap;
		if($this->isTransactionActive()){
			$sql = null;
			$args = [];
			foreach($aids as $aid){
				if($sql == null)
					$sql = 'select actor_id,uid from box_actor where actor_id in (?';
				else
					$sql .= ',?';
				$args[] = $aid;
			}
			if($sql != null){
				$sql .= ')';
				$stmt = $this->sql('box_actor',$sql);
				$stmt->select($args);
				while($row = $stmt->fetch(\PDO::FETCH_NUM)){
					$ret[intval($row[0])] = intval($row[1]);
				}
			}
		}else{
			$qus = [];
			foreach($aids as $aid){
				$qus[] = new Query(['='=>$aid]);
			}
			if(!empty($qus)){
				$rss = $this->getHs()->selectMulti(
						new Table('box_actor',['actor_id','uid']),
						$qus
				);
				foreach($rss as $rs)foreach($rs as $row){
					$ret[intval($row[0])] = intval($row[1]);
				}
			}
		}
		foreach($aids as $aid){
			if(isset($ret[$aid])){
				$this->setPA($aid,$ret[$aid]);
				continue;
			}
			$ret[$aid] = 0;
		}
		return $ret;
	}
	
	/**
	 * ステータスを新規生成する存在する場合、生成しない
	 * @param int $actor_id
	 * @param int $std_id
	 * @param int $num
	 */
	public function create($uid, $std_id, $num){
		$this->useTransaction();
		$actor_id = $this->getActorId($uid);
		if($this->stmt_new == null)
			$this->stmt_new = $this->sql('box_actor_status','insert ignore into box_actor_status (actor_id,std_id,num) values(?,?,?)');

		$this->stmt_new->insert([$actor_id,$std_id,$num]);
	}
	private $stmt_new = null;
	/**
	 * 名前を取得する
	 * @param int $aid
	 * @return string 名前
	 */
	public function getName($aid){
		if($this->isTransactionActive()){
			$stmt = $this->sql('box_actor','select name from box_actor where actor_id=?');
			$stmt->select([intval($aid)]);
			
			if($row = $stmt->fetch(\PDO::FETCH_NUM)){
				return $row[0];
			}
		}else{
			$rs = $this->getHs()->select(
					new Table('box_actor',['name']),
					new Query(['='=>$aid])
			);
			foreach($rs as $row){
				return $row[0];
			}
		}
		return null;
	}
	/**
	 * 名前を取得する
	 * @param array $aids
	 * @return array [芥ID=>名前,・・・]の連想配列
	 */
	public function getNameMulti($aids){
		$ret = [];
		if($this->isTransactionActive()){
			
			$sql = null;
			$args = [];
			foreach($aids as $aid){
				$aid = intval($aid);
				if($aid == 0) continue;
				if($sql == null){
					$sql = 'select actor_id,name from box_actor where actor_id in (?';
				}else{
					$sql .= ',?';
				}
				$args[]=$aid;
			}
			if($sql != null){
				$sql .= ')';
				$stmt = $this->sql('box_actor',$sql);
				$rs = $stmt->selectAll($args,\PDO::FETCH_NUM);
				foreach($rs as $row){
					$ret[intval($row[0])] = $row[1];
				}
			}
		}else{

			$querys = [];
			foreach($aids as $aid){
				$aid = intval($aid);
				if($aid == 0) continue;
				$querys[] = new Query(['='=>$aid]);
			}
			if(!empty($querys)){
				
				$rss = $this->getHs()->selectMulti(
						new Table('box_actor',['actor_id','name']),
						$querys
				);
				foreach($rss as $rs){
					foreach($rs as $row){
						$ret[intval($row[0])] = $row[1];
					}
				}
			}
		}
		foreach($aids as $aid){
			if(array_key_exists($aid,$ret)) continue;
			$ret[$aid] = '';
		}
		
		return $ret;
	}
	
	/**
	 * ログインする
	 * @param int $uid ユーザーID
	 * @param int $aid アクターID
	 * @return boolean ログイン成否
	 */
	public function login($uid,$aid){
		$count = 0;
		if($this->isTransactionActive()){
			$stmt = $this->sql('box_actor','select 1 from box_actor where actor_id = ? and uid = ?');
			$count = count($stmt->selectAll([$aid,$uid],\PDO::FETCH_NUM));
		}else{
			$rs = $this->getHs()->select(
					new Table('box_actor',['uid']),
					new Query(['='=>$aid],-1)
			);
			$uid = intval($uid);
			foreach($rs as $row){
				if(intval($row[0]) == $uid){
					$count = 1;
					break;
				}
			}
		}
		if($count > 0){
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			$time = new Time();

			$Pstatus->set($uid, self::std_last_action,$time->get());
			
			$this->useTransaction();
			$stmt = $this->sql('box_actor','update box_actor set login_date=? where actor_id=?');
			$stmt->update([$time->getMySQLDateTime(),$aid]);

			$stmt = $this->sql('box_player','update box_player set login_date=?,last_action=?,current_actor=? where uid=?');
			$stmt->update([$time->getMySQLDateTime(),$time->getMySQLDateTime(),$aid,$uid]);
			
			$init = $this->getInitStatus();
			$alist=[];
			foreach($init as $std_id => $num){
				// astateのみ
				if($this->check($std_id)){
					$alist[] = [$aid,$std_id,$num];
				}
			}
			$this->createMulti($alist);
			return true;
		}
		return false;
	}
	

	/**
	 * ステータスを複数行生成する存在する場合、生成しない
	 * @param array $list [[actor_id,std_id,num]...]の形式であること 内部をforeachで回すのでkeyはなんでもOK
	 */
	public function createMulti($list){
		if(empty($list)) return;
		$sql = null;
		$args = [];
		foreach($list as $line){
			if($sql == null)
				$sql = 'insert ignore into box_actor_status (actor_id,std_id,num) values(?,?,?)';
			else
				$sql .= ',(?,?,?)';
			$args[] = intval($line[0]);
			$args[] = intval($line[1]);
			$args[] = intval($line[2]);
		}

		$this->useTransaction();
		$stmt = $this->sql('box_actor_status',$sql);
		$stmt->insert($args);
	}
	
	/**
	 * ステータスを追加する
	 * @param int $actor_id
	 * @param int $std_id
	 * @param int $num
	 */
	public function add($actor_id, $std_id, $num){
		if(intval($num) == 0) return;
		$this->useTransaction();
		if($num < 0){
			if($this->stmt_sub == null)
				$this->stmt_sub = $this->sql('box_actor_status','update box_actor_status set num = num-? where actor_id=? and std_id=?');

			if($this->stmt_sub->update([-$num,$actor_id,$std_id]) < 1)
				throw new \Exception('update value is out of range');
		}else{
			if($this->stmt_add == null)
				$this->stmt_add = $this->sql('box_actor_status','insert into box_actor_status (actor_id,std_id,num) values(?,?,?) on duplicate key update num = if(num+values(num)>0xffffffff,0xffffffff,num+values(num))');

			$this->stmt_add->insert([$actor_id,$std_id,$num]);
		}
	}
	private $stmt_add = null;
	private $stmt_sub = null;
	

	/**
	 * ステータスを複数行追加する
	 * 同じパラを複数回追加してもOK
	 * @param array $list [[actor_id,std_id,num]...]の形式であること 内部をforeachで回すのでkeyはなんでもOK
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
				
			list($aid,$sid) = explode(',',$key);
			if($val > 0){
				if($add_sql == null){
					$add_sql = 'insert into box_actor_status (actor_id,std_id,num) values(?,?,?)';
				}else{
					$add_sql .= ',(?,?,?)';
				}
				$add_arg[]=$aid;
				$add_arg[]=$sid;
				if($val > 0xffffffff) $val = 0xffffffff;
				$add_arg[]=$val;
			}else{
				if($sub_sql == null){
					$sub_sql = 'update box_actor_status set num = case when actor_id=? and std_id=? then num-?';
				}else{
					$sub_sql .= ' when actor_id=? and std_id=? then num-?';
				}
				$sub_arg[]=$aid;
				$sub_arg[]=$sid;
				$sub_arg[]=-intval($val);
	
				if($sub_sql2 == null){
					$sub_sql2 = ' where actor_id in (?';
				}else{
					$sub_sql2 .= ',?';
				}
				$sub_arg2[] = $aid;
			}
		}
		if($add_sql != null){
			$this->useTransaction();
			$add_sql .= ' on duplicate key update num = if(num+values(num)>0xffffffff,0xffffffff,num+values(num))';
			$stmt = $this->sql('box_actor_status',$add_sql);
			$stmt->insert($add_arg);
		}
		if($sub_sql != null){
			$sub_sql .= ' else num end' .$sub_sql2.')';
			$this->useTransaction();
			$sub_arg = array_merge($sub_arg,$sub_arg2);
			$stmt = $this->sql('box_actor_status',$sub_sql);
			$stmt->update($sub_arg);
		}
	}
	
	/**
	 * ステータスを設定する
	 * @param int $actor_id
	 * @param int $std_id
	 * @param int $num
	 */
	public function set($actor_id, $std_id, $num){
		$this->useTransaction();
		if($this->stmt_set == null)
			$this->stmt_set = $this->sql('box_actor_status','insert into box_actor_status (actor_id,std_id,num) values(?,?,?) on duplicate key update num = ?');

		if($num > 0xffffffff) $num = 0xffffffff;
		
		$this->stmt_set->insert([$actor_id,$std_id,$num,$num]);
	}
	private $stmt_set = null;
	/**
	 * ステータスを複数行設定する
	 * @param array $list [[actor_id,std_id,num]...]の形式であること 内部をforeachで回すのでkeyはなんでもOK
	 */
	public function setMulti($list){
		if(empty($list)) return;
		$sql = null;
		$args = [];
		foreach($list as $line){
			if($sql == null)
				$sql = 'insert into box_actor_status (actor_id,std_id,num) values(?,?,?)';
			else
				$sql .= ',(?,?,?)';
			$args[] = intval($line[0]);
			$args[] = intval($line[1]);
			$num = intval($line[2]);
			if($num > 0xffffffff) $num = 0xffffffff;
			$args[] = $num;
		}

		$sql .= ' on duplicate key update num = values(num)';
		$this->useTransaction();
		$stmt = $this->sql('box_actor_status',$sql);
		$stmt->insert($args);
	}
	
	/**
	 * ステータスを取得する
	 * @param int $actor_id
	 * @param int $std_id 
	 * @return int ステータスの数値
	 */
	public function getStatus($actor_id, $std_id){
		$rs=[];
		if($this->isTransactionActive()){
			$stmt = $this->sql('box_actor_status','select num from box_actor_status where actor_id=? and std_id=?');
			$rs = $stmt->selectAll([$actor_id,$std_id],\PDO::FETCH_NUM);
		}else{
			$rs = $this->getHs()->select(
					new Table('box_actor_status',['num']),
					new Query(['=' => [$actor_id,$std_id]])
			);
		}
		if(empty($rs)){
			return 0;
		}
		return intval($rs[0][0]);
	}

	/**
	 * ステータスを取得する
	 * @param int $actor_id
	 * @param array $std_ids
	 * @return array[stdid=>num, ... ]
	 */
	public function getStatusMulti($actor_id,array $std_ids){
		if(empty($std_ids)) return [];
		if($this->isTransactionActive()){
			$list=[];
			foreach($std_ids as $i){
				$list[] = '?';
			}
			$sql = 'select std_id, num from box_actor_status where std_id in('.implode(',',$list).') and actor_id = ?';
			$stmt = $this->sql('box_actor_status',$sql);
			$list = $std_ids;
			$list[] = $actor_id;
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
				$qus[]=new Query(['='=>[$actor_id,$std_id]]);
			}
			$rss = $this->getHs()->selectMulti(
					new Table('box_actor_status',['std_id','num']),
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
	/**
	 * ステータスを取得する
	 * @param array $list [[アクターID,論理ID], ...]形式の配列 
	 * @return array[aid => [stdid=>num, ... ], ...]
	 */
	public function getStatusMultiActor(array $list){
		if(empty($list)) return [];

		$info = [];
		$ret = [];
		foreach($list as $line){
			if(count($line) != 2)continue;
			$aid = intval($line[0]);
			$std_id = intval($line[1]);
			if(!isset($info[$aid]))
				$info[$aid] = [];
			$info[$aid][] = $std_id;
			if(!isset($ret[$aid]))
				$ret[$aid] = [];
		}

		if($this->isTransactionActive()){
			$sql = null;
			$args = [];
			foreach($info as $aid => $std_ids){
				if($sql == null)
					$sql = 'select actor_id,std_id,num from box_actor_status where (actor_id=? and std_id in(';
				else
					$sql .= ' or (actor_id=? and std_id in(';
				$args[] = $aid;
				$zero = true;
				foreach($std_ids as $std_id){
					if($zero)
						$sql .= '?';
					else
						$sql .= ',?';
					$zero = false;
					$args[] = $std_id;
				}
				$sql .= '))';
			}
			if($sql == null) return [];
			$stmt = $this->sql('box_actor_status',$sql);
			$stmt->select($args);
			while($row = $stmt->fetch(\PDO::FETCH_NUM)){
				$ret[intval($row[0])][intval($row[1])] = intval($row[2]);
			}
		}else{
			$qus=[];
			foreach($info as $aid => $std_ids)foreach($std_ids as $std_id){
				$qus[]=new Query(['='=>[$aid,$std_id]]);
			}
			if(!empty($qus)){
				$rss = $this->getHs()->selectMulti(
						new Table('box_actor_status',['actor_id','std_id','num']),
						$qus
				);
				
				foreach($rss as $rs)foreach($rs as $row){
					$ret[intval($row[0])][intval($row[1])] = intval($row[2]);
				}
			}
		}
		foreach($info as $aid => $std_ids){
			if(!isset($ret[$aid]))
				$ret[$aid] = [];
			foreach($std_ids as $std_id){
				if(!isset($ret[$aid][$std_id]))
					$ret[$aid][$std_id] = 0;
			}
		}
		return $ret;
	}
	private static $FIELD = [
			'std_id','name','info'
	];
	/**
	 * 論理IDのアクターステータスを取得する
	 * @param int $std_id
	 * @return NULL|array select * from player_status FETCH_ASSOC型の結果 intとか数値は正しい値に変換される
	 */
	public function getData($std_id){
		$std_id = intval($std_id);
		
		$cache = $this->cache();
		$key = 'Arpg.Logic.Util.ActorStatus.getData.'.$std_id;
		$ret = $cache->get($cache::TYPE_APC,$key);
		if($ret == null){
			$rs = $this->getHs(false)->select(
				new Table('actor_status',self::$FIELD),
				new Query(['='=>$std_id])
			);
			if(empty($rs)) return null;
			$rs = $rs[0];
			$ret = [];
			
			for($i=0,$len=count(self::$FIELD);$i<$len;++$i){
				$dat = $rs[$i];
				if(is_numeric($dat)){
					$it = intval($dat);
					$ft = $dat+0;
					if($it == $ft)
						$dat = $it;
					else
						$dat = $ft;
				}
				$ret[self::$FIELD[$i]] = $dat;
			}
			$ret['std_id'] = $std_id;
			$cache->set($cache::TYPE_APC,$key,$ret);
		}
		return $ret;
	}
	/**
	 * 論理IDのアクターステータスを取得する
	 * @param array $std_ids
	 * @return array select * from player_status FETCH_ASSOC型の結果を [std_id=>結果, ...] の形で返す
	 */
	public function getDatas($std_ids){
		$ret = [];
		foreach($std_ids as $std_id){
			if(array_key_exists($std_id,$ret)) continue;
			
			$ret[$std_id] = $this->getData($std_id);
		}
		return $ret;
	}
	
	/**
	 *  アクターデータ初期化用ステータスを取得する
	 *  @return array [stdid => num, ... ]の形式
	 */
	public function getInitStatus(){
		$key = 'Arpg.Logic.Util.ActorStatus.getInitStatus';
		$cache = $this->cache();
		$ret = $cache->get($cache::TYPE_APC,$key);
		if($ret == null){
			$rs = $this->getHs(false)->select(
					new Table('actor_init',['std_id','num']),
					new Query(['>'=>0],-1)
			);
			$ret = [];
			foreach($rs as $row){
				$ret[intval($row[0])] = intval($row[1]);
			}
			$cache->set($cache::TYPE_APC,$key,$ret);
		}
		return $ret;
	}
	
	const std_last_action = 1004;	// 最終ログインチケット
}

?>