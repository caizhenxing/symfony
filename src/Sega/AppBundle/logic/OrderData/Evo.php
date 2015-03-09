<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\OrderData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\ResError as ResError;


class Evo extends \Dcs\Arpg\Logic{
	public $maxLv;
	public $physicAtk; // 攻撃力/防御力
	public $magicAtk;
	public $physicDef;
	public $magicDef;
	public $addonSlot;
	
	public $recipe;

	public $money;
	
	/**
	 * 進化実行
	 * @param int $uid
	 * @param int $cid
	 */
	public function run($uid, $cid) {
		$rs = $this->getHs()->select(
				new Table('box_equip',['uid','std_id', 'evo','state','level','evo_bonus_atk','evo_bonus_matk','evo_bonus_def','evo_bonus_mdef']),
				new Query(['=' => $cid])
		);

		foreach($rs as $row){
			if(intval($row[0]) != $uid) continue;
			if(intval($row[3]) > 0) continue;
			
			
			$PState = $this->get('Arpg.Logic.Util.PlayerStatus');
			$Stack = $this->get('Arpg.Logic.Util.StackItem');
			$Equip = $this->get('Arpg.Logic.Util.Equip');
			$status = $PState->getStatusMulti($uid,[self::std_money,self::std_evo_max]);
			$money = $status[self::std_money];
			$now_evo_max = $status[self::std_evo_max];

			$std_id = intval($row[1]);
			$evo = intval($row[2]);
			$lv = intval($row[4]);
			$b_atk = $row[5]+0;
			$b_matk = $row[6]+0;
			$b_def = $row[7]+0;
			$b_mdef = $row[8]+0;
			
			$data = $Equip->getData($std_id);
			
			$maxlv = $data['evo'.$evo.'_maxlv'];
			$bonus_rate = ($lv-1)/($maxlv-1);
			$evomax = $data['evo_max'];

			$recipe = $Equip->getRecipe($std_id, $evo);
			if($evomax <= $evo)
				throw new ResError('this card is max evo.',100);
			if($recipe->price > $money)
				throw new ResError('too low money.',200);
			
			$this->money = $money-$recipe->price;

			$sub_stack=[];
			$sub_equip=[];
			$list = [];
			for($i=0;$i<count($recipe->matIds);++$i){
				if($recipe->matNums[$i] < 1) continue;
				$matid = $recipe->matIds[$i];
				if($Stack->check($recipe->matIds[$i]))
					$sub_stack[]=[$uid,$matid,-$recipe->matNums[$i]];
				else{
					if(!isset($sub_equip[$matid]))
						$sub_equip[$matid] = 0;
					$sub_equip[$matid] += $recipe->matNums[$i];
				}
			}
			$qus=[];
			foreach($sub_equip as $matid=>$num){
				$qus[]=new Query(['='=>[$uid,0,$matid]],$num);
			}
			$rss = $this->getHs()->selectMulti(new Table('box_equip',['id'],'UID'),$qus);
			$dels = [];
			for($i=0;$i<count($qus);++$i){
				$rs = $rss[$i];
				$q = $qus[$i];
				if(count($rs) != $q->limit)
					throw new ResError('too low equip materials.',100);
				foreach($rs as $row){
					$dels[] = intval($row[0]);
				}
			}
			
			$evo = intval($evo+1);
			
			$b_atk += $data['evo'.$evo.'_static_bonus_atk']*$this->bonus($bonus_rate);
			$b_matk += $data['evo'.$evo.'_static_bonus_matk']*$this->bonus($bonus_rate);
			$b_def += $data['evo'.$evo.'_static_bonus_def']*$this->bonus($bonus_rate);
			$b_mdef += $data['evo'.$evo.'_static_bonus_mdef']*$this->bonus($bonus_rate);
			
			$this->useTransaction();
			if($this->sql('box_equip','update box_equip set evo=?,evo_bonus_atk=?,evo_bonus_matk=?,evo_bonus_def=?,evo_bonus_mdef=? where id=? and state=0')->update([$evo,$b_atk,$b_matk,$b_def,$b_mdef,$cid]) < 1)
				throw new \Exception('dont update box_equip data');
			
			$PState->setMulti([
					[$uid,self::std_money,$this->money],
					[$uid,self::std_evo_max,$now_evo_max < $evo?$evo:$now_evo_max]
			]);

			// スタックアイテム減少
			try{
				$Stack->addMulti($sub_stack);
			}catch(\Exception $e){
				throw new ResError($e,100);
			}
			// 装備アイテム減少
			if(!$Equip->delMulti($uid,$dels))
				throw new ResError('too low equip materials.',100);
			
			$this->maxLv = intval($data['evo'.$evo.'_maxlv']);
			
			$grow = $Equip->getGrowRate($data['grow_type'],$lv);
			$this->physicAtk = intval(($data['atk']+$b_atk)*$grow);
			$this->magicAtk = intval(($data['matk']+$b_matk)*$grow);
			$this->physicDef = intval(($data['def']+$b_def)*$grow);
			$this->magicDef = intval(($data['mdef']+$b_mdef)*$grow);
			$this->addonSlot = intval($evo+1);
			$this->recipe = $Equip->getRecipe($std_id,$evo);
			
			// 各種回数をカウント
			$PState->addMulti([
					[$uid,3000000+$std_id,1],
					[$uid,self::std_evo_count,1]
			],false);
			
			return;
		}
		throw new ResError('dont has card.',1000);
	}
	
	/**
	 * 裏ワザ用
	 * おこられたら抜く
	 */
	private function bonus($brate){
		$rand = mt_rand(0,1000000);
		if($brate*10 > $rand)
			return 2;
		if($brate*50 > $rand)
			return 1.5;
		if($brate*150 > $rand)
			return 1.1;
		if($brate*500 > $rand)
			return 1.02;
		return 1;
	}
	//STD_ID
	const std_money = 10000;
	const std_evo_count = 330;
	const std_evo_max = 331;
}
?>