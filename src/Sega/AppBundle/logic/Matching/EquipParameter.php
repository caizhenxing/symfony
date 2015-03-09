<?php
namespace Logic\Matching;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\Util\Equip as Equip;

/**
 * マッチング後ダンジョン用データ
 */
class EquipParameter extends \Dcs\Arpg\Logic{
	public $actor_id;
	public $hp=0;
	public $atk=0;		// PPA / c1
	public $def=0;		// PPDh + PPDa
	public $matk=0;		// PMA / c2
	public $mdef=0;		// PMDh + PMDa
	public $tatk=0;		// ( PPA + PMA ) / c5 （剣霊必殺技用）
	public $pr_atk=[1,1,1,1,1,1,1]; // 6個分 (属性)
	public $pr_def=[1,1,1,1,1,1,1];	// 6個分（属性）
	public $critical=0;
	public $critical_val=1;
	public $shield=0;
	public $c3=0;
	public $c4=0;
	public $skill=[];		// スキルstd_id(0,1:通常スキル、2:剣霊必殺技)
	public $w_std_id=0;	// 武器のstdid
	public $w_rarity=0;	// 武器のレアリティ
	public $w_attribute=0;	// 武器の属性
	public $teq_effect=0;	// 召喚技エフェクト

	public $sp_duns; // 特効ダンジョンID
	public $sp_rate; // 特効倍率
	
	// アドオン関連でベースパラメータがないもの
	public $auto_guard=0;
//	public $s_recover=0;		// スキル回復増加率
	public $t_recover=0;		// 剣霊ゲージ増加率
	public $stiff_rate=0;	// マヒ確率
	public $poison_rate=0;	// 毒確率
	public $stiff_resist=0;	// マヒ耐性
	public $poison_resist=0;	// 毒耐性
	public $weak_resist=0;	// 虚弱耐性
	public $angry_resist=0;	// 怒り耐性
	public $confuse_resist=0;// 混乱耐性

	public $add_hp=0;			// HP吸収
	public $add_skill=0;			// スキル回復倍率
	public $add_fire=0;			// 火魔法発動率
	public $add_water=0;			// 水魔法発動率
	public $add_storm=0;			// 風魔法発動率
	public $add_holy=0;			// 光魔法発動率
	public $add_dark=0;			// 闇魔法発動率
	public $add_boost=0;			// 被ダメ時硬化率
	public $add_potion=0;		// ポーション強化
	public $evade_death=0;			// 即死回避
	public $add_critical=0;		// クリティカル威力増加
	public $add_buff=0;			// バフ延長
	public $add_heal=0;			// ヒール強化

