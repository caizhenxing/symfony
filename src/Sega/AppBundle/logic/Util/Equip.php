<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\CardData as CardData;
use \Logic\ItemData as ItemData;
use \Dcs\Arpg\ResError as ResError;

class Equip extends \Dcs\Arpg\Logic{
	const MAX_SUPPLIES = 3;
	const TYPE_NONE = 0;
	const TYPE_WEAPON = 1;
	const TYPE_HEADGEAR = 2;
	const TYPE_COSTUME = 3;
	const TYPE_AMULET = 4;
	const TYPE_RING = 5;
	const TYPE_ETC = 6;
	
	
	const WEAPON_NONE = 0;
	const WEAPON_SWORD = 1;
	const WEAPON_HAMMER = 2;
	const WEAPON_ROD = 3;
	
	const STATE_HAS = 0;
	const STATE_DEL = 1;
	const STATE_PRESENT = 2;
	
	/**
	 * Equipかチェック
	 * @param int $std_id 論理ID
	 */
	public static function check($std_id){
		$sid = intval($std_id);
		return 300000 <= $sid && $sid < 400000;
	}
	/**
	 * 装備部位番号に変換
	 * @param int $std_id 論路ID
	 * @return int 装備部位番号
	 */
	public static function std2type($std_id){
		$std_id = intval($std_id);
		if(300000 <= $std_id && $std_id < 350000)
			return self::TYPE_WEAPON;
		if(350000 <= $std_id && $std_id < 355000)
			return self::TYPE_ETC;
		if(360000 <= $std_id && $std_id < 362000)
			return self::TYPE_HEADGEAR;
		if(370000 <= $std_id && $std_id < 372000)
			return self::TYPE_COSTUME;
		if(380000 <= $std_id && $std_id < 385000)
			return self::TYPE_AMULET;
		if(390000 <= $std_id && $std_id < 395000)
			return self::TYPE_RING;
		return self::TYPE_NONE;
	}
	/**
	 * 武器の種別番号に変換
	 * @param int $std_id 論路ID
	 * @return int 武器種別番号
	 */
	public static function std2wtype($std_id){
		$std_id = intval($std_id);
		if(300000 <= $std_id && $std_id < 302000)
			return self::WEAPON_SWORD;
		if(310000 <= $std_id && $std_id < 312000)
			return self::WEAPON_HAMMER;
		if(320000 <= $std_id && $std_id < 322000)
			return self::WEAPON_ROD;
		return self::WEAPON_NONE;
	}
	/**
	 * カード倉庫の空き容量を取得する
	 */
	public function freeSpace($uid){
		$box_size = 0;
		if($this->isTransactionActive()){
			$rs = $this->sql('box_equip','select count(id) from box_equip where uid=? and state=0')->selectAll([$uid],\PDO::FETCH_NUM);
			if(!empty($rs)){
				$box_size = intval($rs[0][0]);
			}
		}else{
			$box_size = count($this->getHs()->select(
					new Table('box_equip',['id'],'UID'),
					new Query(['='=>[$uid,0]],-1)
			));
		}
		$box_size = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatus($uid,self::std_card_box_max) - $box_size;
		return $box_size;
	}
	/**
	 * いろいろ選別する
	 * @param string $skill equip_dataのskill1またはskill2またはaddonの文字列
	 */
	private function drawRate($str){
		$buff = explode(',',$str);
		$total=0;
		$rate=[];
		foreach($buff as $elem){
			$elem = explode(':',$elem);
			if(count($elem) != 2)continue;
			if($elem[1]+0 <= 0) continue;
			$total+=$elem[1]+0;
			$rate[]=[intval($elem[0]),$total];
		}
		if(empty($rate) || $total <= 0)return 0;
		$max=1000000;
		$per = mt_rand(0,$max)/$max * $total;
		$len=count($rate);
		for($i=0;$i<$len;++$i){
			$elem = $rate[$i];
			if($per <= $elem[1])
				return $elem[0];
		}
		return $rate[$len-1][0];
	}
	/**
	 * アイテムを追加する
	 * @param int $uid
	 * @param int $std_id
	 * @param int $state STATE_***系の定数 デフォ値:STATE_HAS
	 * @return int ユニークな装備品ID
	 */
	public function add($uid, $std_id, $state=0){
		if($this->stmt_add == null)
			$this->stmt_add = $this->sql('box_equip','insert into box_equip (uid,std_id,level,exp,skill,addon,state,create_date,update_date) values(?,?,1,0,?,?,?,?,?)');
		
		$rs = $this->getData($std_id);
		
		if($rs == null){
			throw new \Exception('Stdid '.$std_id.' is not exist');
		}
		
		$skill = [];
		$id = $this->drawRate($rs['skill1']);
		if($id > 0)
			$skill[] = $id;
		$id = $this->drawRate($rs['skill2']);
		if($id > 0)
			$skill[] = $id;

		$addon = [];
		$id = $this->drawRate($rs['addon']);
		if($id > 0)
			$addon[] = $id;
		$now = new \Dcs\Arpg\Time();
		$now_sql = $now->getMySQLDateTime();
		$this->useTransaction();
		$ret = intval($this->stmt_add->insert([$uid,$std_id,implode(',',$skill),implode(',',$addon),intval($state),$now_sql,$now_sql]));
		return $ret;
	}
	private $stmt_add = null;
	
