<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Sega\AppBundle\Controller;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Logic\GameData as GameData;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;
use \Logic\Util\DevParam as DevParam;
use \Dcs\Arpg\Time as Time;

class BBSController extends \Dcs\DcsController{

	const PRESET_NUM = 30;
	/**
	 * プリセットメッセージを取得
	 * data: セッションキー
	 * RPC構造
	 * data:[string]
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getPresetMessageAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			$uid = $user->getUid();
			$dat = [];
			for($i=0;$i<self::PRESET_NUM;++$i){
				$dat[]='';
			}
			$rs = $this->selectHsCache(
				new Table('chat_preset_message',['id','mes']),
				new Query(['>='=>0],-1)
			);
			$max = 0;
			foreach($rs as $row){
				$i = intval($row[0]);
				if($i >= 0 && $i < self::PRESET_NUM){
					$dat[$i] = $row[1];
					if($i > $max)
						$max = $i;
				}
			}
			$rs = $this->getHs()->select(
					new Table('box_preset_message',['num','mes']),
					new Query(['='=>$uid],-1)
			);
			foreach($rs as $row){
				$i = intval($row[0]);
				if($i >= 0 && $i < self::PRESET_NUM){
					$dat[$i] = $row[1];
					if($i > $max)
						$max = $i;
				}
			}
			for($i=0;$i<self::PRESET_NUM;++$i){
				if(isset($dat[$i])) continue;
				$dat[$i] = '';
			}
			return $dat;
		});
	}
	/**
	 * プリセットメッセージ設定
	 * data: [ skey=>セッションキー, iid=>メッセージインデックス, msg=>メッセージ文字列
	 * RPC構造
	 * data:null
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function setPresetMessageAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$mes = $data['msgs'];

			if(empty($mes)) return 1;

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$Text = $this->get('Arpg.Logic.Util.Text');

			$sql = null;
			$arg = [];
			foreach($mes as $d){
				if($sql == null)
					$sql = 'insert into box_preset_message (uid,num,mes) values(?,?,?)';
				else
					$sql .= ',(?,?,?)';
				$arg[] = $uid;
				$arg[] = intval($d['id']);
				$arg[] = $d['msg'];
				if($Text->checkNg($d['msg']))
					throw new ResError('setting preset word is ng',301);
			}
			if($sql != null){
				$sql .= ' on duplicate key update mes = values(mes)';
				$this->useTransaction();
				$this->sql('box_preset_message',$sql)->insert($arg);
			}
			return 1;
		});
	}

	/**
	 * オートワード取得
	 * リクエストデータ構造
	 * SessionKey
	 * レスポンスデータ構造
	 * array[GameData.AutoWord]
	 * @param unknown $data
	 * @return \Symfony\Component\HttpFoundation\Response|multitype:string unknown
	 */
	public function getAutoWordAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			$uid = $user->getUid();
			$dat = [];

			$rs = $this->selectHsCache(
					new Table('chat_auto_word',['id','title','mes']),
					new Query(['>='=>0],-1)
			);
			foreach($rs as $row){
				$i = intval($row[0]);
				if($i >= 0 && $i < self::PRESET_NUM){
					$dat[$i] = [
							'id' => $i,
							'title' => $row[1],
							'msg' => $row[2],
							'toggle' => 1
					];
				}
			}
			$rs = $this->getHs()->select(
					new Table('box_auto_word',['id','mes','enable']),
					new Query(['='=>$uid],-1)
			);

			foreach($rs as $row){
				$i = intval($row[0]);
				if(!isset($dat[$i])) continue;
				$dat[$i]['msg'] = $row[1];
				$dat[$i]['toggle'] = intval($row[2]);
			}

			usort($dat,function($a,$b){
				if($a['id'] == $b['id'])
					return 0;
				return ($a['id'] < $b['id']) ? -1 : 1;
			});

