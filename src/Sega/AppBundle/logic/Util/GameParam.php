<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

/**
 * ゲームパラメータ
 */
class GameParam extends \Dcs\Arpg\Logic{
	// タイプ
	const TRAIN_EXP_RATE=1;		// 強化経験値レート
	const TRAIN_SSUC_RATE=2;	// 強化大成功率
	const TRAIN_SSUC_BONUS=3;	// 強化大成功ボーナス倍率
	const TRAIN_TYPE_BONUS=4;	// 強化同種ボーナス倍率
	const TRAIN_ATTR_BONUS=5;	// 強化同属性ボーナス倍率
	// const MAX_STACK=6;			// 倉庫最大スタック数
	const USER_SEARCH_NUM=7;	// ユーザー検索件数
	const ADDON_STONE_CP=8;		// アドオン鉱石CP額
	const ADDON_CI0_RATE=9;		
	const ADDON_CI1_RATE=10;
	const ADDON_CI2_RATE=11;
	const ADDON_CI3_RATE=12;
	const ADDON_CI4_RATE=13;
	const ADDON_CI5_RATE=14;
	const ADDON_CI_RATE=15;
	
	const CURE_STP_CP=16;		// スタミナ回復CP額
	const EXT_CARD_CP=17;		// 倉庫拡張CP額
	const SPIRIT_CP=19;			// 妖精さんCP額
	const WAREHOUSE_MAX=20;		// 倉庫拡張上限
	
	const WAREHOUSE_EXT=21;		// 1回の倉庫拡張幅
	
	const CLEAR_POINT_S=22;		// Sランク時　到達値
	const CLEAR_POINT_A=23;		// Aランク時　到達値
	const CLEAR_POINT_B=24;		// Bランク時　到達値
	const CLEAR_POINT_C=25;		// Cランク時　到達値
	const CLEAR_BORDER_S=26;	// Sランク判定ポイント
	const CLEAR_BORDER_A=27;	// Aランク判定ポイント
	const CLEAR_BORDER_B=28;	// Bランク判定ポイント
	const CLEAR_BORDER_C=29;	// Cランク判定ポイント
	
	const FRIEND_REPOINT_TIME=30;	// フレンドゴースト再取得時間
	const GHOST_FRIEND_POINT=31;	// フレンドゴーストポイント
	const GHOST_PLAYER_POINT=32;	// 冒険者ゴーストポイント

	const CHARA_CREATE_CP=34;	// ホームバッチ再取得時間
	
	const STP_HEAL_SEC=35;	// １スタミナが回復する秒数
	
	const FRIEND_EXT_CP=36;
	const GOOD_SEND=37;		// GOOD送信時ポイント
	const GOOD_ACCEPT=38;	// GOOD受信時ポイント
	
	const KBOX_KEY_CP=39;

	const TUTORIAL_GACHA=40;
	/**
	 * パラメータを取得
	 * @param int $type
	 */
	public function getParam($type){
		$rs = $this->selectHsCache(
				new Table('game_param',['priority','param','effective_from','effective_to']),
				new Query(['='=>intval($type)]),
				3600
		);
		$ret = 0;
		$pri = -1;
		$now = new \Dcs\Arpg\Time();
		foreach($rs as $row){
			$p = intval($row[0]);
			if($p <= $pri) continue;
			$from = new \Dcs\Arpg\Time();
			$from->setMySQLDateTime($row[2]);
			$to = new \Dcs\Arpg\Time();
			$to->setMySQLDateTime($row[3]);
			if($now->get() < $from->get() || $to->get() <= $now->get() ) continue;
			$pri = $p;
			$ret = $row[1]+0;
		}
		
		return $ret;
	}
}

?>