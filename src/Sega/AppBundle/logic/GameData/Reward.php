<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\GameData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\CardData as CardData;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\DetailTimeLog as DTL;
use \Dcs\Arpg\AddTimer as AD;

class Reward extends \Dcs\Arpg\Logic{
	public $type;
	public $name;
	public $stdId;
	public $num;
	public $item=null;
	public $card=null;
	
	public $isNew=false;
	public $isInPresentBox=false;
	
	const TYPE_NO = 0;
	const TYPE_PLAYER_STATUS = 1;
	const TYPE_ACTOR_STATUS = 2;
	const TYPE_STACK_ITEM = 3;
	const TYPE_CARD_DATA = 4;
	
	/**
	 * 初期化
	 * cardデータは倉庫に入っていない
	 * @param int $std_id
	 * @param int $num
	 */
	public function init($std_id,$num = 1){
		if($num < 1){
			$this->type = self::TYPE_NO;
			return;
		}
		$std_id = intval($std_id);
		$num = intval($num);
		$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$Equip = $this->get('Arpg.Logic.Util.Equip');
		$this->stdId = $std_id;
		$this->num = $num;
		if($Pstatus->check($std_id)){
			$this->type = self::TYPE_PLAYER_STATUS;
			$this->name = $Pstatus->getData($std_id)['name'];
		}elseif($Astatus->check($std_id)){
			$this->type = self::TYPE_ACTOR_STATUS;
			$this->name = $Astatus->getData($std_id)['name'];
		}elseif($Stack->check($std_id)){
			$this->type = self::TYPE_STACK_ITEM;
			$this->item = $this->get('Arpg.Logic.ItemData');
			$this->item->initData($std_id,$num);
			$this->name = $this->item->name;
		}elseif($Equip->check($std_id)){
			$this->type = self::TYPE_CARD_DATA;
			$card = $this->get('Arpg.Logic.CardData');
			$card->initData($std_id);
			$this->name = $card->name;
			$this->card=[];
			for($i=0;$i<$num;++$i)
				$this->card[]=$card;
		}else{
			$this->type = self::TYPE_NO;
		}
	}
	
	/**
	 * カードで初期化する
	 * @param array $cards [Logic\\CardData] 形式 カードのstdIDはすべて同じであること
	 */
	public function initByCard($cards){
		if(empty($cards)){
			$this->type = self::TYPE_NO;
			return;
		}
		$this->num = count($cards);
		$this->type = self::TYPE_CARD_DATA;
		$this->card = $cards;
		foreach($cards as $card){
			$this->stdId = $card->stdId;
			$this->name = $card->name;
			break;
		}
	}
	
