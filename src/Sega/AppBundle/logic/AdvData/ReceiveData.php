<?php
namespace Logic\AdvData;

class ReceiveData extends \Dcs\Arpg\Logic{
	const TBL = 'lang_adv_jp';
	const INDEX_DUNGEON_ID = 'DID';
	public static $FLD = [
			'dungeon_id','type','step','command','wait_tap',
			'chara_name','font_size','message','texture','texture_reverse',
			'sound','effect'
	];

	public $dungeonId;
	public $type;
	public $step;
	public $cmd;
	public $isWaitTap;
	public $charaName;
	public $fontSize;
	public $message;
	public $texture;
	public $isTexRev;
	public $sound;
	public $assetBundle;
	
	/**
	 * データ初期化
	 */
	public function init($row) {
		$this->dungeonId = intval($row[0]);
		$this->type = intval($row[1]);
		$this->step = intval($row[2]);
		$this->cmd = intval($row[3]);
		$this->isWaitTap = intval($row[4]);
		$this->charaName = $row[5];
		$this->fontSize = intval($row[6]);
		$this->message = $row[7];
		$this->texture = $row[8];
		$this->isTexRev = intval($row[9]);
		$this->sound = $row[10];
		$effect = $this->get('Arpg.Logic.Util.Effect')->getData(intval($row[11]));
		if($effect == null)
			$this->assetBundle = '';
		else
			$this->assetBundle = $effect['file'];
			
		
	}

	public function __sleep(){
		return [
		'dungeonId',
		'type',
		'step',
		'cmd',
		'isWaitTap',
		'charaName',
		'fontSize',
		'message',
		'texture',
		'isTexRev',
		'sound',
		'assetBundle',
		];
	}
}
?>