<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\ItemData as ItemData;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\DetailTimeLog as DTL;

/**
 * ガチャ
 */
class Gacha extends \Dcs\Arpg\Logic{
	
	/**
	 * ガチャを引いた回数
	 * @param int $uid
	 * @param int $gid
	 * @return int
	 */
	public function getDrawGachaCount($uid,$gid){
		return $this->get('Arpg.Logic.Util.PlayerStatus')->getStatus($uid,$gid,false);
	}
	/**
	 * レアりてぃ毎の武具産出数
	 * @param int $uid
	 * @param int $rarity
	 * @return int
	 */
	public function getDrawRarityCount($uid,$rarity){
		return  $this->get('Arpg.Logic.Util.PlayerStatus')->getStatus($uid,self::std_gacha_rarity_count_base+intval($rarity),false);
	}
	/**
	 * ガチャIDからガチャを引く
	 * @param int $uid
	 * @param int $gid
	 * @return array [\Sega\Dao\GameData\Reward]  null の場合失敗
	 */
	public function drawByGid($uid,$gid){
		DTL::Lap('Arpg.Logic.Util.Gacha.drawByGid start');
		$rs = $this->selectHsCache(
				new Table('gacha_data',['cost_std_id','cost_val','func','debug']),
				new Query(['=' => $gid])
		);
		if(empty($rs)) return [];
		$row = $rs[0];
		if(!\Dcs\Arpg\Config::Debug && intval($row[3]) == 0 ) // デバッグ用
			return [];
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		$std_id = intval($row[0]);
		$val = intval($row[1]);

		DTL::Lap('get gacha shop data');
		
		try{
			if($Pstatus->check($std_id)){
				$Pstatus->add($uid,$std_id,-$val);
			}elseif($Stack->check($std_id)){
				$Stack->add($uid,$std_id,-$val);
			}
		}catch(\Exception $e){
			throw new ResError("too low item $std_id",100);
		}
		$Pstatus->add($uid,$gid,1,false); // ガチャを引いた回数をカウント
		return $this->drawByFuncMulti($uid,explode(',',$row[2]));
	}

	/**
	 * ガチャFunctionIDからガチャを引く
	 * @param int $uid
	 * @param array $fids
	 * @param bool $force_warehouse  強制的に倉庫に入れる デフォルトfalse
	 * @return array [\Sega\Dao\GameData\Reward] null の場合失敗
	 */
	public function drawByFuncMulti($uid,$fids,$force_warehouse=false){
		DTL::Lap('Arpg.Logic.Util.Gacha.drawByFuncMulti start');
		if($force_warehouse == null)
			$force_warehouse = false;
		$gacha_id=[];
		foreach($fids as $fid){
			if(is_numeric($fid)){
				$std_id = intval($fid);
				if(isset($gacha_id[$std_id]))
					++$gacha_id[$std_id];
				else
					$gacha_id[$std_id] = 1;
			}
		}
		$gacha = $this->get('Arpg.Logic.Gacha');
		$cards = [];
		$adder = [];
		foreach($gacha_id as $id=>$num){
			DTL::Lap('gacha draw '.$id.'*'.$num);
			$gacha->setGachaId($id);
			$rs = $gacha->drawCards($num);
			foreach($rs as $card){
				$adder[] = [intval($card['asset_id']),intval($card['asset_count']),$force_warehouse];
			}
			DTL::Lap('gacha draw end');
		}
		$Reward = $this->get('Arpg.Logic.GameData.Reward');
		$ret= $Reward->add($uid,$adder,10000);
		DTL::Lap('add gacha reward');
		// TODO $retの順番を$fidsの順番に並び替える
		return $ret;
	}
	
	const std_gacha_rarity_count_base = 50;
}

?>