<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\Util\GameParam as GameParam;

/**
 * ●● 注意
 * DungeonData.Result内でこのクラスを使用しているが、
 * DB接続回数を減らすため、initを使用せず直入力している
 * なので、変更を加えたらそちら側も変更をする必要がある
 * @author Takeda_Yoshihiro
 *
 */
class State extends \Dcs\Arpg\Logic{
	public $level;
	public $exp;
	public $expMax;

	public $costMax;
	public $baseHp;
	
	public $stp;
	public $stpMax;
	public $stpTime;
	public $stpHealSec;

	public $money;
	public $cp;
	public $gachaPoint; // ガチャポイント ※2000を超えるのでアイテムでは対応不可

	public $wareHouseSize;
	
	public $nbFriend;
	public $friendMax;
	
	public $boostTime;
	public $boostTitle;
	
	
	/**
	 * データ初期化
	 * @param \Dcs\CmnAccount $account
	 */
	public function init($uid){
		
		$PState = $this->get('Arpg.Logic.Util.PlayerStatus');
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		
		$rs1 = $PState->getStatusMulti($uid,[
				self::level,
				self::exp,
				self::stp,
				self::money,
				self::cp,
				self::gacha,
				self::warehouse,
				self::std_use_boost,
				self::std_use_boost_limit,
		]);

		$this->level = $rs1[self::level];
		$this->exp = $rs1[self::exp];
		$this->money = $rs1[self::money];
		$this->cp = $rs1[self::cp];
		$this->gachaPoint = $rs1[self::gacha];
		$this->wareHouseSize = $rs1[self::warehouse];

		$lvdata = $PState->getLvData($this->level);
		
		$now = new \Dcs\Arpg\Time();
		$now = $now->get();
		$shs = $this->get('Arpg.Logic.Util.GameParam')->getParam(GameParam::STP_HEAL_SEC);
		if($shs < 1) $shs = 1;
		
		$this->stp = intval(($now - $rs1[self::stp]) / $shs);
		$this->stpTime = $now;
		
		$this->expMax = $lvdata['exp'];
		$this->stpMax = $lvdata['stp'];
		$this->costMax = $lvdata['cost'];
		$this->baseHp = $lvdata['hp'];
		$this->stpHealSec = $shs;
		
		if($this->stp > $this->stpMax)
			$this->stp = $this->stpMax;
		
		$friend = $this->get('gaia.friend.friend_management_service');
		$this->friendMax = $friend->getFriendLimit($uid);
		$this->nbFriend = count($friend->friendIds($uid));
		$this->boostTitle = $Stack->boostData($rs1[self::std_use_boost])['name'];
		$this->boostTime = $rs1[self::std_use_boost_limit];
	}
	
	// STD_ID対応
	const level		= 1;
	const exp		= 2;
	const stp		= 3;
	
	const money		= 10000;
	const cp		= 10001;
	const gacha		= 10003;
	
	const warehouse	= 7;
	const std_use_boost = 300;
	const std_use_boost_limit = 301;
}

?>