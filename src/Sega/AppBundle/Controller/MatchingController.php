<?php
namespace Sega\AppBundle\Controller;


use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Gaia\Bundle\HandlerSocketBundle\Util\HandlerSocketUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Logic\PlayerData\HomePopup as HomePopup;
use \Dcs\Security as sec;
use \Dcs\Arpg\Time as Time;
use \Dcs\DetailTimeLog as DTL;

class MatchingController extends \Dcs\DcsController{
	/**
	 * 公開ルーム検索
	 * data:{
	 * 		did: ダンジョンID
	 * 		num: 取得部屋数
	 * 		blist: ブラックリストルームID
	 * }
	 * RPC構造
	 * data:array HostDataリスト
	 */
	public function findAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$did = intval($data['did']);
			$num = intval($data['num']);
			$blist = $data['blist'];


			$limit = new Time();

			$bind = [$limit->getMySQLDateTime(),$did];

			$in_sql = null;
			foreach($blist as $brmid){
				if($in_sql == null){
					$in_sql = 'and id not in (?';
				}else{
					$in_sql .= ',?';
				}
				$bind[] = intval($brmid);
			}
			if($in_sql == null)
				$in_sql = '';
			else
				$in_sql .= ')';
			$rs = $this->sql('action_room',"select id,host_data from action_room where `limit` > ? and dungeon_id=? and open = 0 and collect_state = 0 $in_sql order by `limit` desc limit $num")->selectAll($bind, \PDO::FETCH_NUM);
			$ret = [];
			foreach($rs as $row){
				if($row[1] == null || strlen($row[1]) < 10) continue;
				$ret[] = $this->editHostData($row[1],$row[0]);
			}