	public $items=[];
	
	
	///////////////////////////////////////////
	private $attr_resist=1;
	private $attr_atk=1;
	private $PPA=0;
	private $PMA=0;
	private $attr_regists=[1,1,1,1,1,1,1];
	/**
	 * 初期化
	 * @param array $cards カードデータリスト 同一アクター
	 * @param array $items [ItemData, ...]
	 */
	public function init($aid, array $cards, array $items){
		$Equip = $this->get('Arpg.Logic.Util.Equip');
		$Dparam = $this->get('Arpg.Logic.Util.DevParam');
		$this->PPA=0;
		$this->PMA=0;
		$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
		$uid = $Astatus->getUserId($aid);
		$this->actor_id = $aid;
		
		$this->attr_atk=1;
		$this->pr_atk=[1,1,1,1,1,1,1];
		$this->pr_def=[1,1,1,1,1,1,1];
		$this->attr_regists=[1,1,1,1,1,1,1];
		$this->attr_resist=1;
		$series=[];
		$type_check=[];
		$wtype = Equip::WEAPON_NONE;
		foreach($cards as $card){
			$this->PPA += $card->phisicalAttack;
			$this->PMA += $card->magicalAttack;
			$this->def += $card->phisicalDefence;
			$this->mdef += $card->magicalDefence;
			$this->critical += $card->criticalRate;
			$this->critical_val += $card->critivalPower;
			$this->shield += $card->shieldRate;
			$type = $Equip->std2type($card->stdId);
			
			if(!isset($type_check[$type])){
				$type_check[$type] = true;
				if(!isset($series[$card->ser_stdid])){
					$series[$card->ser_stdid] = 1;
				}else{
					++$series[$card->ser_stdid];
				}
			}
			
			if($type == Equip::TYPE_WEAPON){
				$wtype = $Equip->std2wtype($card->stdId);
				$this->skill = $card->skills;
				for($i=count($this->skill);$i<2;++$i){
					$this->skill[]=0;
				}
				$this->skill[2] = $card->tec_stdid;
				$this->teq_effect = $card->teqEffect();
				$this->w_std_id = $card->stdId;
				$this->w_rarity = $card->rarity;
				$this->w_attribute = $card->attr;
				$this->sp_duns = $card->spID();
				$this->sp_rate = $card->spRate();
			}
			foreach($card->addons as $a_stdid){
				$addon = $this->get("Arpg.Logic.CardData.Addon");
				if(!$addon->init($a_stdid))continue;
				$this->addAddon($addon->type,$addon->pow());
			}
		}
		
		$Series = $this->get('Arpg.Logic.Util.EquipSeries');
		// シリーズ効果算出
		foreach($series as $std_id => $num){
			if($num < 2) continue;
			$data = $Series->getData($std_id);
			if(!isset($data['lv'.$num]) || empty($data['lv'.$num])) continue;
			foreach($data['lv'.$num] as $aid => $pow){
				$this->addAddon($aid,$pow);
			}
		}
		
		for($i=1;$i<7;++$i){
			$this->pr_atk[$i] *= $this->attr_atk;
		}
		
		$pr1 = $Dparam->param(61);
		$pr2 = $Dparam->param(62);
		foreach($cards as $card){
			for($i=1;$i<7;++$i){
				$this->pr_def[$i] += $this->attRate($pr1, $pr2, $card,$i);
			}
		}
		for($i=1;$i<7;++$i){
			$this->pr_def[$i] *= ($this->attr_regists[$i]<0?0:$this->attr_regists[$i]);
		}
		$this->tatk = ($this->PPA+$this->PMA)/$Dparam->param(10);
		$this->atk = $this->PPA/$Dparam->param(1);
		$this->matk = $this->PMA/$Dparam->param(2);
		$this->def *= $Dparam->param(57);
		$this->mdef *= $Dparam->param(58);
		$this->c3 = $Dparam->param(5);
		$this->c4 = $Dparam->param(6);
		$this->items = [];
		$inum = [];
		foreach($items as $i){
			if($i == null) continue;
			$inum[] = $i->stdId;
		}
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$inum = $Stack->getNumMulti($uid,$inum);
		for($i=0;$i<\Logic\Util\Equip::MAX_SUPPLIES;++$i){
			$it = $items[$i];
			$std_id = 0;
			$num = 0;
			$name = '';
			if($it != null){
				$std_id = $it->stdId;
				$name = $it->name;
				$num = $inum[$std_id];
				$num = $num>$it->nbEquip?$it->nbEquip:$num;
				$inum[$std_id] -= $num;
			}
			$this->items[$i] = ['id'=>$std_id,'num'=>$num,'name'=>$name];
		}
		$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		$plv = $Pstatus->getStatus($Astatus->getUserId($this->actor_id),self::std_player_lv);
		$this->hp += $Pstatus->getLvData($plv)['hp'];
	}
	private function addAddon($type,$pow){
		$type = intval($type);
		$pow = $pow+0;
		switch($type){
			case 1:
				$this->hp += $pow;
				break;
			case 2:
				$this->PPA += $pow;
				break;
			case 3:
				$this->def += $pow;
				break;
			case 4:
				$this->PMA += $pow;
				break;
			case 5:
				$this->mdef += $pow;
				break;
			case 6:
				$this->critical += $pow/100;
				break;
			case 7:
				$this->auto_guard += $pow/100;
				break;
			case 8:
				$this->add_skill += $pow/100;
				break;
			case 9:
				$this->t_recover += $pow/100;
				break;
			case 10:
				$this->stiff_rate += $pow/100;
				break;
			case 11:
				$this->poison_rate += $pow/100;
				break;
			case 12:
				$this->attr_atk += $pow/100;
				break;
			case 13:
				$this->stiff_resist += $pow/100;
				break;
			case 14:
				$this->poison_resist += $pow/100;
				break;
			case 15:
				$this->attr_resist += $pow/100;
				break;
			case 16:
				$this->confuse_resist += $pow/100;
				break;
			case 17:
				$this->angry_resist += $pow/100;
				break;
			case 18:
				$this->weak_resist += $pow/100;
				break;
			case 19:
				$this->add_hp += $pow;
				break;
			case 20:
				$this->add_fire += $pow/100;
				break;
			case 21:
				$this->add_water += $pow/100;
				break;
			case 22:
				$this->add_storm += $pow/100;
				break;
			case 23:
				$this->add_holy += $pow/100;
				break;
			case 24:
				$this->add_dark += $pow/100;
				break;
			case 25:
				$this->add_boost += $pow/100;
				break;
			case 26:
				$this->add_potion += $pow;
				break;
			case 27;
				$this->evade_death += $pow;
				break;
			case 28:
				$this->add_critical += $pow;
				break;
			case 29:
				$this->add_buff += $pow;
				break;
			case 30:
				$this->add_heal += $pow;
				break;
			case 31:
				if($wtype == Equip::WEAPON_SWORD){
					$PPA += $pow;
					$PMA += $pow;
				}
				break;
			case 32:
				if($wtype == Equip::WEAPON_HAMMER){
					$PPA += $pow;
					$PMA += $pow;
				}
				break;
			case 33:
				if($wtype == Equip::WEAPON_ROD){
					$PPA += $pow;
					$PMA += $pow;
				}
				break;
			case 34:
				$this->stiff_resist += $pow/100;
				$this->poison_resist += $pow/100;
				$this->weak_resist += $pow/100;
				$this->angry_resist += $pow/100;
				$this->confuse_resist += $pow/100;
				break;
			case 35:
				$this->pr_atk[1] += $pow/100;
				break;
			case 36:
				$this->pr_atk[2] += $pow/100;
				break;
			case 37:
				$this->pr_atk[3] += $pow/100;
				break;
			case 38:
				$this->pr_atk[4] += $pow/100;
				break;
			case 39:
				$this->pr_atk[5] += $pow/100;
				break;
			case 40:
				$this->pr_atk[6] += $pow/100;
				break;
			case 41:
				$this->attr_regists[1] -= $pow/100;
				break;
			case 42:
				$this->attr_regists[2] -= $pow/100;
				break;
			case 43:
				$this->attr_regists[3] -= $pow/100;
				break;
			case 44:
				$this->attr_regists[4] -= $pow/100;
				break;
			case 45:
				$this->attr_regists[5] -= $pow/100;
				break;
			case 46:
				$this->attr_regists[6] -= $pow/100;
				break;
			default:
				break;
		}
	}
	/**
	 * 
	 * @param unknown $card
	 * @param unknown $enemy
	 */
	private function attRate($pr1, $pr2, $card,$enemy){
		$base = 0;
		if($enemy == 0)
			return 0;
		elseif($card->type == Equip::TYPE_COSTUME)
			$base = $pr1*$this->attr_resist;
		elseif($card->type == Equip::TYPE_HEADGEAR)
			$base = $pr2*$this->attr_resist;
		else
			return 0;
		$rate = 0;
		$player = intval($card->attr);
		$enemy = intval($enemy);
		
		if($player == $enemy){
			// 同種
			$rate = 0;
		}elseif($player == 6){
			// 自分が無
			$rate = 0;//1;
		}elseif($enemy == 6){
			// 相手が無
			$rate = 0; //-1;
		}elseif($player == 4){
			// 自分が光
			if($enemy == 5)
				$rate = -1;
		}elseif($player == 5){
			// 自分が闇
			if($enemy == 4)
				$rate = -1;
		}elseif(($player%3)+1 == $enemy){
			$rate = 1;
		}elseif((($player+1)%3)+1 == $enemy){
			$rate = -1;
		}
		return $rate*$base;
	}
	const std_player_lv = 1;
}

?>