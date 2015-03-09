<?php
namespace Logic;

use \Logic\Util\Equip as Equip;
use \Dcs\Arpg\AddTimer as AD;

class CardData extends \Dcs\Arpg\Logic{
	//const DBSQLBASE = 'select ed.`gender` as gender, ed.`std_id` as std_id, ed.`name` as `name`,ed.addon_max as addon_max,ed.atk_speed as atk_speed, ed.move_speed as move_speed, be.id as id, be.`level` as lv, be.exp as exp, be.skill as skill, be.addon as addon, be.evo as evo, ed.atk as atk, ed.def as def, ed.matk as matk, ed.mdef as mdef, ed.cost as cost, ed.attribute as attr, ed.job as job, ed.train_exp as train_exp, ed.techniq as teq, ed.grow_type as grow_type, ed.critical as critical, ed.critical_val as critical_val, ed.shield as shield, ed.shield_val as shield_val, ed.fall as fall, ed.info as info, ed.rarity as rarity, ed.sell as sell, be.actor_id as actor_id from box_equip as be left join equip_data as ed on be.std_id = ed.std_id where be.state=0';
	//const DBSQL = 'select ed.`gender` as gender, ed.`std_id` as std_id, ed.`name` as `name`,ed.addon_max as addon_max,ed.atk_speed as atk_speed, ed.move_speed as move_speed, be.id as id, be.`level` as lv, be.exp as exp, be.skill as skill, be.addon as addon, be.evo as evo, ed.atk as atk, ed.def as def, ed.matk as matk, ed.mdef as mdef, ed.cost as cost, ed.attribute as attr, ed.job as job, ed.train_exp as train_exp, ed.techniq as teq, ed.grow_type as grow_type, ed.critical as critical, ed.critical_val as critical_val, ed.shield as shield, ed.shield_val as shield_val, ed.fall as fall, ed.info as info, ed.rarity as rarity, ed.sell as sell, be.actor_id as actor_id from box_equip as be left join equip_data as ed on be.std_id = ed.std_id where be.state=0 and be.uid = ?';

	const DBSQLCORE	= 'select id,`level`,exp,skill,addon,evo,std_id,evo_bonus_atk,evo_bonus_matk,evo_bonus_def,evo_bonus_mdef,state,`lock` from box_equip';
//	const DBSQLBASE	= 'select id,`level`,exp,skill,addon,evo,std_id,evo_bonus_atk,evo_bonus_matk,evo_bonus_def,evo_bonus_mdef,state,`lock` from box_equip where state=0';
//	const DBSQL		= 'select id,`level`,exp,skill,addon,evo,std_id,evo_bonus_atk,evo_bonus_matk,evo_bonus_def,evo_bonus_mdef,state,`lock` from box_equip where state=0 and uid = ?';
	
	/**
	 * HandlerSocket用テーブル名
	 * @var string
	 */
	const DBTBL = 'box_equip';
	/**
	 * HandlerSocket用フィールドリスト
	 * @var array
	 */
	public static $CLMS = ['id','level','exp','skill','addon','evo','std_id','evo_bonus_atk','evo_bonus_matk','evo_bonus_def','evo_bonus_mdef','state','lock'];
	
	/**
	 * HandlerSocket用UID検索インデックス
	 * @var string
	 */
	const IDX_UID = 'UID';

	/**
	 * HandlerSocket用ID-UserID-Status検索インデックス
	 * @var string
	 */
	const IDX_IUS = 'IUS';
	
	/**
	 * HandlerSocket用ID-Status検索インデックス
	 * @var string
	 */
	const IDX_IS = 'IS';
	