	/**
	 * 報酬追加
	 * @param int $uid 追加するユーザーID
	 * @param array $list [[論理ID,数,強制倉庫フラグ(default:false)], ... ] 追加するデータリスト
	 * @param string|int $mes 入力するメッセージ または、lang_text_jpのid
	 * @param bool $strict 厳密にチェックを行う　デフォルト値false
	 * @return array [Reward]
	 */
	public function add($uid, $list, $mes='',$strict=false){
		DTL::Lap('Arpg.Logic.Util.GameData.Reward.add start');
		if(empty($list)) return [];
		$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$Equip = $this->get('Arpg.Logic.Util.Equip');
		$Present = $this->get('Arpg.Logic.Util.Present');
		$Collection = $this->get('Arpg.Logic.Util.Collection');

		$collection = [];
		$stdids = [];
		$ret = [];
		
		$add_p=[];
		$add_a=[];
		$add_s=[];
		$add_c=[];
		$add_pc=[];
		$add_pm=[];	// プレゼントマテリアル
		$aid = null;
		$free = null;
		
		DTL::Lap('reward prepare');
		
		for($i=0,$len=count($list);$i<$len;++$i){
			$line = $list[$i];
			$std_id = intval($line[0]);
			$num = intval($line[1]);
			$force_free = isset($line[2])?$line[2]:false;
			
			if($num < 1){
				if($strict)
					throw new ResError("std_id[$std_id] is zero reward.",100);
				else
					\Dcs\Log::e("std_id[$std_id] is zero reward.");
				continue;
			}
		
			$dat = null;
			if($Pstatus->check($std_id)){
				$dat = $this->get('Arpg.Logic.GameData.Reward');
				$dat->name = $Pstatus->getData($std_id)['name'];
				$dat->type = self::TYPE_PLAYER_STATUS;
				$add_p[] = [$uid,$std_id,$num];
			}elseif($Astatus->check($std_id)){
				if($aid == null)
					$aid = $Astatus->getActorId($uid);
				$dat = $this->get('Arpg.Logic.GameData.Reward');
				$dat->name = $Astatus->getData($std_id)['name'];
				$dat->type = self::TYPE_ACTOR_STATUS;
				$add_a[] = [$aid,$std_id,$num];
			}elseif($Stack->check($std_id)){
				$dat = $this->get('Arpg.Logic.GameData.Reward');
				$dat->type = self::TYPE_STACK_ITEM;
				$dat->item = $this->get('Arpg.Logic.ItemData');
				$dat->item->initData($std_id,$num);
				$dat->name = $dat->item->name;
				$add_s[] = [$uid,$std_id,$num];
			}elseif($Equip->check($std_id)){
				if($free === null)
					$free = $Equip->freeSpace($uid);
				if($Equip->std2type($std_id) == $Equip::TYPE_ETC){
					$dat = $this->get('Arpg.Logic.GameData.Reward');
					$dat->name = $Equip->getData($std_id)['name'];
					$dat->type = self::TYPE_CARD_DATA;
					$dat->card = [];
					if($free < $num && !$force_free){
						$dat->isInPresentBox = true;
						$cd = $this->get('Arpg.Logic.CardData');
						$cd->initData($std_id);
						$dat->card = array_fill(0,$num,$cd);
						$add_pm[] = [$std_id,$num];
					}else{
						for($j=0;$j<$num;++$j){
							$dat->card[$j] = null;
							$add_c[] = [$std_id,$Equip::STATE_HAS];
						}
						$free-=$num;
					}
				}else{
					$dat = $this->get('Arpg.Logic.GameData.Reward');
					$dat->name = $Equip->getData($std_id)['name'];
					$dat->type = self::TYPE_CARD_DATA;
					$dat->card = [];
					for($j=0;$j<$num;++$j){
						$dat->card[$j] = null;
						if($free > 0 || $force_free)
							$add_c[] = [$std_id,$Equip::STATE_HAS];
						else{
						$dat->isInPresentBox = true;
							$add_pc[] = $std_id;
						}
						--$free;
					}
				}
			}
			if($dat == null){
				if($strict)
					throw new ResError("std_id[$std_id] is not reward.",100);
				else
					\Dcs\Log::e("std_id[$std_id] is not reward.");
				continue;
			}
			$dat->stdId = $std_id;
			$dat->num = $num;
			$ret[]=$dat;
			$collection[] = [$std_id,$num];
			$stdids[$std_id] = $std_id;
		}

		DTL::Lap('reward collect');
		
		$ccount = $Collection->numMulti($uid,$stdids);
		foreach($ret as &$dat){
			if($dat->type == self::TYPE_STACK_ITEM || $dat->type == self::TYPE_CARD_DATA)
			$dat->isNew = $ccount[$dat->stdId] < 1;
		}
		unset($dat);
		
		$Pstatus->addMulti($add_p);
		$Astatus->addMulti($add_a);
		$Stack->addMulti($add_s);

		DTL::Lap('reward insert');
		
		$cards = array_merge($Equip->addMulti($uid,$add_c),$Present->addEquipItems($uid,$add_pc,$mes));
		$Present->addStackItems($uid,$add_pm,$mes,true);

		DTL::Lap('present insert');

		$sql = null;
		$args=[$uid];
		foreach($cards as $cid){
			if($sql == null)
				$sql = CardData::DBSQLCORE.' force index(UID) where uid=? and id in (?';
			else
				$sql .= ',?';
			$args[]=$cid;
		}
		if($sql != null){
			$sql .= ')';
			$stmt = $this->sql(CardData::DBTBL,$sql);
			$stmt->select($args);
			while($row = $stmt->fetch(\PDO::FETCH_NUM)){
				$card = $this->get('Arpg.Logic.CardData');
				$card->init($row);
				$do_break=false;
				foreach($ret as &$dat){
					if($dat->type != self::TYPE_CARD_DATA) continue;
					foreach($dat->card as &$c_box){
						if($c_box === null){
							$c_box = $card;
							$do_break = true;
							break;
						}
					}
					unset($c_box);
					if($do_break) break;
				}
				unset($dat);
			}
		}
		DTL::Lap('make reward data');
		$Collection->addMulti($uid,$collection);
		DTL::Lap('update collection');
		return $ret;
	}
}
?>