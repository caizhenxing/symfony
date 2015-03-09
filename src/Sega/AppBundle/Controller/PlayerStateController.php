<?php

namespace Sega\AppBundle\Controller;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Security as sec;
use \Dcs\Cache as Cache;
use \Dcs\Arpg\ResError as ResError;
use \Logic\Util\Equip as Equip;
use \Logic\Util\GameParam as GameParam;
use \Logic\CardData as CardData;
use \Logic\PlayerData\Mission as Mission;
use \Dcs\Arpg\Time as Time;
use \Logic\Util\Mail as UMail;
use \Dcs\DetailTimeLog as DTL;
use \Logic\Util\StackItem;

class PlayerStateController extends \Dcs\DcsController{
	/**
	 * メールアドレス設定
	 *
	 * リクエストデータ構造
	 *	[
	 *		'skey' => セッションキー
	 *		'mail' => メールアドレス
	 *	]
	 * レスポンスデータ
	 * 		bool
	 */
	public function setMailAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$mail = $data['mail'];

			if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $mail)){
				throw new ResError("invalid mail $mail",310);
			}
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV1);
			$uid = $user->getUid();

			$this->useTransaction();
			$this->sql('box_player','update box_player set mail=? where uid = ?')->update([$mail,$uid]);
			return true;
		});
	}
	/**
	 * CP履歴用の文字列を取得する
	 *
	 * リクエストデータ構造
	 *		skey
	 * レスポンスデータ
	 * 		string
	 */
	public function getCpAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();

			$os_cp = self::std_android_cp;
			$header = getallheaders();
			if(isset($header['SGNOsType']) && intval($header['SGNOsType']) == 1)
				$os_cp = self::std_apple_cp;

			$Text = $this->get('Arpg.Logic.Util.Text');
			$dec = $Text->getText(5);
			$tho = $Text->getText(6);
			$pstate = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatusMulti($uid,[self::std_cp,$os_cp]);
			$buycp = number_format($pstate[$os_cp],0,$dec,$tho);
			$getcp = number_format($pstate[self::std_cp]-$pstate[$os_cp],0,$dec,$tho);
			return $Text->getText(10610,['[buycp]'=>$buycp,'[getcp]'=>$getcp]);
		});
	}
	/**
	 * キャラクリに必要なモデルを生成する
	 *
	 * リクエストデータ構造
	 *		null
	 * レスポンスデータ
	 * 		List<string>
	 */
	public function charaCreateDataAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$key = 'Arpg.Controller.PlayerStateController.charaCreateDataAction';
			$dat = $this->cache()->get(Cache::TYPE_APC,$key);
			if($dat == null){
				// キャラ栗データ
				$collect = [];
				// 体データ
				for($i=0;$i<6;++$i){
					$collect["bod000_a00_m_$i"] = true;
					$collect["bod000_a00_w_$i"] = true;
				}
				// 目データ
				for($i=0;$i<8;++$i){
					$collect["eye001_a00_m_$i"] = true;
					$collect["eye001_a00_w_$i"] = true;
				}
				$rs = $this->getHs(false)->select(
							new Table('actor_create_model',['model']),
							new Query(['>'=>0],-1)
				);
				$qus1 = [];
				$qus2 = [];
				foreach($rs as $row){
					$std_id =  intval($row[0]);
					if(300000 <= $std_id && $std_id < 400000){
						$qus1[] = new Query(['='=>$std_id]);
					}
					if(600000 <= $std_id && $std_id < 604000){
						$qus2[] = new Query(['='=>$std_id]);
					}
				}
				$rss = $this->getHs(false)->selectMulti(
						new Table('equip_model',['model_m','model_w','texture_m','texture_w']),
						$qus1
				);
				foreach($rss as $rs)foreach($rs as $row)for($i=0;$i<4;++$i){
					if(strlen($row[$i]) > 1){
						$collect[$row[$i]] = true;
					}
				}
				$rss = $this->getHs(false)->selectMulti(
						new Table('actor_model',['std_id','model']),
						$qus2
				);
				foreach($rss as $rs)foreach($rs as $row){
					$std_id = intval($row[0]);
					$model = $row[1];
					if($std_id < 600000){
						continue;
					}elseif ($std_id < 602000){
						$collect[$model.'_all'] = true;
						for($i=0;$i<8;++$i){
							$collect[$model.'_'.$i] = true;
						}
					}elseif ($std_id < 604000){
						$collect[$model] = true;
						for($i=0;$i<6;++$i){
							$collect[$model.'_'.$i] = true;
						}
					}
				}
				$dat = [];
				foreach($collect as $key => $val){
					$dat[] = $key;
				}
				$this->cache()->set(Cache::TYPE_APC,$key,$dat);
			}
			return $dat;
		});
	}

	/**
	 * ステート設定を取得
	 * リクエストデータ構造
	 * data: string //
	 * RPC構造
	 * data:	Arpg.Logic.PlayerData.Stateコンテナ
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$dat = $this->get('Arpg.Logic.PlayerData.State');
			$dat->init($uid);
			return $dat;
		});
	}


	/**
	 * 基礎情報設定を取得
	 * リクエストデータ構造
	 * data: {mSid:string セッションID}
	 * RPC構造
	 * data: Arpg.Logic.PlayerData.Basicコンテナ
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getBasicAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$dat = $this->get('Arpg.Logic.PlayerData.Basic');
			$dat->init($user->getUid());

			$caid = $dat->getCurrentActor();
			if($caid > 0){
				$this->get('Arpg.Logic.Util.ActorStatus')->login($user->getUid(),$caid);
			}
			return $dat;
		});
	}

	/**
	 * アクターを取得
	 *
	 * リクエストデータ構造
	 *		セッションキー
	 * レスポンスデータ
	 * 		Arpg.Logic.PlayerData.Actorコンテナ
	 */
	public function getActorAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$aid = $this->get('Arpg.Logic.Util.ActorStatus')->getActorId($uid);
			$rs = $this->getHs()->select(
					new Table('box_actor', array('actor_id','state')),
					new Query(array('=' => $aid))
			);
			foreach($rs as $row){
				if(intval($row[1]) != 0) break;
				$act = $this->get('Arpg.Logic.PlayerData.Actor');
				$act->initByAid(intval($row[0]),$uid);
				return $act;
			}
			return null;
		});
	}

	/**
	 * アクターリストを取得
	 *
	 * リクエストデータ構造
	 *		セッションキー
	 *
	 * レスポンスデータ
	 * array: [Arpg.Logic.PlayerData.Actorコンテナ]
	 */
	public function getActorListAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();

			$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');

			$ps = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatusMulti($uid,[self::player_level,self::std_equip_set]);
			$set_size = $ps[self::std_equip_set];
			if($set_size < 1)$set_size=1;
			$rs = $this->getHs()->select(
					new Table('box_actor', ['actor_id','name'],'UID'),
					new Query(['=' => [$uid,0]],-1)
			);
			$names = [];
			$qus = [];
			$param = [];
			foreach($rs as $row){
				$aid = intval($row[0]);
				$names[$aid] = $row[1];

				$param[] = [$aid,self::std_eset];
				for($i=0;$i<$set_size;++$i){
					$param[] = [$aid,self::std_eset_w+$i*10];
					$param[] = [$aid,self::std_eset_h+$i*10];
					$param[] = [$aid,self::std_eset_c+$i*10];
					$param[] = [$aid,self::std_eset_n+$i*10];
					$param[] = [$aid,self::std_eset_r+$i*10];
				}
			}

			$ass = $Astatus->getStatusMultiActor($param);
			$equiped = [];
			foreach($ass as $aid => $as){
				$eset = $as[self::std_eset];
				$eq = [];
				$eq[] = $as[self::std_eset_w+$eset*10];
				$eq[] = $as[self::std_eset_h+$eset*10];
				$eq[] = $as[self::std_eset_c+$eset*10];
				$eq[] = $as[self::std_eset_n+$eset*10];
				$eq[] = $as[self::std_eset_r+$eset*10];
				$equiped[$aid] = $eq;
				$qus[] = new Query(['='=>[$as[self::std_eset_w+$eset*10],0]]);
				$qus[] = new Query(['='=>[$as[self::std_eset_h+$eset*10],0]]);
				$qus[] = new Query(['='=>[$as[self::std_eset_c+$eset*10],0]]);
				$qus[] = new Query(['='=>[$as[self::std_eset_n+$eset*10],0]]);
				$qus[] = new Query(['='=>[$as[self::std_eset_r+$eset*10],0]]);
			}
			$cards=[];
			if(!empty($qus)){
				$rss = $this->getHs()->selectMulti(
						new Table(CardData::DBTBL,CardData::$CLMS,'IS'),
						$qus
				);
				foreach($rss as $rs)foreach($rs as $row){
					$buf = $this->get('Arpg.Logic.CardData');
					$buf->init($row);
					$cards[$buf->id] = $buf;
				}
			}
			$plv = $ps[self::player_level];

			$dat = [];
			foreach($names as $aid => $name){
				$clist = [];
				foreach($equiped[$aid] as $id){
					if(isset($cards[$id]))
						$clist[] = $cards[$id];
				}
				$act = $this->get('Arpg.Logic.PlayerData.ActorDetail');
				$act->init($aid,$name,$plv,$clist);
				$dat[] = $act;
			}
			return $dat;
		});
	}
	/**
	 * アクター変更
	 *
	 * リクエストデータ構造
	 *		[
	 *			skey : セッションキー,
	 *			aid : アクターID
	 * 		]
	 * レスポンスデータ
	 * 		bool
	 */
	public function setCurrentActorAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$aid = intval($data['aid']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			if($this->get('Arpg.Logic.Util.ActorStatus')->login($user->getUid(),$aid)){
				return true;
			}
			throw new ResError("dont login aid $aid",100);
		});
	}


	/**
	 * 名前変更を取得
	 * リクエストデータ構造
	 * data: {skey:{mSid:string セッションID},name:string}
	 * RPC構造
	 * data:{Arpg.Logic.PlayerData.Basic}
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function renameAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$name = $data['name'];

			if(mb_strlen($name) < 2 || 16 < mb_strlen($name)){
				throw new ResError('invalid name.',100);
			}else{
				$user = $this->createCmnAccount();
				$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV1);

				$rs = $this->getHs()->select(
						new Table('box_player',['uid'],'name_unique'),
						new Query(['='=>$name])
				);
				if(!empty($rs)){
					throw new ResError('already use name.',100);
				}else{
					$this->useTransaction();

					if($this->sql('box_player','update box_player set `name`=?,`rename`=0 where uid=? and `rename`>0')->update([$name,$user->getUid()]) > 0){
						$uid = $user->getUid();
						$dat = $this->get('Arpg.Logic.PlayerData.Basic');
						$dat->init($uid);

						return $dat;
					}else{
						throw new ResError('already set your name.',100);
					}
				}
			}
		});
	}



	/**
	 * ホーム画面公式データ
	 * RPC構造
	 * data:{Arpg.Logic.PlayerData.HomePub}
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getHomePubAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$dat = $this->get('Arpg.Logic.PlayerData.HomePub');
			$dat->init();

			return $dat;
		});
	}

	/**
	 * ホーム画面ポップアップ
	 * リクエストデータ構造
	 * data: セッションID
	 * レスポンスデータ構造
	 * Arpg.Logic.PlayerData.HomePopup
	 */
	public function getHomePopupAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('getHomePopupAction start');
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$puid = -1;
			$last = -1;
			if(isset($data['uid']))
				$puid = intval($data['uid']);
			if(isset($data['last']))
				$last = intval($data['last']);
			DTL::Lap('make param');

			$popup =  $this->get('Arpg.Logic.PlayerData.HomePopup');
			if($puid < 0 || $last < 0){
				$popup->reloadTime = $this->get('Arpg.Logic.Util.DevParam')->param(52);
				$popup->action = 0;
				return $popup;
			}
			return $popup->receive($puid,$last);
		});
	}

	/**
	 * ホーム情報
	 * リクエストデータ構造
	 * data: {mSid:string セッションID}
	 * RPC構造
	 * data:{Arpg.Logic.PlayerData.Home}
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getHomeAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$dat = $this->get('Arpg.Logic.PlayerData.Home');
			$dat->init($user->getUid());

			return $dat;
		});
	}

	/**
	 * ホームバッチ情報
	 * リクエストデータ構造
	 * data: {mSid:string セッションID}
	 * レスポンスデータ構造
	 * PlayerData.HomeBatch
	 */
	public function getHomeBatchAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$puid = $this->get('Arpg.Logic.Util.PlayerStatus')->getPublicId($uid);

			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			$Stack = $this->get('Arpg.Logic.Util.StackItem');

			$now = new Time();
			$now_sql = $now->getMySQLDateTime();
			$now = $now->get();
			$time = new Time();

			// プレゼント
			$nb_present = $this->get('gaia_present_service')->getPresentCount($uid);


			// メッセージ
			$stmt = $this->sql('box_mail','select id from box_mail where uid=? and state=0 and end_date > ?');
			$stmt->select([$uid,$now_sql]);
			$nb_mes = 0;
			while($row = $stmt->fetch(\PDO::FETCH_NUM)){
				++$nb_mes;
			}
			$rs = $this->getHs()->select(
					new Table('mail_all',['id','send_date'],'END'),
					new Query(['>'=>$now_sql],-1)
			);
			$qus=[];
			foreach($rs as $row){
				$time->setMySQLDateTime($row[1]);
				if($now < $time->get()) continue; // 未送信
				$qus[]=new Query(['='=>[$uid,intval($row[0])]]);
			}
			if(!empty($qus)){
				$rss = $this->getHs()->selectMulti(
						new Table('box_mail_all',['state']),
						$qus
				);
				$size = 0;
				foreach($rss as $rs)foreach($rs as $row){
					++$size;
					if(intval($row[0]) == UMail::STATE_NEW)
						++$nb_mes;
				}
				$nb_mes += count($qus) - $size;
			}

			// ガチャ
			$is_gacha=false;
			$rs = $this->get('Arpg.Logic.GachaData.Banner')->getList();
			$pstate = [];
			$stack = [];
			$pnum = [];
			$snum = [];
			foreach($rs as $d){
				if($now < $d->from || $d->to < $now) continue;
				$std_id = $d->costStdID;

				if($Pstatus->check($std_id) && (!isset($pstate[$std_id]) || $pstate[$std_id] > $d->costValue) && $std_id != self::std_cp){
					$pstate[$std_id] = $d->costValue;
					$pnum[] = $std_id;
				}
				if($Stack->check($std_id) && (!isset($stack[$std_id]) || $stack[$std_id] > $d->costValue)){
					$stack[$std_id] = $d->costValue;
					$snum[] = $std_id;
				}
			}
			if(!$is_gacha){
				$pnum = $Pstatus->getStatusMulti($uid,$pnum);
				foreach($pstate as $std_id => $cost){
					$is_gacha = $pnum[$std_id] >= $cost;
					if($is_gacha)break;
				}
			}
			if(!$is_gacha){
				$snum = $Stack->getNumMulti($uid,$snum);
				foreach($stack as $std_id => $cost){
					$is_gacha = $snum[$std_id] >= $cost;
					if($is_gacha)break;
				}
			}


			// フレンド
			$fids = $this->get('gaia.friend.friend_offer_service')->offeredIds($uid);
			$is_friend=!empty($fids);

			// 最終書き込み時間
			$last_bbs=0;
			$rs = $this->getHs()->select(
					new Table('box_bbs',['bbs_id']),
					new Query(['='=>$puid],-1)
			);
			$bbsids = [];
			foreach($rs as $row){
				$bbsids[] = intval($row[0]);
			}
			$rs = $this->get('gaia.bbs.non_thread_bbs_service')->getBbsInfo($bbsids);
			$time=new Time();
			foreach($rs as $row){
				if($row['last_post_time'] == null) continue;
				$ltime = $time->setMySQLDateTime($row['last_post_time'])->get();
				if($last_bbs < $ltime)
					$last_bbs = $ltime;
			}


			// ミッション
			$is_mission=false;
			$Mission = $this->get('Arpg.Logic.Util.Mission');
			$list = $Mission->getList($uid,$Mission->getCleared($uid),$now);
			foreach($list as $line){
				if($line->stat == Mission::STATE_CLEAR){
					$is_mission = true;
					break;
				}
			}

			return [
					'nbPresent'=>$nb_present,
					'nbMessage'=>$nb_mes,
					'isGacha'=>$is_gacha,
					'isFrined'=>$is_friend,
					'isMission'=>$is_mission,
					'lastBbsTime'=>$last_bbs,
					'reloadTime'=>$this->get('Arpg.Logic.Util.DevParam')->param(51),
			];
		});
	}
	/**
	 * ゴースト用アクターデータの取得
	 * リクエストデータ構造
	 * data: int actor_id
	 * RPC構造
	 * data:[Arpg.Logic.PlayerData.Actor]
	 * err: {
	 * 		code: 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getGhostDataAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$aid = intval(json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true));
			$actor = $this->get('Arpg.Logic.PlayerData.Actor');
			$actor->initByAid($aid);
			return $actor;
		});
	}

	public function expandeWarehouseAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();
			$Gparam = $this->get('Arpg.Logic.Util.GameParam');
			$Dparam = $this->get('Arpg.Logic.Util.DevParam');
			$Stock = $this->get('Arpg.Logic.Util.StackItem');
			$Pstate = $this->get('Arpg.Logic.Util.PlayerStatus');
			$ps = $Pstate->getStatusMulti($uid,[self::std_cp,self::std_warehouse]);

			if($ps[self::std_warehouse] >= $Dparam->param(77))
				throw new ResError('cant expande warehouse. warehouse size is max.',100);
			$Pstate->add($uid, self::std_warehouse, $Dparam->param(79));
			$new_size = $ps[self::std_warehouse]+$Dparam->param(79);

			$dat = null;
			$std_id = 0;
			$num = 0;
			if($dat == null){
				$std_id = self::std_wext_ticket;
				$num = $Stock->getNum($uid,$std_id);
				try{
					$Stock->add($uid,self::std_wext_ticket,-1);
					$num-=1;
					$dat=[
						'value'=>$new_size,
						'costStdId'=>$std_id,
						'costValue'=>$num
					];
				}catch(\Exception $e){
					// チケットなし
				}
			}
			if($dat == null){
				$std_id = self::std_cp;
				$num = $ps[$std_id];
				try{
					$Pstate->add($uid,self::std_cp,-$Gparam->getParam(GameParam::EXT_CARD_CP));
					$num-=$Gparam->getParam(GameParam::EXT_CARD_CP);
					$dat=[
						'value'=>$new_size,
						'costStdId'=>$std_id,
						'costValue'=>$num
					];
				}catch(\Exception $e){
					// チケットなし
				}
			}
			if($dat==null)
				throw new ResError('too low item.',100);
			 return $dat;
		});
	}

	/**
	 * ログインボーナス
	 * リクエストデータ構造
	 * data: セッションキー
	 * レスポンスデータ構造
	 * PlayerData.LoginBonus
	 */
	public function loginBonusAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();

			$ret = $this->get('Arpg.Logic.PlayerData.LoginBonus');
			if($ret->init($uid))
				return $ret;
			return null;
		});
	}

	/**
	 * チュートリアルステップ書き込み
	 * @param unknown $data
	 * @return \Symfony\Component\HttpFoundation\Response|object
	 */
	public function setTutorialStepAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$tag = intval($data['tag']);
			$step = intval($data['step']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$this->sql('box_tutorial','insert into box_tutorial (uid,tag,step) value(?,?,?) on duplicate key update step = values(step)')->insert([$uid,$tag,$step]);
			return true;
		});
	}

	/**
	 * ユーザーコメント設定
	 */
	public function setCommentAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$mes = $data['mes'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();

			$this->useTransaction();
			$this->sql('box_player','update box_player set message = ? where uid = ? ')->update([$mes,$uid]);

			return true;
		});
	}


	private function checkNgName($name){
		if(count($name) < 1) return false;
		$list = ['[',']',' ','　',"\t"];

		foreach($list as $ng){
			if(mb_stripos($name,$ng) === false) continue;
			return true;
		}
		return false;
	}
	/**
	 * アクター新規生成
	 */
	public function newActorAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('newActorAction start');
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$name = $data['name'];
			$gender = intval($data['gender']);
			$ftype = intval($data['faceType']);
			$htype = intval($data['hairStyle']);
			$bcolor = intval($data['bodyColor']);
			$ecolor = intval($data['eyeColor']);
			$hcolor = intval($data['hairColor']);


			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();

			DTL::Lap('check Login');


			$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
			$Stack = $this->get('Arpg.Logic.Util.StackItem');
			$Equip = $this->get('Arpg.Logic.Util.Equip');
			$Present = $this->get('Arpg.Logic.Util.Present');
			$Text = $this->get('Arpg.Logic.Util.Text');
			$Gparam = $this->get('Arpg.Logic.Util.GameParam');
			$Gacha = $this->get('Arpg.Logic.Util.Gacha');
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');

			if($this->checkNgName($name) || $Text->checkNg($name)){
				throw new ResError("ng word name[$name]",301);
			}


			$max_actor = $this->get('Arpg.Logic.Util.DevParam')->param(50);
			$num = count($this->getHs()->select(
					new Table('box_actor',['actor_id'],'UID'),
					new Query(['='=>[$uid,0]],-1)
			));
			if($max_actor < $num)
				throw new ResError('create actor overflow',100);


			$set_size = $Pstatus->getStatus($uid,self::std_equip_set);

			if($set_size < 1)
				$set_size = 1;

			if($num > 0){
				try{
					$Stack->add($uid,self::std_chara_create_ticket,-1);
				}catch(\Exception $e){
					// チケットなしなのでCP消費
					try{
						$Pstatus->add($uid,self::std_cp,$this->get('Arpg.Logic.Util.GameParam')->getParam(-GameParam::CHARA_CREATE_CP));
					}catch(\Exception $e){
						throw new ResError('create actor no ticket no cp',100);
					}
				}
			}

			DTL::Lap('check status');

			// 新規アクター追加
			$now = new Time();
			$this->useTransaction();
			$aid = intval($this->sql('box_actor','insert ignore into box_actor (uid,name,name_find,update_date) values(?,?,?,?)')->insert([$uid,$name,$Text->convertFindText($name),$now->getMySQLDateTime()]));
			$Astatus->login($uid,$aid);


			DTL::Lap('add Actor');

			// アクターステータス生成
			$rs = $Astatus->getInitStatus();
			$has_weap = false;
			$weapon = $Gacha->drawByFuncMulti($uid,[$Gparam->getParam(GameParam::TUTORIAL_GACHA)],true);
			if(!empty($weapon)){
				$weapon = $weapon[0];
				$has_weap = true;
			}else{
				$weapon = null;
			}

			$alist=[];
			$slist=[];
			$elist=[];
			$equip=[];
			$plist=[];
			$collection = [];
			$set_item = [];
			foreach($rs as $std_id => $num){
				$collection[] =$std_id;
				if($Astatus->check($std_id)){
					$alist[]=[$aid,$std_id,$num];
				}elseif($Stack->check($std_id)){
					$type = $Stack->std2type($std_id);
					if($num > 0){
						$slist[]=[$uid,$std_id,$num];
						if($type == StackItem::TYPE_DRAG || $type == StackItem::TYPE_MDRAG){
								$set_item[] = $std_id;
						}
					}
				}elseif($Equip->check($std_id)){
					for($i=0;$i<intval($num);++$i){
						if($Equip->std2type($std_id) == Equip::TYPE_WEAPON){
							if($has_weap)
								continue;
							$has_weap = true;
						}

						$elist[]=[$std_id,$Equip::STATE_HAS];
					}
				}
			}

			DTL::Lap('make actor status');

			// アバタ―情報
			$alist[]=[$aid,50023,$gender];
			$alist[]=[$aid,50024,$htype];
			$alist[]=[$aid,50025,$hcolor];
			$alist[]=[$aid,50026,$bcolor];
			$alist[]=[$aid,50027,$ftype];
			$alist[]=[$aid,50028,$ecolor];

			$this->get('Arpg.Logic.Util.Collection')->createMulti($uid,$collection);

			$Stack->createMulti($slist);
			$Astatus->createMulti($alist);

			$equip=$Equip->addMulti($uid,$elist);
			if($weapon != null){
				$equip[] = $weapon->card[0]->id;
			}
			usort($set_item,function($a,$b){
				if($a == $b){
					return 0;
				}
				return ($a < $b) ? -1 : 1;
			});

			$set_list=[['sid' => 0,'eq' => $equip,'it' => $set_item]];
			$Equip->equipByActorID($uid,$aid,0,$set_list);

			for($i=1;$i<$set_size;++$i){
				$set_list[]=['sid' => $i,'eq' => [0,0,0,0,0],'it' => [0,0,0]];
			}

			$act = $this->get('Arpg.Logic.PlayerData.Actor');
			$act->initByAid($aid,$uid);

			DTL::Lap('make avator');

			// Reward生成
			$sql = null;
			$args = [];
			foreach($equip as $id){
				if($sql == null)
					$sql = CardData::DBSQLCORE . ' force index(`IS`) where state=0 and id in (?';
				else
					$sql .= ',?';
				$args[] = $id;
			}
			$stmt = $this->sql(CardData::DBTBL,$sql.')');
			$stmt->select($args);
			$reward = [];
			while($row = $stmt->fetch(\PDO::FETCH_NUM)){
				$c = $this->get('Arpg.Logic.CardData');
				$c->init($row);
				$r = $this->get('Arpg.Logic.GameData.Reward');
				$r->initByCard([$c]);
				$reward[] = $r;
			}
			foreach($slist as $sline){
				$r = $this->get('Arpg.Logic.GameData.Reward');
				$r->init($sline[1],$sline[2]);
				$reward[] = $r;
			}

			DTL::Lap('add reward');

			return ['actor'=>$act,'reward'=>$reward,'equip'=>[
					'actor_id' =>$aid,
					'sid' => 0,
					'sheet'=>$set_list
			]];
		});
	}
	/**
	 * アクター削除
	 */
	public function deleteActorAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$aid = intval($data['aid']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();
			$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
			$current_aid = $Astatus->getActorId($uid);

			$rs = $this->getHs()->select(
					new Table('box_actor',['actor_id'],'UID'),
					new Query(['='=>[$uid,0]],-1)
			);
			$num = count($rs);
			if($num == 1)
				throw new ResError('dont delete last actor',100);

			$now = new Time();
			$now = $now->getMySQLDateTime();
			$this->useTransaction();
			$this->sql('box_actor','update box_actor set state=1,update_date=? where uid=? and actor_id=? and state=0')->update([$now,$uid,$aid]);

			if($current_aid == $aid){
				$min = -1;
				foreach($rs as $row){
					$caid = intval($row[0]);
					if($min < 0 || $min > $caid)
						$min = $caid;
				}
				if($min > 0)
					$Astatus->login($uid,$min);
			}

			return true;
		});
	}
	public function recoverStaminaAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();

			$Stack = $this->get('Arpg.Logic.Util.StackItem');
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');

			$pst = $Pstatus->getStatusMulti($uid,[self::stp_time,self::player_level]);
			$now = new Time();
			$now = $now->get();
			$lvd = $Pstatus->getLvData($pst[self::player_level]);
			$shs = $this->get('Arpg.Logic.Util.GameParam')->getParam(GameParam::STP_HEAL_SEC);
			if($shs < 1) $shs = 1;
			$nowstp = intval(($now - $pst[self::stp_time]) / $shs);
			$max_stp = $lvd['stp'];
			if($nowstp >= $max_stp)
				throw new ResError('already recover stamina',100);

			try{
				$Stack->add($uid,self::std_stamina_drag,-1);
			}catch(\Exception $e){
				$use_cp = $this->get('Arpg.Logic.Util.GameParam')->getParam(GameParam::CURE_STP_CP);
				try{
					$Pstatus->add($uid,self::std_cp,-$use_cp);
				}catch(\Exception $e){
					throw new ResError('no drag no cp',100);
				}
			}

			// 全快
			$Pstatus->set($uid,self::stp_time,0);
			return true;
		});
	}

	/**
	 * ブーストアイテム使用
	 * リクエストデータ構造
	 * [
	 * 		'skey' => セッションキー
	 * 		'iid' => アイテムSTD_ID
	 * ]
	 * レスポンスデータ構造
	 * PlayerData.State
	 */
	public function useBoostAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$iid = intval($data['iid']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();

			$Stack = $this->get('Arpg.Logic.Util.StackItem');
			if($Stack->std2type($iid) != \Logic\Util\StackItem::TYPE_BOOST)
				return new ResError("$iid is not boost item",100);

			$ret = $this->get('Arpg.Logic.PlayerData.State');
			$ret->init($uid);
			$bst = $Stack->boostData($iid);
			$time = new Time();
			$time->add($bst['time']);
			$ret->boostTitle = $bst['name'];
			$ret->boostTime = $time->get();
			$this->get('Arpg.Logic.Util.PlayerStatus')->setMulti([
					[$uid,self::std_use_boost, $iid],
					[$uid,self::std_use_boost_limit,$time->get()]
			]);
			$Stack->add($uid,$iid,-1);

			return $ret;
		});
	}

	const std_use_boost = 300;
	const std_use_boost_limit = 301;
	const std_tutorial = 200;
	const std_warehouse = 7;
	const std_money		= 10000;
	const std_cp		= 10001;
	const std_apple_cp = 10010;
	const std_android_cp = 10011;
	const std_wext_ticket= 203004;
	const std_chara_create_ticket = 203006;
	const stp_time = 3;
	const player_level = 1;
	const std_stamina_drag = 203005;
	const std_equip_set = 6;

	const std_eset = 50050;
	const std_eset_w = 50051;
	const std_eset_h = 50052;
	const std_eset_c = 50053;
	const std_eset_n = 50054;
	const std_eset_r = 50055;
	const std_eset_i = 50056;

}
?>