<?php
namespace Logic\ActionTutorial;

class Tips extends \Dcs\Arpg\Logic{
	const TBL = 'loading_tips';
	public static $FLD = [
			'id','world_id','title','message','image_file'
	];

	public $id;
	public $worldId;
	public $title;
	public $message;
	public $imageFile;

	public function __sleep(){
		return [
			'id',
			'worldId',
			'title',
			'message',
			'imageFile'
		];
	}
	/**
	 * データ初期化
	 */
	public function init($row) {
		$this->id = intval($row[0]);
		$this->worldId = intval($row[1]);
		$this->title = $row[2];
		$this->message = $row[3];
		$this->imageFile = $row[4];
	}
	
}
?>