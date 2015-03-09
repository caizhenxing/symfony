<?php
namespace Dcs;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
/**
 * 種類数カウンターと回数カウンターがある
 * 
 * ・種類数カウンター
 * tableの種類数をカウントする場合
 * AnalysisCounter::table(テーブル名);と入れると、テーブル名の種類をカウントする
 * 出力する際は、 フォーマットに <u.table> を入れれば、出力される
 * table部分は好きに変更可能
 * メソッド側をAnalysisCounter::t(テーブル名);とした場合、フォーマット側は <u.t> となる
 * 
 * ・回数カウンター
 * sqlの実行カウントする場合
 * AnalysisCounter::sql();を呼ぶと、呼ばれた回数をカウントする
 * 出力する際は、 フォーマットに <s.sql> を入れれば、出力される
 * sql部分は好きに変更可能
 * メソッド側をAnalysisCounter::s();とした場合、フォーマット側は <s.s> となる
 */
class AnalysisCounter{
	
	public static function __callStatic($name,$args){
		if(empty($args)){
			// シンプルカウンター
			if(!isset(self::$sSimple[$name]))
				self::$sSimple[$name] = 0;
			else
				++self::$sSimple[$name];
		}else{
			// ユニーク名カウンタ
			$un = $args[0];
			if(!isset(self::$sUnique[$name]))
				self::$sUnique[$name] = [];
			self::$sUnique[$name][$un] = true;
		}
	}
	
	/**
	 * 指定フォーマットで書き出す
	 * @param string $format 指定しない場合、ymlで指定したフォーマットになる
	 * @return string
	 */
	public static function toString($format = null){
		if($format == null)
			$format = self::$sFormat;
		$ptn = self::$sTags;
		foreach(self::$sUnique as $name => $list){
			$ptn['<u.'.$name.'>'] = ''.count($list);
		}
		foreach(self::$sSimple as $name => $num){
			$ptn['<s.'.$name.'>'] = ''.$num;
		}
		return strtr($format,$ptn);
	}
	
	private static $sUnique = [];
	private static $sSimple = [];
	private static $sFormat = "";
	private static $sTags = [];
	const TAGS_KEY = "Dcs.AnalysisCounter.Construct.Tags";
	public function onKernelRequest(GetResponseEvent $event){}
	public function __construct($format){
		self::$sFormat = $format;
		$apcu = extension_loaded('apcu');
		if($apcu){
			$tags = apc_fetch(self::TAGS_KEY);
		}
		if(empty($tags) || !$tags){
			$tags = [];
			preg_match_all('/<[s|u].[a-zA-Z_]+[a-zA-Z0-9_]*>/',$format,$out);
			foreach($out as $list)foreach($list as $line){
				$tags[$line] = '0';
			}
		}
		if($apcu){
			apc_store(self::TAGS_KEY,$tags);
		}
		self::$sTags = $tags;
	}
}
?>