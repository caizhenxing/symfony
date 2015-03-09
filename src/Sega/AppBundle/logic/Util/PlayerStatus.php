<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\Cache as Cache;

class PlayerStatus extends \Dcs\Arpg\Logic{
	
	/**
	 * プレイヤーステータスかチェック
	 * @param int $std_id 論理ID
	 */
	public static function check($std_id){
		$sid = intval($std_id);
		return 0 < $sid && $sid < 50000;
	}

	/* APC使用しない
	private function getU($p){
		return null;
	}
	private function getP($u){
		return null;
	}
	private function setPU($p,$u){
	}
	//*/
	
	//* APC 使用
	const KEY_P2U = 'Arpg.Logic.Util.PlayerStatus.P2U.';
	const KEY_U2P = 'Arpg.Logic.Util.PlayerStatus.U2P.';
	const KEY_TIME = 3600;
	private function getU($p){
		$p = intval($p);
		$c = $this->cache();
		return $c->get(Cache::TYPE_APC,self::KEY_P2U.$p);
	}
	private function getP($u){
		$u = intval($u);
		$c = $this->cache();
		return $c->get(Cache::TYPE_APC,self::KEY_U2P.$u);
	}
	private function setPU($p,$u){
		$p = intval($p);
		$u = intval($u);
		$c = $this->cache();
		$c->set(Cache::TYPE_APC,self::KEY_P2U.$p,$u,self::KEY_TIME);
		$c->set(Cache::TYPE_APC,self::KEY_U2P.$u,$p,self::KEY_TIME);
	}
	//*/
	/**
	 * public_id をキーとして ユーザIDを取得します
	 *
	 * @param int $publicId フレンド検索に利用する公開ID
	 *
	 * @return int 該当するユーザのユーザID
	 */
	public function getUserId($publicId){
		if(($ret = $this->getU($publicId)) != null)
			return $ret;
		$user = $this->get('gaia.user.user_service');
		$ret=  $user->getUserIdByPublicId($publicId);
		$this->setPU($publicId,$ret);
		return $ret;
	}
	/**
	 * public_id をキーとして ユーザIDを取得します
	 *
	 * @param array $publicId フレンド検索に利用する公開IDリスト
	 *
	 * @return array [公開ID1 => ユーザーID1, ... ]
	 */
	public function getUserIds(array $publicIds){
		$pids = [];
		$ret = [];
		foreach($publicIds as $pid){
			if(($uid = $this->getU($pid)) != null)
				$ret[intval($pid)] = $uid;
			else
				$pids[] = $pid;
		}

		if(!empty($pids)){
			$ret2 = $this->get('gaia.user.user_service')->getUserIds($pids);
			foreach($ret2 as $pid => $uid){
				$ret[intval($pid)] = intval($uid);
				$this->setPU($pid,$uid);
			}
		}
		return $ret;
	}
	
	/**
	 * ユーザIDをキーとして公開IDを取得します
	 *
	 * @param int $uid ユーザID
	 *
	 * @return int 該当のユーザの public_id（フレンド検索時にユーザが利用するID）
	 */
	public function getPublicId($userId){
		if(($ret = $this->getP($userId)) != null)
			return $ret;
		$pid = $this->get('gaia.user.user_service')->getPublicId($userId);
		$this->setPU($pid,$userId);
		return $pid;
	}
	
	/**
	 * ユーザーIDをキーとして公開IDを取得します
	 * @param array $uids ユーザーIDリスト
	 * @return array ['ユーザーID1' => 公開ID1, ... ]
	 */
	public function getPublicIds(array $userIds){
		$uids = [];
		$ret = [];
		foreach($userIds as $uid){
			if(($pid = $this->getP($uid)) != null)
				$ret[intval($uid)] = $pid;
			else
				$uids[] = $uid;
		}
		if(!empty($uids)){
			$ret2 = $this->get('gaia.user.user_service')->getPublicIds($uids);
			foreach($ret2 as $uid => $pid){
				$ret[intval($uid)] = intval($pid);
				$this->setPU($pid,$uid);
			}
		}
		return $ret;
	}
	
	
	
