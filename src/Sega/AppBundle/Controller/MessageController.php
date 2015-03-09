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
use \Logic\MessageData\Head as MHead;
use \Logic\MessageData\Body as MBody;
use \Logic\Util\Mail as UMail;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\Arpg\Time as Time;

class MessageController extends \Dcs\DcsController{

	/**
	 * メッセージヘッダを取得
	 * リクエストデータ構造
	 * 	skey
	 * レスポンスデータ構造
	 * 	[Arpg.Logic.MessageData.Head]
	 */
	public function getListAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
				
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
		
			$ret = [];
			$now = new \Dcs\Arpg\Time();
			$now = $now->getMySQLDateTime();
			// 個人送信
			$stmt = $this->sql(MHead::HS_TBL, MHead::SQL.' where uid=? and state in (0,1,2) and end_date > ?');
			$stmt->select([$uid,$now]);
			while($row = $stmt->fetch(\PDO::FETCH_NUM)){
				$dat = $this->get('Arpg.Logic.MessageData.Head');
				$dat->init($row);
				$ret[] = $dat;
			}
			
			// 全体送信
			$stmt = $this->sql(MHead::HS_TBL, MHead::SQLALL.' where end_date > ? and send_date < ?');
			$stmt->select([$now,$now]);
			$qus = [];
			$ret2 = [];
			while($row = $stmt->fetch(\PDO::FETCH_NUM)){
				$dat = $this->get('Arpg.Logic.MessageData.Head');
				$dat->initAll($row);
				$ret2[] = $dat;
				
				$qus[] = new Query(['='=>[$uid,intval($row[0])]]);
			}
			if(!empty($qus)){
				$rss = $this->getHs()->selectMulti(
						new Table('box_mail_all',['mail_id','state']),
						$qus
				);
				$states = [];
				foreach($rss as $rs)foreach($rs as $row){
					$states[intval($row[0])] = intval($row[1]);
				}
				foreach($ret2 as $dat){
					$oid = $dat->getOriginalId();
					if(isset($states[$oid])){
						if($states[$oid] == UMail::STATE_DEL)
							continue; 
						$dat->state = $states[$oid];
					}
					else
						$dat->state = UMail::STATE_NEW;
					$ret[] = $dat;
				}
			}
			
			usort($ret,function($a,$b){
				$ad = $a->receiveTime;
				$bd = $b->receiveTime;
				if($ad == $bd) return 0;
				return ($ad < $bd) ? 1 : -1;
			});
			
