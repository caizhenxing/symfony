<?php
namespace Sega\AppBundle\Controller;
/* ***********************************************************
 * ***********************************************************
 * ***********************************************************
 * フレンドに表示する名前は●●アクター名●●
 * 表示するレベルは●●アクターレベル●●
 *
 * ***********************************************************
 * ***********************************************************
 * ***********************************************************/

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;
use \Logic\Util\Equip as Equip;
use \Logic\Util\ActorStatus as ActorStatus;
use \Logic\CardData as CardData;
use \Logic\Util\GameParam as GameParam;
use \Dcs\Arpg\Time as Time;
use \Dcs\DetailTimeLog as DTL;

class FindTemp{
	public $uid = null;		// ユーザーID
	public $pid = null;		// パブリックユーザーID
	public $name = null;
	public $lv = null;
	public $mes = null;
	public $spirit = null;
	public $time = null;
	public $stat = null;
	public $aid = null;
}
class FriendController extends \Dcs\DcsController{
	const STAT_ACCEPT = 1;
	const STAT_REQUEST = 2;
	const STAT_FRIEND = 3;

	// OrderData.FRIEND_RESULT対応
	const RES_NONE = 0;
	const RES_OVER = 1;  // フレンド上限
	const RES_REQED = 2; // 申請済み
	const RES_ALRDY = 3; // すでにフレンド
	const RES_SUC = 4;	// 成功

	// PlayerData.FRIEND_SEARCH_TYPE
	const SEARCH_NAME=1;
	const SEARCH_LEVEL=2;
	const SEARCH_INAME=3;
	const SEARCH_RECOMMEND = 4;