	/**
	 * ステータスを新規生成する存在する場合、生成しない
	 * @param string $uid
	 * @param int $std_id
	 * @param int $num
	 */
	public function create($uid, $std_id, $num,$is_check=true){
		if($is_check && !self::check($std_id)){
			\Dcs\Log::w('$std_id is not player status.',true);
			return;
		}
		if($this->stmt_new == null)
			$this->stmt_new = $this->sql('box_player_status','insert ignore into box_player_status (uid,std_id,num) values(?,?,?)');

		$this->useTransaction();
		$this->stmt_new->insert([$uid,$std_id,$num]);
	}
	private $stmt_new = null;
	/**
	 * ステータスを複数行生成する存在する場合、生成しない
	 * @param array $list [[$uid,std_id,num]...]の形式であること
	 */
	public function createMulti($list){
		if(empty($list)) return;
		$sql = null;
		$args = [];
		foreach($list as $line){
			if($sql == null)
				$sql = 'insert ignore into box_player_status (uid,std_id,num) values(?,?,?)';
			else
				$sql .= ',(?,?,?)';
			$args[] = intval($line[0]);
			$args[] = intval($line[1]);
			$args[] = intval($line[2]);
		}

		$this->useTransaction();
		$stmt = $this->sql('box_player_status',$sql)->insert($args);
	}
	
	/**
	 * ステータスを追加する
	 * @param string $uid
	 * @param int $std_id 10001(cp)を指定して減算する時、足りない分は課金CPから減らされる
	 * @param int $num 追加する数
	 */
	public function add($uid, $std_id, $num,$is_check=true){
		if(intval($num) == 0) return;
		
		if($is_check && !$this->check($std_id))
			\Dcs\Log::e('invalid value. $std_id',true);
		if($num < 0){
			if($std_id == self::std_cp){
				$this->addMulti([[$uid,$std_id,$num]],$is_check);
				return;
			}
				
			$this->useTransaction();
			if($this->stmt_sub == null)
				$this->stmt_sub = $this->sql('box_player_status','update box_player_status set num = num-? where uid=? and std_id=?');

			if($this->stmt_sub->update([-$num,$uid,$std_id]) < 1)
				throw new \Exception('update value is out of range');
		}else{
			$this->useTransaction();
			if($num > 0xffffffff) $num = 0xffffffff;
			if($this->stmt_add == null)
				$this->stmt_add = $this->sql('box_player_status','insert into box_player_status (uid,std_id,num) values(?,?,?) on duplicate key update num = if(num+values(num)>0xffffffff,0xffffffff,num+values(num))');

			$this->stmt_add->insert([$uid,$std_id,$num]);
		}
	}
	private $stmt_add = null;
	private $stmt_sub = null;
	