			return $dat;
		});
	}
	/**
	 * オートワード設定
	 * リクエストデータ構造
	 * 		skey=>セッションキー
	 * 		words=>[GameData.AutoWord]
	 * レスポンスデータ構造
	 * 		bool
	 */
	public function setAutoWordAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$words = $data['words'];

			if(empty($words))
				return 1;

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$Text = $this->get('Arpg.Logic.Util.Text');

			$sql = null;
			$arg = [];
			foreach($words as $word){
				if($sql == null)
					$sql = 'insert into box_auto_word (uid,id,mes,`enable`) values(?,?,?,?)';
				else
					$sql .= ',(?,?,?,?)';
				$arg[] = $uid;
				$arg[] = intval($word['id']);
				$arg[] = $word['msg'];
				$arg[] = intval($word['toggle']);
				if($Text->checkNg($word['msg']))
					throw new ResError('setting auto word is ng',301);
			}
			if($sql == null) return 1;
			$sql .= ' on duplicate key update mes = values(mes),`enable` = values(`enable`)';
			$this->useTransaction();
			$this->sql('box_auto_word',$sql)->insert($arg);

			return 1;
		});
	}

	/**
	 * チャットリスト取得
	 * リクエストデータ構造
	 * 		セッションキー
	 * レスポンスデータ構造
	 * 		array <GameData.ChatThread>
	 */
	public function getListAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();

			$max = intval($this->get('Arpg.Logic.Util.DevParam')->param(56));
			
			$public_id = $this->get('Arpg.Logic.Util.PlayerStatus')->getPublicId($uid);
			$ptmt = $this->sql('box_bbs','select bbs_id from box_bbs where public_id = ? order by join_date desc limit '.$max);
			$rs = $ptmt->selectAll([$public_id],\PDO::FETCH_NUM);

			$bbsids = [];
			foreach($rs as $row){
				$bbsids[] = intval($row[0]);
			}
			$BBS = $this->get('gaia.bbs.non_thread_bbs_service');

			$rs = $BBS->getBbsInfo($bbsids);
			$time = new Time();
			usort($rs,function($a,$b) use($time){
				if($a['last_post_time'] == null && $b['last_post_time'] == null)
					return 0;
				if($a['last_post_time'] == null)
					return -1;
				if($b['last_post_time'] == null)
					return 1;
				$at = $time->setMySQLDateTime($a['last_post_time'])->get();
				$bt = $time->setMySQLDateTime($b['last_post_time'])->get();
				if($at == $bt) return 0;
				return ($at < $bt) ? 1 : -1;
			});

			array_splice($rs,$max);

			$bbs_ids = '';
			$count = 0;
			if(empty($rs)){// 空なら抜ける
				return array();
			}
			foreach ($rs as $key => $value) {
				if(!empty($value['board_id'])){
					if($count > 0){
						$bbs_ids .= ',';
					}
					$bbs_ids .= $value['board_id'];
					$count++;
				}
			}

			$qus=[];
			for($i=0,$len=count($rs);$i<$len;++$i){
				$qus[] = new Query(['='=>intval($rs[$i]['board_id'])],-1);
			}
			$mems = $this->getHs()->selectMulti(
					new Table('box_bbs',['public_id'],'BBSID'),
					$qus
			);
			$ret = [];
			for($i=0,$len=count($rs);$i<$len;++$i){
				$row = $rs[$i];
				$bid = intval($row['board_id']);
				$mem = $mems[$i];
				$members = [];
				foreach($mem as $m){
					$members[] = intval($m[0]);
				}
				$ret[] = [
					'threadId' => $bid,
					'time' => $time->setMySQLDateTime($row['last_post_time'])->get(),
					'title' => $row['name'],
					'comment' =>  $row['description'],
					'members' => $members,
					'refreshInterval' => $this->get('Arpg.Logic.Util.DevParam')->param(63),
				];
			}
			return $ret;
		});
	}
	private function getRecordList($tid,$rnm,$puid){
		$BBS = $this->get('gaia.bbs.non_thread_bbs_service');
		$Dparam = $this->get('Arpg.Logic.Util.DevParam');
		$rcd = [];
		try{
			if($rnm > 0){
				$from = new Time();
				$from->set($rnm);
				$from->add(1);
				$to = new Time();
				$to->add(3600*5);
				$rcd = $BBS->getMessagesInPeriod($tid,$from->getDateTime(),$to->getDateTime());
			}else
				$rcd = $BBS->getMessages($tid,$Dparam->param(59));
		}catch(\Exception $e){}

		// 装備武器取得
		$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
		$uids = [];
		foreach($rcd as $rd){
			$pid = intval($rd['user_id']);
			$uids[] = $pid;
		}
		$aids = $Astatus->getActorIdMulti($uids);
		$qus = [];
		foreach($aids as $aid){
			$qus[] = new Query(['='=>$aid]);
		}

		$rss = $this->getHs()->selectMulti(
				new Table('box_actor',['uid','spirit','name']),
				$qus
		);

		$stdid = [];
		$name = [];
		if(!empty($rss)){
			foreach($rss as $rs)foreach($rs as $row){
				$pid = intval($row[0]);
				$stdid[$pid] = intval($row[1]);
				$name[$pid] = $row[2];
			}
		}

		$ret = [];
		$time = new Time();
		for($i=0,$len=count($rcd);$i<$len;++$i){
			$rd = $rcd[$i];
			$pid = intval($rd['user_id']);
			$time->setMySQLDateTime($rd['post_time']);
			$ret[] = [
					'cardStdId'=>$stdid[$pid],
					'playerName'=>$name[$pid],
					'comment' => $rd['message'],
					'time' => $time->get(),
					'isMine' => $puid==$pid?1:0
			];
		}
		return $ret;
	}

	/**
	 * スレッドメッセージリスト取得
	 * リクエストデータ構造
	 * 		tid スレッドID
	 * 		rnm 取得最終レコード
	 * レスポンスデータ構造
	 * 		array <GameData.ChatRecord>
	 */
	public function getThreadAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();

			return $this->getRecordList(intval($data['tid']),intval($data['rnm']),$uid);
		});
	}

	public function createThreadAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$pids = $data['uid'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();
			$pid = $this->get('Arpg.Logic.Util.PlayerStatus')->getPublicId($uid);
			$members = [$pid];
			foreach($pids as $p){
				$p = intval($p);
				if(isset($members[$p]))continue;
				$members[]=$p;
			}

			$puids = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserIds($pids);
			$uids = [$uid];
			foreach($puids as $puid){
				if($puid == $uid) continue;
				$uids[] = $puid;
			}
			$title = $this->createChatTitle($uids);
			$info='';

			$BBS = $this->get('gaia.bbs.non_thread_bbs_service');
			$tid = $BBS->createBbs($title,$info);
			$this->addChatMember($tid,$members);
			$time = new Time();
			return [
					'threadId' => $tid,
					'time' => $time->get(),
					'title' => $title,
					'comment' => $info,
					'members' => $members,
					'refreshInterval' => $this->get('Arpg.Logic.Util.DevParam')->param(63),
			];
		});
	}
	private function addChatMember($tid,$public_ids){
		// ユーザー追加
		$sql = null;
		$args = [];
		$now = new Time();
		foreach($public_ids as $pid){
			if($sql == null)
				$sql = 'insert ignore into box_bbs(public_id,bbs_id,join_date) values(?,?,?)';
			else
				$sql .= ',(?,?,?)';
			$args[] = $pid;
			$args[] = $tid;
			$args[] = $now->getMySQLDateTime();
		}
		$this->useTransaction();
		$this->sql('box_bbs',$sql)->insert($args);
	}
	public function addChatMemberAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$tid = intval($data['tid']);
			$pids = $data['uid'];
			if(empty($pids))
				return true;

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);


			$rs = $this->getHs()->select(
					new Table('box_bbs',['public_id'],'BBSID'),
					new Query(['='=>$tid],-1)
			);

			for($i=0,$len=count($pids);$i<$len;++$i){
				$pid = intval($pids[$i]);
				$pids[$i] = $pid;
			}

			$this->addChatMember($tid,$pids);

			foreach($rs as $row){
				$pid = intval($row[0]);
				if(in_array($pid,$pids)) continue;
				$pids[] = $pid;
			}
			$puids = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserIds($pids);
			$title = $this->createChatTitle($puids);

			$this->updateBbsDate($tid);
			$BBS = $this->get('gaia.bbs.non_thread_bbs_service');
			return $BBS->updateBoard($tid,['name'=>$title]) != 0;
		});
	}
	public function exitChatAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$tid = intval($data['tid']);

			$BBS = $this->get('gaia.bbs.non_thread_bbs_service');
			$Text = $this->get('Arpg.Logic.Util.Text');
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			$puid = $Pstatus->getPublicId($uid);
			$sql = 'delete from box_bbs where bbs_id = ? and public_id = ?';
			$args = [$tid,$puid];

			$rs = $this->getHs()->select(
					new Table('box_bbs',['public_id'],'BBSID'),
					new Query(['='=>$tid],-1)
			);

			$puids = [];
			foreach($rs as $row){
				$puids[] = intval($row[0]);
			}
			$uids = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserIds($puids);
			$names = $this->userNames($uids);

			$this->useTransaction();
			$title = null;
			$now = new Time();
			foreach($names as $id => $name){
				if($id == $uid){
					$BBS->postMessage($uid,$tid,$Text->getText(1707,['[player]'=>$name]),$now->getDateTime());
				}else{
					if($title == null)
						$title = $name;
					else
						$title .= ','.$name;
				}
			}
			$this->sql('box_bbs',$sql)->delete($args);
			if($title == null)
				$title = '';

			return $BBS->updateBoard($tid,['name'=>$title]) != 0;
		});
	}
	public function postChatAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$tid = intval($data['tid']);
			$msg = $data['msg'];
			$rnm = intval($data['rnm']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();

			if($this->get('Arpg.Logic.Util.Text')->checkNg($msg))
				throw new ResError('post chat word is ng',301);

			$now = new Time();
			$BBS = $this->get('gaia.bbs.non_thread_bbs_service');
			$BBS->postMessage($uid,$tid,$msg,$now->getDateTime());
			$BBS->updateBoard($tid,['description'=>$msg]);
			
			$this->updateBbsDate($tid);
			
			return $this->getRecordList($tid,$rnm,$uid);
		});
	}
	private function updateBbsDate($tid){
		$now = new Time();
		$stmt = $this->sql('box_bbs','update box_bbs set join_date = ? where bbs_id = ?');
		$stmt->update([$now->getMySQLDateTime(),$tid]);
	}
	private function createChatTitle($uids){
		$names = $this->userNames($uids);
		$ret = null;
		usort($names,function($a,$b){
			return strcmp($a,$b);
		});
		foreach($names as $name){
			if($ret == null)
				$ret = $name;
			else
				$ret .= ','.$name;
		}
		return $ret;
	}
	private function userNames($uids){
		$qus = [];
		$aids = $this->get('Arpg.Logic.Util.ActorStatus')->getActorIdMulti($uids);
		foreach($aids as $aid){
			$qus[] = new Query(['='=>intval($aid)]);
		}
		if(!empty($qus)){
			$rss = $this->getHs()->selectMulti(
					new Table('box_actor',['name','uid']),
					$qus
			);
			$dat = [];
			foreach($rss as $rs)foreach($rs as $row){
				$dat[intval($row[1])] = $row[0];
			}
			return $dat;
		}
		return [];
	}
}
?>