<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\OrderData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\Util\GameParam as GameParam;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\DetailTimeLog as DTL;

class Addon extends \Dcs\Arpg\Logic{
	// アドオンスロット最大数
	const ADDON_SLOT_MAX = 4;
	// 稀霊石最大数
	const RARE_STONE_MAX = 5;

	public $isSuccess;
	public $cutinType;
	public $addonInfo;

	public $money;
	public $addAddon;

	/**
	 * アドオン合成実行
	 * @param int $uid
	 * @param int $cid ベースカードID
	 * @param int $mid 素材カードID
	 * @param int $snum 石個数
	 * @param int $aidx 素材アドオン
	 * @param int $slot アドオンスロット番号
	 */
	public function run($uid, $cid, $mid,$snum,$aidx,$slot) {
		DTL::Lap('Addon run start');
		// マイナスの個数はだめ
		if($snum < 0 || $snum > self::RARE_STONE_MAX)
			throw new ResError('invalid stone num.',101,['VAL'=>'snum']);

		$snum = intval($snum);

		$GParam = $this->get('Arpg.Logic.Util.GameParam');
		$PState = $this->get('Arpg.Logic.Util.PlayerStatus');
		$Text = $this->get('Arpg.Logic.Util.Text');

		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$stone = $Stack->getNum($uid,self::std_stone);
		$use_stone = $snum;
		if($snum > $stone){
			$sub_cp = ($snum-$stone)*$GParam->getParam(GameParam::ADDON_STONE_CP);
			try{
				$PState->add($uid,self::std_cp,-$sub_cp);
			}catch(\Exception $e){
				throw new ResError('too low cp.',210);
			}
			$use_stone = $stone;
		}
		DTL::Lap('stone check');

		$rs = $this->getHs()->selectMulti(
				new Table('box_equip',['uid','std_id', 'addon', 'state', 'evo']),
				[new Query(['=' => $cid]),new Query(['=' => $mid])]
		);
		foreach($rs[0] as $row){
			DTL::Lap('start addon execute');
			if(intval($row[0]) != $uid) continue;
			if(intval($row[3]) > 0) continue;// 持ってない
			$Equip = $this->get('Arpg.Logic.Util.Equip');
			$m_addon = null;
			$m_std_id = null;
			$m_info = null;
			foreach($rs[1] as $row2){
				if(intval($row2[0]) != $uid) continue;
				if(intval($row2[3]) > $uid) continue;// 持ってない
				$m_std_id = intval($row2[1]);
				$m_addon = explode(',',$row2[2]);
				$m_info = $Equip->getData($m_std_id);
			}

			DTL::Lap('material item check');
			// アイテムなし
			if($m_addon == null)
				throw new ResError('dont exists material item.',1000);

			$EquipAddon = $this->get('Arpg.Logic.Util.EquipAddon');

			$new_addon = [];
			foreach($m_addon as $addon){
				if($EquipAddon->check($addon))
					$new_addon[] = intval($addon);
			}
			$m_addon = $new_addon;

			DTL::Lap('addon exist check');
			// 存在しないアドオン指定
			if(count($m_addon) <= $aidx)
				throw new ResError('dont exists addon.',1001);

			$b_std_id = intval($row[1]);
			$b_addon_max = 1+intval($row[4]);
			$b_addon = explode(',',$row[2]);

			if($Equip->std2type($b_std_id) == $Equip::TYPE_AMULET || $Equip->std2type($b_std_id) == $Equip::TYPE_RING){
				throw new ResError('invalid item type.',100);
			}
			// 種別違い
			if($Equip->std2type($b_std_id) != $Equip->std2type($m_std_id) || $Equip->std2wtype($b_std_id) != $Equip->std2wtype($m_std_id))
				throw new ResError('dont match equip type.',100);

			DTL::Lap('same item check');

			$insert_addon = intval($m_addon[$aidx]);

			$rate = $EquipAddon->getSwitchRates($insert_addon)[$snum];
			$price = $EquipAddon->getSwitchCost($insert_addon);

			$money = $PState->getStatus($uid,self::std_money);

			if($money < $price)
				throw new ResError('too low money.',200);// 金足りず

			$this->isSuccess = 0;
			$this->cutinType = 0;
			if(mt_rand(0,100) <= $rate){
				$this->isSuccess = 1;
			}else{
				// カットイン演出
				$ci = $GParam->getParam(GameParam::ADDON_CI0_RATE+$snum);
				if(mt_rand(0,100) <= $ci){
					$this->cutinType = 1;
					$ci = $GParam->getParam(GameParam::ADDON_CI_RATE);
					if(mt_rand(0,100) <= $ci){
						$this->isSuccess = 1;
					}
				}
			}
			$swap=[];
			foreach($b_addon as $addon){
				if($EquipAddon->check($addon))
					$swap[] = intval($addon);
			}
			$b_addon = $swap;

			DTL::Lap('success check & cutin select');
			// アドオンの付け替えが出来る様に修正
			if($this->isSuccess > 0){// 成功
				if($slot < 0 || $slot > self::ADDON_SLOT_MAX){
					// 指定スロットがおかしい
					$this->isSuccess = 0;
					$this->addonInfo = $Text->getText(10300);
				}else{
					if(empty($b_addon) || empty($b_addon[$slot])){// 指定スロットが空の場合
						$b_addon[] = $insert_addon;
					}else{// 指定スロットがすでに埋まっている場合
						$b_addon[$slot] = $insert_addon;
					}
					$this->addonInfo = $EquipAddon->getName($insert_addon);
				}
			}
			// if($this->isSuccess > 0 && count($b_addon) < $b_addon_max){
			// 	$b_addon[] = $insert_addon;
			// 	$this->addonInfo = $EquipAddon->getName($insert_addon);
			// }else{
			// 	$this->isSuccess = 0;
			// 	$this->addonInfo = $Text->getText(10300);
			// }

			$b_addon = array_slice($b_addon, 0, $b_addon_max);

			$this->money = $money - $price;

			$this->useTransaction();
			if($this->isSuccess > 0){
				if($this->sql('box_equip','update box_equip set addon=? where id=? and state=0')->update([implode(',',$b_addon),$cid]) < 1)
					throw new ResError('dont update base_equip.',2);

				$this->addAddon = $this->get('Arpg.Logic.CardData.Addon');
				$this->addAddon->init($insert_addon);
			}
			DTL::Lap('update base item');

			if($this->sql('box_equip','delete from box_equip where id=?')->delete([$mid]) < 1)
				throw new ResError('dont delete material equip.',2);


			DTL::Lap('delete material item');
			$PState->set($uid,self::std_money,$this->money );

			// 各種回数をカウント
			$PState->addMulti([
					[$uid,4000000+$b_std_id,1],
					[$uid,self::std_run_count,1],
					[$uid,self::std_suc_count,$this->isSuccess > 0?1:0]
			],false);

			try{
				$Stack->addMulti([
						[$uid,self::std_stone,-$use_stone],
						[$uid,self::std_rare_spirit,$m_info['rare_spirit']]
				]);
			}catch(\Exception $e){
				throw new ResError($e->getMessage(),100);
			}

			DTL::Lap('update status');
			return;
		}
		throw new ResError('dont exists base item.',1000);
	}
	//STD_ID
	const std_money = 10000;
	const std_stone = 203002;
	const std_cp = 10001;
	const std_run_count = 340;
	const std_suc_count = 341;
	const std_rare_spirit = 203008;
}
?>