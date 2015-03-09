<?php
namespace Logic\GachaData;


use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\Time as Time;

class Banner extends \Dcs\Arpg\Logic{
	public static $DBKEY = [
		'id','title','cost_std_id', 'cost_val','banner_image',
		'order','cost_name','info','effective_from','effective_to',
		'category_priority','category','str_cost','str_num','display',
		'debug'
	];
	public $gachaID;
	public $bannerImage;
	public $info;
	public $title;
	public $costStdID;
	public $costValue;
	public $costName;
	public $from;
	public $to;
	public $bannerStrCost;
	public $bannerStrNum;
	public $dispType;
	
	public function getList(){
		$rs = $this->selectHsCache(
				new Table('gacha_data', self::$DBKEY,'ORDER'),
				new Query(array('>'=>0),-1)
		);
		$ret = [];
		foreach($rs as $row){
			$dat = $this->get('Arpg.Logic.GachaData.Banner');
			$dat->init($row);
			$ret[] = $dat;
		}
		return $ret;
	}
	
	public function order(){
		return $this->mOrder;
	}
	
	public function enable(){
		if(!\Dcs\Arpg\Config::Debug && $this->mDebug != 0 )
			return false;
		$now = new Time();
		$now = $now->get();

		if($now < $this->from || $this->to < $now) return false;
		return true;
	}
	
	/**
	 * データ初期化
	 */
	private function init(array $row) {
		$this->gachaID = intval($row[0]);
		$this->title = $row[1];
		$this->costStdID = intval($row[2]);
		$this->costValue = intval($row[3]);
		$this->bannerImage = $row[4];
		$this->mOrder = intval($row[5]);
		$this->costName = $row[6];
		$this->info = $row[7];
		$time = new Time();
		$time->setMySQLDateTime($row[8]);
		$this->from = $time->get();
		$time->setMySQLDateTime($row[9]);
		$this->to = $time->get();
		$this->categoryPriority = intval($row[10]);
		$this->category = intval($row[11]);
		$this->bannerStrCost = $row[12];
		$this->bannerStrNum = $row[13];
		$this->dispType = intval($row[14]);
		$this->mDebug = intval($row[15]);
	}
	private $mOrder;
	private $mDebug;
}
?>