			return $ret;
		});
	}
	/**
	 * ルームホストユーザーでルームを検索する
	 * data:{
	 * 	pids: ホストユーザーパブリックID配列
	 * 	open: 公開ルームフラグ
	 * 	state: 募集ステート
	 * RPC構造
	 * data:array HostDataリスト
	 */
	public function findByHostAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$pids = $data['pids'];
			$open = intval($data['open']);
			$state = intval($data['state']);
			$blist = $data['blist'];

			$limit = new Time();
			$bind = [$limit->getMySQLDateTime()];
			$insql = null;
			foreach($pids as $pid){
				if($insql == null){
					$insql = 'public_id in(?';
				}else{
					$insql .= ',?';
				}
				$bind[] = intval($pid);
			}

			if($insql != null){
				$insql .= ')';

				$bl_sql = null;
				foreach($blist as $brmid){
					if($bl_sql == null){
						$bl_sql = 'and id not in (?';
					}else{
						$bl_sql .= ',?';
					}
					$bind[] = intval($brmid);
				}
				if($bl_sql == null)
					$bl_sql = '';
				else
					$bl_sql .= ')';

				$is_err = false;
				switch($state){
					case self::state_rematch:
						$insql.= ' and collect_state <> '.self::state_close;
						break;
					case self::state_all:
						$insql.= ' and collect_state = '.self::state_all;
						break;
					default:
						$is_err = true;
						break;
				}

				if(!$is_err){
					$insql .= ' and open = '.$open;
					$rs = $this->sql('action_room',"select id,host_data from action_room where `limit` > ? and $insql $bl_sql")->selectAll($bind,\PDO::FETCH_NUM);
					$ret = [];
					foreach($rs as $row){
						if($row[1] == null || strlen($row[1]) < 10) continue;
						$ret[] = $this->editHostData($row[1],$row[0]);
					}
					return $ret;
				}else{
					return [];
				}
			}else{
				return [];
			}
		});
	}
	/**
	 * チケットによるルーム検索
	 * data: int チケットID
	 * RPC構造
	 * data:array HostDataリスト
	 */
	public function findByTicketAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$ticket = intval($data['tid']);
			$state = intval($data['state']);
			$blist = $data['blist'];

			$limit = new Time();

			$insql = null;
			switch($state){
				case self::state_rematch:
					$insql = ' and collect_state <> '.self::state_close;
					break;
				case self::state_all:
					$insql = ' and collect_state = '.self::state_all;
					break;
				default:
					break;
			}
			if($insql != null){
				$bl_sql = null;

				$bind = [$limit->getMySQLDateTime(),$ticket];

				foreach($blist as $brmid){
					if($bl_sql == null){
						$bl_sql = 'and id not in (?';
					}else{
						$bl_sql .= ',?';
					}
					$bind[] = intval($brmid);
				}
				if($bl_sql == null)
					$bl_sql = '';
				else
					$bl_sql .= ')';

				$rs = $this->sql('action_room',"select id,host_data from action_room where `limit` > ? and ticket_id=? and open=1 $insql $bl_sql")->selectAll($bind,\PDO::FETCH_NUM);
				$ret = [];
				foreach($rs as $row){
					if($row[1] == null || strlen($row[1]) < 10) continue;
					$ret[] = $this->editHostData($row[1],$row[0]);
				}
				return $ret;
			}else{
				return [];
			}
		});
	}
	/**
	 * ルーム番号によるルーム検索
	 * data: int チケットID
	 * RPC構造
	 * data: HostData
	 */
	public function findByRidAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$rid = intval($data['rid']);
			$state = intval($data['state']);

			$limit = new Time();

			$insql = null;
			switch($state){
				case self::state_rematch:
					$insql = ' and collect_state <> '.self::state_close;
					break;
				case self::state_all:
					$insql = ' and collect_state = '.self::state_all;
					break;
				default:
					break;
			}

			if($insql != null){
				$bind = [$limit->getMySQLDateTime(),$rid];
				$rs = $this->sql('action_room',"select id,host_data from action_room where `limit` > ? and id=? and open=0 $insql")->selectAll($bind, \PDO::FETCH_NUM);
				foreach($rs as $row){
					if($row[1] == null || strlen($row[1]) < 10) continue;
					return $this->editHostData($row[1],$row[0]);
				}
			}

			return null;
		});

	}
	/**
	 * マッチングルームとローカルダンジョンを新規生成
	 * リクエストデータ構造
	 * data:{
	 * 		did : int ダンジョンコンフィグID
	 * 		pid : int パブリックユーザーID
	 * 		limit : int 生存時間
	 * 		open : 公開部屋フラグ
	 * 		state : 募集ステータス
	 * 		hdata : ホストパラメータ
	 * }
	 * RPC構造
	 * data : {
	 * 		rmid ; int ルームID
	 * 		dun : object マッチングダンジョンデータ
	 * }
	 */
	public function createDARAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('start action');
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$did = intval($data['did']);
			$pid = intval($data['pid']);
			$open = intval($data['open']);
			$state = intval($data['state']);
			$interval = intval($data['limit']);

			$uid = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserId($pid);

			$limit = new Time();

			$ctime = $limit->getMySQLDateTime();
			if($interval <= 0)
				$limit->set(0);
			else
				$limit->add($interval+60);
			$ltime = $limit->getMySQLDateTime();

			$ticket = 0;
			for($i=0;$ticket == 0;++$i){
				if($i > self::retry_insert){
					throw new \Symfony\Component\HttpKernel\Exception\HttpException(408,'insert action_ticket failed');
				}elseif($i > 0){
					DTL::Lap("retry insert action_ticket $i");
				}
				$ticket = intval($this->getHs()->insert(
						new Table('action_ticket', ['dungeon_id','create_time']),
						[$did,$ctime]
				));
			}

			DTL::Lap('fetch action_ticket');

			$dun = $this->get('Arpg.Logic.Matching.Dungeon');
			$dun->init($did,$ticket,$uid,$pid);
			$rmid = 0;
			for($i=0;$rmid == 0;++$i){
				if($i > self::retry_insert){
					throw new \Symfony\Component\HttpKernel\Exception\HttpException(408,'insert action_room failed');
				}elseif($i > 0){
					DTL::Lap("retry insert action_room $i");
				}
				$rmid = intval($this->getHs()->insert(
						new Table('action_room', ['dungeon_id','ticket_id','public_id','limit','open','collect_state']),
						[$did,$ticket,$pid,$ltime,$open,$state]
				));
			}
			if($open == 0 && $state == 0)
				// 公開部屋のためALLpopupに追加
				$this->get('Arpg.Logic.PlayerData.HomePopup')->send([0],HomePopup::ACT_JOIN,$uid,$did,$ticket,$rmid);

			DTL::Lap('end action');
			return [
				'dun' => $dun,
				'rid' => $rmid,
			];
		});
	}
	/**
	 * マッチングルームを作成
	 * 分離後部屋を立ち上げるときは、ローカルダンジョン生成がいらないため
	 * data:{
	 * 		tid : int チケットID
	 * 		pid : int パブリックユーザーID
	 * 		limit : int 生存時間
	 * 		open : 公開部屋フラグ
	 * 		state : 募集ステータス
	 * 		hdata : ホストパラメータ
	 * }
	 * RPC構造
	 * data : int ルームID
	 */
	public function createRoomAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$ticket = intval($data['tid']);
			$pid = intval($data['pid']);
			$open = intval($data['open']);
			$state = intval($data['state']);
			$interval = intval($data['limit']);

			$uid = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserId($pid);

			$limit = new Time();
			if($interval <= 0)
				$limit->set(0);
			else
				$limit->add($interval+60);
			$ltime = $limit->getMySQLDateTime();
			$rs = $this->getHs()->select(
					new Table('action_ticket',['dungeon_id']),
					new Query(['='=>$ticket])
			);
			if(count($rs) > 0){
				$did = intval($rs[0][0]);

				$room_id = 0;
				for($i=0;$room_id == 0;++$i){
					if($i > self::retry_insert){
						throw new \Symfony\Component\HttpKernel\Exception\HttpException(408,'insert action_room failed');
					}elseif($i > 0){
						DTL::Lap("retry insert action_room $i");
					}

					$room_id = intval($this->getHs()->insert(
						new Table('action_room', ['dungeon_id','ticket_id','public_id','limit','open','collect_state']),
						[$did,$ticket,$pid,$ltime,$open,$state]
					));
				}
				return $room_id;
			}
		});
	}

	/**
	 * マッチングルームリマインド
	 * リクエストデータ構造
	 * data:{
	 * 		rmid : int ルームID
	 * 		limit : int 生存時間
	 * 		state : 募集ステータス
	 * 		hdata : ホストパラメータ
	 * }
	 */
	public function remindAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('start action');

			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$id = intval($data['rmid']);
			$state = intval($data['state']);
			$ticket = intval($data['ticket']);
			$hdata = json_encode($data['hdata']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();

			DTL::Lap('login end');

			$limit = new Time();
			$limit->add(intval($data['limit'])+60);
			$this->getHs()->update(
					new Table('action_room', ['limit','collect_state','host_data']),
					new Query(['=' => $id]),
					[$limit->getMySQLDateTime(),$state,$hdata]
			);

			DTL::Lap('update action_room');

			$this->remindCommon($uid,$id,$limit,$ticket);

			DTL::Lap('end action');
		});
	}

	/**
	 * カレントルームリマインド
	 * リクエストデータ構造
	 * data:{
	 * 		rmid : int ルームID
	 * 		limit : int 生存時間
	 * 		state : 募集ステータス
	 * 		hdata : ホストパラメータ
	 * }
	 */
	public function remindCurrentAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$id = intval($data['rmid']);
			$ticket = intval($data['ticket']);

			$limit = new Time();
			$limit->add(intval($data['limit'])+60);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();

			$this->remindCommon($uid,$id,$limit,$ticket);
		});
	}

	public function remindCommon($uid, $id,Time $limit,$ticket){
		DTL::Lap('start remindCommon');
		// 挑戦中チケット
		if($ticket > 0){
			// JoinPopup
			$rs = $this->getHs()->select(
				new Table('action_room',['dungeon_id','open','collect_state']),
				new Query(['='=>$id])
				);
			DTL::Lap('fetch action_room');

			if(!empty($rs)){
				$dungeon_stdid = intval($rs[0][0]);
				$open = intval($rs[0][1]) == 0;
				$collect = intval($rs[0][1]) == 0;
				if($open && $collect){
					$fuids = $this->get('gaia.friend.friend_management_service')->friendIds($uid);
					DTL::Lap('fetch friend ids');
					$this->get('Arpg.Logic.PlayerData.HomePopup')->send($fuids,HomePopup::ACT_JOIN,$uid,$dungeon_stdid,$ticket,$id);
				}
			}

			DTL::Lap('popup send');
		}
		DTL::Lap('end remindCommon');
	}

	/**
	 * 招待メール発行
	 * リクエストデータ構造
	 * data:[int] パブリックユーザーIDリスト
	 * RPC構造
	 * data : bool
	 */
	public function sendInviteAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$pid = intval($data['pid']);
			$fpids = $data['ids'];
			$ticket = intval($data['ticket']);

			$rs = $this->getHs()->select(
				new Table('action_ticket',['dungeon_id']),
				new Query(['='=>$ticket])
			);
			if(empty($rs)) return null;
			$dungeon_stdid = intval($rs[0][0]);

			$uid = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserId($pid);
			$this->get('Dcs.RequestLock')->lock($uid,\Dcs\RequestLock::LOCK_LV2);

			$fuids = $this->get('Arpg.Logic.Util.PlayerStatus')->getUserIds($fpids);
			$sendto = [];
			foreach($fuids as $fuid){
				$sendto[] = $fuid;
			}
			$this->get('Arpg.Logic.PlayerData.HomePopup')->send($sendto,HomePopup::ACT_INVITE,$uid,$dungeon_stdid,$ticket,0);
			return null;
		});
	}

	/**
	 * ホストパラメータをホストデータに変換する
	 * @param array $host_param ホストパラメータの連想配列
	 * @return string ホストデータ
	 */
	private function convertHost($host_param){
		$ret = [];
		$useNat = intval($host_param['useNat']);
		if($useNat != 0 && $host_param['exIP'] == $_SERVER['REMOTE_ADDR']){
			$ret['ip']=$host_param['inIP'];
			$ret['port']=$host_param['inPort'];
			$ret['useNat'] = 0;
		}else if($host_param['exIP'] == 'UNASSIGNED_SYSTEM_ADDRESS' || intval($host_param['exPort']) == 65535 || count(explode('.',$host_param['exIP'])) != 4){
			$ret['ip']=$host_param['inIP'];
			$ret['port']=$host_param['inPort'];
			$ret['useNat'] = 0;
		}else if(strcmp($host_param['exIP'],$host_param['inIP']) == 0){
			$ret['ip']=$host_param['inIP'];
			$ret['port']=$host_param['inPort'];
			$ret['useNat'] = $useNat;
		}else{
			$ret['ip']=$host_param['exIP'];
			$ret['port']=$host_param['exPort'];
			$ret['useNat'] = $useNat;
		}
		$ret['guid'] = $host_param['guid'];
		$ret['connectedPlayers']=intval($host_param['nowUser']);
		$ret['playerLimit']=intval($host_param['maxUser']);
		$ret['passwordProtected'] = intval($host_param['hasPass']);
		return $ret;
	}

	/**
	 * ホストデータを編集する
	 * @param string $data
	 * @param int $room_id
	 * @return array
	 */
	private function editHostData($data, $room_id){
		$data = $this->convertHost(json_decode($data,true));
		$data['room'] = intval($room_id);
		return $data;
	}

		/*
		 *
		public string type;		// ゲームタイプ //
		public string name;		// ゲーム名 //
		public string comm;		// コメント //
		public bool useNat;		// NAT使用フラグ //
		public int nowUser;		// 現ユーザー数 //
		public int maxUser;		// 最大ユーザー数 //
		public string inIP;		// 内部IPアドレス //
		public int inPort;		// 内部ポート //
		public string exIP;		// 外部IPアドレス //
		public int exPort;		// 外部ポート //
		public string guid;		// GUID //
		public bool hasPass;	// パスワード使用フラグ //
		 */

	const open_true=0;
	const open_false=1;

	const state_all=0;
	const state_rematch=1;
	const state_close=2;

	const std_try_ticket = 1011;

	const retry_insert = 5;
}