	/**
	 * ステータスを複数行追加する
	 * 同じパラを複数回追加してもOK
	 * 10001(cp)を指定して減算する時、足りない分は課金CPから減らされる CPは自分のぶんしか減らせない
	 * @param array $list [[$uid,std_id,num]...]の形式であること
	 */
	public function addMulti($list,$is_check=true){
		$hlist = [];
		$cp = 0;
		foreach($list as $line){
			$std_id = intval($line[1]);
			if($is_check && !$this->check($std_id))
				\Dcs\Log::e("invalid value. $std_id",true);
			$key = intval($line[0]).','.$std_id;
			if(array_key_exists($key,$hlist)){
				$hlist[$key] += intval($line[2]);
			}else{
				$hlist[$key] = intval($line[2]);
			}
			if($std_id == self::std_cp)
				$cp = intval($line[0]);
		}

		$add_sql = null;
		$sub_sql = null;
		$sub_sql2 = null;
		$add_arg = [];
		$sub_arg = [];
		$sub_arg2 = [];

		$os_cp = null;
		if($cp > 0){
			$uid = $cp;
			$os_cp = self::std_android_cp;
			$header = getallheaders();
			if(isset($header['SGNOsType']) && intval($header['SGNOsType']) == 1)
				$os_cp = self::std_apple_cp;

			$ps = $this->getStatusMulti($uid,[self::std_cp,$os_cp]);
			$cp = $ps[self::std_cp]-$ps[$os_cp]; // std_cpが合算値のため
		}
		$use_os_cp = 0;
		$lock=[];
		foreach($hlist as $key=>$val){
			if($val == 0) continue;
			
			list($uid,$sid) = explode(',',$key);
			if($val > 0){
				if($add_sql == null){
					$add_sql = 'insert into box_player_status (uid,std_id,num) values(?,?,?)';
				}else{
					$add_sql .= ',(?,?,?)';
				}
				$add_arg[]=$uid;
				$add_arg[]=$sid;
				if($val > 0xffffffff) $val = 0xffffffff;
				$add_arg[]=$val;
			}else{
				if($sub_sql == null){
					$sub_sql = 'update box_player_status set num = case when uid=? and std_id=? then num-?';
				}else{
					$sub_sql .= ' when uid=? and std_id=? then num-?';
				}
				$sub_arg[]=$uid;
				$sub_arg[]=$sid;
				$val = intval($val);
				if($sid == self::std_cp){
					if($cp < -$val){
						$use_os_cp += -$val-$cp;
						$val = -$cp;
					}
					$cp += $val;
				}
				$sub_arg[]=-$val;
				if(!isset($lock[$uid])){
					if($sub_sql2 == null){
						$sub_sql2 = ' where uid in (?';
					}else{
						$sub_sql2 .= ',?';
					}
					$sub_arg2[] = $uid;
					$lock[$uid] = true;
				}
			}
		}
		if($add_sql != null){
			$add_sql .= ' on duplicate key update num = if(num+values(num)>0xffffffff,0xffffffff,num+values(num))';
			$this->useTransaction();
			$this->sql('box_player_status',$add_sql)->insert($add_arg);
		}
		if($use_os_cp > 0){
			$sub_sql .= ' when uid=? and std_id=? then num-?';
			$sub_arg[]=$uid;
			$sub_arg[]=$os_cp;
			$sub_arg[]=intval($use_os_cp);
		}
		if($sub_sql != null){
			$sub_sql .= ' else num end' .$sub_sql2.')';
			$sub_arg = array_merge($sub_arg,$sub_arg2);
			$this->useTransaction();
			$this->sql('box_player_status',$sub_sql)->update($sub_arg);
		}
	}
	
	/**
	 * ステータスを設定する
	 * @param string $uid
	 * @param int $std_id
	 * @param int $num
	 */
	public function set($uid, $std_id, $num,$is_check=true){
		if($std_id == self::std_cp)
			throw new \Exception('dont set CP. use add');
		
		if($this->stmt_set == null)
			$this->stmt_set = $this->sql('box_player_status','insert into box_player_status (uid,std_id,num) values(?,?,?) on duplicate key update num = ?');

		if($is_check && !$this->check($std_id))
			\Dcs\Log::e('invalid value. $std_id',true);
		
		if($num > 0xffffffff) $num = 0xffffffff;
		
		$this->useTransaction();
		$this->stmt_set->insert([$uid,$std_id,$num,$num]);
	}
	private $stmt_set = null;
	/**
	 * ステータスを複数行設定する
	 * @param array $list [[$uid,std_id,num]...]の形式であること
	 */
	public function setMulti($list,$is_check=true){
		if(empty($list)) return;
		$sql = null;
		$args = [];
		foreach($list as $line){
			
			if($sql == null)
				$sql = 'insert into box_player_status (uid,std_id,num) values(?,?,?)';
			else
				$sql .= ',(?,?,?)';
			$std_id = intval($line[1]);
			if($std_id == self::std_cp)
				throw new \Exception('dont set CP. use add');
			
			if($is_check && !$this->check($std_id))
				\Dcs\Log::e('invalid value. $std_id',true);
			$args[] = intval($line[0]);
			$args[] = $std_id;
			$num = intval($line[2]);
			if($num > 0xffffffff) $num = 0xffffffff;
			$args[] = $num;
		}
		$sql .= ' on duplicate key update num = values(num)';
		$this->useTransaction();
		$this->sql('box_player_status',$sql)->insert($args);
	}

