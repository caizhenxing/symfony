<?php
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\ResError as ResError;

class Present extends \Dcs\Arpg\Logic{

	/**
	 * スタックアイテムを追加する
	 * @param int $uid		追加するユーザー
	 * @param int $std_id 追加する論理ID
	 * @param int $num 追加する数
	 * @param string|int $mes 入力するメッセージ または、lang_text_jpのid
	 */
	public function addStackItem($uid, $std_id, $num, $mes='',$no_check=false){
		$Present = $this->get(self::PRESENT_SERVICE);
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$std_id = intval($std_id);
		$num = intval($num);
		if(!$no_check && !$Stack->check($std_id))
			throw new ResError('dont stack item',100);
		$this->useTransaction();
		if(is_int($mes)){
			$mes = $this->get('Arpg.Logic.Util.Text')->getText($mes);
		}
		$Present->addPresentBox($uid,[$this->createParam($std_id,0,$num)],0,$mes);
	}
	/**
	 * スタックアイテムを追加する
	 * @param int $uid		追加するユーザー
	 * @param array $param [[追加する論理ID,追加する数], ...]
	 * @param string|int $mes 入力するメッセージ または、lang_text_jpのid
	 */
	public function addStackItems($uid, $param, $mes='',$no_check=false){
		if(empty($param)) return [];
		$Present = $this->get(self::PRESENT_SERVICE);
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$list = [];
		foreach($param as $line){
			$std_id = intval($line[0]);
			if(!$no_check && !$Stack->check($dat['asset_type_id']))
				throw new ResError('dont stack item',100);
			$list[] = $this->createParam($std_id,0,intval($line[1]));
		}
		$this->useTransaction();
		if(is_int($mes)){
			$mes = $this->get('Arpg.Logic.Util.Text')->getText($mes);
		}
		$Present->addPresentBox($uid,$list,0,$mes);
	}
	/**
	 * 装備アイテムを追加する
	 * @param int $uid		追加するユーザー
	 * @param int $stdid 追加する論理ID
	 * @param string|int $mes 入力するメッセージ または、lang_text_jpのid
	 * @return 倉庫内ID
	 */
	public function addEquipItem($uid, $std_id, $mes=''){
		$Present = $this->get(self::PRESENT_SERVICE);
		$Equip = $this->get('Arpg.Logic.Util.Equip');
		$std_id = intval($std_id);
		if(!$Equip->check($std_id))
			throw new ResError('dont equip item',100);
		$this->useTransaction();
		$box_id = $Equip->add($uid, $std_id,$Equip::STATE_PRESENT);
		if(is_int($mes)){
			$mes = $this->get('Arpg.Logic.Util.Text')->getText($mes);
		}
		$Present->addPresentBox($uid,[$this->createParam($std_id,$box_id,1)],0,$mes);
		return $box_id;
	}
	
	/**
	 * 装備アイテムを追加する
	 * @param int $uid		追加するユーザー
	 * @param array $stdids 追加する論理IDリスト
	 * @param string|int $mes 入力するメッセージ または、lang_text_jpのid
	 * @return array [倉庫内ID, ... ]
	 */
	public function addEquipItems($uid, $std_ids, $mes=''){
		if(empty($std_ids)) return [];
		$Present = $this->get(self::PRESENT_SERVICE);
		$Equip = $this->get('Arpg.Logic.Util.Equip');
		$list = [];
		$elist=[];
		foreach($std_ids as $std_id){
			$std_id = intval($std_id);
			
			if(!$Equip->check($std_id))
				throw new ResError('dont equip item',100);
			$elist[] = [$std_id,$Equip::STATE_PRESENT];
		}
		$ret = $Equip->addMulti($uid,$elist);
		for($i=0,$len=count($ret);$i<$len;++$i){
			$list[$i]=$this->createParam(intval($std_ids[$i]),$ret[$i],1);
		}

		if(is_int($mes)){
			$mes = $this->get('Arpg.Logic.Util.Text')->getText($mes);
		}
		if(!empty($list)){
			$this->useTransaction();
			$Present->addPresentBox($uid,$list,0,$mes);
		}
		return $ret;
	}
	
	private function createParam($std_id,$id,$num){
		return [
			'asset_type_id' => $std_id,
			'asset_id' => $id,
			'asset_count' => $num,
		];
	}
	const PRESENT_SERVICE = 'gaia_present_service';
}
?>