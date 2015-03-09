<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\GameData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\ResError as ResError;

class FactoryItem extends \Dcs\Arpg\Logic{
	/**
	 * パラメータに [int 工場タイプ]を渡す
	 */
	const DBTBL = 'factory_product_list';
	public static $FLD = ['std_id','type','level_unlock','level_open','create_time','price','material','num', 'level_close'];
	
	public $stdId;
	public $type;
	public $name;
	public $info;
	public $unlockLv;
	public $buildLv;
	public $closeLv;
	public $num;
	
	/**
	 * レシピデータ
	 * @var Arpg\Dao\GameData\RecipeData
	 */
	public $recipe;
	
	/**
	 * データ初期化
	 */
	public function init($row) {
		
		$this->stdId = intval($row[0]);
		
		$this->type = intval($row[1]);
		$this->unlockLv = intval($row[2]);
		$this->buildLv = intval($row[3]);
		
		$util = null;
		$Equip = $this->get('Arpg.Logic.Util.Equip');
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		if($Equip->check($this->stdId)){
			$util = $Equip;
		}elseif($Stack->check($this->stdId)){
			$util = $Stack;
		}else 
			throw new ResError('stdid:'.$this->stdId.' is not stackitem or equipitem.');
		
		
		$item = $util->getData($this->stdId);
		
		$this->name = $item['name'];
		$this->info = $item['info'];
		
		$this->recipe = $this->get('Arpg.Logic.GameData.RecipeData');
		$this->recipe->initHs([$row[6],$row[4],$row[5]]);
		$this->num = intval($row[7]);
		$this->closeLv = intval($row[8]);
	}
}
?>