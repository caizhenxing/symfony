<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\OrderData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\Util\GameParam as GameParam;
use \Dcs\Arpg\ResError as ResError;
use \Logic\CardData;

class Train extends \Dcs\Arpg\Logic{
	public $beforePer;
	public $afterPer;
	
	public $afterCard;
	
	public $money;
	public $result;
	
	public $greatRate;
	
	/**
	 * 強化実行
	 * @param int $uid ユーザーID
	 * @param int $cid カードユニークID
	 * @param array $mats 素材装備倉庫内IDリスト
	 * @return boolean true:実行できた false:データ不整合
	 */
	public function run($uid,$cid,$mats) {
		// マイナスの個数はだめ
		if(count($mats) <= 0) 
			throw new ResError('dont select material.',100);
		
		$Equip = $this->get('Arpg.Logic.Util.Equip');
		$PState = $this->get('Arpg.Logic.Util.PlayerStatus');
		$GParam = $this->get('Arpg.Logic.Util.GameParam');

		if(!$Equip->delMulti($uid,$mats))
			throw new ResError('dont have material card.',100);
		
		$sql = 'select id,uid,std_id,level,exp,evo,state from box_equip where id in(? ';
		foreach($mats as $mat){
			$sql .= ',?';
		}
		$sql .= ')';
		$arg = $mats;
		$arg[] = $cid;
		$stmt = $this->sql('box_equip',$sql);
		$stmt->select($arg);
		
		// 各アイテムを収集
		$base_item=null;
		$mat_items=[];
		$std_ids =[];
		while($item = $stmt->fetch(\PDO::FETCH_NUM)){
			$item['id'] = intval($item[0]);
			$item['uid'] = intval($item[1]);
			$item['std_id'] = intval($item[2]);
			$item['level'] = intval($item[3]);
			$item['exp'] = intval($item[4]);
			$item['evo'] = intval($item[5]);
			$item['state'] = intval($item[6]);
			
			if($item['uid'] != $uid) continue;
			$std_ids[] = $item['std_id'];
			
			$item['type'] = $Equip->std2type($item['std_id']);
			if($item['type'] == $Equip::TYPE_WEAPON){
				$item['type'] = 100+$Equip->std2wtype($item['std_id']);
			}
			if($item['id'] == $cid){
				if($item['state'] == 0){
					$base_item = $item;
				}
			}else{
				$mat_items[] = $item;
			}
		}
		$binfos = $Equip->getDatas($std_ids);
		
		if($base_item != null){
			$binfo = $binfos[$base_item['std_id']];
			$base_item['attr'] = $binfo['attribute'];
			$base_item['cost'] = $Equip->getTrainCost($binfo['grow_type'],$base_item['level']);
		}
		foreach($mat_items as &$item){
			$binfo = $binfos[$item['std_id']];
			$item['attr'] = $binfo['attribute'];
			$item['train_exp'] = $binfo['train_exp'];
			$item['rare_spirit'] = $binfo['rare_spirit'];
		}
		unset($item);
		
		$this->result = self::SUCCESS;
		// 大成功
		if($GParam->getParam(GameParam::TRAIN_SSUC_RATE) > (mt_rand(0,10000)/100)){
			$this->result = self::GREAT_SUC;
		}
		
		$this->greatRate = $GParam->getParam(GameParam::TRAIN_SSUC_BONUS);
		
		// 経験値を取得
		$aexp = 0;
		$amoney = 0;
			$rareSpirit = 0;
		foreach($mat_items as $item){
			$exp_rate = $GParam->getParam($GParam::TRAIN_EXP_RATE);
			$rate = 1;
			if($base_item['attr'] == $item['attr'])
				$rate *= $GParam->getParam($GParam::TRAIN_ATTR_BONUS);
			/*if($base_item['type'] == $item['type'])
				$rate *= $GParam->getParam($GParam::TRAIN_TYPE_BONUS);
				*/
			if($this->result == self::GREAT_SUC)
				$rate *= $GParam->getParam($GParam::TRAIN_SSUC_BONUS);

			$aexp += ($item['train_exp']+intval(($item['level']-1)*$exp_rate))*$rate;
			$amoney += $base_item['cost'];
			$rareSpirit += $item['rare_spirit'];
		}
		if($base_item != null){
			
			
			$money = $PState->getStatus($uid,self::std_money);
			
			$std_id = $base_item['std_id'];
			
			$data = $Equip->getData($std_id);
			
			$lv = $base_item['level'];
			$exp = $base_item['exp'];

			$evo = $base_item['evo'];
			$gtype = intval($data['grow_type']);

			$mexp = $Equip->getMaxExp($gtype,$lv);
			if($mexp < 1)
				throw new ResError('card is max train.',100); 
			
			$this->beforePer = $lv*100+intval($exp*100/$mexp);
			
			$mlv = $data['evo'.$evo.'_maxlv'];
			
			if($money < $amoney)
				throw new ResError('too low money.',200);

			$exp += $aexp;
			while($lv<$mlv){
				$mexp = $Equip->getMaxExp($gtype,$lv);
				if($exp < $mexp) break;
				$exp -= $mexp;
				++$lv;
			}
			if($lv >= $mlv){
				$lv = $mlv;
				$exp = 0;
			}
			
			
			$this->afterPer = $lv*100+intval($exp*100/$mexp);
			
			$this->money = $money - $amoney;
			
			$this->useTransaction();
			if($this->sql('box_equip','update box_equip set level=?, exp=? where id=? and state=0')->update([$lv,$exp,$cid]) < 1){
				throw new \Exception("dont update box_equip data sql: update box_equip set level=?, exp=? where id=? and state=0  arg: $lv, $exp, $cid");
			}
			$PState->set($uid,self::std_money,$this->money);
			
			$cardData = $this->get('Arpg.Logic.CardData');
			$cardData->init($this->sql(CardData::DBTBL,CardData::DBSQLCORE.' force index(`IS`) where state=0 and id=? limit 1')->selectOne([$cid],\PDO::FETCH_NUM));
			$this->afterCard = $cardData;
			
			// 各種回数をカウント
			$PState->addMulti([
					[$uid,2000000+$std_id,1],
					[$uid,self::std_train_count,1],
					[$uid,self::std_train_succount,$this->result == self::GREAT_SUC?1:0]
			],false);
			// 稀霊石
			$this->get('Arpg.Logic.Util.StackItem')->add($uid,self::std_rare_spirit,$rareSpirit);
			
			return;
		}
		throw new ResError('dont exists item.',1000);
		
	}
	
	//STD_ID
	const std_money = 10000;
	const std_train_count = 320;
	const std_train_succount = 321;
	const std_rare_spirit = 203008;
	
	const SUCCESS = 1;
	const GREAT_SUC = 2;
}
?>