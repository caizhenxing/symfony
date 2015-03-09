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

class EquipController extends \Dcs\DcsController
{
	/**
	 * 装備品のロックを行う
	 * リクエスト
	 *  {
	 *  	skey : セッションキー
	 *  	lock : List<long> ロックする装備のID
	 *  	unlock : List<long> ロック解除する装備のID
	 *  }
	 */
	public function setLockAction($data)
	{
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$lock = $data['lock'];
			$unlock = $data['unlock'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();

			$sql = null;
			$args = [];
			if($lock == null)
				$lock = [];
			if($unlock == null)
				$unlock = [];
			foreach($lock as $row){
				if($sql == null)
					$sql = 'update box_equip set `lock` = case when id=? then 1';
				else
					$sql .= ' when id=? then 1';
				$args[] = intval($row);
			}
			foreach($unlock as $row){
				if($sql == null)
					$sql = 'update box_equip set `lock` = case when id=? then 0';
				else
					$sql .= ' when id=? then 0';
				$args[] = intval($row);
			}
			if($sql != null){
				$sql .= ' else `lock` end where uid = ? and state = 0';
				$args[] = $uid;
				$this->useTransaction();
				$stmt = $this->sql('box_equip',$sql);
				$stmt->update($args);
			}
			return null;
		});
	}
	/**
	 * 装備品リストを取得
	 * リクエストデータ構造
	 * data: {mSid:string セッションID}
	 * RPC構造
	 * data:[Arpg.Logic.CardDataコンテナ]
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getCardStockAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$rs = $this->getHs()->select(
					new Table(CardData::DBTBL,CardData::$CLMS,CardData::IDX_UID),
					new Query(['='=>[$uid,0]],-1)
			);
// 			$table = CardData::DBTBL;
// 			$culmun = "`id`,`level`,`exp`,`skill`,`addon`,`evo`,`std_id`,`evo_bonus_atk`,`evo_bonus_matk`,`evo_bonus_def`,`evo_bonus_mdef`,`state`,`lock`";
// 			$sql = "select $culmun from $table where uid = ?";
// 			$stmt = $this->sql($table,$sql);
// 			$rs = $stmt->selectAll(array($uid),\PDO::FETCH_NUM);

			$set_size = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatus($uid,self::std_equip_set);
			if($set_size < 1)
				$set_size = 1;
			\Dcs\DetailTimeLog::Lap('fetch card data');

			$cards = [];
			foreach($rs as $row){
				$card = $this->get('Arpg.Logic.CardData');
				$card->init($row);
				$cards[] = $card;
			}
			\Dcs\DetailTimeLog::Lap('create card data');


			$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
			$actor_id = $Astatus->getActorId($uid);

			$rs = $this->getHs()->select(
					new Table('box_actor',['actor_id'],'UID'),
					new Query(['='=>[$uid,0]],-1)
			);

// 			$table = "box_actor";
// 			$sql = "select actor_id from $table where uid = ?";
// 			$stmt = $this->sql($table,$sql);
// 			$rs = $stmt->selectAll(array($uid),\PDO::FETCH_NUM);

			$param = [];
			foreach($rs as $row){
				$aid = intval($row[0]);
				$param[] = [$aid,self::std_eset];
				for($i=0;$i<$set_size;++$i){
					$param[] = [$aid,self::std_eset_w+$i*10];
					$param[] = [$aid,self::std_eset_h+$i*10];
					$param[] = [$aid,self::std_eset_c+$i*10];
					$param[] = [$aid,self::std_eset_n+$i*10];
					$param[] = [$aid,self::std_eset_r+$i*10];
					for($j=0;$j<\Logic\Util\Equip::MAX_SUPPLIES;++$j){
						$param[] = [$aid,self::std_eset_i+$i*10+$j];
					}
				}
			}
			$ass = $Astatus->getStatusMultiActor($param);
			$equiped = [];
			foreach($ass as $aid => $as){
				$set = [];
				for($i=0;$i<$set_size;++$i){
					$eq = [];
					$eq[] = $as[self::std_eset_w+$i*10];
					$eq[] = $as[self::std_eset_h+$i*10];
					$eq[] = $as[self::std_eset_c+$i*10];
					$eq[] = $as[self::std_eset_n+$i*10];
					$eq[] = $as[self::std_eset_r+$i*10];
					$it = [];
					for($j=0;$j<\Logic\Util\Equip::MAX_SUPPLIES;++$j){
						$it[$j] = $as[self::std_eset_i+$i*10+$j];
					}

					$set[] = [
						'sid' => $i,
						'eq' => $eq,
						'it' => $it,
					];
				}
				$equiped[] = [
						'actor_id' => $aid,
						'sid' => $as[self::std_eset],
						'sheet' => $set
				];
			}

			return [
				'card' => $cards,
				'equiped' => $equiped
			];
		});
	}

	/**
	 * アイテムリストを取得
	 * リクエストデータ構造
	 * data: {mSid:string セッションID}
	 * RPC構造
	 * data:[Arpg.Logic.ItemDataコンテナ ]
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getItemStockAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			$stmt = $this->sql(\Logic\ItemData::DBHS_TBL ,\Logic\ItemData::DBSQL);
			$stmt->select([$user->getUid()]);

			$dat = [];
			while($row = $stmt->fetch(\PDO::FETCH_NUM)){
				$item = $this->get('Arpg.Logic.ItemData');
				if($item->init($row) && $item->isEffective())
					$dat[] = $item;
			}

			return $dat;
		});
	}
	/**
	 * 装備情報を取得
	 * リクエストデータ構造
	 * data: {mSid:string セッションID}
	 * RPC構造
	 * data:Arpg.Logic.PlayerData.Equipコンテナ
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getEquipAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$dat = $this->get('Arpg.Logic.PlayerData.Equip');
			$dat->init($user->getUid());
			return $dat;
		});
	}

	/**
	 * 装備を変更
	 * リクエストデータ構造
	 * data: {skey:{mSid:string セッションID},equip:PlayerData.Equip}
	 * RPC構造
	 * data:Arpg.Logic.PlayerData.Equip
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function tryEquipAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$sid = $data['set_id'];
			$sheet = $data['sheet'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();

			$this->get('Arpg.Logic.Util.Equip')->equip($uid,$sid,$sheet);
			return true;
		});
	}
	/**
	 * アクション用装備情報取得
	 * リクエストデータ構造
	 * data: int actor_id
	 * RPC構造
	 * data: Arpg.Logic.PlayerData.ActionEquip
	 * err: {
	 * 		code: 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getActionEquipedAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$aid = intval(json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true));

			$dat = $this->get('Arpg.Logic.PlayerData.ActionEquip');
			$dat->init($aid);

			return $dat;
		});
	}
	public function sellItemAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$iid = intval($data['iid']);
			$num = intval($data['num']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();


			try{
				$sitem = $this->get('Arpg.Logic.Util.StackItem');
				$sitem->add($uid,$iid,-$num);

				$mst = $this->get('Arpg.Logic.Util.StackItem')->getData($iid);

				if($mst != null){
					$this->get('Arpg.Logic.Util.PlayerStatus')->add($uid,self::std_money,intval($mst['sell'])*$num);

					$dat = $this->get('Arpg.Logic.PlayerData.State');
					$dat->init($uid);
					return $dat;
				}else{
					throw new ResError('dont exists item',1000);
				}
			}catch(\Exception $e){
				\Dcs\Log::e($e);
				throw new ResError('invalid value',100);
			}
		});
	}
	public function sellCardAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$skey = $data['skey'];
			$ids = $data['ids'];

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);
			$uid = $user->getUid();

			$stmt = $this->sql('box_actor','select actor_id from box_actor where uid = ? and state = 0');
			$rs = $stmt->selectAll([$uid],\PDO::FETCH_NUM);
			$aids = [];
			foreach($rs as $row){
				$aids[] = $row[0];
			}
			$eq = $this->get('Arpg.Logic.Util.Equip')->getEquipedByActors($aids,true);
			$eqs = [];
			foreach($eq as $l){
				$l = $l['card'];
				foreach($l as $c){
					$eqs[$c]=true;
				}
			}
			foreach($ids as $m){
				$m = intval($m);
				if(isset($eqs[$m])){
					throw new ResError('equiped card use to sell.',100);
				}
			}
			
			$Equip = $this->get('Arpg.Logic.Util.Equip');
			$list = $Equip->getEquipBaseInfos($ids);
			$money = 0;
			$rareSpirit = 0;
			foreach($list as $info){
				$money += $info['sell'];
				$rareSpirit += $info['rare_spirit'];
			}
				
			if($Equip->delMulti($uid,$ids)){
				$this->get('Arpg.Logic.Util.PlayerStatus')->add($uid, self::std_money, $money);
				// 稀霊石
				$this->get('Arpg.Logic.Util.StackItem')->add($uid,self::std_rare_spirit,$rareSpirit);
				$dat = $this->get('Arpg.Logic.PlayerData.State');
				$dat->init($uid);
				return $dat;
			}else{
				throw new ResError('dont exists item',1000);
			}
		});
	}

	/**
	 * Action用装備情報を取得
	 * リクエストデータ構造
	 * array(actor_id, ... )
	 * レスポンスデータ構造
	 * array[Matching.EquipParameter, ...]
	 * }
	 */
	public function getEquipParamAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$aids = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$eqs = $this->get('Arpg.Logic.Util.Equip')->getEquipedByActors($aids);

			$dat = [];
			foreach($eqs as $aid => $eq){
				$eparam = $this->get('Arpg.Logic.Matching.EquipParameter');
				$eparam->init($aid, $eq['card'],$eq['item']);
				$dat[]=$eparam;
			}
			return $dat;
		});
	}
	const std_equip_set = 6;
	const std_eset = 50050;
	const std_eset_w = 50051;
	const std_eset_h = 50052;
	const std_eset_c = 50053;
	const std_eset_n = 50054;
	const std_eset_r = 50055;
	const std_eset_i = 50056;
	const std_money		= 10000;
	const std_rare_spirit = 203008;
}
