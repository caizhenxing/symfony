<?php

namespace Sega\AppBundle\Controller;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\Arpg\Time as Time;
use \Logic\Util\GameParam as GameParam;
use \Logic\Util\Quest as Quest;
use \Dcs\DetailTimeLog as DTL;
use \Logic\DungeonData\Bonus;

class QuestDataController extends \Dcs\DcsController{
	/**
	 * いいね！実行
	 * リクエストデータ構造
	 * [
	 * 		"skey" => セッションキー,
	 * 		"aid" => アクターID
	 * ]
	 * レスポンスデータ構造
	 *  GameData.Reward
	 */
	public function iineAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$aid = intval($data['aid']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV1);
			$uid = $user->getUid();

			$auid = $this->get('Arpg.Logic.Util.ActorStatus')->getUserId($aid);

			$Gparam = $this->get('Arpg.Logic.Util.GameParam');
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');

			$p_send = $Gparam->getParam(GameParam::GOOD_SEND);
			$p_accept = $Gparam->getParam(GameParam::GOOD_ACCEPT);

			$adder = [
					[$uid,self::std_gacha_p,$p_send],
					[$uid,self::std_good_send,1],
					[$auid,self::std_good_gacha_p,$p_accept],
					[$auid,self::std_good_gacha_num,1]
			];

			$Pstatus->addMulti($adder);

