<?php
namespace Dcs;

/**
 * 詳細時間ログ
 * @author Takeda_Yoshihiro
 *
 */
class DetailTimeLog{
	/**
	 * 前回Lapから現在までの時間を追加
	 * @param string $mes
	 */
	static public function Lap($mes = 'lap'){
		RequestTimerEvent::Lap($mes);
	}

	
	
	
	static public function Start(){
		Log::w('Dcs.DetailTimeLog.Start is deprecated method.');
	}
	static public function Log($title = 'start'){
		Log::w('Dcs.DetailTimeLog.Log is deprecated method.');
	}
	static public function End(){
		Log::w('Dcs.DetailTimeLog.End is deprecated method.');
	}
	static public function Size(){
		Log::w('Dcs.DetailTimeLog.Size is deprecated method.');
	}
}

?>