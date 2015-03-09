<?php
namespace Logic\DungeonData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\Util\Quest as Quest;
use \Dcs\Arpg\ResError as ResError;
use \Logic\Util\GameParam as GameParam;
use \Dcs\DetailTimeLog as DTL;

class Bonus extends \Dcs\Arpg\Logic{
	const T_FLORA = 0;
	const T_EXP = 1;
	const T_COMP = 2;
	const T_DROP = 3;
	
	public $flora = null;
	public $exp = null;
	public $comp = null;
	public $drop = null;
	
	const HS_TBL = 'action_boost';
	public static $HS_FLD = ['boost_std_id','ticket_id'];
	
	/**
	 * Hsの取得結果で初期化
	 * @param array $rs Bonus::HS_TBLとBonus::$HS_FLDで取得したハンドらソケットの結果
	 */
	public function initByHs($rs){
		$bst_money = 0;
		$bst_exp = 0;
		$bst_comp = 0;
		$bst_drop = 0;
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		foreach($rs as $row){
			$bst = $Stack->boostData($row[0]);
			if($bst == null) continue;;
			if($bst_money < $bst['flr'])
				$bst_money = $bst['flr'];
			if($bst_exp < $bst['exp'])
				$bst_exp = $bst['exp'];
		}
		$this->flora = $this->makeStr($bst_money);
		$this->exp = $this->makeStr($bst_exp);
		$this->comp = $this->makeStr($bst_comp);
		$this->drop = $this->makeStr($bst_drop);
	}
	/**
	 * ダンジョンチケット番号で初期化
	 * @param int $ticket
	 */
	public function init($ticket){
		$bst_money = 0;
		$bst_exp = 0;
		$bst_comp = 0;
		$bst_drop = 0;
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$rs = $this->getHs()->select(new Table(self::HS_TBL,self::$HS_FLD), new Query(['='=>intval($ticket)],-1));
		foreach($rs as $row){
			$bst = $Stack->boostData($row[0]);
			if($bst == null) continue;;
			if($bst_money < $bst['flr'])
				$bst_money = $bst['flr'];
			if($bst_exp < $bst['exp'])
				$bst_exp = $bst['exp'];
		}
		$this->flora = $this->makeStr($bst_money);
		$this->exp = $this->makeStr($bst_exp);
		$this->comp = $this->makeStr($bst_comp);
		$this->drop = $this->makeStr($bst_drop);
	}
	
	/**
	 * 個別に設定
	 * @param enum $type T_XXX
	 * @param number $num
	 */
	public function set($type, $num){
		switch(intval($type)){
			case self::T_FLORA:
				$this->flora = $this->makeStr($num);
				break;
			case self::T_EXP:
				$this->exp = $this->makeStr($num);
				break;
			case self::T_COMP:
				$this->comp = $this->makeStr($num);
				break;
			case self::T_DROP:
				$this->drop = $this->makeStr($num);
				break;
		}
	}
	
	private function makeStr($num){
		if($num <= 1) return null;
		$r = intval(round($num*100));
		$u = intval($r/100);
		$d = $r%100;
		return $u.($d > 0?".$d":'');
	}
}
?>