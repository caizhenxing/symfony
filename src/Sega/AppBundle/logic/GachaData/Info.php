<?php
namespace Logic\GachaData;
use \Dcs\Arpg\Time as Time;

class Info extends \Dcs\Arpg\Logic{
	public static $DBKEY = array('image', 'view_time','effective_from','effective_to');
	public $infoImage;
	public $viewTime;
	
	/**
	 * データ初期化
	 */
	public function init(array $row) {
		$this->infoImage = $row[0];
		$this->viewTime = intval($row[1]);
		$time = new Time();
		$time->setMySQLDateTime($row[2]);
		$this->from = $time->get();
		$time->setMySQLDateTime($row[3]);
		$this->to = $time->get();
	}
	
	public function enable(){
		$now = (new Time())->get();
		return $this->from <= $now && $now <= $this->to;
	}
	
	private $from;
	private $to;
}
?>