<?php
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\Time as Time;
use \Dcs\Arpg\ResError as ResError;

class Mission extends \Dcs\Arpg\Logic{

	/**
	 * データを取得する
	 * @param int $std_id ミッションID
	 * @return PlayerData.Mission
	 */
	public function getData($std_id){
		$std_id = intval($std_id);
		$list = $this->getDataAll();
		foreach($list as $mis){
			if($mis->id == $std_id)
				return $mis;
		}
		return null;
	}
	/**
	 * 全データを取得する
	 * @return array
	 */
	public function getDataAll(){
		$rs = $this->selectHsCache(
				new Table(\Logic\PlayerData\Mission::HS_TBL,\Logic\PlayerData\Mission::$HS_FLD),
				new Query(['>'=>0],-1)
		);
		$list = [];
		foreach($rs as $row){
			$mis = $this->get('Arpg.Logic.PlayerData.Mission');
			$mis->init($row);
			$list[] = $mis;
		}
		return $list;
	}
	/**
	 * クリア済みクエスト一覧
	 * @param int $uid
	 * @return array<int>
	 */
	public function getCleared($uid){
		$ret = [0 => 0];
		$time = new Time();
		if($this->isTransactionActive()){
			$stmt = $this->sql('box_clear_mission','select id,last_clear from box_clear_mission where uid = ?');
			$stmt->select([$uid]);
			while($row=$stmt->fetch(\PDO::FETCH_NUM)){
				$ret[intval($row[0])] = $time->setMySQLDateTime($row[1])->get();
			}
		}else{
			$rs = $this->getHs()->select(
					new Table('box_clear_mission',['id','last_clear']),
					new Query(['='=>$uid],-1)
			);
			foreach($rs as $row){
				$ret[intval($row[0])] = $time->setMySQLDateTime($row[1])->get();
			}
		}
		return $ret;
	}
	/**
	 * ミッション完了した
	 * @param int $uid ユーザーID
	 * @param int $mid ミッションID
	 * @return PlayerData.Mission
	 */
	public function complete($uid, $mid){
		$cleared = $this->getCleared($uid);
		$mis = $this->getData($mid);
		$ulv = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatus($uid,1);
		if($mis == null || !$mis->enable($ulv,$cleared))
			throw new ResError('dont enable mission',4000);
		$this->init();
		$mis->add();
		$mis->exec($uid);
		if($mis->stat != $mis::STATE_CLEAR)
			throw new ResError('dont enable mission',4000);
		
		$this->useTransaction();
		$this->sql(
				'box_clear_mission',
				'insert into box_clear_mission (uid,id,last_clear) values (?,?,?) on duplicate key update last_clear = values(last_clear)'
		)->insert([$uid,$mid,(new Time())->getMySQLDateTime()]);
		return $mis;	
	}
	/**
	 * クリアした
	 * @param array<Logic\PlayerData\Mission> $mis クリアしたミッション一覧
	 */
	public function clear($uid,array $mis){
		$sql = null;
		$args = [];
		$time = new Time();
		foreach($mis as $m){
			if($sql == null){
				$sql = 'insert into box_clear_mission (uid,id,last_clear) values (?,?,?) ';
			}else
				$sql .= ',(?,?,?)';
			$args[] = $uid;
			$args[] = intval($m->id);
			$args[] = $time->getMySQLDateTime();
		}
		if($sql == null) return;
		$sql .= ' on duplicate key update last_clear = values(last_clear)';
		
		$this->useTransaction();
		$this->sql('box_clear_mission',$sql)->insert($args);
	}
	
	/**
	 * 
	 * @param int $uid
	 * @param array<int> $cleared クリア済みミッション一覧
	 * @return array<\Dcs\Arpg\PlayerData.Mission>
	 */
	public function getList($uid,$cleared){
		$this->init();

		$ulv = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatus($uid,1);

		$list = $this->getDataAll();
				
		$data=[];
		foreach($list as $mis){
			if(!$mis->enable($ulv,$cleared))
				continue;
			
			$mis->add();
			$data[]=$mis;
		}
		$ret=[];
		foreach($data as $dat){
			$dat->exec($uid);
			$ret[] = $dat;
		}
		return $ret;
	}
	