	/**
	 * ステータスを取得する
	 * @param int $uid 10001(cp)の場合、有料CPとの合算値となる
	 * @param int $std_id
	 */
	public function getStatus($uid, $std_id,$is_check=true){
		if($std_id == self::std_cp){
			return $this->getStatusMulti($uid,[$std_id],$is_check)[$std_id];
		}
		
		$rs = [];
		if($is_check && !$this->check($std_id))
			\Dcs\Log::e("invalid value. $std_id",true);
		if($this->isTransactionActive()){
			if($this->stmt_get == null)
				$this->stmt_get = $this->sql('box_player_status','select num from box_player_status force index(PRIMARY) where uid = ? and std_id = ? for update');
			$rs = $this->stmt_get->selectAll([$uid,$std_id],\PDO::FETCH_NUM);
		}else{
			$rs = $this->getHs()->select(
					new Table('box_player_status',['num']),
					new Query(['=' => [$uid,$std_id]])
			);
		}
		
		if(empty($rs)) return 0;
		
		return intval($rs[0][0]);
	}
	private $stmt_get = null;

	/**
	 * ステータスを取得する
	 * @param int $uid 
	 * @param array $std_ids 10001(cp)の場合、有料CPとの合算値となる
	 * @return array[stdid=>num, ... ]
	 */
	public function getStatusMulti($uid,array $std_ids,$is_check=true){
		if(empty($std_ids)) return [];
		$is_cp = false;
		foreach($std_ids as $std_id){
			if(intval($std_id) == self::std_cp){
				$is_cp = true;
				break;
			}
		}
		$os_cp = null;
		if($is_cp){
			$os_cp = self::std_android_cp;
			$header = getallheaders();
			if(isset($header['SGNOsType']) && intval($header['SGNOsType']) == 1)
				$os_cp = self::std_apple_cp;
			$std_ids[] = $os_cp;
		}
		if($this->isTransactionActive()){
			$list=[];
			foreach($std_ids as $i){
				$list[] = '?';
				if($is_check && !$this->check($i))
					\Dcs\Log::e("invalid value. $i",true);
			}
			$stmt = $this->sql('box_player_status','select std_id, num from box_player_status force index(PRIMARY) where std_id in('.implode(',',$list).') and uid = ? for update');
			$list = $std_ids;
			$list[] = $uid;
			$rs = $stmt->selectAll($list,\PDO::FETCH_NUM);
			
			$ret = [];
			foreach($rs as $row){
				$ret[intval($row[0])] = intval($row[1]);
			}
			if($is_cp && isset($ret[$os_cp])){
				$ret[self::std_cp] += $ret[$os_cp];
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
				if($is_check && !$this->check($std_id))
					\Dcs\Log::e("invalid value. $std_id",true);
				$qus[]=new Query(['='=>[$uid,$std_id]]);
			}
			$ret = [];
			if(!empty($qus)){
				$rss = $this->getHs()->selectMulti(
						new Table('box_player_status',['std_id','num']),
						$qus
				);
	
				foreach($rss as $rs)foreach($rs as $row){
					$ret[intval($row[0])] = intval($row[1]);
				}
				if($is_cp && isset($ret[$os_cp])){
					$ret[self::std_cp] += $ret[$os_cp];
				}
				foreach($std_ids as $std_id){
					$std_id = intval($std_id);
					if(!array_key_exists($std_id,$ret))
						$ret[$std_id] = 0;
				}
			}
			return $ret;
		}
	}

