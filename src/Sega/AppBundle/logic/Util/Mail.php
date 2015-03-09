<?php
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class Mail extends \Dcs\Arpg\Logic{

	const TYPE_INFO_N=1;
	const TYPE_INFO_S=2;
	const TYPE_INFO_A=3;
	const TYPE_REWARD=4;
	const TYPE_INVITE=5;
	const TYPE_MAIL=6;
	
	const STATE_NEW = 0;
//	const STATE_READ= 1; 既読状態自体存在しない
	const STATE_ACCEPT = 2;
	const STATE_DEL = 3;

	/**
	 * メッセージを送る
	 * @param array $list 
	 * 	[[
	 * 			"type'=>int TYPE_XX 未設定の場合、送信されない,
	 * 			'from'=>string 送り主, 
	 * 			'to'=>[int 宛先UID, ...] 一つもない場合送信されない,
	 * 			'subject' => string タイトル,
	 * 			'message' => string 本文,
	 * 			'reward' => int 報酬STD_ID,
	 * 			'reward_num' => int 報酬数,
	 * 			'limit' => int メッセージ有効期間(秒) 未設定の場合604800秒(一週間),
	 * 	], ...]
	 */
	public function send($list){
		$sql = null;
		$args = [];
		$insert_count = 0;
		foreach($list as $line){
			if(!isset($line['type']) || !isset($line['to']) || empty($line['to'])) continue;
			$type = intval($line['type']);
			$to_uids = $line['to'];
			$from = isset($line['from'])? $line['from']: '';
			$subject = isset($line['subject'])? $line['subject']: '';
			$message = isset($line['message'])? $line['message']: '';
			$reward = (isset($line['reward'])&&is_numeric($line['reward']))? intval($line['reward']): 0;
			$reward_num = (isset($line['reward_num'])&&is_numeric($line['reward_num']))? intval($line['reward_num']): 0;
			$limit = (isset($line['limit'])&&is_numeric($line['limit']))? intval($line['limit']): 604800;

			$end = new \Dcs\Arpg\Time();
			$end = $end->add($limit)->getMySQLDateTime();

			$now = new \Dcs\Arpg\Time();
			$now_sql = $now->getMySQLDateTime();
			
			foreach($to_uids as $uid){
				if($sql == null)
					$sql = 'insert into box_mail (uid,`type`,`from`,subject,body,create_date,end_date,state,reward_std_id,reward_num) values(?,?,?,?,?,?,?,0,?,?)';
				else
					$sql .= ',(?,?,?,?,?,?,?,0,?,?)';
				$args[] = intval($uid);
				$args[] = $type;
				$args[] = $from;
				$args[] = $subject;
				$args[] = $message;
				$args[] = $now_sql;
				$args[] = $end;
				$args[] = $reward;
				$args[] = $reward_num;
				++$insert_count;
			}
		}
		if($sql != null){
			$this->useTransaction();
			$stmt = $this->sql('box_mail',$sql);
			$stmt->insert($args);
			if($insert_count != $stmt->rowCount())
				throw new \Exception('message send error.');
		}
	}

	/**
	 * 全送信型メールデータを取得する
	 * @param int $mid メールID
	 * @return NULL|array select * from mail_all FETCH_ASSOC型の結果 intとか数値は正しい値に変換される
	 */
	public function getInfo($mid){
		$mid = intval($mid);
		
		$cache = $this->cache();
		$key = 'Arpg.Logic.Util.Mail.getData.'.$mid;
		$ret = $cache->get($cache::TYPE_APC,$key);
		if($ret == null){
			$rs = $this->getHs(false)->select(
				new Table('mail_all',self::$ALLM_DATA),
				new Query(['='=>$mid])
			);
			if(empty($rs)) return null;
			$rs = $rs[0];
			$ret = [];
			
			for($i=0,$len=count(self::$ALLM_DATA);$i<$len;++$i){
				$dat = $rs[$i];
				if(is_numeric($dat)){
					$it = intval($dat);
					$ft = $dat+0;
					if($it == $ft)
						$dat = $it;
					else
						$dat = $ft;
				}
				$ret[self::$ALLM_DATA[$i]] = $dat;
			}
			$cache->set($cache::TYPE_APC,$key,$ret);
		}
		return $ret;
	}
	private static $ALLM_DATA =['id','type','from','subject','body','send_date','end_date','can_delete','reward_std_id','reward_num'];
}

?>