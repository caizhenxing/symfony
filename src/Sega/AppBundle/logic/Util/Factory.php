<?php
/**
 * メモ
 * 地道に高速化１を実装ずみ
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\GameData\RecipeData as RecipeData;
use \Logic\ItemData as ItemData;
use \Logic\CardData as CardData;
use \Dcs\Arpg\ResError as ResError;

class Factory extends \Dcs\Arpg\Logic{
	// 工場タイプ
	const PHARMACY	= 1;
	const MATERIAL	= 2;
	const MAGICAL	= 3;
	
	// SQL
	const SQL_LINENUP_OPEN = 'select type,std_id,num,create_time,price,material from factory_product_list where type = ? and level_open <= ? and level_close > ?';
	/**
	 * 現在の状態を取得
	 * @param int $uid
	 * @param int $type 工場タイプ
	 * @return array [
	 * 	'lv' => 工場レベル,
	 * 	'make' => 製造中STDID,
	 * 	'num' => 製造数,
	 * 	'end' => 終了時間,
	 * 	'money' => お金
	 * ]:
	 */
	public function getState($uid, $type){
		$sub = intval(10*($type-1));
		$slv = self::std_lv+$sub;
		$smk = self::std_mk+$sub;
		$snum = self::std_num+$sub;
		$send = self::std_end+$sub;
		
		$status = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatusMulti($uid,[$slv,$smk,$snum,$send,self::std_money]);
		
		return [
			'lv' => ($status[$slv] < 1)?1:$status[$slv],
			'make' => $status[$smk],
			'num' => $status[$snum],
			'end' => $status[$send],
			'money' => $status[self::std_money],
		];
	}
	/**
	 * 最大レベルかどうは判定
	 * @param int $type
	 * @param int $lv
	 * @return boolean 
	 */
	public function isMaxLv($type,$lv){
		$rs = $this->selectHsCache(
				new Table('factory_upgrade',RecipeData::$DBKEY),
				new Query(['=' => [$type, $lv]])
		);
		
		return count($rs) < 1;
	}
	
	/**
	 * アイテムを生成する
	 * @param int $uid
	 * @param int $type
	 * @param int $iid
	 * @param int $create_num
	 * @exception ResError
	 */
	public function make($uid, $type,$iid,$create_num){
		$state = $this->getState($uid,$type);
		if($state['num'] > 0)
			throw new ResError("new using factory. factory type $type",100);

		// 複雑なSQLなのでHSを使わない
		$rs = $this->selectSqlCache(self::SQL_LINENUP_OPEN,[$type,$state['lv'],$state['lv']]);
		$data = null;
		foreach($rs as $row){
			if(intval($row['std_id']) == $iid && intval($row['num']) == $create_num){
				$data = $row;
			}
		}
		if($data == null)
			throw new ResError('invalid data',100);

		$PStatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		
		$StackItem = $this->get('Arpg.Logic.Util.StackItem');
		$Equip = $this->get('Arpg.Logic.Util.Equip');
		
		$mat = explode(',',$data['material']);

		$stack = [];
		$equip = [];
		foreach($mat as $line){
			if(strlen($line) < 1) continue;
			list($std_id,$num) = explode(':',$line);
			if($StackItem->check($std_id))
				$stack[]=[$uid,intval($std_id), -intval($num)];
			elseif($Equip->check($std_id)){
				$std_id = intval($std_id);
				if(!isset($equip[$std_id]))
					$equip[$std_id] = 0;
				$equip[$std_id] += intval($num);
			}
		}
		
		$sql = null;
		$arg = [$uid];
		$dels = [];
		foreach($equip as $std_id=>$num){
			if($num < 1)
				continue;
			if($sql == null){
				$sql = 'select id,std_id from box_equip force index(UID) where uid = ? and state = 0 and std_id in (?';
			}else{
				$sql .= ',?';
			}
			$arg[] = $std_id;
		}
		if($sql != null){
			$sql .= ') for update';
			$stmt = $this->sql('box_equip',$sql);
			$rs = $stmt->selectAll($arg,\PDO::FETCH_NUM);
			
			$collect = [];
			foreach($rs as $row){
				$eq_id = intval($row[0]);
				$std_id = intval($row[1]);
				if(!isset($collect[$std_id]))
					$collect[$std_id] = [];
				$collect[$std_id][] = $eq_id;
			}
			foreach($equip as $std_id=>$num){
				if($num < 1)
					continue;
				if(!isset($collect[$std_id]) || count($collect[$std_id]) < $num)
					throw new ResError('too low equip materials.',100);
				for($i = 0;$i<$num;++$i){
					$dels[] = $collect[$std_id][$i];
				}
			}
		}
		
		$sub = intval(10*($type-1));
		$slv = self::std_lv+$sub;
		$stotal = self::std_total+$sub;
		$smk = self::std_mk+$sub;
		$snum = self::std_num+$sub;
		$send = self::std_end+$sub;
		
		// Update 開始
		// お金減少
		try{
			$PStatus->addMulti([
					[$uid,self::std_money,-intval($data['price'])],
					[$uid,$stotal,intval($data['price'])],
			]);
		}catch(\Exception $e){
			throw new ResError('factory make too low money',100);
		}
		// スタックアイテム減少
		try{
			$StackItem->addMulti($stack);
		}catch(\Exception $e){
			throw new ResError('factory make too low materials.'.json_encode($stack),100);
		}
		// 装備アイテム減少
		if(!$Equip->delMulti($uid,$dels))
			throw new ResError('factory make too low equip materials.',100);
		

		$time = new \Dcs\Arpg\Time();
		$PStatus->setMulti([
				[$uid,$smk,$iid],
				[$uid,$snum,$create_num],
				[$uid,$send,$time->get()+intval($data['create_time'])]
		]);
	}
	
	/**
	 * 完成した物品を倉庫に移動
	 * @param unknown $uid
	 * @param unknown $type
	 * @param array Arpg.Logic.GameData.Reward
	 */
	public function move($uid, $type,&$item,&$cards,&$present){
		$sub = intval(10*($type-1));
		$smk = self::std_mk+$sub;
		$snum = self::std_num+$sub;
		$send = self::std_end+$sub;
		$scount = self::std_create_count+$sub;
		
		$PStatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		$status = $PStatus->getStatusMulti($uid,[$snum,$smk,$send]);
		if($status[$snum] < 1)
			throw new ResError("dont make item in factory[$type].",100);
		
		$now = new \Dcs\Arpg\Time();
		if($now->get() < $status[$send])
			throw new ResError("item dont complete factory[$type].",100);
		
		$ret = $this->get('Arpg.Logic.GameData.Reward')->add($uid,[[$status[$smk],$status[$snum]]]);
		if(empty($ret))
			throw new ResError("item dont create item factory[$type] std_id[".$status[$smk].'].',100);
		$ret = $ret[0];
		
		$PStatus->set($uid,$snum,0);
		$adder = [
			[$uid,$scount,1],
			[$uid,self::std_create_count+30,1],
			[$uid,$status[$smk],$status[$snum]],
			[$uid,2000000+$status[$smk],$status[$snum]]
		];
		$PStatus->addMulti($adder,false);
		
		return $ret;
	}
	
	/**
	 * 製造中のアイテムを中止する
	 * アイテムは帰ってこない
	 * @param int $uid
	 * @param int $type
	 * @return array ['code'=>エラーコード,'mes'=>メッセージ]を返す nullの場合成功
	 */
	public function cancel($uid, $type){
		$sub = intval(10*($type-1));
		$smk = self::std_mk+$sub;
		$snum = self::std_num+$sub;
		$send = self::std_end+$sub;

		$PStatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		$status = $PStatus->getStatusMulti($uid,[$snum,$send]);
		if($status[$snum] < 1)
			throw new ResError("dont make item in factory[$type].",100);
		
		$PStatus->set($uid,$snum,0);
	}
	
	/**
	 * 工場を拡張する
	 * @param int $uid
	 * @param int $type
	 * @return array ['code'=>エラーコード,'mes'=>メッセージ]を返す nullの場合成功
	 */
	public function upgrade($uid, $type){
		$sub = intval(10*($type-1));
		$slv = self::std_lv+$sub;
		$stotal = self::std_total+$sub;
		$PStatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		$lv = $PStatus->getStatus($uid, $slv);
		
		$rs = $this->selectHsCache(
				new Table('factory_upgrade',RecipeData::$DBKEY),
				new Query(['=' => [$type, $lv]])
		);
		if(count($rs) < 1)
			throw new ResError("factory[$type] lv is max.",100);

		$recipe = $this->get('Arpg.Logic.GameData.RecipeData');
		$recipe->initHs($rs[0]);
		
		try{
			$PStatus->addMulti([
					[$uid,self::std_money,-$recipe->price],
					[$uid,$stotal,$recipe->price],
			]);
		}catch(\Exception $e){
			throw new ResError('factory upgrade too low money.',100);
		}

		$StackItem = $this->get('Arpg.Logic.Util.StackItem');
		$stack = [];
		for($i=0;$i<count($recipe->matIds);++$i){
			$std_id = $recipe->matIds[$i];
			$num = $recipe->matNums[$i];
			$stack[] = [$uid,$std_id, -$num];
		}
		try{
			$StackItem->addMulti($stack);
		}catch(\Exception $e){
			throw new ResError('factory upgrade too low materials.'.json_encode($stack),100);
		}
		
		$PStatus->set($uid,$slv,$lv+1);
	}


	/**
	 * 妖精を使用する
	 * @param int $uid
	 * @param int $type
	 */
	public function useSpirit($uid, $type){
		$PStatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$sub = intval(10*($type-1));
		$send = self::std_end+$sub;
		
		$now = new \Dcs\Arpg\Time();
		if($PStatus->getStatus($uid,$send) < $now->get()){
			throw new ResError("already complete in factory[$type].",100);
		}
		$done = false;
		try{
			$Stack->add($uid,self::std_spirit,-1);
			$done = true;
		}catch(\Exception $e){}
		if(!$done){
			try{
				$Gparam = $this->get('Arpg.Logic.Util.GameParam');
				$PStatus->add($uid,self::std_cp,-$Gparam->getParam(GameParam::SPIRIT_CP));
				$done=true;
			}catch(\Exception $e){}
		}
		if(!$done){
			throw new ResError('dont has spirit cost',100);
		}
		
		$PStatus->set($uid,$send,0);
		$PStatus->add($uid,self::std_use_spirit_count,1);
	}
	

	// STDID
	const std_lv = 100;
	const std_total = 101;
	const std_mk = 102;
	const std_num = 103;
	const std_end = 104;
	const std_money = 10000;
	const std_cp = 10001;
	const std_spirit = 203003;
	const std_use_spirit_count = 130;
	const std_create_count = 105;
}

?>