			$ret = $this->get('Arpg.Logic.GameData.Reward');
			$ret->init(self::std_gacha_p,$p_send);
			return $ret;
		});
	}
	/**
	 * ワールド情報取得
	 * リクエストデータ構造
	 * RPC構造
	 * data:[
	 * 		{
	 * 			id: int
	 * 			title: string
	 * 			fileIconOn: string
	 * 			fileIconOff: string
	 * 			fileMap: string
	 * 			isExt: bool
	 * 			strExt: string
	 * 			isLimit: bool
	 * 			strLimit: string
	 * 		}
	 * ]
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getWorldAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('getWorldAction start');
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			$Quest = $this->get('Arpg.Logic.Util.Quest');
			$uid = $user->getUid();
			$list = $Quest->getWorldInfo();

			DTL::Lap('get world info');

			$rs = $this->getHs()->select(
					new Table('box_quest',['world_id','area_id','dungeon_id','nb_clear']),
					new Query(['='=>$uid])
			);
			$cleared = [];
			foreach($rs as $row){
				if(intval($row[3]) > 0)
					$cleared[1000000+10000*intval($row[0])+100*intval($row[1])+intval($row[2])] = true;
			}
			$all_debug = \Dcs\Arpg\Config::Debug && intval($this->get('Arpg.Logic.Util.DevParam')->param(85)) > 0;

			DTL::Lap('select user data');

			$dat = [];
			foreach($list as $line){
				if($line->world_id == 98 || $line->world_id == 99) continue;
				if(!$all_debug){
					$duns = $Quest->getDungeonInfo($line->world_id);
					$open = false;
					foreach($duns as $dun){
						if($dun->type != Quest::MAIN) continue;
						if(empty($dun->parent_dungeon)){
							$open = true;
							break;
						}
						foreach($dun->parent_dungeon as $pd){
							if(isset($cleared[$pd])){
								$open = true;
								break;
							}
						}
						if($open)
							break;
					}
					if(!$open)
						break;
				}

				$dat[] = array(
						'id' => $line->world_id,
						'title' => $line->title,
						'fileIcon' => $line->file_icon_on,
						'fileMap' => $line->file_map,
						'mapWidth' => $line->width,
						'mapHeight' => $line->height
				);
			}
			DTL::Lap('end');
			return $dat;
		});
	}

	const WORLD_ID = 0;
	const AREA_ID = 1;
	const DUNGEON_ID = 2;
	const NB_TRY = 3;
	const NB_CLEAR = 4;
	const NB_GET_MAIN = 5;
	const HUNT = 6;
// ['world_id','area_id','dungeon_id','nb_try','NB_CLEAR','NB_GET_MAIN']

	/**
	 * エリア情報取得
	 * リクエストデータ構造
	 * $data:{
	 * 		skey: Sessionkey
	 * 		wid: int ワールドID
	 * }
	 * RPC構造
	 * data:[
	 * 		{
	 * 			id: int
	 * 			worldId: int
	 * 			title: string
	 * 			info: string
	 * 			fileIcon: string
	 * 			fileBg: string
	 * 			areaType: int
	 * 			posX: int
	 * 			posY: int
	 *
	 * 			isNext: bool
	 * 			isClear: bool
	 * 			isLimit: bool
	 * 			strLimitInfo: string
	 * 			isExt: bool
	 * 			strExtInfo: string
	 * 		}
	 * ]
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getAreaAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('getAreaAction start');
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$wid = intval($data['wid']);
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			$uid = $user->getUid();
			$Quest = $this->get('Arpg.Logic.Util.Quest');
			// エリア情報
			$areas = $Quest->getAreaInfo($wid);
			// ダンジョン情報
			$duns = $Quest->getDungeonInfo($wid);

			DTL::Lap('get area and dungeon info');

			$boxes = $this->getHs()->select(
					new Table('box_quest',['world_id','area_id','dungeon_id','nb_try','nb_clear','nb_get_main','hunt']),
					new Query(['='=>[$uid]],-1)
			);

			$buf = [];
			foreach($boxes as $box){
				$buf[1000000+intval($box[self::WORLD_ID])*10000+intval($box[self::AREA_ID])*100+intval($box[self::DUNGEON_ID])]=$box;
			}

			DTL::Lap('select user data');

			$boxes = $buf;
			$now = new Time();
			$now = $now->get();
			$dat = [];
			$all_debug = \Dcs\Arpg\Config::Debug && intval($this->get('Arpg.Logic.Util.DevParam')->param(85)) > 0;


			foreach($areas as $area){
				$aid = $area->area_id;
				$num = 0;
				$is_new = false;
				$is_next = false;
				$is_clear = true;
				$is_find = false;
				$is_comp = true;// コンプリート
				foreach($duns as $dun){
					if($dun->area_id != $aid)continue;
					if(!$dun->enable($now) && !$all_debug)continue;

					$is_main = $dun->type == 1;
					$did = $dun->dungeon_id;
					if($wid == 98){
						// コンプ判定しない
						$is_comp = false;
						++$num;
						if(!isset($box[self::DUNGEON_ID]) || intval($box[self::DUNGEON_ID]) < 1){
							$is_new = true;
						}
						if(!isset($box[3]) || intval($box[3]) < 1){
							$is_clear = false;
							if($is_main)
								$is_next = true;
						}
						if(isset($box[4]) && intval($box[4]) > 0){
							$is_find = true;
						}
					}else{

						$dstd_id = 1000000+$wid*10000+$aid*100+$did;
						if(isset($boxes[$dstd_id])){
							$box = $boxes[$dstd_id];
							++$num;
							if(intval($box[3]) < 1){
								$is_new = true;
							}
							if(intval($box[4]) < 1){
								$is_clear = false;
								if($is_main)
									$is_next = true;
							}
							if(intval($box[5]) > 0){
								$is_find = true;
							}
							if(intval($box[self::HUNT]) < $dun->comp_max){
								// 未コンプダンジョンが有るのでフラグを下ろす
								$is_comp = false;
							}
						}else{
							// 探索データが無いのでコンプフラグを下ろす
							$is_comp = false;
							// openチェック
							$count = 0;
							$is_open = true;
							foreach($dun->parent_dungeon as $std_id){
								if($std_id != 0){
									if(!isset($boxes[$std_id])){
										$is_open=false;
										break;
									}
									$box = $boxes[$std_id];
									if(intval($box[4]) < 1){
										$is_open=false;
										break;
									}
								}
								++$count;
							}
							if($count > 0 && $is_open){
								$is_new = true;
								if($is_main)
									$is_next = true;
								$is_clear = false;
								++$num;
							}
						}
					}
				}

				if(!(\Dcs\Arpg\Config::Debug && intval($this->get('Arpg.Logic.Util.DevParam')->param(85)) > 0) && $num < 1){
					continue;
				}

				$dat[] = array(
						'worldId' => $wid,
						'id' => $aid,
						'title' => $area->title,
						'fileIcon' => $area->file_icon,
						'mainSpirit' => $area->main_spirit,
						'fileBg' => $area->file_bg,
						'posX' => $area->pos_x,
						'posY' => $area->pos_y,

						'isNext' => $is_next?1:0,
						'isClear' => $is_clear?1:0,
						'isComp' => $is_comp?1:0,
						'isNew' => $is_new?1:0,
						'isFindSpirit' => $is_find?1:0,
				);

				DTL::Lap("create data $wid $aid ");
			}

			DTL::Lap('end');
			return $dat;
		});
	}
	/**
	 * ダンジョン情報取得
	 * リクエストデータ構造
	 * $data:{
	 * 		skey: Sessionkey
	 * 		wid: int ワールドID
	 * 		aid: int エリアID
	 * }
	 * RPC構造
	 * data:[
	 * 		{
	 * 			worldId: int
	 * 			areaId: int
	 * 			id: int
	 * 			title: string
	 * 			enemyLv: int
	 * 			stp: int
	 * 			partyMode: int
	 * 			fileIcon: string
	 * 			strMessage: string
	 * 			strInfo: string
	 *
	 * 			nbTry: int
	 * 			nbClear: int
	 * 			isExt: bool
	 * 			strExtInfo: string
	 * 			extTime: string
	 * 			isLimit: bool
	 * 			strLimitInfo: string
	 *
	 * 			nbItem:int
	 * 			fileItem1: string
	 * 			fileItem2: string
	 * 			fileItem3: string
	 * 			fileItem4: string
	 * 			fileItem5: string
	 * 			fileItem6: string
	 *
	 * 			config: int
	 * 		}
	 * ]
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getDungeonAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('getDungeonAction start');
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$wid = intval($data['wid']);
			$aid = intval($data['aid']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			$uid = $user->getUid();
			$Quest = $this->get('Arpg.Logic.Util.Quest');

			$duns = [];
			if($wid == 98){
				// スペシャルダンジョン
				$duns = $Quest->getDungeonInfo($wid);
			}else{
				$duns = $Quest->getDungeonInfo($wid,$aid);
			}

			DTL::Lap('get dungeon info');

			$dat = [];
			foreach($duns as $dun){
				$dungeon_uid = 1000000+$dun->world_id*10000+$dun->area_id*100+$dun->dungeon_id;

				$ins = $this->createDungeonData($uid,$dungeon_uid, $dun);
				if($ins != null){

					$dat[] = $ins;
				}

				DTL::Lap("create dungeon data $dungeon_uid");
			}
			usort($dat,function($a,$b){
				$a = $a['priority'];
				$b = $b['priority'];
				if ($a == $b) {
					return 0;
				}
				return ($a < $b) ? -1 : 1;
			});
			DTL::Lap('end');
			return $dat;
		});
	}
	/**
	 * 乱入可能ダンジョン取得
	 * リクエストデータ構造
	 * 		セッションキーデータ
	 * レスポンスデータ構造
	 * 		List<PlayerData.DungeonJoin>
	 */
	public function getJoinDungeonAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$puid = intval($this->get('Arpg.Logic.Util.PlayerStatus')->getPublicId($uid));

			$Quest = $this->get('Arpg.Logic.Util.Quest');

			$Popup = $this->get('Arpg.Logic.PlayerData.HomePopup');
			$pps = $Popup->select($puid,['action','actor','dungeon','room','public_uid','actor_weapon','ticket']);
			$pps2 = $Popup->select(0,['action','actor','dungeon','room','public_uid','actor_weapon','ticket']);
			$dun = [];
			$boxes = [];
			$qus2 = [];
			$info = [];
			$room = [];
			foreach($pps as $row){
				if(intval($row[0]) != 1)continue;


				$qus2[] = new Query(['='=>intval($row[6])]);
				$dudid = intval($row[2]);
				$wid = intval($dudid/10000)%100;
				$aid = intval($dudid/100)%100;
				$did = $dudid % 100;
				$room[intval($row[3])] = true;

				$ins = $this->createDungeonData($uid,$dudid,$Quest->getDungeonInfo($wid,$aid,$did)[0]);
				if($ins != null)
					$info[$dudid] = $ins;
			}
			$swap=[];
			foreach($pps2 as $row){
				if(intval($row[0]) != 1)continue;

				$dudid = intval($row[2]);
				$pid = intval($row[4]);
				if(isset($room[intval($row[3])]) || $pid == $puid) continue;

				$qus2[] = new Query(['='=>intval($row[6])]);
				$dudid = intval($row[2]);
				$wid = intval($dudid/10000)%100;
				$aid = intval($dudid/100)%100;
				$did = $dudid % 100;

				$ins = $this->createDungeonData($uid,$dudid,$Quest->getDungeonInfo($wid,$aid,$did)[0]);
				if($ins != null)
					$info[$dudid] = $ins;
				$swap[] = $row;
			}
			$pps2 = $swap;

			// 存在しないインフォを作成
			foreach($pps as $row){
				if(intval($row[0]) != 1)continue;

				$did = intval($row[2]);
				if(isset($info[$did])) continue;
				$ins = $this->createDungeonData($uid,$did,$Quest->getDungeonInfoByStdID($did)[0]);
				if($ins != null)
					$info[$did] = $ins;
			}
			foreach($pps2 as $row){
				if(intval($row[0]) != 1)continue;

				$did = intval($row[2]);
				if(isset($info[$did])) continue;
				$ins = $this->createDungeonData($uid,$did,$Quest->getDungeonInfoByStdID($did)[0]);
				if($ins != null)
					$info[$did] = $ins;
			}
			// ボーナス情報取得
			$rss = $this->getHs()->selectMulti(
					new Table(Bonus::HS_TBL,Bonus::$HS_FLD),
					$qus2
			);
			$bonus = [];
			if(!empty($rss))foreach($rss as $rs){
				if(!isset($rs[0][1]))continue;
				$b = $this->get('Arpg.Logic.DungeonData.Bonus');
				$b->initByHs($rs);
				$bonus[intval($rs[0][1])] = $b;
			}
			// チケット情報取得
			$rss = $this->getHs()->selectMulti(
					new Table('action_ticket',['id','create_time']),
					$qus2
			);
			$ctime = [];
			$time = new Time();
			if(!empty($rss))foreach($rss as $rs)foreach($rs as $row){
				$time->setMySQLDateTime($row[1]);
				$ctime[intval($row[0])] = $time->get();
			}
			$is_collect = [];
			$ret = [];
			foreach($pps as $row){
				if(intval($row[0]) != 1)continue;

				$did = intval($row[2]);
				$ticket = intval($row[6]);
				if(!isset($info[$did])) continue;
				$inf = $info[$did];
				$room_id = intval($row[3]);
				$ret[] = [
					'apid' => intval($row[4]),
					'actorName' => $row[1],
					'actorStdId' => intval($row[5]),
					'roomId' => $room_id,
					'createTime' => $ctime[$ticket],
					'dungeon' => $inf,
					'friendState' => 3,
					'bonus' => isset($bonus[$ticket])?$bonus[$ticket]:null,
				];
				$is_collect[] = new Query(['='=>$room_id]);
			}
			foreach($pps2 as $row){
				if(intval($row[0]) != 1)continue;

				$did = intval($row[2]);
				$ticket = intval($row[6]);
				if(!isset($info[$did])) continue;
				$inf = $info[$did];
				$room_id = intval($row[3]);

				$ret[] = [
					'apid' => intval($row[4]),
					'actorName' => $row[1],
					'actorStdId' => intval($row[5]),
					'roomId' => $room_id,
					'createTime' => $ctime[$ticket],
					'dungeon' => $inf,
					'friendState' => 0,
					'bonus' => isset($bonus[$ticket])?$bonus[$ticket]:null,
				];
				$is_collect[] = new Query(['='=>$room_id]);
			}

			// ソート
			usort($ret, function($a,$b){
				$afs = $a['friendState'];
				$bfs = $b['friendState'];
				if($afs < $bfs) return 1;
				if($afs > $bfs) return -1;
				$act = $a['createTime'];
				$bct = $b['createTime'];
				if($act < $bct) return 1;
				if($act > $bct) return -1;
				return 0;
			});

			// 募集中かどうか
			$rss = $this->getHs()->selectMulti(
					new Table('action_room',['id','collect_state']),
					$is_collect
			);
			$is_collect = [];
			if(!empty($rss))foreach($rss as $rs)foreach($rs as $row){
				$is_collect[intval($row[0])] = (intval($row[1]) == 0);
			}


			// 重複と上限切り
			$pids=[];
			$rids=[];
			$swap=[];
			$counter = 0;
			for($i=0,$len=count($ret);$i<$len && $counter < 10;++$i){
				$d = $ret[$i];
				$pid = $d['apid'];
				if(isset($pids[$pid])) continue;
				$rid = $d['roomId'];
				if(!$is_collect[$rid]) continue;
				if(isset($rids[$rid])) continue;
				unset($d['apid']);
				$swap[] = $d;
				$pids[$pid] = true;
				$rids[$rid] = true;
				++$counter;
			}

			return $swap;
		});
	}
	public function getDungeonByStdIDAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$dungeon_uid = intval($data['sid']);
			$wid = intval($dungeon_uid/10000)%100;
			$aid = intval($dungeon_uid/100)%100;
			$did = $dungeon_uid % 100;

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$Quest = $this->get('Arpg.Logic.Util.Quest');

			$dun = [];

			$duns = $Quest->getDungeonInfo($wid,$aid,$did);
			$now = new Time();
			$now = $now->get();
			foreach($duns as $dun){
				return $this->createDungeonData($uid,$dungeon_uid, $dun,$now);
			}

			return null;
		});
	}
	private $mBoxes = null;
	/**
	 *
	 * @param unknown $uid
	 * @param unknown $dungeon_uid
	 * @return null | [
	 *		'wid' => ワールドID,
	 *		'aid' => エリアID,
	 *		'did' => ダンジョンID,
	 *		'try' => 挑戦数,
	 *		'clr' => クリア数,
	 *		'main' => メイン獲得数,
	 *		'hunt' => ,
	 * 	]
	 */
	private function getBox($uid, $dungeon_uid){
		$dungeon_uid = intval($dungeon_uid);
		if($this->mBoxes == null){
			$buf = $this->getHs()->select(
					new Table('box_quest',['world_id','area_id','dungeon_id','nb_try','nb_clear','nb_get_main','hunt']),
					new Query(['='=>[$uid]],-1)
			);
			$this->mBoxes = [];
			foreach($buf as $box){
				$this->mBoxes[1000000+intval($box[0])*10000+intval($box[1])*100+intval($box[2])]=[
					'wid' => intval($box[0]),
					'aid' => intval($box[1]),
					'did' => intval($box[2]),
					'try' => intval($box[3]),
					'clr' => intval($box[4]),
					'main' => intval($box[5]),
					'hunt' => intval($box[6]),
				];
			}
		}
		if(isset($this->mBoxes[$dungeon_uid]))
			return $this->mBoxes[$dungeon_uid];
		return null;
	}
	const ENDDATE = 1261440000; // 2050 01 01 00 00 00
	private static $DungeonCache = [];
	private function createDungeonData($uid, $dungeon_uid, $dun){
		$all_debug = \Dcs\Arpg\Config::Debug && intval($this->get('Arpg.Logic.Util.DevParam')->param(85)) > 0;
		$key = $uid.':'.$dungeon_uid;
		if(isset(self::$DungeonCache[$key])) return self::$DungeonCache[$key];
		if(!$dun->enable() && !$all_debug) return null;
		$conf_id =$dun->config;
		$box_data = $this->getBox($uid,$dungeon_uid);
		if($all_debug){
			// フルオープンデバッグ
			if($box_data == null){
				$box_data = [
					'wid' => $dun->world_id,
					'aid' => $dun->area_id,
					'did' => $dun->dungeon_id,
					'try' => 0,
					'clr' => 0,
					'main' => 0,
					'hunt' => 0
				];
			}
		}else{
			// 通常版
			if($box_data==null){
				if($dun->world_id == 98){
					// スペシャルはなくてもOK
					$box_data = [
						'wid' => $dun->world_id,
						'aid' => $dun->area_id,
						'did' => $dun->dungeon_id,
						'try' => 0,
						'clr' => 0,
						'main' => 0,
						'hunt' => 0
					];
				}else{
					// オープンチェック
					$no_zero = false;
					$is_open = true;
					foreach($dun->parent_dungeon as $pdid){
						if($pdid != 0){
							$box = $this->getBox($uid,$pdid);
							if($box == null || $box['clr'] < 1){
								$is_open = false;
								break;
							}
						}
						$no_zero = true;
					}
					if(!$no_zero || !$is_open)
						return null;
					$box_data = [
						'wid' => $dun->world_id,
						'aid' => $dun->area_id,
						'did' => $dun->dungeon_id,
						'try' => 0,
						'clr' => 0,
						'main' => 0,
						'hunt' => 0
					];
				}
			}
		}

		$conf_data = null;
		$assets = [];
		$ckey = 'QuestGetDungeon.'.$dungeon_uid;
		$cache = $this->cache()->get(\Dcs\Cache::TYPE_MEMCACHE,$ckey);
		if($cache == null){
			$rs = $this->getHs(false)->select(
					new Table('dungeon_config',['id','stage','time']),
					new Query(['='=>$conf_id])
			);
			if(empty($rs)) return null;
			$conf_data = $rs[0];

			$stages = explode(',',$conf_data[1]);
			$conf_data[1] = count($stages);

			// アセットバンドル情報収集
			$qus=[];
			$qus2=[];
			foreach($stages as $stage){
				$stage = intval($stage);
				$qus[]=new Query(['='=>$stage]);
				$qus2[]=new Query(['='=>$stage],-1);
			}
			$rss = $this->getHs(false)->selectMulti(
					new Table('stage_model',['name','minimap']),
					$qus
			);
			foreach($rss as $rs)foreach($rs as $row){
				$assets[]=$row[0];
				$assets[]=$row[1];
			}
			$rss = $this->getHs(false)->selectMulti(
					new Table('enemy_place',['enemies'],'STAGE_INDEX'),
					$qus2
			);
			$qus2=[];
			foreach($rss as $rs)foreach($rs as $row){
				$enms = explode(',',$row[0]);
				foreach($enms as $enm){
					$qus2[]=new Query(['='=>intval($enm)]);
				}
			}
			if(!empty($qus2)){
				$rss = $this->getHs(false)->selectMulti(
						new Table('enemy_data',['name']),
						$qus2
				);
				foreach($rss as $rs)foreach($rs as $row){
					$assets[] = $row[0];
				}
			}
			// $assetsを整理
			$buff = $assets;
			$assets=[];
			foreach($buff as $asset){
				if(in_array($asset,$assets)) continue;
				$assets[]=$asset;
			}
			$this->cache()->set(\Dcs\Cache::TYPE_MEMCACHE,$ckey,[$conf_data,$assets]);

			DTL::Lap("create dungeon config cache $dungeon_uid");

		}else{
			$conf_data = $cache[0];
			$assets = $cache[1];
		}

		$ext = $this->get('Arpg.Logic.Util.Quest')->checkExt($dungeon_uid);

		$Text = $this->get('Arpg.Logic.Util.Text');
		// データ生成
		$ret = array(
				'id' => $dun->dungeon_id,
				'worldId' => $dun->world_id,
				'areaId' => $dun->area_id,
				'priority' => $dun->priority,
				'attr' => $dun->attribute,
				'title' => $dun->title,
				'enemyLv' => $dun->enemy_level,
				'dungeonType' => $dun->type,
				'stp' => $dun->try_stp,
				'config' => $conf_id,
				'fileIcon' => $dun->file_icon,
				'strMessage' => $dun->str_message,
				'from' => $all_debug?0:$dun->from(),
				'to' => $all_debug?self::ENDDATE:$dun->to(),
				'startType' => $dun->start_type,
				'towerMax' => count($dun->tower),


				'limitLevel'=>$dun->limit_lv,
				'limitLevelTxt'=>$Text->getText(self::txt_limit_lv,['[level]'=>$dun->limit_lv]),
				'limitAttr'=>$dun->limit_attr,
				'limitAttrTxt'=>$Text->getText(self::txt_limit_attr,['[attr]'=>$Text->getText($dun->limit_attr+10)]),
				'limitWeapon'=>$dun->limit_weapon,
				'limitWeaponTxt'=>$Text->getText(self::txt_limit_wtype,['[wtype]'=>$Text->getText($dun->limit_weapon+30)]),

				'isMulti' => $dun->party_mode == 2,

				'nbTry' => $box_data['try'],
				'nbClear' => $box_data['clr'],

				'strCondition' => '',
				'timeLimit' => intval($conf_data[2]),
				'stageNum' => $conf_data[1],
				'fileItems' => $dun->file_item,

				'isExt' => $ext != null,
				'strExtInfo' => $ext!=null?$ext['info']:'',
				'extTime' => $ext!=null?$ext['to']:0,
				'limitAttr' => $dun->limit_attr,
				'limitWeapon' => $dun->limit_weapon,

				'compRate' => $box_data['hunt']*100/$dun->comp_max,
				'compOpen1' => $dun->comp_open1*100/$dun->comp_max,
				'compStdId1' => $dun->comp_stdid1,
				'compStdId2' => $dun->comp_stdid2,
				'compNum1' => $dun->comp_num1,
				'compNum2' => $dun->comp_num2,

				'friendName' => '',
				'friendNum' => 0,
				'friendTime' => 0,

				'assetBundle' => $assets,
				'info' => $dun->str_info,
				'fileAreaBg' => $this->get('Arpg.Logic.Util.Quest')->getAreaInfo($dun->world_id,$dun->area_id)[0]->file_bg,
		);
		self::$DungeonCache[$key] = $ret;
		return $ret;
	}
	/**
	 * クエスト開始
	 * リクエストデータ構造
	 * data:{
	 * 		skey:sessionkey
	 * 		did: ダンジョン std_id
	 * 		aids: アクターユニークID
	 * }
	 * RPC構造
	 * data: bool
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー 100 書き込み失敗？
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function beginDungeonAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('beginDungeonAction start');
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			//$std_id = intval($data['did']);
			$ticket = intval($data['tid']);
			$aids = $data['aids'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$Quest = $this->get('Arpg.Logic.Util.Quest');
			$PlayerStatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			$uid = $user->getUid();

			$this->updateActionDate($uid);

			$pstate = $PlayerStatus->getStatusMulti($uid,[self::std_player_lv,self::std_use_boost,self::std_use_boost_limit,self::std_equip_set]);

			$set_size = $pstate[self::std_equip_set];
			if($set_size < 1)$set_size=1;

			$rs = $this->getHs()->select(new Table('action_ticket',['dungeon_id']),new Query(['='=>$ticket]));

			if(empty($rs)){
				throw new ResError("dont find ticket $ticket",100);
			}
			$std_id = intval($rs[0][0]);

			$did = $std_id % 100;
			$aid = intval($std_id/100) % 100;
			$wid = intval($std_id/10000) % 100;

			$rs = $Quest->getDungeonInfo($wid,$aid,$did);

			$Gparam = $this->get('Arpg.Logic.Util.GameParam');
			// ポイント追加
			$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
			$friends = $this->get('gaia.friend.friend_management_service')->friendIds($uid);
			$aid = $Astatus->getActorId($uid);
			$param = [self::std_eset];
			for($i=0;$i<$set_size;++$i){
				$param[] = self::std_eset_w+$i*10;
			}
			$astate = $Astatus->getStatusMulti($aid,$param);

			DTL::Lap('fetch friend list');
			$adder = [];
			$p_time = new Time();

			$p_time->add($Gparam->getParam($Gparam::FRIEND_REPOINT_TIME));
			$p_time = $p_time->getMySQLDateTime();
			$f_point = $Gparam->getParam($Gparam::GHOST_FRIEND_POINT);
			$p_point = $Gparam->getParam($Gparam::GHOST_PLAYER_POINT);
			$sql=null;
			$args=[];
			$fuids = $Astatus->getUserIdMulti($aids);

			DTL::Lap('fetch friend actor ids');

			foreach($aids as $actor_id){
				$fuid = $fuids[$actor_id];
				$point = 0;
				if(in_array($fuid,$friends))
					$point = $f_point;
				else
					$point = $p_point;
				$adder[] = [$uid,self::std_gacha_p,$point];
				$adder[] = [$fuid,self::std_save_gacha_p,$point];
				$adder[] = [$fuid,self::std_save_gacha_num,1];
				if($sql == null)
					$sql = 'insert into box_ghost (uid,fuid,open_time) values(?,?,?)';
				else
					$sql .= ',(?,?,?)';
				$args[] = $uid;
				$args[] = $fuid;
				$args[] = $p_time;
			}

			DTL::Lap('add ghost time');

			if($sql != null){
				$sql .= ' on duplicate key update open_time = values(open_time)';
				$this->useTransaction();
				$this->sql('box_ghost',$sql)->insert($args);
				DTL::Lap('add ghost point');
			}
			if(!empty($rs)){
				$rs = $rs[0];
				if($rs->limit_lv > $pstate[self::std_player_lv])
					throw new ResError("level limit dungeon $did , ulv : ".$pstate[self::std_player_lv],100);
				if($rs->limit_attr > 0 || $rs->limit_weapon > 0){
					$rs1 = $this->getHs()->select(new Table('box_equip',['std_id'],'IUS'), new Query(['='=>[$astate[self::std_eset_w+$astate[self::std_eset]*10],$uid,0]]));
					if(empty($rs1))
						throw new ResError("dont find weapon. uid : $uid",100);
					$Equip = $this->get('Arpg.Logic.Util.Equip');
					$info = $Equip->getData(intval($rs1[0][0]));
					if($rs->limit_attr > 0  && $rs->limit_attr != $info->attribute)
						throw new ResError("attribute limit dungeon $did , uid : $uid",100);
					if($rs->limit_weapon > 0 && $rs->limit_weapon != $Equip->std2wtype($info->std_id))
						throw new ResError("weapon limit dungeon $did , uid : $uid",100);
				}
				$now = new Time();
				$from = new Time();
				$from->setMySQLDateTime($rs->effective_from);
				$to = new Time();
				$to->setMySQLDateTime($rs->effective_to);
				$ext = $Quest->checkExt($std_id);
				$qbonus_type = 0;
				$qbonus_value = 0;
				if($ext != null){
					$qbonus_type = $ext['type'];
					$qbonus_value = $ext['rate'];
				}
				$all_debug = \Dcs\Arpg\Config::Debug && intval($this->get('Arpg.Logic.Util.DevParam')->param(85)) > 0;
				$now = $now->get();
				if($all_debug || ($from->get() <= $now && $now < $to->get())){
					$use_stp = $rs->try_stp;
					// TODO stp使用軽減
					$PlayerStatus->setMulti([
							[$uid,self::try_dungeon,$std_id],
							[$uid,self::try_ticket,$ticket],
							[$uid,self::try_time,$now],
							[$uid,self::std_quest_bonus_type,$qbonus_type],
							[$uid,self::std_quest_bonus_value,$qbonus_value*100],
							[$uid,self::std_use_stp,$use_stp],
							[$uid,self::std_kbox_id,0],
					]);

					if($pstate[self::std_use_boost_limit]>$now){
						$this->useTransaction();
						$this->sql('action_boost','insert ignore into action_boost (ticket_id, boost_std_id) values(?,?)')->insert([$ticket, $pstate[self::std_use_boost]]);
					}
					$PlayerStatus->addMulti($adder);
					$Quest->update($uid,$std_id,Quest::FLAG_BEGIN);
					DTL::Lap('beginDungeonAction end');
					return true;
				}
			}

			DTL::Lap('beginDungeonAction end');
			throw new ResError('dont exist dungeon. did'.$std_id,100);
		});
	}

	/**
	 * クエスト終了
	 * リクエストデータ構造
	 * data:{
	 * 		skey:sessionkey
	 * 		clear: クリアフラグ
	 * 		item: 使用したアイテムリスト
	 * }
	 * RPC構造
	 * data: bool
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー 100 書き込み失敗？
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function endDungeonAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('endDungeonAction start');

			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$clear = $data['clear'] != 0;
			$item = $data['item'];
			$tbox = $data['tbox'];
			$time = isset($data['time'])?intval($data['time']):0;
			$kill = intval($data['kill']);
			//$ticket = intval($data['ticket']);
			$did = intval($data['did']);
			$elixir = isset($data['elixir'])?intval($data['elixir']):0;
			$xlink = [];
			if(isset($data['cross']))
				$xlink = $data['cross'];

			if(!is_array($item))
				$item = [];

			$user = $this->createCmnAccount();

			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);

			$dat = $this->get('Arpg.Logic.DungeonData.Result');
			$dat->init($user->getUid(),$did,$clear, $item, $tbox,$time,$kill,$xlink,$elixir);

			DTL::Lap('endDungeonAction end');
			return $dat;
		});
	}
	/**
	 * 鍵付宝箱開錠
	 * リクエストデータ構造
	 * [
	 * 		'skey' => セッションキー,
	 * 		'key' => 鍵使ったフラグ
	 * ]
	 * レスポンスデータ構造
	 *  GameData.Reward
	 */
	public function openKboxAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$use = intval($data['key'])>0;

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();

			$Quest = $this->get('Arpg.Logic.Util.Quest');
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			$Stack = $this->get('Arpg.Logic.Util.StackItem');

			$pstat = $Pstatus->getStatusMulti($uid,[self::std_kbox_id,self::std_cp]);
			if($pstat[self::std_kbox_id] < 1000000)
				return null;
			$rs = $Quest->getDungeonInfoByStdID($pstat[self::std_kbox_id]);
			if(empty($rs))
				return null;
			$dun = $rs[0];

			$sadder = [];
			$padder = [];

			$res = [];


			$ret = null;
			$use_cp = $this->get('Arpg.Logic.Util.GameParam')->getParam(GameParam::KBOX_KEY_CP);
			$is_kg = false;
			$key_size = $Stack->getNum($uid,self::std_box_key);
			if(!$use){
				if(mt_rand(0,99) < $dun->kbox_nokey_rate){
					$ret = $this->get('Arpg.Logic.Util.Gacha')->drawByFuncMulti($uid,[$dun->kbox_nokey_gacha]);
					if(empty($ret)){
						$ret = null;
					}else{
						$ret = $ret[0];
					}
				}
			}elseif($key_size > 0){
				$res['costStdId'] = self::std_box_key;
				$res['costValue'] = $key_size-1;
				$sadder[] = [$uid,self::std_box_key,-1];
				$is_kg=true;
			}elseif($pstat[self::std_cp] >= $use_cp){
				$res['costStdId'] = self::std_cp;
				$res['costValue'] = $pstat[self::std_cp] - $use_cp;
				$padder[] = [$uid,self::std_cp,-$use_cp];
				$is_kg=true;
			}else{
				throw new ResError('too low box_key or cp',100);
			}
			if($is_kg){
				$ret = $this->get('Arpg.Logic.Util.Gacha')->drawByFuncMulti($uid,[$dun->kbox_key_gacha]);
				if(empty($ret)){
					$ret = null;
				}else{
					$ret = $ret[0];
				}
			}
			$Stack->addMulti($sadder);
			$Pstatus->addMulti($padder);
			$Pstatus->set($uid,self::std_kbox_id,0);

			$res['value'] = $ret;
			return $res;
		});
	}

	// STD_ID
	const std_use_boost = 300;
	const std_use_boost_limit = 301;
	const try_dungeon = 1010;	// 挑戦中ダンジョンSTDID
	const try_ticket = 1011;
	const try_time = 1012;		// 挑戦開始時間
	const std_quest_bonus_type = 1013;
	const std_quest_bonus_value = 1014;
	const std_use_stp = 1015;
	const std_kbox_id = 1017;
	const std_gacha_p = 10003;
	const std_save_gacha_p = 1030;
	const std_save_gacha_num = 1032;
	const std_good_send = 305;
	const std_good_gacha_p = 1040;
	const std_good_gacha_num = 1042;
	const std_player_lv = 1;
	const std_cp = 10001;
	const std_equip_set = 6;

	const std_eset = 50050;
	const std_eset_w = 50051;
	const std_eset_h = 50052;
	const std_eset_c = 50053;
	const std_eset_n = 50054;
	const std_eset_r = 50055;
	const std_eset_i = 50056;

	const std_box_key = 203009;

	const txt_limit_lv = 10200;
	const txt_limit_attr = 10201;
	const txt_limit_wtype = 10202;
}
?>