	/**
	 * アイテムを追加する
	 * @param int $uid
	 * @param array $params [[論理ID,STATE_***系の定数 デフォ値:STATE_HAS], ...]
	 * @return array [ユニークな装備品ID, ...]
	 */
	public function addMulti($uid, $params){
		if(empty($params)){
			return [];
		}
		$qus = null;
		$std_ids = [];
		foreach($params as $param){
			if($qus == null){
				$qus = 'insert into box_equip (uid,std_id,level,exp,skill,addon,state,create_date,update_date) values(?,?,1,0,?,?,?,?,?)';
			}else{
				$qus .= ',(?,?,1,0,?,?,?,?,?)';
			}
			$std_ids[] = intval($param[0]);
		}
		$rs = $this->getDatas($std_ids);
		$args = [];
		$now = new \Dcs\Arpg\Time();
		$now_sql = $now->getMySQLDateTime();
		for($i=0,$len=count($params);$i<$len;++$i){
			$param = $params[$i];
			$std_id = intval($param[0]);
			$row = $rs[$std_id];
			$state = self::STATE_HAS;
			if(isset($param[1])){
				$state = intval($param[1]);
			}
	
			$skill = [];
			$id = $this->drawRate($row['skill1']);
			if($id > 0)
				$skill[] = $id;
			$id = $this->drawRate($row['skill2']);
			if($id > 0)
				$skill[] = $id;
			
			$addon = [];
			$id = $this->drawRate($row['addon']);
			if($id > 0)
				$addon[] = $id;
			
			$args[]=$uid;
			$args[]=$std_id;
			$args[]=implode(',',$skill);
			$args[]=implode(',',$addon);
			$args[]=$state;
			$args[]=$now_sql;
			$args[]=$now_sql;
		}

		$this->useTransaction();
		$stmt = $this->sql('box_equip',$qus);
		$iid = intval($stmt->insert($args));
		
		$psize = count($params);
		if($psize != $stmt->rowCount())
			throw new \Exception('cant all insert.');
		
		$ret = [];
		for($i=0;$i<$psize;++$i){
			$ret[] = $iid+$i;
		}
		return $ret;
	}
	
