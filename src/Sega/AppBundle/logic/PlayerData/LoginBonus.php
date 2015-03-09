<?php
/**
 * メモ
 * 仮実装
 */
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\GameData\RecipeData as RecipeData;
use \Dcs\Arpg\Time as Time;
use \Logic\Util\GameParam as GameParam;

class LoginBonus extends \Dcs\Arpg\Logic{
	public $month;
	public $loginNum;
	public $message;
	public $itemList;
	
	
	public $reward;
	
	/**
	 * データ初期化
	 * @return falseの場合 更新なし
	 */
	public function init($uid){
		$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		$pstate = $Pstatus->getStatusMulti($uid,[self::std_ymd,self::std_count]);
		
		$now = new Time();
		$now = $now->getDateTime();
		$year = intval($now->format('Y'));
		$month = intval($now->format('n'));
		$day = intval($now->format('j'));
		$ymd = $year*10000+$month*100+$day;
		
		$pre_ymd = $pstate[self::std_ymd];
		
		$pre_year = intval($pre_ymd/10000)%10000;
		$pre_month = intval($pre_ymd/100)%100;
		$pre_day = $pre_ymd%100;
		if(\Dcs\Arpg\Config::Debug && intval($this->get('Arpg.Logic.Util.DevParam')->param(84)) > 0){
			$pre_day = $day-1;
			$pre_ymd -= 1;
		}
		$this->month = $month;

		if($pre_ymd >= $ymd)
			return false;

		if($month > $pre_month || $year > $pre_year){
			$this->loginNum = 1;
		}
		elseif($day > $pre_day){
			$this->loginNum = $pstate[self::std_count] + 1;
		}
		
		// 全部とったよ
		if($this->loginNum > self::max_day)
			return false;
		
		$rs = $this->selectHsCache(new Table('login_bonus',['day','message','reward_stdid','reward_num','mark']),new Query(['='=>[$year,$month]],-1));
		
		if(empty($rs)){
			\Dcs\Log::e('dont set login bonus $year $month');
			return false;
		}
		
		$this->itemList = [];
		for($i=0;$i<self::max_day;++$i){
			$this->itemList[] = [
					'stdId' => 0,
					'isMark' => 0
			];
		}
		$is_create = true;
		$def_mes = '';
		$def_item = 0;
		$def_num = 0;
		foreach($rs as $row){
			$in_day = intval($row[0]);
			if($in_day > self::max_day) continue;
			$std_id = intval($row[2]);
			$num = intval($row[3]);
			$mark = intval($row[4]);
			if($in_day < 1){
				if($in_day == 0){
					$def_mes = $row[1];
					$def_item = $std_id;
					$def_num = $num;
				}
				continue;
			}
			$this->itemList[$in_day-1] = [
					'stdId' => $std_id,
					'isMark' => $mark
			];
			if($is_create && $in_day == $this->loginNum){
				$this->message = $row[1];
				$rws = $this->get('Arpg.Logic.GameData.Reward')->add($uid,[[$std_id,$num]],10004);
				if(!empty($rws))
					$this->reward = $rws[0];
				$is_create= false;
			}
		}
		
		for($i=0;$i<self::max_day;++$i){
			if(!isset($this->itemList[$i]))
				$this->itemList[$i] = [
						'stdId' => $def_item,
						'isMark' => 0
				];
		}
		if($is_create){
			$this->message = $def_mes;
			$rws = $this->get('Arpg.Logic.GameData.Reward')->add($uid,[[$def_item,$def_num]],10004);
			if(!empty($rws))
				$this->reward = $rws[0];
			$is_create= false;
		}
		
		$Pstatus->setMulti([[$uid,self::std_ymd,$ymd],[$uid,self::std_count,$this->loginNum]]);
		
		return true;
	}
	const max_day = 25;
	const std_ymd = 210;
	const std_count = 211;
}

?>