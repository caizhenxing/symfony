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
use \Logic\AdvData\ReceiveData as ReceiveData;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\Arpg\Time as Time;

class TradeController extends \Dcs\DcsController{

	/**
	 * 交換所のメニューを取得
	 * リクエストデータ構造
	 * レスポンスデータ構造
	 * array [GameData.TradeMenu]
	 */
	public function getMenuAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			
			$rs = $this->selectHsCache(
					new Table('item_trade_type',['id','sub_menu_name','effective_from','effective_to','kind','navi'],'TYPE'),
					new Query(['='=>2],-1)
			);
			$now = new Time();
			$now = $now->get();
			$from = new Time();
			$to = new Time();
			
			$ret = [];
			foreach($rs as $row){
				$from->setMySQLDateTime($row[2]);
				$to->setMySQLDateTime($row[3]);
				if($now < $from->get() || $to->get() < $now)
					continue;
				$ret[] = [
						'name' => $row[1],
						'id' => intval($row[0]),
						'kind' => intval($row[4]),
						'navi' => intval($row[5]),
				];
			}
			usort($ret,function($a,$b){
				$a = $a['id'];
				$b = $b['id'];
				if ($a == $b) {
					return 0;
				}
				return ($a < $b) ? -1 : 1;
			});
			return $ret;
		});
	}
	/**
	 * 交換リストを取得
	 * リクエストデータ構造
	 * [
	 * 		'skey' => セッションキー
	 * 		'type' => トレードタイプ
	 * ]
	 * 
	 * レスポンスデータ構造
	 * array [GameData.Trade]
	 */
	public function getListAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$type = intval($data['type']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			
			$Stack = $this->get('Arpg.Logic.Util.StackItem');
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			
			// ショップデータ
			$shop = $this->getShopData($type);
			if($shop == null)
				throw new ResError("dont exists shop $type",100);
				
			$now = new Time();
			$now = $now->get();
			$from = new Time();
			$from = $from->setMySQLDateTime($shop['effective_from'])->get();
			$to = new Time();
			$to = $to->setMySQLDateTime($shop['effective_to'])->get();
			if($now < $from)
				throw new ResError("dont open shop $type",3100,['shop'=>$shop['title']]);
			if($to < $now)
				throw new ResError("already close shop $type",3101,['shop'=>$shop['title']]);

			$std_id = intval($shop['cost']);
			$name = '';
			if($Stack->check($std_id)){
				$name = $Stack->getData($std_id)['name'];
			}elseif($Pstatus->check($std_id)){
				$name = $Pstatus->getData($std_id)['name'];
			}
			
			$ret = [
				'title' => $shop['title'],
				'payName' => $name,
				'payStdId' => $std_id,
				'type' => $shop['disp_type'],
				'trade' => []
			];
			
			$rs = $this->selectHsCache(
					new Table('item_trade',[
							'std_id','category','category_priority','title','info',
							'display','pay_std_id','pay_num','items','limit',
							'effective_from','effective_to','banner','message','conf_mes',
							'err_mes','open_view','debug'],'TYPE'),
					new Query(['='=>$type],-1)
			);
			$now = new Time();
			$now = $now->get();
			$arg = [];
			$trade = [];
			$limit = [];
			foreach($rs as $row){
				$tid = intval($row[0]);
				$from = new Time();
				$from->setMySQLDateTime($row[10]);
				$to = new Time();
				$to->setMySQLDateTime($row[11]);
				$view = new Time();
				$view->setMySQLDateTime($row[16]);
				
				if(!\Dcs\Arpg\Config::Debug && intval($row[17]) != 0 ) // デバッグ用
					continue;
				if($now > $to->get()) // 期限切れ
					continue;
				if($now < $view->get()) // 表示前
					continue;

				$limit[$tid] = intval($row[9]);
				$arg[] = $tid;
				$buff = explode(',',$row[8]);
				$items = [];
				foreach($buff as $elem){
					$elem = explode(':',$elem);
					if(count($elem) != 2)continue;
					$std_id = intval($elem[0]);
					$num = intval($elem[1]);
					if($num > 0)
						$items[] =['stdId'=>$std_id,'num'=>$num];
				}
				$std_id = intval($row[6]);
				$name = '';
				if($Stack->check($std_id)){
					$name = $Stack->getData($std_id)['name'];
				}elseif($Pstatus->check($std_id)){
					$name = $Pstatus->getData($std_id)['name'];
				}
				$trade[$tid] = [
					'tradeId' => $tid,
					'displayType' => intval($row[5]),
					'category' => intval($row[1]),
					'categoryPriority' => intval($row[2]),
					'title' => $row[3],
					'info' => $row[4],
					'payStdId' => $std_id,
					'payNum' => intval($row[7]),
					'payName' => $name,
					'tradeMax' => 0,
					'startTime' => $from->get(),
					'endTime' => $to->get(),
					'fileBanner' => $row[12],
					'items' => $items,
					'message' => $row[13],
					'confMes' => $row[14],
					'errMes' => $row[15],
				];
			}
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			// アイテムカウント
			$count = $Pstatus->getStatusMulti($uid,$arg,false);
			
			// データ生成
			foreach($trade as $tid => $dat){
				if($limit[$tid] > 0 && $count[$tid] >= $limit[$tid]) continue;
				$dat['tradeMax'] = $limit[$tid]-$count[$tid];
				$ret['trade'][] = $dat;
			}
			return $ret;
		});
	}
	/**
	 * 交換実行を取得
	 * リクエストデータ構造
	 * [
	 * 		'skey'=> セッションキー
	 * 		'tid'=> トレードID
	 * ]
	 * 
	 * レスポンスデータ構造
	 * array [GameData.Reward]
	 */
	public function tradeAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$tid = intval($data['tid']);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();
			

			$rs = $this->selectHsCache(
					new Table('item_trade',['pay_std_id','pay_num','items','limit','effective_from','effective_to','title','type']),
					new Query(['='=>$tid])
			);
			if(empty($rs))
				throw new ResError("dont find tradeid:$tid",100);
			
			// 期間チェック
			$row = $rs[0];
			$now = new Time();
			$now = $now->get();
			$from = new Time();
			$from->setMySQLDateTime($row[4]);
			$to = new Time();
			$to->setMySQLDateTime($row[5]);
			if($now < $from->get() || $to->get() < $now)
				throw new ResError("outside the specified period tid:$tid ".$row[4].' ～ '.$row[5],100);

			// 最大値チェック
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			$count = $Pstatus->getStatus($uid,$tid,false);
			$limit = intval($row[3]);
			if($limit > 0 && $count >= $limit)
				throw new ResError("already reached max trade tid:$tid",100);

			// 受け取り
			$buff = explode(',',$row[2]);
			$items = [];
			foreach($buff as $elem){
				$elem = explode(':',$elem);
				if(count($elem) != 2)continue;
				$std_id = intval($elem[0]);
				$num = intval($elem[1]);
				if($num > 0)
					$items[] =[$std_id,$num];
			}
			$ttkey = 'Arpg.TradeController.tradeAction.TTkey';
			$trade_type = $this->cache()->get(\Dcs\Cache::TYPE_APC,$ttkey);
			if($trade_type == null){
				$rs2 = $this->getHs(false)->select(
						new Table('item_trade_type',['id','title']),
						new Query(['>='=>0],-1)
				);
				$trade_type = [];
				foreach($rs2 as $row2){
					$trade_type[intval($row2[0])] = $row2[1];
				}
				$this->cache()->set(\Dcs\Cache::TYPE_APC,$ttkey,$trade_type);
			}
			$ret = $this->get('Arpg.Logic.GameData.Reward')->add($uid,$items,$this->get('Arpg.Logic.Util.Text')->getText(10005,['[trade]'=>$trade_type[intval($row[7])]]));
				
			// 支払
			$Stack = $this->get('Arpg.Logic.Util.StackItem');
			$pstd_id = intval($row[0]);
			$pnum = intval($row[1]);
			try{
				if($Pstatus->check($pstd_id)){
					$Pstatus->add($uid,$pstd_id,-$pnum);
				}elseif($Stack->check($pstd_id)){
					$Stack->add($uid,$pstd_id,-$pnum);
				}
			}catch(\Exception $e){
				throw new ResError($e,100);// アイテム足りず
			}
			
			// カウント追加
			$Pstatus->add($uid,$tid,1,false);
			
			return $ret;
		});
	}
	
	private function getShopData($id){
		$fld = ['type','title','sub_menu_name','cost','disp_type','effective_from','effective_to','kind'];
		$id = intval($id);
		
		$cache = $this->cache();
		$key = 'Arpg.Controller.TradeController.getShopData.'.$id;
		$ret = $cache->get($cache::TYPE_APC,$key);
		if($ret == null){
			$rs = $this->getHs(false)->select(
					new Table('item_trade_type',$fld),
					new Query(['='=>$id])
			);
			if(empty($rs)) return null;
			$rs = $rs[0];
			$ret = [];
				
			for($i=0,$len=count($fld);$i<$len;++$i){
				$dat = $rs[$i];
				if(is_numeric($dat)){
					$it = intval($dat);
					$ft = $dat+0;
					if($it == $ft)
						$dat = $it;
					else
						$dat = $ft;
				}
				$ret[$fld[$i]] = $dat;
			}
			$ret['id'] = $id;
			$cache->set($cache::TYPE_APC,$key,$ret);
		}
		return $ret;
	}
}
?>