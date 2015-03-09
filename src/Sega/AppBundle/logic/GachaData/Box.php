<?php
namespace Logic\GachaData;

class Box extends \Dcs\Arpg\Logic{
	public $card=null;
	public $item=null;
	public $isNew;
	/**
	 * データ初期化
	 */
	public function init($card,$item,$isNew) {
		$this->card = $card;
		$this->item = $item;
		$this->isNew = $isNew;
	}
}
?>