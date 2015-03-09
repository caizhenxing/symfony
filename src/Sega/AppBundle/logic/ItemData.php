<?php
namespace Logic;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class ItemData extends \Dcs\Arpg\Logic{
	const DBSQLBASE = 'select std_id,num,uid from box_stack_item where ';
	const DBSQL = 'select std_id,num,uid from box_stack_item where uid = ?';

	const DBHS_TBL = 'box_stack_item';
	static public $DBHS_FLD = ['std_id','num','uid'];
	
	public $stdId;
	public $name;
	public $type; //ソート用に必要かなぁ…
	public $nbEquip;
	public $flavorText;
	public $rarity;
	public $price;
	
	// ユーザー毎に異なる情報
	public $num;	
	
	/**
	 * データ初期化
	 * @param $row SQLをFETCH_NUMで取得した結果またはHSで取得した結果
	 * @return boolean 成功
	 */
	public function init(array $row){
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$std_id = intval($row[0]);
		if(!$Stack->check($std_id))
			return false;
		
		$this->mUid = intval($row[2]);

		$this->initInner($std_id);
		$this->num =  intval($row[1]);
		
		return true;
	}

	public function initData($std_id,$num=0){
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$std_id = intval($std_id);
		if(!$Stack->check($std_id))
			return false;
		
		$this->initInner($std_id);
		$this->num =  intval($num);
		
		return true;
	}
	
	/**
	 * 有効期間中か判定
	 * @return bool
	 */
	public function isEffective(){
		$now = new \Dcs\Arpg\Time();
		$now = $now->get();
		return $now <= $this->endDate;
	}
	
	private function initInner($std_id){
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$data = $Stack->getData($std_id);
		
		$this->type = $Stack->std2type($data['std_id']);
		$this->stdId = $data['std_id'];
		$this->name = $data['name'];
		$this->nbEquip = $data['nb_equip'];
		$this->flavorText = $data['info'];
		$this->rarity = $data['rarity'];
		$this->price = $data['sell'];
		$time = new \Dcs\Arpg\Time();
		$time->setMySQLDateTime($data['delete_date']);
		$this->endDate = $time->get();
	}
	
	public function uid(){
		return $this->mUid;
	}
	private $endDate;
	private $mUid;
	// アイテムタイプ
	const type_equip = 1;
	const type_boost = 2;
	const type_etc = 3;
}

?>