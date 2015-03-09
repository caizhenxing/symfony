<?php
namespace Dcs;

// ストップウォッチ
class StopWatch{
	/**
	 * 計測を開始する
	 */
	public function start(){
		$this->mStartTime = microtime(true);
	}
	/**
	 * 経過時間の取得
	 * @return  スタートからの経過ミリ秒
	 */
	public function time(){
		return intval((microtime(true) - $this->mStartTime)*1000);
	}
	/**
	 * 経過時間を取得して計測時間をリセットする
	 * @return  スタートからの経過ミリ秒
	 */
	public function retime(){
		$t = intval((microtime(true) - $this->mStartTime)*1000);
		$this->start();
		return $t;
	}
	/**
	 * 経過時間をログに出力して取得する
	 * @return  スタートからの経過ミリ秒
	 */
	public function log($mes=''){
		$t = $this->time();
		error_log($mes.' ['.$t.'ms]');
		return $t;
	}
	/**
	 * 経過時間をログに出力して取得し計測時間をリセットする
	 * @return  スタートからの経過ミリ秒
	 */
	public function relog($mes=''){
		$t = $this->retime();
		error_log($mes.' ['.$t.'ms]');
		return $t;
	}
	/**
	 * コンストラクタ
	 * 自動開始を行う
	 */
	public function __construct(){
		$this->start();
	}
	
	private $mStartTime;
	
}

?>