	/**
	 * 友達一覧
	 * data:{
	 * 		skey: セッションキー
	 * }
	 * RPC構造
	 * data: array[PlayerData.Friend]
	 */
	public function getListAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);


			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			$fman = $this->get('gaia.friend.friend_management_service');
			$temp = [];

			$uids = [];
			$ids = $fman->friendIds($user->getUid());
			$oids = $this->get('gaia.friend.friend_offer_service')->offeredIds($user->getUid());
			$rids = $this->get('gaia.friend.friend_offer_service')->offeringIds($user->getUid());
			$fpids = $this->get('Arpg.Logic.Util.PlayerStatus')->getPublicIds(array_merge($ids,$oids,$rids));
			// フレンドリスト
			foreach($ids as $fuid){
				$pid = $fpids[$fuid];
				if(!$pid)continue;
				$t = new FindTemp();
				$t->uid = $fuid;
				$t->pid = $pid;
				$t->stat = self::STAT_FRIEND;
				$uids[] = $fuid;
				$temp[] = $t;
			}

			// フレンドになりたがっている人リスト
			foreach($oids as $fuid){
				$pid = $fpids[$fuid];
				if(!$pid)continue;
				$t = new FindTemp();
				$t->uid = $fuid;
				$t->pid = $pid;
				$t->stat = self::STAT_ACCEPT;
				$uids[] = $fuid;
				$temp[] = $t;
			}

			// フレンドになりたい人リスト
			foreach($rids as $fuid){
				$pid = $fpids[$fuid];
				if(!$pid)continue;
				$t = new FindTemp();
				$t->uid = $fuid;
				$t->pid = $pid;
				$t->stat = self::STAT_REQUEST;
				$uids[] = $fuid;
				$temp[] = $t;
			}

			return $this->getFriendList($uids,$temp);
		});
	}
	/**
	 * 友達一覧
	 * data:{
	 * 		skey: セッションキー
	 * }
	 * RPC構造
	 * data: array[PlayerData.Friend]
	 */
	public function getBuddiesAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);


			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			$fman = $this->get('gaia.friend.friend_management_service');
			$ids = $fman->friendIds($user->getUid());
			$temp = [];

			$uids = [];
			// フレンドリスト
			$fpids = $this->get('Arpg.Logic.Util.PlayerStatus')->getPublicIds($ids);
			foreach($ids as $fuid){
				$pid = $fpids[$fuid];
				if(!$pid)continue;
				$t = new FindTemp();
				$t->uid = $fuid;
				$t->pid = $pid;
				$t->stat = self::STAT_FRIEND;
				$temp[] = $t;
				$uids[] = $fuid;
			}

			return $this->getFriendList($uids,$temp);
		});
	}

	private function getFriendList($uids,$temp){
		if(empty($uids)){
			return [];
		}else{
			$aids = $this->get('Arpg.Logic.Util.ActorStatus')->getActorIdMulti($uids);

			foreach($temp as &$t){
				$t->aid = $aids[$t->uid];
			}
			unset($t);

			// アクター名を収集
			$qus=[];
			$aids = array();
			foreach($temp as $t){
				$qus[]=new Query(['='=>$t->aid]);
				$aids[] = $t->aid;
			}

			$rss = $this->getHs()->selectMulti(
					new Table('box_actor',['uid','name','spirit']),
					$qus
			);
// 			$table = "box_actor";
// 			$colmun = "`actor_id`,`uid`,`name`,`spirit`";
// 			$where = "actor_id IN (".implode(",",$aids).")";
// 			$sql = "select $colmun from $table where $where";
// 			$param = array();
// 			$stmt = $this->sql($table,$sql);
// 			$rs = $stmt->selectAll($param,\PDO::FETCH_NUM);
// 			$rss = array();
// 			foreach ($aids as $key => $value) {
// 				$data = array();
// 				foreach ($rs as $rs_value) {
// 					if($value == $rs_value[0]){
// 						$data[] = array($rs_value[1],$rs_value[2],$rs_value[3]);
// 						break;
// 					}
// 				}
// 				$rss[] = $data;
// 			}

			foreach($rss as $rs){
				if(empty($rs)) continue;
				$rs = $rs[0];
				if($rs == null || count($rs) < 3)continue;
				for($i=0,$len=count($temp);$i<$len;++$i){
					$t = $temp[$i];
					if($t->uid != intval($rs[0])) continue;
					$t->name = $rs[1];
					$t->spirit = intval($rs[2]);
					$temp[$i] = $t;
				}
			}

			// アクターレベルを収集

			$qus = [];
			foreach($temp as $t){
				$qus[] = new Query(['='=>[$t->uid,self::std_player_level]]);
				$qus[] = new Query(['='=>[$t->uid,self::std_last_action]]);
			}
			$rss = $this->getHs()->selectMulti(
					new Table('box_player_status',['uid','std_id','num']),
					$qus
			);
// 			$rss = array();
// 			$table = "box_player_status";
// 			$colmun = "`uid`,`std_id`,`num`";
// 			$where = "uid = ? and std_id = ?";
// 			$sql = "select $colmun from $table where $where";
// 			$stmt = $this->sql($table,$sql);
// 			foreach($temp as $t){
// 				$param1 = array($t->uid,self::std_player_level);
// 				$rss[] = $stmt->selectAll($param1,\PDO::FETCH_NUM);
// 				$param2 = array($t->uid,self::std_last_action);
// 				$rss[] = $stmt->selectAll($param2,\PDO::FETCH_NUM);
// 			}



			foreach($rss as $rs){
				if(empty($rs)) continue;
				$rs = $rs[0];
				if($rs == null || count($rs) < 3)continue;
				for($i=0,$len=count($temp);$i<$len;++$i){
					$t = $temp[$i];
					if($t->uid != intval($rs[0])) continue;
					switch(intval($rs[1])){
						case self::std_player_level:
							$t->lv = intval($rs[2]);
							break;
						case self::std_last_action:
							$t->time = intval($rs[2]);
							break;
						default:break;
					}
					$temp[$i] = $t;
				}
			}

			// プレイヤーメッセージを収集
			$qus = [];
			$uids = array();
			foreach($temp as $t){
				$qus[] = new Query(['='=>$t->uid]);
				$uids[] = $t->uid;
			}
			$rss = $this->getHs()->selectMulti(
					new Table('box_player',['uid','message']),
					$qus
			);

// 			$table = "box_player";
// 			$colmun = "`uid`,`message`";
// 			$where = "uid IN (".implode(",",$uids).")";
// 			$sql = "select $colmun from $table where $where";
// 			$param = array();
// 			$stmt = $this->sql($table,$sql);
// 			$rs = $stmt->selectAll($param,\PDO::FETCH_NUM);
// 			$rss = array();

// 			foreach ($uids as $value) {
// 				$data = array();
// 				foreach ($rs as $rs_value) {
// 					if($value == $rs_value[0]){
// 						$data[] = $rs_value;
// 						break;
// 					}
// 				}
// 				$rss[] = $data;
// 			}
			foreach($rss as $rs){
				if(empty($rs)) continue;
				$rs = $rs[0];
				if($rs == null || count($rs) < 2)continue;
				for($i=0,$len=count($temp);$i<$len;++$i){
					$t = $temp[$i];
					if($t->uid != intval($rs[0])) continue;
					$t->mes = $rs[1];
					$temp[$i] = $t;
				}
			}


			$data = [];
			foreach($temp as $ftmp){
				$data[] = [
				'id'=> $ftmp->pid,
				'actorId'=> $ftmp->aid,
				'name'=> $ftmp->name,
				'level'=> $ftmp->lv,
				'message'=> $ftmp->mes,
				'iconStdId'=> $ftmp->spirit,
				'stat'=> $ftmp->stat,
				'time'=> $ftmp->time
				];
			}
			return $data;
		}
	}

	/**
	 * ゴースト取得
	 * data: セッションキー
	 * RPC構造
	 * data: array[PlayerData.Friend]
	 */
	public function getGhostAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$max_num = 20;

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			$Gparam = $this->get('Arpg.Logic.Util.GameParam');
			$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
			$fman = $this->get('gaia.friend.friend_management_service');
			$user_aid = $Astatus->getActorId($user->getUid());
			$plv = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatus($user->getUid(),self::std_player_level);

			$uid = $user->getUid();
			// Friend のActorIDを収集
			$fids = $fman->friendIds($uid);
			$oids = $this->get('gaia.friend.friend_offer_service')->offeredIds($uid);
			$bids = $this->get('gaia.friend.friend_offer_service')->offeringIds($uid);

			$rs = $this->getHs()->select(
					new Table('box_ghost',['fuid','open_time']),
					new Query(['='=>$user->getUid()],-1)
			);
			$open=[];
			$time = new Time();
			foreach($rs as $row){
				$time->setMySQLDateTime($row[1]);
				$open[intval($row[0])] = $time->get();
			}
			$faids=$Astatus->getActorIdMulti(array_merge($fids,$oids,$bids));
			// 各Friendリストを連想配列化
			$swap = $fids;
			$fids=[];
			foreach($swap as $id){
				$fids[$id] = true;
			}
			$swap = $oids;
			$oids=[];
			foreach($swap as $id){
				$oids[$id] = true;
			}
			$swap = $bids;
			$bids=[];
			foreach($swap as $id){
				$bids[$id] = true;
			}
			// friendをAID化
			$finfo = [];
			$now = new Time();
			$now = $now->get();
			$qus=[];
			$dat = [];
			foreach($faids as $fuid => $faid){
				$op = isset($open[$fuid])?$open[$fuid]:0;
				$finfo[$faid] = [
					'uid' => $fuid,
					'open' => $op,
					'state' => isset($fids[$fuid])?3:(isset($oids[$fuid])?1:(isset($bids[$fuid])?2:0)),
				];
				if($op > $now) continue;
				$qus[] = new Query(['='=>[$fuid,self::std_player_level]]);
				$qus[] = new Query(['='=>[$fuid,self::std_last_action]]);
			}
			if(!empty($qus)){
				$rss = $this->getHs()->selectMulti(
						new Table('box_player_status',['uid','std_id','num']),
						$qus
				);
				foreach($rss as $rs)foreach($rs as $row){
					$aid = $faids[intval($row[0])];
					if(!isset($dat[$aid]))$dat[$aid]=[];
					if(!isset($dat[$aid]['time']))$dat[$aid]['time']=0;
					$std_id = intval($row[1]);
					switch($std_id){
						case self::std_player_level:
							$dat[$aid]['lv'] = intval($row[2]);
							break;
						case self::std_last_action:
							$dat[$aid]['time'] = intval($row[2]);
							break;
						default:
							break;
					}
					$dat[$aid]['is_f'] = isset($finfo[$aid])?($finfo[$aid]['state']==3):false;
					$dat[$aid]['aid'] = $aid;
				}
			}
			// 近しいユーザーのActorID
			$stmt = $this->sql('box_player_status','select box_player.current_actor,box_player.last_action,box_player_status.num from box_player_status left join box_player on  box_player_status.uid=box_player.uid where box_player_status.std_id = ? and box_player.current_actor <> 0 and box_player_status.num <= ? and box_player.uid <> ? order by box_player.last_action desc limit '.$max_num);
			$rs = $stmt->selectAll([self::std_player_level,$plv,$uid],\PDO::FETCH_NUM, \Dcs\SqlStatement::MODE_SLAVE);

			$time = new Time();
			foreach($rs as $row){
				$faid = intval($row[0]);
				if($faid == 0) continue;
				if(isset($finfo[$faid]) && $finfo[$faid]['state'] == 3) continue;
				$dat[$faid] =['lv'=>intval($row[2]),'time'=>$time->setMySQLDateTime($row[1])->get(),'is_f'=>(isset($finfo[$faid])?($finfo[$faid]['state']==3):false),'aid'=>$faid];
			}
			{// 上限切り
				$fc=0;
				$dat = $this->sortGhost($dat);

				$swap=[];
				for($i=0,$len=count($dat);$i<$len;++$i){
					$line = $dat[$i];
					if($line['is_f']){
						if($fc < 10)
							$swap[]=$line;
						++$fc;
					}else{
						$swap[]=$line;
					}
				}
				$swap = array_slice($swap,0,$max_num);
				$dat=[];
				foreach($swap as $line){
					$dat[$line['aid']]=$line;
				}
			}

			// アクター名を収集
			$qus=[];
			foreach($dat as $aid=>$dmy){
				$qus[]=new Query(['='=>$aid]);
			}

			$rss = $this->getHs()->selectMulti(
					new Table('box_actor',['actor_id', 'uid','name','spirit']),
					$qus
			);
			$fuids=[];
			foreach($rss as $rs){
				if(empty($rs)) continue;
				$fuids[] = intval($rs[0][1]);
			}
			$fpids=$this->get('Arpg.Logic.Util.PlayerStatus')->getPublicIds($fuids);

			foreach($rss as $rs){
				if(empty($rs)) continue;
				$rs = $rs[0];
				if($rs == null || count($rs) < 4)continue;
				$aid = intval($rs[0]);
				if(!isset($dat[$aid])) continue;

				$dat[$aid]+=[
						'pid' => $fpids[intval($rs[1])],
						'name' => $rs[2],
						'spirit' => intval($rs[3])
				];
			}

			$dat = $this->sortGhost($dat);
			$Text = $this->get('Arpg.Logic.Util.Text');
			$ftxt = $Text->getText(10100,['[point]'=>$Gparam->getParam($Gparam::GHOST_FRIEND_POINT)]);
			$btxt = $Text->getText(10101,['[point]'=>$Gparam->getParam($Gparam::GHOST_PLAYER_POINT)]);
			$ret = [];
			for($i=0,$len=count($dat);$i<$len;++$i){
				$line = $dat[$i];
				if(!isset($line['pid'])) continue;
				$aid = $line['aid'];
				$state = isset($finfo[$aid])?$finfo[$aid]['state']:0;
				$ret[] = [
					'id'=> $line['pid'],
					'actorId'=>$aid,
					'name'=> $line['name'],
					'level'=> $line['lv'],
					'message'=> ($state==3)?$ftxt:$btxt,
					'iconStdId'=> $line['spirit'],
					'stat'=> $state,
					'time'=> array_key_exists('time',$line)?$line['time']:0,
				];
			}
			return [
					'players' => $ret,
					'ghostPtMsg' => $btxt,
			];
		});
	}
	private function sortGhost($dat){
		$swap=[];
		foreach($dat as $line){
			$swap[] = $line;
		}
		usort($swap,function($a,$b){
			if($a['is_f'] && !$b['is_f']) return -1;
			if(!$a['is_f'] && $b['is_f']) return 1;
			if($a['time'] == $b['time']){
				if($a['lv'] == $b['lv']) return 0;
				return $a['lv'] > $b['lv']?-1:1;
			}
			return $a['time'] > $b['time']?-1:1;
		});
		return $swap;
	}

	/**
	 * 友達詳細
	 * data:{
	 * 		skey: セッションキー
	 * 		uid: int ユーザーID
	 * }
	 * RPC構造
	 * data: array[PlayerData.Detail]
	 */
	public function getDetailAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$pid = $data['uid'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			
			$uid = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserId($pid);

			$bplayer = $this->getHs()->select(
					new Table('box_player',['iname','login_date','message','current_actor']),
					new Query(['='=>$uid])
			);
			if(empty($bplayer))
				throw new ResError('dont find friend. friend public id is $pid',100);

			$bplayer = $bplayer[0];
			$pstatus = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatusMulti(
					$uid,
					[
							self::std_player_level,
							self::std_last_action
					]
			);
			$aid = intval($bplayer[3]);

			$bactor = $this->getHs()->select(
					new Table('box_actor',['name','spirit']),
					new Query(['='=>$aid])
			);
			if(empty($bactor))
				throw new ResError('dont find actor. actor id is $aid',100);

			$bactor = $bactor[0];

			$plv = $pstatus[self::std_player_level];

			$plvdata = $this->get('Arpg.Logic.Util.PlayerStatus')->getLvData($plv);

			$equiped = $this->get('Arpg.Logic.Util.Equip')->getEquipedByActors([$aid])[$aid];

			$atk=$matk=$def=$mdef=$cost=0;
			foreach($equiped['card'] as $buf){
				$atk += $buf->phisicalAttack;
				$matk += $buf->magicalAttack;
				$def += $buf->phisicalDefence;
				$mdef += $buf->magicalDefence;
				$cost += $buf->cost;
			}
			$friend = $this->get('gaia.friend.friend_management_service')->friendIds($user->getUid());
			$accept = $this->get('gaia.friend.friend_offer_service')->offeredIds($user->getUid());
			$request = $this->get('gaia.friend.friend_offer_service')->offeringIds($user->getUid());
			$stat=0;
			if(in_array($uid,$accept))
				$stat = 1;
			elseif(in_array($uid,$request))
				$stat = 2;
			elseif(in_array($uid,$friend))
				$stat = 3;

			$time = new Time();

			return [
				'id'=> $pid,
				'level'=> $plv,
				'message'=> $bplayer[2],
				'searchId'=>$bplayer[0],
				'lastLoginTime'=> $time->setMySQLDateTime($bplayer[1])->get(),
				'actorName'=> $bactor[0],
				'iconStdId'=> $bactor[1],
				'baseHp'=>$plvdata['hp'],
				'actorCost'=>$cost,
				'costMax'=>$plvdata['cost'],
				'actorPhysicAtk'=>$atk,
				'actorMagicAtk'=>$matk,
				'actorPhysicDef'=>$def,
				'actorMagicDef'=>$mdef,
				'actorStat'=> $stat,
			];
		});
	}



	/**
	 * 友達申請
	 * data:{
	 * 		skey: セッションキー
	 * 		ids: array ユーザーID
	 * }
	 * RPC構造
	 * data: array[int]
	 */
	public function tryRequestAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('tryRequestAction start');
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$ids = $data['ids'];


			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV1);
			$uid = $user->getUid();

			$friend = $this->get('gaia.friend.friend_management_service')->friendIds($user->getUid());
			$limit = $this->get('gaia.friend.friend_management_service')->getFriendLimit($uid);
			$accept = $this->get('gaia.friend.friend_offer_service')->offeredIds($user->getUid());
			$request = $this->get('gaia.friend.friend_offer_service')->offeringIds($user->getUid());

			DTL::Lap('fetch friends');

			$foff = $this->get('gaia.friend.friend_offer_service');
			$len = count($friend)+count($accept)+count($request);

			$ret = [];
			DTL::Lap('start request');
			$fuids = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserIds($ids);
			DTL::Lap('get public id');
			foreach($ids as $pid){
				$fuid = $fuids[$pid];
				if(in_array($fuid,$friend)){
					$ret[] = self::RES_ALRDY;
				}elseif($limit <= $len){
					$ret[] = self::RES_OVER;
				}elseif(in_array($fuid,$request)){
					$ret[] = self::RES_REQED;
				}elseif(in_array($fuid,$accept)){
					$ret[] = self::RES_REQED;
				}else{
					try{
						if($foff->request($uid,$fuid) == 1){
							$ret[] = self::RES_SUC;
							++$len;
						}else{
							$ret[] = self::RES_NONE;
						}
					}catch(\Exception $e){
						$ret[] = self::RES_NONE;
						\Dcs\Log::e($e,true);
					}
				}
			}
			DTL::Lap('done request');
			DTL::Lap('tryRequestAction end');
			return $ret;
		});
	}


	/**
	 * 友達申請キャンセル
	 * data:{
	 * 		skey: セッションキー
	 * 		ids: array ユーザーID
	 * 		debug: bool
	 * }
	 * RPC構造
	 * data: array[int]
	 */
	public function tryCancelAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$ids = $data['ids'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV1);
			$uid = $user->getUid();

			$foff = $this->get('gaia.friend.friend_offer_service');
			$ret = [];
			$fuids = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserIds($ids);
			foreach($ids as $pid){
				try{
					$fuid = $fuids[$pid];
					$foff->cancel($uid,$fuid);
					$ret[] = 1;
				}catch(\Exception $e){
					$ret[] = 0;
					\Dcs\Log::e($e,true);
				}
			}
			return $ret;
		});
	}

	/**
	 * 友達承認
	 * data:{
	 * 		skey: セッションキー
	 * 		ids: array ユーザーID
	 * 		debug: bool
	 * }
	 * RPC構造
	 * data: bool
	 */
	public function tryAdmitAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('tryAdmitAction start');

			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$ids = $data['ids'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();

			$foff = $this->get('gaia.friend.friend_offer_service');
			$ret = [];
			$fuids = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserIds($ids);
			foreach($ids as $pid){
				try{
					$fuid = $fuids[$pid];
					DTL::Lap('get public id');
					$foff->accept($fuid,$uid);
					DTL::Lap('try accept');
					$ret[] = 1;
				}catch(\Exception $e){
					$ret[] = 0;
					\Dcs\Log::e($e,true);
				}
			}
			DTL::Lap('tryAdmitAction end');
			return $ret;
		});
	}
	/**
	 * 友達削除
	 * data:{
	 * 		skey: セッションキー
	 * 		ids: array ユーザーID
	 * 		debug: bool
	 * }
	 * RPC構造
	 * data: array[int]
	 */
	public function tryDeleteAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$ids = $data['ids'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();

			$fm = $this->get('gaia.friend.friend_management_service');
			$ret = [];
			$fuids = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserIds($ids);
			foreach($ids as $pid){
				try{
					$fuid = $fuids[$pid];
					$fm->removeFriend($uid,$fuid);
					$ret[] = 1;
				}catch(\Exception $e){
					$ret[] = 0;
					\Dcs\Log::e($e,true);
				}
			}
			return $ret;
		});
	}

	/**
	 * 友達拒否
	 * data:{
	 * 		skey: セッションキー
	 * 		ids: array ユーザーID
	 * 		debug: bool
	 * }
	 * RPC構造
	 * data: bool
	 */
	public function tryRejectAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$ids = $data['ids'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();

			$foff = $this->get('gaia.friend.friend_offer_service');
			$ret = [];
			$fuids = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserIds($ids);
			foreach($ids as $pid){
				try{
					$fuid = $fuids[$pid];
					$foff->reject($fuid,$uid);
					$ret[] = 1;
				}catch(\Exception $e){
					$ret[] = 0;
					\Dcs\Log::e($e,true);
				}
			}
			return $ret;
		});
	}
	/**
	 * 友達検索
	 * data:{
	 * 		skey: セッションキー
	 * 		type:検索種類
	 * 		word:検索文字列
	 * }
	 * RPC構造
	 * data: array[PlayerData.Friend]
	 */
	public function findFriendAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$type = intval($data['type']);
			$word = $data['word'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();


			$max = $this->get('Arpg.Logic.Util.GameParam')->getParam(GameParam::USER_SEARCH_NUM);
			$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
			$Text = $this->get('Arpg.Logic.Util.Text');

			$iglist = array_merge(
					$this->get('gaia.friend.friend_management_service')->friendIds($user->getUid()),//友
					$this->get('gaia.friend.friend_offer_service')->offeredIds($user->getUid()),//こちらを見ているリスト
					$this->get('gaia.friend.friend_offer_service')->offeringIds($user->getUid())// 共になりたい
			);
			$iglist[] = $uid;
			$aid = 0;
			$igalist=[];
			$buf = $Astatus->getActorIdMulti($iglist);
			foreach($buf as $fuid => $elem){
				$igalist[] = $elem;
				if($uid == $fuid){
					$aid = $elem;
				}
			}

			$faid = [];

			if($type == self::SEARCH_NAME){
				$args=[$uid,'%'.$Text->convertFindText($word).'%'];
				$sql = null;
				foreach($igalist as $fuaid){
					if($sql == null){
						$sql='actor_id not in(?';
					}else{
						$sql.=',?';
					}
					$args[]=$fuaid;
				}
				if($sql != null)
					$sql = 'select actor_id from box_actor where uid <> ? and name_find like ? and '.$sql.') and state = 0 order by login_date desc limit '.$max;
				else
					$sql = 'select actor_id from box_actor where uid <> ? and name_find like ? and state = 0  order by login_date desc limit '.$max;
				$stmt = $this->sql('box_actor',$sql);
				$stmt->select($args,\Dcs\SqlStatement::MODE_SLAVE);
				while($row = $stmt->fetch(\PDO::FETCH_NUM)){
					$faid[] = intval($row[0]);
				}
			}elseif($type == self::SEARCH_LEVEL){
				if(is_numeric($word)){
					$args=[$uid,self::std_player_level,intval($word)];
					$sql = null;
					foreach($iglist as $fuid){
						if($sql == null){
							$sql='box_player.uid not in(?';
						}else{
							$sql.=',?';
						}
						$args[]=$fuid;
					}
					if($sql != null)
						$sql = 'select box_player.current_actor from box_player left join box_player_status on box_player.uid = box_player_status.uid where box_player.uid <> ? and box_player.current_actor <> 0 and box_player_status.std_id = ? and box_player_status.num = ? and '.$sql.') order by box_player.last_action limit '.$max;
					else
						$sql = 'select box_player.current_actor from box_player left join box_player_status on box_player.uid = box_player_status.uid where box_player.uid <> ? and box_player.current_actor <> 0 and box_player_status.std_id = ? and box_player_status.num = ? order by box_player.last_action limit '.$max;

					$stmt = $this->sql('box_player',$sql);
					$stmt->select($args,\Dcs\SqlStatement::MODE_SLAVE);
					while($row = $stmt->fetch(\PDO::FETCH_NUM)){
						$faid[] = intval($row[0]);
					}
				}
			}elseif($type == self::SEARCH_INAME){
				$args=[$uid,$word];
				$sql = null;
				foreach($iglist as $fuid){
					if($sql == null){
						$sql='uid not in(?';
					}else{
						$sql.=',?';
					}
					$args[]=$fuid;
				}
				if($sql != null)
					$sql = 'select current_actor from box_player where uid <> ? and current_actor <> 0 and iname like ? and last_action is not null and '.$sql.') order by last_action desc limit '.$max;
				else
					$sql = 'select current_actor from box_player where uid <> ? and current_actor <> 0 iname like ? and last_action is not null order by last_action desc limit '.$max;

				$stmt = $this->sql('box_player',$sql);
				$stmt->select($args,\Dcs\SqlStatement::MODE_SLAVE);
				while($row = $stmt->fetch(\PDO::FETCH_NUM)){
					$faid[] = intval($row[0]);
				}
			}elseif($type == self::SEARCH_RECOMMEND){
				$over_max = $max * 5;
				$args=[];
				$sql = null;
				foreach($iglist as $fuid){
					if($sql == null){
						$sql='where uid not in(?';
					}else{
						$sql.=',?';
					}
					$args[]=$fuid;
				}
				if($sql != null)
					$sql = 'select uid,current_actor from box_player '.$sql.') and current_actor <> 0 order by last_action desc limit '.$over_max;
				else
					$sql = 'select uid,current_actor from box_player where current_actor <> 0 order by last_action desc limit '.$over_max;

				$stmt = $this->sql('box_player',$sql);
				$stmt->select($args,\Dcs\SqlStatement::MODE_SLAVE);
				$qus = [ new Query(['='=>[$uid,self::std_player_level]])];
				$u2a = [];
				while($row = $stmt->fetch(\PDO::FETCH_NUM)){
					$qus[] = new Query(['='=>[intval($row[0]),self::std_player_level]]);
					$u2a[intval($row[0])] = intval($row[1]);
				}

				$ulv=0;
				$rss = $this->getHs()->selectMulti(
						new Table('box_player_status',['uid','num']),
						$qus
				);
				foreach($rss as $rs)foreach($rs as $row){
					if(intval($row[0]) == $uid){
						$ulv = intval($row[1]);
						break;
					}
				}

				$book = [];
				foreach($rss as $rs)foreach($rs as $row){
					$lv = intval($row[1])-$ulv;
					$book[] = ['id'=>intval($row[0]),'lv'=>$lv*$lv];
				}
				usort($book, function($a,$b){
					if($a['lv'] == $b['lv'])
						return 0;
					return ($a['lv'] < $b['lv']) ? -1 : 1;
				});
				$len = count($book);
				for($i=0;$i<$max&&$i<$len;++$i){
					$fuid = $book[$i]['id'];
					if(!isset($u2a[$fuid]))continue;
					$faid[] = $u2a[$fuid];
				}
			}

			if(empty($faid)){
				return [];
			}else{
				$dat=[];
				// アクター名を収集
				$qus=[];
				foreach($faid as $row){
					$qus[]=new Query(['='=>$row]);
				}
				$rss = $this->getHs()->selectMulti(
						new Table('box_actor',['actor_id', 'uid','name','spirit']),
						$qus
				);
				$fuids = [];
				foreach($rss as $rs){
					if(empty($rs)) continue;
					$fuids[] = intval($rs[0][1]);
				}
				$fpids = $this->get('Arpg.Logic.Util.PlayerStatus')->getPublicIds($fuids);
				$qus=[];
				foreach($rss as $rs){
					if(empty($rs)) continue;
					$rs = $rs[0];
					if($rs == null || count($rs) < 4)continue;
					$fuid = intval($rs[1]);
					$dat[$fuid]=[
							'aid' => intval($rs[0]),
							'uid' => $fuid,
							'pid' => $fpids[intval($rs[1])],
							'name' => $rs[2],
							'spirit' => intval($rs[3])
					];
					$qus[] = new Query(['='=>[$fuid,self::std_player_level]]);
					$qus[] = new Query(['='=>[$fuid,self::std_last_action]]);
				}

				$rss = $this->getHs()->selectMulti(
						new Table('box_player_status',['uid','std_id','num']),
						$qus
				);
				foreach($rss as $rs){
					if(empty($rs)) continue;
					$rs = $rs[0];
					if($rs == null || count($rs) < 3)continue;
					$fuid = intval($rs[0]);

					if(!isset($dat[$fuid])) continue;

					switch(intval($rs[1])){
						case self::std_player_level:
							$dat[$fuid]['lv'] = intval($rs[2]);
							break;
						case self::std_last_action:
							$dat[$fuid]['time'] = intval($rs[2]);
							break;
						default:break;
					}
				}

 				// プレイヤーメッセージを収集
 				$qus = [];
 				foreach($dat as $t){
 					$qus[] = new Query(['='=>$t['uid']]);
 				}
 				$rss = $this->getHs()->selectMulti(
						new Table('box_player',['uid','message']),
 						$qus
 				);
 				foreach($rss as $rs){
					if(empty($rs)) continue;
 					$rs = $rs[0];
 					if($rs == null || count($rs) < 2)continue;
					$fuid = intval($rs[0]);
					if(!isset($dat[$fuid])) continue;
					$dat[$fuid]['mes'] = $rs[1];
 				}
				$ret = [];
				foreach($dat as $line){
					$ret[] = [
					'id'=> $line['pid'],
					'actorId'=>$line['aid'],
					'name'=> $line['name'],
					'level'=> $line['lv'],
					'message'=> $line['mes'],
					'iconStdId'=> $line['spirit'],
					'stat'=> 0,
					'time'=> array_key_exists('time',$line)?$line['time']:0,
					];
				}
				return $ret;
			}
		});
	}
	public function extendAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();

			$use_cp = $this->get('Arpg.Logic.Util.GameParam')->getParam(GameParam::FRIEND_EXT_CP);
			$Dparam = $this->get('Arpg.Logic.Util.DevParam');
			$Stack = $this->get('Arpg.Logic.Util.StackItem');
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');

			$fm = $this->get('gaia.friend.friend_management_service');
			$limit = $fm->getFriendLimit($uid);

			if($limit >= $Dparam->param(77))
				throw new ResError('dont extend friend. freand is max',100);

			$ticket = $Stack->getNum($uid,self::std_ext_ticket);
			$ret = null;
			if($ticket > 0){
				$limit += $Dparam->param(78);
				$fm->updateFriendLimit($uid,$limit);
				$Stack->add($uid,self::std_ext_ticket,-1);
				$ret = [
					'value' => $limit,
					'costStdId' => self::std_ext_ticket,
					'costValue' => 1,
				];
			}elseif($Pstatus->getStatus($uid,self::std_cp) > $use_cp){
				$limit += $Dparam->param(78);
				$fm->updateFriendLimit($uid,$limit);
				$Pstatus->add($uid,self::std_cp,-$use_cp);
				$ret = [
					'value' => $limit,
					'costStdId' => self::std_cp,
					'costValue' => $use_cp,
				];
			}else{
				throw new ResError('dont extend friend. too low ticket or cp',100);
			}

			return $ret;
		});
	}
	const std_player_level = 1;
	const std_last_action = 1004;
	const std_ext_ticket = 203007;
	const std_cp = 10001;
}