	/**
	 * アイテムを削除する
	 * @param int $uid
	 * @param int $box_equip_id
	 * @return bool 成否
	 */
	public function del($uid, $box_equip_id){
		$this->useTransaction();
		if($this->stmt_del == null)
			$this->stmt_del = $this->sql('box_equip','update box_equip set state=1,update_date=? where uid=? and id=? and state=0');
		$now = new \Dcs\Arpg\Time();
		$now_sql = $now->getMySQLDateTime();
		$count = $this->stmt_del->update([$now_sql,$uid,$box_equip_id]);
		if($count > 0){
			return true;
		}
		\Dcs\Log::e("装備品を削除できません uid:$uid box_equip_id:$box_equip_id");
		return false;
	}
	private $stmt_del = null;
	
	/**
	 * アイテムsを削除する
	 * @param int $uid
	 * @param array $box_equip_ids
	 * @return bool true 全削除OK false 削除できないアイテムがあった
	 */
	public function delMulti($uid, $box_equip_ids){
		return $this->changeEquipStates($uid,self::STATE_HAS,self::STATE_DEL,$box_equip_ids);
	}
	
	/**
	 * 装備する
	 * @param int $uid
	 * @param int $set_id 装備するセットID
	 * @param array $set_list 以下の形式の変更するセット
	 * [['sid'=>セット番号,'eq'=>array 装備品の倉庫内ID, 'it'=>array 装備する消耗品ID], ... ]
	 */
	public function equip($uid, $set_id, $set_list=[]){
		$actor_id = $this->get('Arpg.Logic.Util.ActorStatus')->getActorId($uid);
		$this->equipByActorID($uid,$actor_id, $set_id, $set_list);
	}
	public function equipByActorID($uid, $actor_id, $set_id, $set_list=[]){
		if(!is_array($set_list))
			$set_list = [];
		$uid = intval($uid);
		$actor_id = intval($actor_id);
		$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
		$as = $Astatus->getStatusMulti($actor_id, [self::std_sex,self::std_eset_w+$set_id*10]);
		$equip_wep_id = $as[self::std_eset_w+$set_id*10];
		$sex = $as[self::std_sex];
		$i2s = [];
		// 倉庫内ID->StdID紐付リスト生成
		if($this->isTransactionActive()){
			// SQLモード
			$size=[];
			$sql = null;
			$arg=[$uid];
			if($equip_wep_id > 0){
				$sql = 'select id,std_id from box_equip where uid=? and state=0 and id in(?';
				$arg[] = $equip_wep_id;
				$size[$equip_wep_id] = true;
			}
			foreach($set_list as $set)foreach($set['eq'] as $i){
				$i = intval($i);
				if($i == 0 || isset($size[$i])) continue;
				if($sql == null)
					$sql = 'select id,std_id from box_equip where uid=? and state=0 and id in(?';
				else
					$sql .= ',?';
				$arg[]=$i;
				$size[$i] = true;
			}
			$size = count($size);
			$sql .= ')';
			$rs = $this->sql('box_equip',$sql)->selectAll($arg,\PDO::FETCH_NUM);
			if(count($rs) != $size)
				throw new \Exception('box_equip dont have item .'.json_encode($set_list));
			foreach($rs as $row){
				$i2s[intval($row[0])] = intval($row[1]);
			}
		}else{
			//HSモード
			$qus=[];
			$lock=[];
			if($equip_wep_id > 0){
				$qus[] = new Query(['='=>[$equip_wep_id,$uid,0]]);
				$lock[$equip_wep_id] = true;
			}
			foreach($set_list as $set)foreach($set['eq'] as $i){
				$i = intval($i);
				$uniq_ids[] = $i;
				if($i == 0 || isset($lock[$i])) continue;
				$qus[]=new Query(['='=>[$i,$uid,0]]);
				$lock[$i] = true;
			}
			$lock = null;
			$rss = $this->getHs()->selectMulti(
					new Table('box_equip',['id','std_id'],'IUS'),
					$qus
			);
			$i2s=[];
			foreach($rss as $rs){
				if(empty($rs))
					throw new \Exception('box_equip dont have item hs.'.json_encode($set_list));
				foreach($rs as $row){
					$i2s[intval($row[0])] = intval($row[1]);
				}
			}
		}
		$updater = [[$actor_id,self::std_eset,$set_id]];
		$equip_wep_std_id=0;
		// 装備アップデータ設定
		foreach($set_list as $set){
			$sid = intval($set['sid']);
			$sub = $sid*10;
			
			// 先にはずす
			$upd = [
				self::TYPE_WEAPON 	=> [$actor_id,self::std_eset_w+$sub,0],
				self::TYPE_HEADGEAR => [$actor_id,self::std_eset_h+$sub,0],
				self::TYPE_COSTUME	=> [$actor_id,self::std_eset_c+$sub,0],
				self::TYPE_RING 	=> [$actor_id,self::std_eset_r+$sub,0],
				self::TYPE_AMULET 	=> [$actor_id,self::std_eset_n+$sub,0],
			];
			
			foreach($set['eq'] as $i){
				if($i == 0) continue;
				$std_id = $i2s[$i];
				$gen = $this->getData($std_id)['gender'];
				if($gen != 2 && $gen+1 != $sex)
					continue;
				$type = self::std2type($std_id);
				switch($type){
					case self::TYPE_WEAPON:
						if($set_id == $sid)
							$equip_wep_std_id = $i2s[$i];
						$upd[self::TYPE_WEAPON] = [$actor_id,self::std_eset_w+$sub,intval($i)];
						break;
					case self::TYPE_HEADGEAR:
						$upd[self::TYPE_HEADGEAR] = [$actor_id,self::std_eset_h+$sub,intval($i)];
						break;
					case self::TYPE_COSTUME:
						$upd[self::TYPE_COSTUME] = [$actor_id,self::std_eset_c+$sub,intval($i)];
						break;
					case self::TYPE_RING:
						$upd[self::TYPE_RING] = [$actor_id,self::std_eset_r+$sub,intval($i)];
						break;
					case self::TYPE_AMULET:
						$upd[self::TYPE_AMULET] = [$actor_id,self::std_eset_n+$sub,intval($i)];
						break;
					default:{
						throw new ResError('item is not equip item1. std_id: '.$row[1],100);
						break;
					}
				}
			}
			foreach($upd as $line){
				$updater[] = $line;
			}
		}
		if($equip_wep_std_id == null)
			$equip_wep_std_id = $i2s[$equip_wep_id];
		
		// 装備消耗品
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		foreach($set_list as $set){
			$sub = intval($set['sid'])*10;
			for($i=0;$i<self::MAX_SUPPLIES;++$i){
				$std_id = isset($set['it'][$i])?intval($set['it'][$i]):0;
				$type = $Stack->std2type($std_id);
				if(($type != StackItem::TYPE_DRAG && $type != StackItem::TYPE_MDRAG))
					$std_id = 0;
				if($std_id > 0)
					$updater[] = [$actor_id,self::std_sup_counter,1];
				$updater[] = [$actor_id,self::std_eset_i+$sub+$i,$std_id];
			}
		}
		
		$Astatus->setMulti($updater);
		
		// アクターデータ側にも更新をかける
		if($equip_wep_std_id != null){
			$info = $this->getData($equip_wep_std_id);
			foreach($rs as $row){
				$sql = 'update box_actor set spirit=?,job=? where actor_id=?';
				$this->sql('box_actor',$sql)->update([$equip_wep_std_id,$info['job'],$actor_id]);
				break;
			}
		}
	}
	/**
	 * 装備品の基礎情報sを取得する
	 * ないアイテムがあってもエラーを出さないので注意
	 * @param array $box_equip_ids 装備品のID配列
	 * @return array DB:equip_dataの内容をFETCH_NUMで取得した結果の配列を[box_equip_id=>object , ...]の形で取得
	 */
	public function getEquipBaseInfos($box_equip_ids){
		if(empty($box_equip_ids)) return [];
		$idp = null;
		if($this->isTransactionActive()){
			$in = null;
			$args=[];
			foreach($box_equip_ids as $id){
				if($in == null)
					$in = '?';
				else
					$in .= ',?';
				$args[]=$id;
			}
			$idp = $this->sql('box_equip',"select id,std_id from box_equip where id in ($in) and state = 0")->selectAll($args,\PDO::FETCH_NUM);
		}else{
			$qus=[];
			foreach($box_equip_ids as $id){
				$qus[]=new Query(['='=>$id]);
			}
			$rss = $this->getHs()->selectMulti(
					new Table('box_equip',['id','std_id','state']),
					$qus
			);
			$idp = [];
			foreach($rss as $rs)foreach($rs as $row){
				if(intval($row[2]) == 0)
					$idp[]=$row;
			}
		}
		if(empty($idp)) return [];
		$std_ids = [];
		
		foreach($idp as $p){
			$std_id = intval($p[1]);
			if(in_array($std_id,$std_ids)) continue;
			$std_ids[]=$std_id;
		}
		$datas = $this->getDatas($std_ids);
		
		$ret = [];
		foreach($idp as $p){
			$std_id = intval($p[1]);
			$id = intval($p[0]);
			if(array_key_exists($std_id,$datas)){
				$ret[$id] = $datas[$std_id];
			}else{
				$ret[$id] = null;
			}
		}
		return $ret;
	}
	