	/**
	 * 作業領域を削除する
	 */
	private function init(){
		self::$done = false;
		self::$mWork = [];
		self::$mPstate = [];
		self::$mAstate = [];
		self::$mQuest = [
			99900=>0,
			99901=>0,
			99902=>0,
			99903=>0,
			99904=>0,
		];
		self::$mFriends = 0;
		self::$mSlots = [];
	}
	/**
	 * 条件データを追加する
	 * @param int $id ミッションID
	 * @param int $type
	 * @param int $target
	 * @param int $slot リミットミッションスロット
	 * @param int $slot_time
	 */
	public function add($id, $type,$target,$slot,$slot_time){
		self::$done = false;
		if($type == self::type_friend){
			self::$mWork[$type] = true;
		}elseif($type == self::type_quest){
			if(!isset(self::$mWork[$type]))
				self::$mWork[$type] = [];
			self::$mWork[$type][$target] = true;
		}else{
			if(!isset(self::$mWork[$type]))
				self::$mWork[$type] = [];
			$has = false;
			if($type == self::type_tfactory)
				$target = $target + 2000000;
			
			foreach(self::$mWork[$type] as $tgt){
				if($tgt == $target){
					$has = true;
					break;
				}
			}
			if(!$has)
				self::$mWork[$type][] = $target;
		}
		if($slot > 0){
			self::$mSlots[$slot] = ['id'=>$id,'type'=>$type,'target'=>$target,'time'=>intval($slot_time)];
		}
	}
	
