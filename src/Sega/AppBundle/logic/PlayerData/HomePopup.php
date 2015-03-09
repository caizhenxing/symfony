<?php
/**
 * 仮実装
 */
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\Time as Time;
use \Dcs\DetailTimeLog as DTL;

class HomePopup extends \Dcs\Arpg\Logic{
	const ACT_NO = 0;
	const ACT_JOIN = 1;
	const ACT_INVITE = 2;

	public $action;
	public $actorName;
	public $questName;
	public $userId;
	public $ticketId; // 出撃に使用するid
	public $roomId; // 出撃に使用するid
	public $dungeonStdId;
	public $reloadTime;
	public $last;
	public $bonus;
	
	/**
	 * ポップアップを送信する
	 * @param array $uids	受信する側のuid
	 * @param int $act		ACT_XXX
	 * @param int $src_uid	送信する側のuid
	 * @param int $dungeon_stdid ダンジョンSTDID
	 * @param int $ticket
	 * @param int $room
	 */
	public function send(array $uids, $act, $src_uid, $dungeon_stdid, $ticket, $room){
		if(empty($uids))
			return;
		for($i=0,$len=count($uids);$i<$len;++$i){
			$uids[$i] = intval($uids[$i]);
		}
		DTL::Lap('HomePopup send start');
		
		$act = intval($act);
		$src_uid = intval($src_uid);
		$dungeon_stdid = intval($dungeon_stdid);
		$ticket = intval($ticket);
		$room = intval($room);
		
		$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
		$actor_id = $Astatus->getActorId($src_uid);

		DTL::Lap('fetch actor id');
		
		$rs = $this->getHs()->select(
				new Table('box_actor',['name','spirit']),
				new Query(['='=>$actor_id])
		);

		DTL::Lap('fetch box_actor');
		
		if(empty($rs))return;
		$aname = $rs[0][0];
		$weapon = intval($rs[0][1]);
		$all_uid = array_merge($uids,[$src_uid]);
		$puids = $this->get('Arpg.Logic.Util.PlayerStatus')->getPublicIds($all_uid);
		$public_uid = $puids[$src_uid];
		$qname = $this->get('Arpg.Logic.Util.Quest')->getDungeonInfoByStdID($dungeon_stdid)[0]->title;

		DTL::Lap('fetch infos');
		
		$sql = null;
		$arg = [];
		$now = $this->getTime();
		foreach($uids as $uid){
			$puid = 0;
			if($uid != 0){
				if(!isset($puids[$uid]) || $puids[$uid] < 1)continue;
				$puid = $puids[$uid];
			}
			$qus[] = [
					$puid,
					$now,
					$act,
					$aname,
					$weapon,
					$qname,
					$public_uid,
					$ticket,
					$room,
					$dungeon_stdid,
			];
		}
		$this->getHs()->insertMulti(new Table('box_home_popup',['uid','time','action','actor','actor_weapon','quest','public_uid','ticket','room','dungeon']),$qus);

		DTL::Lap('insert popup data size:'.count($qus));
		
		DTL::Lap('HomePopup send end');
	}

	
	/**
	 * 受信
	 * @param int $uid
	 * @param int $last 最後に取得したPopupID
	 * @return Logic\PlayerData\HomePopup
	 */
	public function receive($puid, $last){
		DTL::Lap('HomePopup receive start');
		
		$puid = intval($puid);

		DTL::Lap('fetch box_player_status');
		if(!is_numeric($last))
			$last = 0;
		
		$rs = $this->select($puid,['id','action','actor','quest','public_uid','ticket','room','dungeon']);

		DTL::Lap('select popup');
		$dat = null;
		$lact = self::ACT_NO;
		foreach($rs as $row){
			$id = intval($row[0]);
			if($id <= $last) continue;
			$act = intval($row[1]);
			if($act == self::ACT_NO) continue;
			
			if($act == self::ACT_INVITE){
				$dat = $row;
				$last = $id;
				$lact = $act;
			}elseif($lact != self::ACT_INVITE){
				$dat = $row;
				$last = $id;
				$lact = $act;
			}
		}
		
		$ret = $this->get('Arpg.Logic.PlayerData.HomePopup');
		$ret->reloadTime = $this->get('Arpg.Logic.Util.DevParam')->param(52);
		$ret->last = $last;
		if($dat == null){
			$ret->action = 0;
			return $ret;
		}
		
		$ret->action = intval($dat[1]);
		$ret->actorName = $dat[2];
		$ret->questName = $dat[3];
		$ret->userId = intval($dat[4]);
		$ret->ticketId = intval($dat[5]);
		$ret->roomId = intval($dat[6]);
		$ret->dungeonStdId = intval($dat[7]);
		
		// ext 追加
		$bonus = $this->get('Arpg.Logic.DungeonData.Bonus');
		$bonus->init($ret->ticketId);
		$ret->bonus = $bonus;
		return $ret;
	}
	/**
	 * box_home_popup の該当時間内をHSで取得する
	 * @param int $uid ユーザーID
	 * @param array $fld フィールド配列
	 * @return array [[フィールド順データ ] ...]
	 */
	public function select($puid,$fld){
		$time = $this->get('Arpg.Logic.Util.DevParam')->param(53);
		$len = intval($time / 10);
		
		$now = $this->getTime();
		$qus = [];
		for($i=0;$i<$len;++$i){
			$qus[] = new Query(['='=>[$puid,($now-$i)]],-1);
		}
		$rss = $this->getHs()->selectMulti(
				new Table('box_home_popup',$fld,'UIDTIME'),
				$qus
		);
		$ret = [];
		foreach($rss as $rs)foreach($rs as $row){
			$ret[] = $row;
		}
		return $ret;
	}
	
	public function convertTime(Time $time){
		return intval($time->get()/10);
	}
	private function getTime(){
		return $this->convertTime(new Time());
	}
	
	const std_last_get = 1003;
}

?>