	/**
	 * データ初期化
	 * @param array $row DB**定数で取得したデータ sqlはFETCH_NUM,hsはそのまま
	 */
	public function init($row){
		$Equip = $this->get('Arpg.Logic.Util.Equip');
		
		$std_id = intval($row[6]);
		
		$this->initData($std_id);
		
		$binfo = $Equip->getData($std_id);
		$skill = $row[3];
		$addon = $row[4];
		$grow_type = intval($binfo['grow_type']);
		$evo = intval($row[5]);
		$b_atk = $row[7]+0;
		$b_matk = $row[8]+0;
		$b_def = $row[9]+0;
		$b_mdef = $row[10]+0;
	
		$this->isLock = intval($row[12]);
		$this->state = intval($row[11]);
		$this->id = intval($row[0]);
		$this->level = intval($row[1]);
		$this->levelMax = $binfo['evo'.$evo.'_maxlv'];
		$this->exp = intval($row[2]);
		$this->expMax = $Equip->getMaxExp($grow_type,$this->level);
		$this->nbAddonSlot = $evo+1;
		$this->evo = $evo;

		$rate = $Equip->getGrowRate($grow_type,$this->level);
		
		$this->phisicalAttack = intval(($binfo['atk']+$b_atk)*$rate);
		$this->magicalAttack = intval(($binfo['matk']+$b_matk)*$rate);
		$this->phisicalDefence = intval(($binfo['def']+$b_def)*$rate);
		$this->magicalDefence = intval(($binfo['mdef']+$b_mdef)*$rate);
		
		$Addon = $this->get('Arpg.Logic.Util.EquipAddon');
		$sep = explode(',',$addon);
		$this->addons = [];
		foreach($sep as $addon_id){
			if(is_numeric($addon_id))
				$this->addons[]=intval($addon_id);
		}
		
		$Skill = $this->get('Arpg.Logic.Util.EquipSkill');
		$sep = explode(',',$skill);
		$this->skills = [];
		foreach($sep as $skill_id){
			if(is_numeric($skill_id))
				$this->skills[]=intval($skill_id);
		}
		if($evo != 0)
			$this->recipe = $Equip->getRecipe($this->stdId,$evo);
	}
	/**
	 * 基本情報のみを乗せたデータ
	 * @param unknown $std_id
	 */
	public function initData($std_id){
		$std_id = intval($std_id);
		$key = "Arpg.Logic.CardData.initData.$std_id";
		$base = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($base == null){
			$base = $this->get('Arpg.Logic.CardData');
			$base->initBase($std_id);
			$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$base);
		}
		foreach(self::$sleepField as $n){
			$this->$n = $base->$n;
		}
	}
	private static $sleepField = [
			'equipLimit','state','growType','type','attr',
			'stdId','name','getText','rarity','cost',
			'trainExp','price','flavorText','id','level',
			'levelMax','exp','expMax','nbAddonSlot','evo',
			'evoMax','cv','illustratorName','mTeqEffect','mSpId',
			'mSpRate','rareSpirit','phisicalAttack','magicalAttack','phisicalDefence',
			'magicalDefence','attackSpeed','moveSpeed','fallPower','criticalRate',
			'critivalPower','shieldRate','shieldPower',
			'skills','addons','tec_stdid','recipe','ser_stdid',
	];
	public function __sleep(){
		return self::$sleepField;
	}
	private function initBase($std_id){
		$Equip = $this->get('Arpg.Logic.Util.Equip');
		
		$binfo = $Equip->getData($std_id);
		$teq = intval($binfo['techniq']);
		$grow_type = intval($binfo['grow_type']);
		$gen = intval($binfo['gender']);
		
		if($gen == 0)
			$this->equipLimit = self::EQ_LIMIT_MALE_ONLY;
		elseif($gen == 1)
		$this->equipLimit = self::EQ_LIMIT_FEMALE_ONLY;
		else
			$this->equipLimit = self::EQ_LIMIT_NO;
		
		$this->state = 0;
		$this->growType = $grow_type;
		$this->type = Equip::std2type($std_id);
		$this->attr = intval($binfo['attribute']);
		$this->stdId = $std_id;
		$this->name = $binfo['name'];
		$this->getText = $binfo['get_text'];
		//$this->job = $binfo['job'];
		$this->rarity = intval($binfo['rarity']);
		$this->cost = intval($binfo['cost']);
		$this->trainExp = intval($binfo['train_exp']);
		$this->price = intval($binfo['sell']);
		$this->flavorText = $binfo['info'];
		$this->id = 0;
		$this->level = 1;
		$this->levelMax = $binfo['evo0_maxlv'];
		$this->exp = 0;
		$this->expMax = $Equip->getMaxExp($grow_type,$this->level);
		$this->nbAddonSlot = 0;
		$this->evo = 0;
		$this->evoMax = $binfo['evo_max'];
		$this->cv = $binfo['cv'];
		$this->illustratorName = $binfo['illustrator'];
		$this->mTeqEffect = $binfo['teq_effect'];
		
		$sep = explode(',',$binfo['specialized_dun']);
		$spids = [];
		foreach($sep as $spid){
			if(is_numeric($spid))
				$spids[] = intval($spid);
		}
		$this->mSpId = $spids;
		$this->mSpRate = $binfo['specialized_rate']+0;
		
		$this->rareSpirit = $binfo['rare_spirit'];
		
		$this->phisicalAttack = intval(($binfo['atk']));
		$this->magicalAttack = intval(($binfo['matk']));
		$this->phisicalDefence = intval(($binfo['def']));
		$this->magicalDefence = intval(($binfo['mdef']));
		
		$this->attackSpeed = $binfo['atk_speed']+0.0;
		$this->moveSpeed = $binfo['move_speed']+0.0;
		
		$this->fallPower = $binfo['fall']+0.0;
		$this->criticalRate = $binfo['critical']+0.0;
		$this->critivalPower = $binfo['critical_val']+0.0;
		$this->shieldRate = $binfo['shield']+0.0;
		$this->shieldPower = $binfo['shield_val']+0.0;
		
		$this->skills = [];
		$this->addons = [];
		
		$Skill = $this->get('Arpg.Logic.Util.EquipSkill');
		
		$this->recipe = $Equip->getRecipe($this->stdId,0);
		
		$this->tec_stdid = $teq;
		$this->ser_stdid = $binfo['series'];
	}
	
	
	private $mTeqEffect=0;
	private $mSpId=0;
	private $mSpRate=1;
	
	public function teqEffect(){
		return $this->mTeqEffect;
	}
	public function spID(){
		return $this->mSpId;
	}
	public function spRate(){
		return $this->mSpRate;
	}
	public $state;
	public $type;
	public $attr;
	public $stdId;
	public $name;
	public $getText;
	//public $job;
	
	public $equipLimit;
	
	public $rarity;
	public $cost;
	public $trainExp; // 素材としてのベース経験値
	public $price; // 売却価格

	public $addons=[];
	public $nbAddonSlot;

	public $skills=[];
	
	public $tec_stdid;
	
	public $ser_stdid;
	
	public $flavorText;
	
	// ユーザー毎に異なる情報
	public $id;
	public $level;
	public $levelMax;
	public $exp;
	public $expMax; // レベルアップに必要な経験値
	public $growType;
	public $evo;
	public $evoMax;

	public $phisicalAttack;
	public $magicalAttack;
	public $phisicalDefence;
	public $magicalDefence;
	
	public $attackSpeed;
	public $moveSpeed;
	
	public $fallPower;
	public $criticalRate;
	public $critivalPower;
	public $shieldRate;
	public $shieldPower;
	
	public $recipe;
	public $isLock;

	public $illustratorName;
	public $cv;
	
	public $rareSpilit;
	
	const EQ_LIMIT_NO=0;
	const EQ_LIMIT_MALE_ONLY=1;
	const EQ_LIMIT_FEMALE_ONLY=2;
}

?>