	public function exec($uid){
		if(self::$done) return;
		self::$done = true;
		// PlayerStatus 系
		$tgt=[];
		if(isset(self::$mWork[self::type_pstate]))
			$tgt = array_merge($tgt,self::$mWork[self::type_pstate]);
		if(isset(self::$mWork[self::type_tfactory]))
			$tgt = array_merge($tgt,self::$mWork[self::type_tfactory]);
		if(isset(self::$mWork[self::type_gacha]))
			$tgt = array_merge($tgt,self::$mWork[self::type_gacha]);
		if(isset(self::$mWork[self::type_titem]))
			$tgt = array_merge($tgt,self::$mWork[self::type_titem]);
		foreach(self::$mSlots as $slot => $info){
			$tgt[] = 2000+$slot;
			$tgt[] = 2050+$slot;
			$tgt[] = 2100+$slot;
		}
		self::$mPstate = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatusMulti($uid,$tgt,false);
		
		// ActorStatus系
		$tgt=[];
		if(isset(self::$mWork[self::type_astate]))
			$tgt = array_merge($tgt,self::$mWork[self::type_astate]);
		if(!empty($tgt)){
			$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
			self::$mAstate = $Astatus->getStatusMulti($Astatus->getActorId($uid),$tgt);
		}
		
		// クエストカテゴリ系
		if(isset(self::$mWork[self::type_quest]) || isset(self::$mWork[self::type_tquest])){
			$cleared = [];
			$rs=[];
			if($this->isTransactionActive()){
				$rs = $this->sql('box_quest','select world_id,area_id,dungeon_id,nb_clear from box_quest where uid = ? and nb_clear > 0')->selectAll([$uid],\PDO::FETCH_NUM);
			}else{
				$rs = $this->getHs()->select(
						new Table('box_quest',['world_id','area_id','dungeon_id','nb_clear']),
						new Query(['='=>$uid],-1)
				);
			}
			foreach($rs as $row){
				$num = intval($row[3]);
				if($num > 0){
					$sub_id = intval($row[0])*10000+intval($row[1])*100+intval($row[2]);
					if(!isset($cleared[$sub_id]))
						$cleared[$sub_id] = 0;
					$cleared[$sub_id] = $cleared[$sub_id]+$num;
					self::$mQuest[99900] = self::$mQuest[99900] + $num;
				}
			}
		

			$datas = $this->get('Arpg.Logic.Util.Quest')->getDungeonInfo();
			foreach($datas as $data){
				$sub_id=$data->world_id*10000+$data->area_id*100+$data->dungeon_id;
				if(!isset($cleared[$sub_id]))
					continue;
				$num = $cleared[$sub_id];
				// タイプ
				if($data->type == 1){
					self::$mQuest[99901]=self::$mQuest[99901]+$num;
				}elseif($data->type == 2){
					self::$mQuest[99902]=self::$mQuest[99902]+$num;
				}
				
				// マルチ？
				if($data->world_id == 99 || ($data->world_id == 98 && $data->party_mode == 2)){
					self::$mQuest[99904]=self::$mQuest[99904]+$num;
				}else{
					self::$mQuest[99903]=self::$mQuest[99903]+$num;
				}
				
				// 単独
				self::$mQuest[1000000+$sub_id] = $num;
			}
		}
		
		// フレンド
		if(isset(self::$mWork[self::type_friend])){
			self::$mFriends = count($this->get('gaia.friend.friend_management_service')->friendIds($uid));
		}
		
		
		// スロット初期化
		$updater=[];
		foreach(self::$mSlots as $slot => $info){
			$slot_id = self::$mPstate[2000+$slot];
			$slot_sub = self::$mPstate[2050+$slot];
			$slot_time = self::$mPstate[2100+$slot];
			if($info['id'] != $slot_id || $info['time'] != $slot_time){
				$slot_id = $info['id'];
				$slot_sub = $this->numInner($info['type'],$info['target']);
				$slot_time = $info['time'];
				$updater[]=[$uid,2000+$slot,$slot_id];
				$updater[]=[$uid,2050+$slot,$slot_sub];
				$updater[]=[$uid,2100+$slot,$slot_time];
			}
			self::$mSlots[$slot] = $slot_sub;
		}
		$this->get('Arpg.Logic.Util.PlayerStatus')->setMulti($updater,false);
	}
	public function num($type,$target,$slot){
		if($slot > 0){
			return $this->numInner($type,$target)-self::$mSlots[$slot];
		}else{
			return $this->numInner($type,$target);
		}
	}
	public function numInner($type,$target){
		if($type == self::type_tfactory)
			$target = $target + 2000000;
		
		switch($type){
			case self::type_pstate:
			case self::type_tfactory:
			case self::type_gacha:
			case self::type_titem:
				if(isset(self::$mPstate[$target]))
					return self::$mPstate[$target];
				break;
			case self::type_astate:
				if(isset(self::$mAstate[$target]))
					return self::$mAstate[$target];
				break;
			case self::type_quest:
			case self::type_tquest:
				if(isset(self::$mQuest[$target]))
					return self::$mQuest[$target];
				break;
			case self::type_friend:
				return self::$mFriends;
				break;
		}
		return 0;
	}
	
	static private $done = false;
	static private $mWork = [];
	static private $mPstate = [];
	static private $mAstate = [];
	static private $mQuest = [
			99900=>0,
			99901=>0,
			99902=>0,
			99903=>0,
			99904=>0,
		];
	static private $mFriends = 0;
	static private $mSlots=[];
	
	const type_pstate = 1;		// プレイヤーステータス T >= X
	const type_astate = 2;		// アクターステータス T >= X
	const type_quest = 3;		// TクエストをX回クリア T 99900:全クエスト 99901:メインクエスト 99902:サブクエスト 99903:シングル 99904:マルチ
	const type_tquest = 4;		// 特定クエストTをX回クリア
	const type_tfactory = 5;	// 特定アイテムTをX回生産
	const type_gacha = 6;		// ガチャTをX回引いた数
	const type_friend = 7;		// フレンドX人できた
	const type_titem = 8;		// アイテムTをX個入手
}
?>