<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\MessageData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\Util\GameParam as GameParam;
use \Logic\Util\Mail as UMail;
use \Dcs\Arpg\ResError as ResError;

class Head extends \Dcs\Arpg\Logic{
	/**
	 * init で使用するSQL文
	 * @var unknown
	 */
	const SQL = 	'select id,type,`from`,subject,create_date,end_date,state,reward_std_id,reward_num from box_mail ';
	const SQLALL =	'select id,type,`from`,subject,send_date,end_date,reward_std_id,reward_num from mail_all ';
	
	/**
	 * initで使用するHS系のデータ
	 * @var unknown
	 */
	const HS_TBL = 'box_mail';
	public static $HS_FLD = ['id','type','from','subject','create_date','end_date','state','reward_std_id','reward_num'];
	
	const HS_IDX_UID = 'UID'; // uid , state の複合インデックス
	
	public $id;
	public $type;
	public $from;
	public $title;
	public $state;
	public $receiveTime;
	public $limitTime;
	public $isReaded;
	
	public $rewardStdId;
	public $rewardNum;
	public $rewardAccepted;
	
	/**
	 * @param array $row self::SQLの取得結果をFETCH_NUMで取得した結果、または、self::HS_XX系を使用したHS取得結果
	 */
	public function init($row){
		$this->mId = intval($row[0]);
		$this->id = '1:'.$this->mId;
		$this->type = intval($row[1]);
		$this->from = $row[2];
		$this->title = $row[3];
		$time = new \Dcs\Arpg\Time();
		$time->setMySQLDateTime($row[4]);
		$this->receiveTime = $time->get();
		
		$time->setMySQLDateTime($row[5]);
		$this->limitTime = $time->get();
		$this->state = intval($row[6]);

		$this->rewardStdId = intval($row[7]);
		$this->rewardNum = intval($row[8]);
	}
	
	/**
	 * 全員送信型設定
	 * @param array $row self::SQLALLの取得結果をFETCH_NUMで取得した結果
	 */
	public function initAll($row){
		$this->mId = intval($row[0]);
		$this->id = '2:'.$this->mId;
		$this->type = intval($row[1]);
		$this->from = $row[2];
		$this->title = $row[3];
		$time = new \Dcs\Arpg\Time();
		$time->setMySQLDateTime($row[4]);
		$this->receiveTime = $time->get();
		
		$time->setMySQLDateTime($row[5]);
		$this->limitTime = $time->get();
		$this->state = 0;
		
		$this->rewardStdId = intval($row[6]);
		$this->rewardNum = intval($row[7]);
	}
	public function getOriginalId(){
		return $this->mId;
	}
	private $mId;
}
?>