	/**
	 * ステータスを取得する
	 * 10001(cp)の場合、有料CPとの合算値となる
	 * @param array $list [[ユーザーID,論理ID], ...]形式の配列 
	 * @return array[uid => [stdid=>num, ... ], ...]
	 */
	public function getStatusMultiPlayer(array $list,$is_check=true){
		if(empty($list)) return [];

		$info = [];
		$ret = [];
		$os_cp = null;
		foreach($list as $line){
			if(count($line) != 2)continue;
			$uid = intval($line[0]);
			$std_id = intval($line[1]);
			if(!isset($info[$uid]))
				$info[$uid] = [];
			$info[$uid][] = $std_id;
			if(!isset($ret[$uid]))
				$ret[$uid] = [];
			if($std_id == self::std_cp){
				if($os_cp == null){
					$os_cp = self::std_android_cp;
					$header = getallheaders();
					if(isset($header['SGNOsType']) && intval($header['SGNOsType']) == 1)
						$os_cp = self::std_apple_cp;
				}
				$info[$uid][] = $os_cp;
			}
		}

		if($this->isTransactionActive()){
			$sql = null;
			$args = [];
			foreach($info as $uid => $std_ids){
				if($sql == null)
					$sql = 'select uid,std_id,num from box_player_status force index(PRIMARY) where (uid=? and std_id in(';
				else
					$sql .= ' or (uid=? and std_id in(';
				$args[] = $uid;
				$zero = true;
				foreach($std_ids as $std_id){
					if($is_check && !$this->check($std_id))
						\Dcs\Log::e("invalid value. $std_id",true);
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
			$sql .= ' for update';
			$rs = $this->sql('box_player_status',$sql)->selectAll($args,\PDO::FETCH_NUM);
			
			foreach($rs as $row){
				$ret[intval($row[0])][intval($row[1])] = intval($row[2]);
			}
		}else{
			$qus=[];
			foreach($info as $uid => $std_ids)foreach($std_ids as $std_id){
				if($is_check && !$this->check($std_id))
					\Dcs\Log::e("invalid value. $std_id",true);
				$qus[]=new Query(['='=>[$uid,$std_id]]);
			}
			if(!empty($qus)){
				$rss = $this->getHs()->selectMulti(
						new Table('box_player_status',['uid','std_id','num']),
						$qus
				);
				
				foreach($rss as $rs)foreach($rs as $row){
					$ret[intval($row[0])][intval($row[1])] = intval($row[2]);
				}
			}
		}
		foreach($info as $uid => $std_ids){
			if(!isset($ret[$uid]))
				$ret[$uid] = [];
			foreach($std_ids as $std_id){
				if(!isset($ret[$uid][$std_id]))
					$ret[$uid][$std_id] = 0;
			}
		}
		foreach($ret as $uid=>$list){
			if(isset($ret[$uid][self::std_cp]))
				$ret[$uid][self::std_cp] += $ret[$uid][$os_cp];
		}
		return $ret;
	}
	
	private static $FIELD = [
			'std_id','name','info'
	];
	/**
	 * 論理IDのユーザーステータスを取得する
	 * @param int $std_id
	 * @return NULL|array select * from player_status FETCH_ASSOC型の結果 intとか数値は正しい値に変換される
	 */
	public function getData($std_id){
		$std_id = intval($std_id);
		
		$cache = $this->cache();
		$key = 'Arpg.Logic.Util.PlayerStatus.getData.'.$std_id;
		$ret = $cache->get($cache::TYPE_APC,$key);
		if($ret == null){
			$rs = $this->getHs(false)->select(
				new Table('player_status',self::$FIELD),
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
	 * 論理IDのユーザーステータスを取得する
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
	 * アクターレベル情報を取得
	 * @param $lv int
	 * @return array ['exp'=>経験値,'stp'=>最大STP,'cost'=>装備コスト,'hp'=>HP]
	 */
	public function getLvData($lv){
		$lv = intval($lv);
		$rs = $this->selectHsCache(
				new Table('player_lv',array('lv','exp','stp','cost','hp')), 
				new Query(array('>' => 0),-1)
		);
		foreach($rs as $row){
			if($lv == intval($row[0]))
				return ['exp'=>intval($row[1]),'stp'=>intval($row[2]),'cost'=>intval($row[3]),'hp'=>intval($row[4])];
		}

		throw new ResError("dont exist player lv $lv",100);
	}
	
	const std_cp = 10001;
	const std_apple_cp = 10010;
	const std_android_cp = 10011;
}

?>