	/**
	 * ユーザーのカレントアクターの装備中情報を取得する
	 * @param array $uids 
	 * @param bool $idonly idデータのみ取得 default:false
	 * @return array[uid=>['card'=>[CardData1, ...], 'item'=>[ItemData1,ItemData2,ItemData3]], ... ]
	 * 		idonly時 array[uid=>['card'=>[CardUniqueID1, ...], 'item'=>[ItemStdID1,ItemStdID2,ItemStdID3]], ... ]
	 */
	public function getEquipedByUsers($uids,$idonly=false){
		$aids = $this->get('Arpg.Logic.Util.ActorStatus')->getActorIdMulti($uids);
		$buf = $this->getEquipedByActors($aids,$idonly);
		$ret=[];
		foreach($aids as $uid => $aid){
			$ret[$uid] = $buf[$aid];
		}
		return $ret;
	}
	/**
	 * アクターの装備中情報を取得する
	 * @param array $aids 
	 * @param bool $idonly idデータのみ取得 default:false
	 * @return array[aid=>['card'=>[CardData1, ...], 'item'=>[ItemData1,ItemData2,ItemData3]], ... ]
	 * 		idonly時 array[aid=>['card'=>[CardUniqueID1, ...], 'item'=>[ItemStdID1,ItemStdID2,ItemStdID3]], ... ]
	 */
	public function getEquipedByActors($aids,$idonly=false){
		$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
		$uids = $Astatus->getUserIdMulti($aids);
		$aids=[];
		foreach($uids as $aid => $uid){
			$aids[$uid] = $aid;
		}
		$std_ids = [];
		$ret = [];
		foreach($aids as $aid){
			$aid = intval($aid);
			$ret[$aid] = ['card'=>[],'item'=>[]];
			for($i=0;$i<self::MAX_SUPPLIES;++$i){
				$ret[$aid]['item'][] = null;
			}
			$std_ids[]=[$aid,self::std_eset];
			for($i=0;$i<9;++$i){
				$std_ids[] = [$aid, self::std_eset_w + $i*10];
				$std_ids[] = [$aid, self::std_eset_h + $i*10];
				$std_ids[] = [$aid, self::std_eset_c + $i*10];
				$std_ids[] = [$aid, self::std_eset_n + $i*10];
				$std_ids[] = [$aid, self::std_eset_r + $i*10];

				for($j=0;$j<self::MAX_SUPPLIES;++$j){
					$std_ids[] = [$aid, self::std_eset_i + $i*10 + $j];
				}
			}
		}
		$ass = $Astatus->getStatusMultiActor($std_ids);
		$eq_id=[];
		$cards=[];
		$items=[];
		$sup_id=[];
		foreach($ass as $aid => $as){
			$uid = $uids[$aid];
			$sid = $as[self::std_eset];
			$eq_id[$as[self::std_eset_w+$sid*10]] = $aid;
			$eq_id[$as[self::std_eset_h+$sid*10]] = $aid;
			$eq_id[$as[self::std_eset_c+$sid*10]] = $aid;
			$eq_id[$as[self::std_eset_r+$sid*10]] = $aid;
			$eq_id[$as[self::std_eset_n+$sid*10]] = $aid;
			$cards[]=$as[self::std_eset_w+$sid*10];
			$cards[]=$as[self::std_eset_h+$sid*10];
			$cards[]=$as[self::std_eset_c+$sid*10];
			$cards[]=$as[self::std_eset_n+$sid*10];
			$cards[]=$as[self::std_eset_r+$sid*10];
			$items[]=[$uid, $as[self::std_eset_i+$sid*10+0]];
			$items[]=[$uid, $as[self::std_eset_i+$sid*10+1]];
			$items[]=[$uid, $as[self::std_eset_i+$sid*10+2]];
			$sup_id[$aid]=[];
			for($i=0;$i<self::MAX_SUPPLIES;++$i){
				$sup_id[$aid][] = $as[self::std_eset_i+$sid*10+$i];
			}
		}
		if($this->isTransactionActive()){
			$sql = null;
			$arg = [];
			foreach($cards as $id){
				if($sql == null){
					if($idonly)
						$sql = 'select id from box_equip force index(`IS`) where state=0 and id in(?';
					else
						$sql = CardData::DBSQLCORE.' force index(`IS`) where state=0 and id in(?';
				}else
					$sql .= ',?';
				$arg[] = $id;
			}
			$sql .= ')';
			$stmt = $this->sql('box_equip',$sql);
			$stmt->select($arg);
			while($row = $stmt->fetch(\PDO::FETCH_NUM)){
				$c = $row;
				$cid = 0;
				if($idonly){
					$c = $cid = intval($row[0]);
				}else{
					$c = $this->get('Arpg.Logic.CardData');
					$c->init($row);
					$cid = $c->id;
				}
				$aid = $eq_id[$cid];
				if(!isset($ret[$aid])) continue;
				$ret[$aid]['card'][] = $c;
			}
			
			$sql = null;
			$arg = [];
			foreach($items as $elem){
				$uid = $elem[0];
				$std_id = $elem[1];
				if($sql == null)
					$sql = ItemData::DBSQLBASE.' (uid=? and std_id=?) ';
				else 
					$sql .= 'or (uid=? and std_id=?) ';
				$arg[] = $uid;
				$arg[] = $std_id;
			}
			$stmt = $this->sql(ItemData::DBHS_TBL,$sql);
			$stmt->select($arg);
			while($row = $stmt->fetch(\PDO::FETCH_NUM)){
				$i = $this->get('Arpg.Logic.ItemData');
				$i->init($row);
				$aid = $aids[$i->uid()];
				if(isset($ret[$aid]) && isset($sup_id[$aid])){
					for($j=0;$j<self::MAX_SUPPLIES;++$j){
						if($sup_id[$aid][$j] == $i->stdId){
							if($idonly){
								$ret[$aid]['item'][$j] = $i->stdId;
							}else{
								$ret[$aid]['item'][$j] = $i;
							}
							$sup_id[$aid][$j] = -1;
						}
					}
				}
			}
		}else{
			$qus =[];
			foreach($cards as $id){
				$qus[] = new Query(['='=>[$id,0]]);
			}
			$rss = null;
			if($idonly){
				$rss = $this->getHs()->selectMulti(
						new Table('box_equip',['id'],'IS'),
						$qus
				);
			}else{
				$rss = $this->getHs()->selectMulti(
						new Table(CardData::DBTBL,CardData::$CLMS,CardData::IDX_IS),
						$qus
				);
			}
			foreach($rss as $rs)foreach($rs as $row){
				$c = $row;
				$cid = 0;
				if($idonly){
					$c = $cid = intval($row[0]);
				}else{
					$c = $this->get('Arpg.Logic.CardData');
					$c->init($row);
					$cid = $c->id;
				}
				$aid = $eq_id[$cid];
				if(!isset($ret[$aid])) continue;
				$ret[$aid]['card'][] = $c;
			}
			
			$qus =[];
			foreach($items as $elem){
				$qus[] = new Query(['='=>$elem]);
			}
			$rss = $this->getHs()->selectMulti(
					new Table(ItemData::DBHS_TBL,ItemData::$DBHS_FLD),
					$qus
			);
			$items = [];
			foreach($rss as $rs)foreach($rs as $row){
				$i = $this->get('Arpg.Logic.ItemData');
				$i->init($row);
				$aid = $aids[$i->uid()];
				if(isset($ret[$aid]) && isset($sup_id[$aid])){
					for($j=0;$j<self::MAX_SUPPLIES;++$j){
						if($sup_id[$aid][$j] == $i->stdId){
							if($idonly){
								$ret[$aid]['item'][$j] = $i->stdId;
							}else{
								$ret[$aid]['item'][$j] = $i;
							}
							$sup_id[$aid][$j] = -1;
						}
					}
				}
			}
		}
		
		return $ret;
	}
	
