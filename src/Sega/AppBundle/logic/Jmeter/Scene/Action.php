<?php

namespace Logic\Jmeter\Scene;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Dcs\Jmeter;
use \Dcs\Security as sec;
use Logic\Util\Equip;

/**
 * Logic\Jmeter\RequestLogicの内容を分割
 * @author takeda_yoshihiro
 *
 */
trait Action{
	private function arpg_readwrite_recover_stamina(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$list = [202001,202002,202003,202004,202005,202006,202007,202008,202009];
					$iid = $list[mt_rand(0,8)];
					$jm->setNext('stdconnect/write/use_boost_item',15,'xor',[
							'skey' => self::skey($jm),
							'iid' => 0
					],$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readwrite_use_boost_item(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$jm->setNext('stdconnect/read/player_dungeon_join',15,'xor',self::skey($jm),$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readonly_player_dungeon_join(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$jm->setNext('stdconnect/read/player_world',15,'xor',self::skey($jm),$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readonly_player_world(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$jm->setNext('stdconnect/read/player_area',15,'xor',[
						'wid' => 1,
						'skey' => self::skey($jm)
					],$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readonly_player_area(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$jm->setNext('stdconnect/read/player_dungeon',15,'xor',[
						'aid' => 1,
						'wid' => 1,
						'skey' => self::skey($jm)
					],$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readonly_player_dungeon(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$jm->setNext('stdconnect/read/find_matching_room',15,'xor',[
							'did' => 1990142,
							'num' => 10,
							'blist' => []
					],$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readonly_find_matching_room(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$ptmt = $this->sqlCon()->prepare('select public_id from GAIA_USER_DATA_ABOUT_FRIEND order by friend_count desc limit 10');
					$ptmt->execute([]);
					$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
					$pids = [];
					foreach($rs as $row){
						$pids[] = intval($row[0]);
					}
					$jm->setNext('stdconnect/read/find_matching_room_by_host',15,'xor',[
							'pids' => $pids,
							'open' => 0,
							'state' => 0,
							'blist' => []
					],$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readonly_find_matching_room_by_host(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$ptmt = $this->sqlCon()->prepare('select id from action_room where open = 0 order by `limit` desc limit 1');
					$ptmt->execute([]);
					$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
					$rid = 0;
					if(!empty($rs))
						$rid = $rs[0][0];
					$jm->setNext('stdconnect/read/find_matching_room_by_rid',15,'xor',[
							'rid' => $rid,
							'state' => 0
					],$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readonly_find_matching_room_by_rid(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$ptmt = $this->sqlCon()->prepare('select id from action_ticket order by create_time desc limit 1');
					$ptmt->execute([]);
					$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
					$tid = 0;
					if(!empty($rs))
						$tid = $rs[0][0];
					$jm->setNext('stdconnect/read/find_matching_room_by_ticket',15,'xor',[
							'tid' => $tid,
							'state' => 0,
							'blist' => []
					],$jm->getAjmData());
					break;
					break;
				}
		}
	}
	private function arpg_readonly_find_matching_room_by_ticket(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$jm->setNext('stdconnect/read/player_ghost',15,'xor',self::skey($jm),$jm->getAjmData());
					break;
				}
		}
	}
	
	private function arpg_readonly_player_ghost(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$dat = $jm->getRpc();
					$dat = $dat->data['players'];
					$fid = -1;
					foreach($dat as $friend){
						if($fid<0||mt_rand(0,10)==1) $fid = $friend['actorId'];
					}
					$ajm = $jm->getAjmData();
					$ajm['fid'] = $fid;
					$jm->setNext('stdconnect/write/create_dungeon_and_room',15,'xor',[
						'state' => 0,
						'did' => 1990142,
						'open' => 1,
						'pid' => $ajm['pid'],
						'limit' => 60,
						'skey' => self::skey($jm)
					],$ajm);
					break;
				}
		}
	}
	
	private function arpg_readwrite_create_dungeon_and_room(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					$dat = $jm->getRpc()->data;
					$tid = $dat['dun']->ticket;
					$jm->setNext('stdconnect/write/begin_dungeon',15,'xor',[
						'tid' => $tid,
						'aids' => [],
						'skey' => self::skey($jm)
					],$jm->getAjmData());
					break;
				}
			default:
				{
					$dat = $jm->getRpc()->data;
					$tid = $dat['dun']->ticket;
					$ajm = $jm->getAjmData();
					$ajm['tid'] = $tid;
					$jm->setNext('stdconnect/write/create_matching_room',15,'xor',[
							'tid' => $tid,
							'pid' => $ajm['pid'],
							'open' => 0,
							'state' => 0,
							'limit' => 60,
							'skey' => self::skey($jm)
					],$ajm);
					break;
				}
		}
	}
	private function arpg_readwrite_create_matching_room(Jmeter $jm,$mode,$scene){
		$ajm = $jm->getAjmData();
		$ajm['rmid'] = $jm->getRpc()->data;
		$jm->setNext('stdconnect/write/begin_dungeon',15,'xor',[
				'tid' => $ajm['tid'],
				'aids' => [$ajm['fid']],
				'skey' => self::skey($jm)
		],$ajm);
	}
	
	private function arpg_readwrite_begin_dungeon(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					$ajm['tstep'] = 2;
					$jm->setNext('stdconnect/write/write_tutorial_step',15,'xor',[
						'tag' => 1,
						'step' => 2,
						'skey' => self::skey($jm)
					],$ajm);
					break;
				}
			default:
				{
					$ptmt = $this->sqlCon()->prepare('select public_id from GAIA_USER_DATA_ABOUT_FRIEND  order by friend_count desc limit 2');
					$ptmt->execute([]);
					$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
					$pids = [];
					foreach($rs as $row){
						$pids[] = $row[0];
					}
					$ajm = $jm->getAjmData();
					$jm->setNext('stdconnect/write/send_action_invite',15,'xor',[
							'pid' => $ajm['pid'],
							'ids' => $pids,
							'ticket' => $ajm['tid']
					],$ajm);
					break;
				}
		}
	}
	private function arpg_readwrite_send_action_invite(Jmeter $jm,$mode,$scene){
		switch($scene){
			case self::SCENE_SINGLE:
				{
					$ajm = $jm->getAjmData();
					$fid = $ajm['fid'];
					$jm->setNext('arpg/Fitting/model_by_actor',15,'xor',$fid,$ajm);
					break;
				}
			default:
				{
					break;
				}
		}
	}
	private function arpg_fitting_model_by_actor(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					$jm->setNext('stdconnect/read/get_equip_param',15,'xor',[
						$ajm['aid']
					],$ajm);
					break;
				}
			default:
				{
					$ajm = $jm->getAjmData();
					if(isset($ajm['is_uact'])){
						unset($ajm['is_uact']);
						$fid = $ajm['fid'];
						$jm->setNext('stdconnect/read/player_equiped_card',15,'xor',$fid,$ajm);
					}else{
						$ajm['is_uact'] = true;
						$fid = $ajm['aid'];
						$jm->setNext('arpg/Fitting/model_by_actor',15,'xor',$fid,$ajm);
					}
					break;
				}
		}
	}
	private function arpg_readonly_player_equiped_card(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					$ajm['tstep'] = 4;
					$jm->setNext('stdconnect/write/write_tutorial_step',15,'xor',[
						'tag' => 1,
						'step' => 4,
						'skey' => self::skey($jm)
					],$ajm);
					break;
				}
			default:
				{
					$ajm = $jm->getAjmData();
					if(isset($ajm['is_uact'])){
						unset($ajm['is_uact']);
						$jm->setNext('stdconnect/read/get_equip_param',15,'xor',[
							$ajm['aid'],
							$ajm['fid']
						],$ajm);
					}else{
						$ajm['is_uact'] = true;
						$fid = $ajm['aid'];
						$jm->setNext('stdconnect/read/player_equiped_card',15,'xor',$fid,$ajm);
					}
					break;
				}
		}
	}
	private function arpg_readonly_get_equip_param(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					$jm->setNext('stdconnect/read/player_equiped_card',15,'xor',$ajm['aid'],$ajm);
					break;
				}
			default:
				{
					$ajm = $jm->getAjmData();
					$jm->setNext('stdconnect/write/remind_matching_room',15,'xor',[
							'skey' => self::skey($jm),
							'rmid' => $ajm['rmid'],
							'state' => 0,
							'ticket' => $ajm['tid'],
							'limit' => 60,
							'hdata' => []
					],$ajm);
					break;
				}
		}
	}
	private function arpg_readwrite_remind_matching_room(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$ajm = $jm->getAjmData();
					$jm->setNext('stdconnect/write/remind_current_room',15,'xor',[
							'skey' => self::skey($jm),
							'rmid' => $ajm['rmid'],
							'ticket' => $ajm['tid'],
							'limit' => 60
					],$ajm);
					break;
				}
		}
	}
	private function arpg_readwrite_remind_current_room(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$jm->setNext('stdconnect/read/fetch_msg_setting_preset_msgs',15,'xor',self::skey($jm),$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readwrite_end_dungeon(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					$ajm['tstep'] = 21010101;
					$jm->setNext('stdconnect/write/write_tutorial_step',15,'xor',[
						'tag' => 2,
						'step' => 1010101,
						'skey' => self::skey($jm)
					],$ajm);
					break;
				}
			default:
				{
					$ptmt = $this->sqlCon()->prepare('select actor_id from box_actor order by login_date desc limit 1');

					$ptmt->execute([]);
					$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
					$aid = 0;
					if(empty($rs))
						$aid = $rs[0][0];
					$jm->setNext('stdconnect/write/iine',15,'xor',[
							'skey' => self::skey($jm),
							'aid' => $aid
					],$jm->getAjmData());
					break;
				}
		}
	}
	private function arpg_readwrite_iine(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					break;
				}
			default:
				{
					$this->cleanAjm($jm);
					// TODO 次シーンへ
					break;
				}
		}
	}
	
	
	private function ActionEndDungeon(Jmeter $jm){
		$ajm = $jm->getAjmData();
		$jm->setNext('stdconnect/write/end_dungeon',15,'xor',[
				'time' => 59,
				'tbox' => [],
				'item' => [],
				'cross' => [],
				'elixir' => 1,
				'clear' => true,
				'kill' => 12,
				'did' => 1990142,
				'skey' => self::skey($jm)
		],$ajm);
	}
	
}
?>