			return $ret;
		});
	}
	/**
	 * メッセージを取得
	 * リクエストデータ構造
	 * 	[
	 * 		'skey' => セッションキー
	 * 		'id' => メッセージID
	 * 	]
	 * レスポンスデータ構造
	 * 		Arpg.Logic.MessageData.Body
	 */
	public function getMesAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$id = $data['id'];
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();
			
			list($kind,$id) = explode(':',$id);
			$kind = intval($kind);
			$id = intval($id);

			$now = new \Dcs\Arpg\Time();
			if($kind == 1){
				// 個人送信型
				$rs = $this->getHs()->select(
						new Table(MBody::HS_TBL,MBody::$HS_FLD),
						new Query(['='=>$id])
				);
				
				if(empty($rs))
					throw new ResError("dont exist mail:$id",100);
				
				$dat = $this->get('Arpg.Logic.MessageData.Body');
				$dat->init($rs[0]);
				
				if($dat->state() == UMail::STATE_DEL || $dat->end()->get() < $now->get())
					throw new ResError("invalid mail:$id",400);
				if($dat->state() == UMail::STATE_NEW){
					$std_id = $dat->rewardStdId;
					$num = $dat->rewardNum;
					if($std_id > 0 && $num > 0){
						$dat->reward = $this->get('Arpg.Logic.GameData.Reward')->add($uid,[[$std_id,$num]],10006)[0];
					}
					$args[] = $id;
					$args[] = $uid;
					$this->useTransaction();
					$stmt = $this->sql('box_mail','update box_mail set state = 2 where id=? and uid=?');
					$stmt->update($args);
				}
			}elseif($kind == 2){
				// 全送信型
				$info = $this->get('Arpg.Logic.Util.Mail')->getInfo($id);
				$time = new Time();
				$time->setMySQLDateTime($info['end_date']);
				if($time->get() < $now->get())
					throw new ResError("over time mail:$id",400);
				$time->setMySQLDateTime($info['send_date']);
				if($now->get() < $time->get())
					throw new ResError("over time mail:$id",400);
				$rs = $this->getHs()->select(
						new Table('box_mail_all',['state']),
						new Query(['='=>[$uid,$id]])
				);
				$state = UMail::STATE_NEW;
				foreach($rs as $row){
					$state = intval($row[0]);
				}
				if($state == UMail::STATE_DEL)
					throw new ResError("over time mail:$id",400);
				
				$dat = $this->get('Arpg.Logic.MessageData.Body');
				$dat->initAll($id,$state);

				if($dat->state() == UMail::STATE_NEW){
					$std_id = intval($info['reward_std_id']);
					$num = intval($info['reward_num']);
					if($std_id > 0 && $num > 0){
						$dat->reward = $this->get('Arpg.Logic.GameData.Reward')->add($uid,[[$std_id,$num]],10006)[0];
					}
					$args[] = $uid;
					$args[] = $id;
					$args[] = UMail::STATE_ACCEPT;
					$args[] = $info['end_date'];
					$this->useTransaction();
					$stmt = $this->sql('box_mail_all','insert into box_mail_all(uid,mail_id,state,del_date) values(?,?,?,?) on duplicate key update state = values(state)');
					$stmt->insert($args);
				}
			}else{
				throw new ResError("dont exist mail type:$kind",400);
			}
			
			return $dat;
		});
	}
	
	/**
	 * メッセージ情報を更新
	 * リクエストデータ構造
	 * 	[
	 * 		'skey' => セッションキー
	 * 		'rids' => 既読IDリスト
	 * 		'dids' => 削除IDリスト
	 * 	]
	 * レスポンスデータ構造
	 * 	[Arpg.Logic.MessageData.Head]
	 */
	public function updateMesAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$aids = $data['aids'];
			$dids = $data['dids'];
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();
			
			$state_one = [];
			$state_all = [];
			foreach($aids as $id){
				list($kind,$id) = explode(':',$id);
				$kind = intval($kind);
				$id = intval($id);
				
				if($kind == 1){
					// 個人
					$state_one[$id] = UMail::STATE_ACCEPT;
				}elseif($kind == 2){
					// 全体
					$state_all[$id] = UMail::STATE_ACCEPT;
				}
			}
			foreach($dids as $id){
				list($kind,$id) = explode(':',$id);
				$kind = intval($kind);
				$id = intval($id);
				
				if($kind == 1){
					// 個人
					$state_one[$id] = UMail::STATE_DEL;
				}elseif($kind == 2){
					// 全体
					$state_all[$id] = UMail::STATE_DEL;
				}
			}
			$now = new Time();
			$now = $now->get();
			$time = new Time();
			$reward_adder = [];
			$qus = [];
			foreach($state_one as $id=>$buf){
				$qus[] = new Query(['='=>$id]);
			}
			if(!empty($qus)){
				$rss = $this->getHs()->selectMulti(
						new Table('box_mail',['id','uid','end_date','state','reward_std_id','reward_num','can_delete']),
						$qus
				);
				$sql1 = null;
				$subsql = '';
				$arg1 = [];
				$subarg = [];
				foreach($rss as $rs)foreach($rs as $row){
					if(intval($row[1]) != $uid) continue;	// 違うユーザー
					$state = intval($row[3]);
					if($state == UMail::STATE_DEL) continue; // すでに削除済み
					$time->setMySQLDateTime($row[2]);
					if($time->get() < $now) continue; // 期限切れ
					$std_id = intval($row[4]);
					$num = intval($row[5]);
					if($state == UMail::STATE_NEW && $std_id > 0 && $num > 0){
						$reward_adder[] = [$std_id,$num];
					}
					$id = intval($row[0]);
					$to_state = $state_one[$id];
					if($state < $to_state && (intval($row[6]) > 0 || $to_state == UMail::STATE_ACCEPT)){
						if($sql1 == null){
							$sql1 = 'update box_mail set state=case when id=? then ? ';
							$subsql = '?';
						}else{
							$sql1 .= 'when id=? then ? ';
							$subsql = ',?';
						}
						$arg1[] = $id;
						$arg1[] = $to_state;
						$subarg[] = $id;
					}
				}
				if($sql1 != null){
					$sql1 .= "else state end where id in ($subsql)";
					$arg1 = array_merge($arg1,$subarg);
				}
			}
			

			$qus = [];
			foreach($state_all as $id=>$buf){
				$qus[] = new Query(['='=>[$uid,$id]]);
			}
			$sql2 = null;
			if(!empty($qus)){
				$rss = $this->getHs()->selectMulti(
						new Table('box_mail_all',['mail_id','state']),
						$qus
				);
				$subsql = '';
				$arg2 = [];
				$subarg = [];
				$Mail = $this->get('Arpg.Logic.Util.Mail');
				foreach($rss as $rs)foreach($rs as $row){
					$id = intval($row[0]);
					$info = $Mail->getInfo($id);
					$state = intval($row[1]);
					if($state == UMail::STATE_DEL) continue; // すでに削除済み
					$time->setMySQLDateTime($info['end_date']);
					if($time->get() < $now) continue; // 期限切れ
					$time->setMySQLDateTime($info['send_date']);
					if($now < $time->get()) continue; // 未送信
				
					
					$std_id = $info['reward_std_id'];
					$num = $info['reward_num'];
					if($state == UMail::STATE_NEW && $std_id > 0 && $num > 0){
						$reward_adder[] = [$std_id,$num];
					}
					$to_state = $state_all[$id];
					if($state < $to_state && ($info['can_delete'] > 0 || $to_state == UMail::STATE_ACCEPT) ){
						if($sql1 == null){
							$sql2 = 'insert into box_mail_all (uid,mail_id,state,del_date) values(?,?,?,?)';
						}else{
							$sql2 .= ',(?,?,?,?)';
						}
						$arg2[] = $uid;
						$arg2[] = $id;
						$arg2[] = $to_state;
						$arg2[] = $info['end_date'];
					}
				}
				if($sql2 != null){
					$sql2 .= ' on duplicate key update state = values(state)';
				}
			}
			$this->useTransaction();
			if($sql1 != null){
				$stmt = $this->sql('box_mail',$sql1);
				$stmt->update($arg1);
			}
			if($sql2 != null){
				$stmt = $this->sql('box_mail_all',$sql2);
				$stmt->insert($arg2);
			}
			return $this->get('Arpg.Logic.GameData.Reward')->add($uid,$reward_adder);
		});
	}
}
?>