	/**
	 * 装備品のステータスを変更する
	 * @param int $uid
	 * @param int $from STATE_***のステート定数
	 * @param int $to STATE_***のステート定数
	 * @param array $box_ids [int equip_boxID]
	 */
	public function changeEquipStates($uid, $from, $to, $box_ids){
		if(empty($box_ids)) return true;
		$now = new \Dcs\Arpg\Time();
		$now_sql = $now->getMySQLDateTime();
		$bind = [intval($to),$now_sql,intval($uid),intval($from)];
		$in_sql = null;
		foreach($box_ids as $beid){
			if($in_sql == null){
				$in_sql = 'id in(?';
			}else{
				$in_sql .= ',?';
			}
			$bind[] =intval($beid);
		}
		if($in_sql != null){
			$in_sql .= ')';
			$this->useTransaction();
			if(count($box_ids) == $this->sql('box_equip',"update box_equip set state=?,update_date=? where uid=? and state=? and $in_sql")->update($bind)){
				return true;
			}
		}
		\Dcs\Log::e("cant change equips state uid:$uid box_equip_id:".json_encode($box_ids));
		return false;
	}
	
	private static $GROW_FUNC_KEY = ['lv','rate','exp','price'];
	
	public static $EQUIP_DATA = [
			'name','info','gender','atk','def',
			'matk','mdef','atk_speed','move_speed','fall',
			'job','critical','critical_val','shield','shield_val',
			'anim_common','anim_model','attribute','grow_type',
			'cost','train_exp','evo_max','addon',
			'skill1','skill2','techniq','teq_effect','rarity',
			'sell','model_m','texture_m','model_w','texture_w',
			'model_flag','cloth_bone',
			'evo0_maxlv','evo1_maxlv','evo2_maxlv','evo3_maxlv',
			'evo1_static_bonus_atk','evo1_static_bonus_def','evo1_static_bonus_matk','evo1_static_bonus_mdef',
			'evo2_static_bonus_atk','evo2_static_bonus_def','evo2_static_bonus_matk','evo2_static_bonus_mdef',
			'evo3_static_bonus_atk','evo3_static_bonus_def','evo3_static_bonus_matk','evo3_static_bonus_mdef',
			'cv','illustrator','series',
			'specialized_dun','specialized_rate', 'rare_spirit','get_text'
	];
	/**
	 * 論理IDの装備品データを取得する
	 * @param int $std_id
	 * @return NULL|array select * from equip_data FETCH_ASSOC型の結果 intとか数値は正しい値に変換される
	 */
	public function getData($std_id){
		$std_id = intval($std_id);
		
		$cache = $this->cache();
		$key = 'Arpg.Logic.Util.Equip.getData.'.$std_id;
		$ret = $cache->get($cache::TYPE_APC,$key);
		if($ret == null){
			$rs = $this->getHs(false)->select(
				new Table('equip_data',self::$EQUIP_DATA),
				new Query(['='=>$std_id])
			);
			if(empty($rs)) return null;
			$rs = $rs[0];
			$ret = [];
			
			for($i=0,$len=count(self::$EQUIP_DATA);$i<$len;++$i){
				$dat = $rs[$i];
				if(is_numeric($dat)){
					$it = intval($dat);
					$ft = $dat+0;
					if($it == $ft)
						$dat = $it;
					else
						$dat = $ft;
				}
				$ret[self::$EQUIP_DATA[$i]] = $dat;
			}
			$ret['std_id'] = $std_id;
			$cache->set($cache::TYPE_APC,$key,$ret);
		}
		return $ret;
	}
	/**
	 * 論理IDの装備品データを取得する
	 * @param array $std_ids
	 * @return array select * from equip_data FETCH_ASSOC型の結果を [std_id=>結果, ...] の形で返す
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
	 * 装備品の最大経験値を取得する
	 * @param int $grow_type
	 * @param int $lv
	 * @return number
	 */
	public function getMaxExp($grow_type,$lv){
		$rs = $this->selectHsCache(
				new Table('equip_grow_func',self::$GROW_FUNC_KEY),
				new Query(['=' => $grow_type],-1)
		);
		foreach($rs as $row){
			if(intval($row[0]) == $lv)
				return intval($row[2]);
		}
		return 0;
	}
	
