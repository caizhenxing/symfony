<?php
namespace Logic\GameData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class NaviChara extends \Dcs\Arpg\Logic{
	public static $CHKEY = array('id','file', 'voice_time');
	public static $MSKEY = array('message','voice');
	
	public $type;
	public $fileChara;
	public $voiceTime;
	public $messages;
	
	/**
	 * データ初期化
	 */
	public function init($row) {
		$id = intval($row[0]);
		$this->type = $id;
		$this->fileChara = $row[1];
		$this->voiceTime = intval($row[2]);
		
		$rs = $this->selectHsCache(
				new Table('navi_chara_message',self::$MSKEY,'NAVI_ID'),
				new Query(['=' => $id],-1)
		);
		$this->messages = [];
		foreach($rs as $line){
			$this->messages[] = [
				'message' => $line[0],
				'fileVoice' => $line[1],
			];
		}
	}
}
?>