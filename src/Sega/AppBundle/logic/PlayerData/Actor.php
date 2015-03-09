<?php
/**
 * メモ
 * 地道に高速化１を実装ずみ
 */
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\Util\Equip as EquipUtil;

class Actor extends \Dcs\Arpg\Logic{
	public $id;
	public $name;
	public $gender;
	public $weaponStdId;

	public $hairStyle;
	public $hairColor;
	public $faceStyle;
	public $skinColor;
	public $eyeColor;

	/**
	 * データ初期化
	 * @param \Dcs\CmnAccount $account
	 */
	public function init($uid, $name){
		$AStatus = $this->get('Arpg.Logic.Util.ActorStatus');
		$actor_id = $AStatus->getActorId($uid);
		
		$this->id = $actor_id;
		$this->name = $name;
		
		$this->weaponStdId = $this->getWid($uid,$actor_id);
		
		$rs = $AStatus->getStatusMulti($actor_id,[
				self::hair_style,
				self::hair_color,
				self::face_style,
				self::skin_color,
				self::eye_color,
				self::gender
		]);
		foreach($rs as $key => $val){
			$this->initSub($key,$val);
		}
	}
	/**
	 * でーた初期化
	 * @param int $actor_id
	 */
	public function initByAid($actor_id,$uid = 0){
		$AStatus = $this->get('Arpg.Logic.Util.ActorStatus');
		if($uid < 1){
			$uid = $AStatus->getUserId($actor_id);
		}
	
		
		$this->id = $actor_id;
		$this->name = $AStatus->getName($actor_id);
		
		$this->weaponStdId = $this->getWid($uid,$actor_id);

		$rs = $AStatus->getStatusMulti($actor_id,[
				self::hair_style,
				self::hair_color,
				self::face_style,
				self::skin_color,
				self::eye_color,
				self::gender
				]);
		foreach($rs as $key => $val){
			$this->initSub($key,$val);
		}
	}
	private function initSub($std_id, $num){
		switch($std_id){
			case self::hair_style:
				$this->hairStyle = $num;
				break;
			case self::hair_color:
				$this->hairColor = $num;
				break;
			case self::face_style:
				$this->faceStyle = $num;
				break;
			case self::skin_color:
				$this->skinColor = $num;
				break;
			case self::eye_color:
				$this->eyeColor = $num;
				break;
			case self::gender:
				$this->gender = $num;
				break;
		}
	}
	private function getWid($uid,$aid){
		$aid = intval($aid);
		if(!isset(self::$mWid[$aid])){

			$set_size = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatus($uid,self::std_equip_set);
			if($set_size < 1)
				$set_size = 1;
			$Equip = $this->get('Arpg.Logic.Util.Equip');
			$std_ids=[self::std_eset];
			for($i=0;$i<$set_size;++$i){
				$std_ids[]=self::std_eset_w+$i*10;
			}
			$as = $this->get('Arpg.Logic.Util.ActorStatus')->getStatusMulti($aid,$std_ids);
			$wid = $as[self::std_eset_w + $as[self::std_eset]*10];
			if($this->isTransactionActive()){
				
				$stmt = $this->sql('box_equip','select std_id from box_equip where uid=? and id = ? and state = 0');
				$stmt->select([intval($uid),$wid]);
				while($row = $stmt->fetch(\PDO::FETCH_NUM)){
					$std_id = intval($row[0]);
					if($Equip->std2type($std_id) == EquipUtil::TYPE_WEAPON)
						self::$mWid[$aid] = $std_id;
					break;
				}
				
			}else{
				$rs = $this->getHs()->select(new Table('box_equip',['std_id'],'IUS'),new Query(['='=>[$wid,$uid,0]]));
				foreach($rs as $row){
					$std_id = intval($row[0]);
					if($Equip->std2type($std_id) == EquipUtil::TYPE_WEAPON)
						self::$mWid[$aid] = $std_id;
					break;
				}
			}
		}
		if(isset(self::$mWid[$aid]))
			return self::$mWid[$aid];
		return 0;
	}
	static private $mWid=[];
	
	// std_id
	const gender	= 50023;
	const hair_style= 50024;
	const hair_color= 50025;
	const face_style= 50027;
	const skin_color= 50026;
	const eye_color	= 50028;
	const std_equip_set = 6;
	
	const std_eset = 50050;
	const std_eset_w = 50051;
}

?>