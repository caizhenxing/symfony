<?php
namespace Dcs\Arpg;

/**
 * Arpg時間を計算するためのクラス
 * @author takeday
 */
class AddTimer{
	private static $stack = [];
	private static $buff = [];
	public static function start($label){
		self::$buff[$label] = microtime(true);
	}
	public static function end($label){
		if(!isset(self::$buff[$label])) return;
		if(!isset(self::$stack[$label])) self::$stack[$label] = 0;
		self::$stack[$label] += microtime(true) - self::$buff[$label];
		unset(self::$buff[$label]);
	}
	
	public static function log(){
		foreach(self::$stack as $label=>$time){
			\Dcs\Log::i(sprintf('   %6d   %s',intval($time*1000),$label));
		}
	}
	public static function clear(){
		self::$stack = [];
		self::$buff = [];
	}
}
?>