	/**
	 * 装備品の成長度を取得
	 * @param int $grow_type
	 * @param int $lv
	 * @return float
	 */
	public function getGrowRate($grow_type,$lv){
		$rs = $this->selectHsCache(
				new Table('equip_grow_func',self::$GROW_FUNC_KEY),
				new Query(['=' => $grow_type],-1)
		);

		foreach($rs as $row){
			if(intval($row[0]) == $lv)
				return $row[1]+0.0;
		}
		return 1;
	}
	/**
	 * 装備品の強化コストを取得
	 * @param int $grow_type
	 * @param int $lv
	 * @return int
	 */
	public function getTrainCost($grow_type, $lv){
		$rs = $this->selectHsCache(
				new Table('equip_grow_func',self::$GROW_FUNC_KEY),
				new Query(['=' => $grow_type],-1)
		);

		foreach($rs as $row){
			if(intval($row[0]) == $lv)
				return intval($row[3]);
		}
		return 0;
	}
	
	/**
	 * 装備品の進化レシピを取得する
	 * @param int $std_id 装備論理ID
	 * @param int $evo	現在の進化段階
	 * @return \Logic\GameData\RecipeData
	 */
	public function getRecipe($std_id,$evo){
		$key = "Arpg.Logic.Util.Equip.getRecipe.$std_id.$evo";
		$ret = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($ret != null)
			return $ret;
		
		$keys = \Logic\GameData\RecipeData::$DBKEY;
		$keys[] = 'evo';
		$rs = $this->getHs(false)->select(
				new Table('equip_material', $keys),
				new Query(['=' => $std_id],-1)
		);

		$ret = $this->get('Arpg.Logic.GameData.RecipeData');
		$evo_key = count($keys)-1;
		foreach($rs as $row){
			if(intval($row[$evo_key]) == $evo){
				$ret->initHs($row);
				break;
			}
		}
		$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$ret);
		return $ret;
	}
	
	// STDID
	const std_card_box_max = 7;
	const std_sup_counter = 50040;
	const std_sex = 50023;
	const std_eset = 50050;
	const std_eset_w = 50051;
	const std_eset_h = 50052;
	const std_eset_c = 50053;
	const std_eset_n = 50054;
	const std_eset_r = 50055;
	const std_eset_i = 50056;
}

?>