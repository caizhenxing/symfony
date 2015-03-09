<?php
/**
 * 仮実装
 */
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\GameData\RecipeData as RecipeData;
use \Logic\Util\Equip as UEquip;

class Home extends \Dcs\Arpg\Logic{
	public $wsName;
	public $wsStdId;
	
	public $wsAttr;
	/**
	 * データ初期化
	 * @param int $uid
	 */
	public function init($uid){
		$set_size = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatus($uid,self::std_equip_set);
		if($set_size < 1)
			$set_size = 1;
		$AStatus = $this->get('Arpg.Logic.Util.ActorStatus');
		$aid = $AStatus->getActorId($uid);
		$std_ids=[self::std_eset];
		for($i=0;$i<$set_size;++$i){
			$std_ids[]=self::std_eset_w+$i*10;
		}
		$as = $AStatus->getStatusMulti($aid,$std_ids);
		$wid = $as[self::std_eset_w + $as[self::std_eset]*10];

		$rs = $this->getHs()->select(
				new Table('box_equip',['std_id'],'IUS'),
				new Query(['='=>[$wid,$uid,0]])
		);
		$weapon = 0;
		foreach($rs as $row){
			$std_id = intval($row[0]);
			if(UEquip::std2type($std_id) == UEquip::TYPE_WEAPON){
				$weapon = $std_id;
			}
			break;
		}
		$rs = $this->selectHsCache(
				new Table('equip_data',['name','attribute']),
				new Query(['='=>$weapon])
		);
		$name = 'Error';
		$attr = 0;
		foreach($rs as $row){
			$name = $row[0];
			$attr = intval($row[1]);
		}
		
		$this->wsName = $name;
		
		$this->wsStdId = $weapon;
		
		$this->wsAttr = $attr;
	}

	const std_eset = 50050;
	const std_eset_w = 50051;
	const std_equip_set = 6;
}

?>