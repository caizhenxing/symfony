<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\GameData\RecipeData as RecipeData;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\DetailTimeLog as DTL;

class Factory extends \Dcs\Arpg\Logic{
	public $type;
	public $lv;
	
	public $canUpGrade;
	public $createName;
	public $createNum;
	public $stdId;
	public $strCreateEndTime;
	public $createEndTime;
	public $naviChara;
	public $upGradeRecipe;
	
	/**
	 * データ初期化
	 * @param int $uid
	 * @param int $type
	 */
	public function init($uid,$type){
		DTL::Lap('PlayerData.Factory.init start');
		$Factory = $this->get('Arpg.Logic.Util.Factory');
		$status = $Factory->getState($uid,$type);
		$this->type = $type;
		$this->lv = $status['lv'];

		DTL::Lap('get status');
		
		$this->canUpGrade = !$Factory->isMaxLv($type,$this->lv);
		$this->createNum = $status['num'];
		$this->createEndTime = $status['end'];
		
		DTL::Lap('level check');

		if($this->createNum > 0){
			$Stack = $this->get('Arpg.Logic.Util.StackItem');
			$Equip = $this->get('Arpg.Logic.Util.Equip');
			$info = null;
			if($Stack->check($status['make'])){
				$info = $Stack->getData($status['make']);
			}elseif($Equip->check($status['make'])){
				$info = $Equip->getData($status['make']);
			}else{
				throw new ResError('invalid create in factory',100);
			}
			$this->createName = $info['name'];
			$this->stdId = $info['std_id'];

			DTL::Lap('get creating item info');
		}
		$this->upGradeRecipe = $this->get('Arpg.Logic.GameData.RecipeData');

		$rs = $this->selectHsCache(
				new Table('factory_upgrade',RecipeData::$DBKEY),
				new Query(['=' => [$this->type, $this->lv]])
		);
		if(count($rs) > 0)
			$this->upGradeRecipe->initHs($rs[0]);

		DTL::Lap('get upgrade recipe');
	}
}

?>