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
trait Friend{
	private function arpg_readonly_player_friend(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					switch($scene){
						case self::SCENE_SINGLE:
							{
								$this->ActionEndDungeon($jm);
								break;
							}
						default:
							{
								$dat = $jm->getRpc()->data;
								
								$afr = null; // 許可するPID
								$dfr = null; // 削除するPID
								$rfr = null; // 拒否するPID
								foreach($dat as $fr){
									if(intval($fr['stat']) == 1)
										if(mt_rand(0,99) < 20)
											$rfr = intval($fr['id']);
										else
											$afr = intval($fr['id']);
									if($dfr == null && intval($fr['stat']) == 3)
										$dfr = intval($fr['id']);
									if($rfr == null && intval($fr['stat']) == 1)
										$rfr = intval($fr['id']);
								}
								
								$ajm = $jm->getAjmData();
								if($afr != null) $ajm['afr'] = $afr;
								if($dfr != null) $ajm['dfr'] = $dfr;
								if($rfr != null) $ajm['rfr'] = $rfr;
								
								$jm->setAjmData($ajm);
								$this->FriendRequestChanger($jm);
				
								break;
							}
					}
				}
		}
	}
	private function FriendRequestChanger(Jmeter $jm){
		$ajm = $jm->getAjmData();
		if(isset($ajm['afr'])){
			$fr = intval($ajm['afr']);
			unset($ajm['afr']);
			$jm->setNext('stdconnect/write/try_friend_admit',15,'xor',[
					'skey' => self::skey($jm),
					'ids' => [$fr]
					],$ajm);
		}elseif(isset($ajm['dfr'])){
			$fr = intval($ajm['dfr']);
			unset($ajm['dfr']);
			$jm->setNext('stdconnect/write/try_friend_del',15,'xor',[
					'skey' => self::skey($jm),
					'ids' => [$fr]
			],$ajm);
		}elseif(isset($ajm['rfr'])){
			$fr = intval($ajm['rfr']);
			unset($ajm['rfr']);
			$jm->setNext('stdconnect/write/try_friend_reject',15,'xor',[
					'skey' => self::skey($jm),
					'ids' => [$fr]
			],$ajm);
		}else
			$jm->setNext('stdconnect/write/request_friend_slot_expanding',15,'xor',self::skey($jm),$ajm);
		
	}
	private function arpg_readwrite_try_friend_admit(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->FriendRequestChanger($jm);
				}
		}
	}
	private function arpg_readwrite_try_friend_del(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->FriendRequestChanger($jm);
				}
		}
	}
	private function arpg_readwrite_try_friend_reject(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->FriendRequestChanger($jm);
				}
		}
	}

	private function arpg_readwrite_request_friend_slot_expanding(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/find_friend',15,'xor',[
							'word' => self::NameBase,
							'type' => 1,
							'skey' => self::skey($jm)
							],$jm->getAjmData());
				}
		}
	}
	
	private function arpg_readonly_find_friend(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$rpc = $jm->getRpc();
					$ids = [];
					if(is_array($rpc->data)&&count($rpc->data)>0){
						foreach($rpc->data as $fr){
							$ids[] = $fr['id'];
							if(count($ids) > 3)
								break;
						}
					}
					$ajm = $jm->getAjmData();
					if(!empty($ids)){
						$ajm['fr'] = $ids[0];
						$jm->setNext('stdconnect/write/try_friend_req',15,'xor',[
							'ids' => $ids,
							'skey' => self::skey($jm)
						],$ajm);
					}else{
						$this->FriendCreateChat($jm);
					}
				}
		}
	}
	private function FriendCreateChat($jm){
		$jm->setNext('stdconnect/write/chat_create',15,'xor',[
				'skey' => self::skey($jm),
				'uid' => []
		],$jm->getAjmData());
	}
	private function arpg_readwrite_try_friend_req(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$ajm = $jm->getAjmData();
					$id = $ajm['fr'];
					unset($ajm['fr']);
					$jm->setNext('stdconnect/write/try_friend_cancel',15,'xor',[
						'ids' => [
							$id
						],
						'skey' => self::skey($jm)
					],$ajm);
				}
		}
	}
	private function arpg_readwrite_try_friend_cancel(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->FriendCreateChat($jm);
				}
		}
	}
	private function arpg_readwrite_chat_create(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$tid = $jm->getRpc()->data['threadId'];
					$ajm = $jm->getAjmData();
					$ajm['tid'] = $tid;
					$ptmt = $this->sqlCon()->prepare('select public_id from GAIA_USER_DATA_ABOUT_FRIEND order by friend_count limit 10');
					$ptmt->execute([]);
					$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
					$upid = intval($ajm['pid']);
					$pid = 0;
					foreach($rs as $row){
						$pid = intval($row[0]);
						if($pid != $upid)
							break;
					}
					$jm->setNext('stdconnect/write/chat_add_member',15,'xor',[
						'skey' => self::skey($jm),
						'tid' => $tid,
						'uid' => [$pid]
					],$ajm);
				}
		}
	}
	private function arpg_readwrite_chat_add_member(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$ajm = $jm->getAjmData();
					
					$jm->setNext('stdconnect/write/chat_post',15,'xor',[
							'skey' => self::skey($jm),
							'tid' => $ajm['tid'],
							'msg' => 'im jmeter',
							'rnm' => 0
					],$ajm);
				}
		}
	}
	private function arpg_readwrite_chat_post(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$ajm = $jm->getAjmData();
					$tid = $ajm['tid'];
					unset($ajm['tid']);
					$jm->setNext('stdconnect/write/chat_exit',15,'xor',[
							'skey' => self::skey($jm),
							'tid' => $ajm,
					],$ajm);
				}
		}
	}
	private function arpg_readwrite_chat_exit(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$jm->setNext('stdconnect/read/chat_list',15,'xor',self::skey($jm),$jm->getAjmData());
				}
		}
	}
	
	
	private function arpg_readonly_chat_list(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$rpc = $jm->getRpc();
					$dat = $rpc->data;
					if(empty($dat))
						$this->changeScene($jm,self::SCENE_SHOP);
					else{
						$jm->setNext('stdconnect/read/chat_thread',15,'xor',[
								'skey' => self::skey($jm),
								'tid' => $dat[0]['threadId'],
								'rnm' => 0
						],$jm->getAjmData());
					}
				}
		}
	}
	
	private function arpg_readonly_chat_thread(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
			default:
				{
					$this->changeScene($jm,self::SCENE_SHOP);
				}
		}
	}
	
}
?>