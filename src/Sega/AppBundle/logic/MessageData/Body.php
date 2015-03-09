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

class Body extends \Dcs\Arpg\Logic{
	/**
	 * init で使用するSQL文
	 * @var unknown
	 */
	const SQL = 'select id,body,can_delete,end_date,state,reward_std_id,reward_num from box_mail ';
	
	/**
	 * initで使用するHS系のデータ
	 * @var unknown
	 */
	const HS_TBL = 'box_mail';
	public static $HS_FLD = ['id','body','can_delete','end_date','state','reward_std_id','reward_num'];
	
	const HS_IDX_UID = 'UID'; // uid , state の複合インデックス
	
	
	public $id;
	public $canDelete;
	public $body;
	public $rewardStdId;		// 0の時なし
	public $rewardNum;		// 0の時なし　
	public $reward=null;
	//public $rewardAccepted;	// リワード受け取り済みフラグ

	/**
	 * @param array $row self::SQLの取得結果をFETCH_NUMで取得した結果、または、self::HS_XX系を使用したHS取得結果
	 */
	public function init($row){
		$this->id = '1:'.intval($row[0]);
		$this->body = $row[1];
		$this->canDelete = intval($row[2]);
		$end = new \Dcs\Arpg\Time();
		$end->setMySQLDateTime($row[3]);
		$this->mEnd = $end;
		$this->mState = intval($row[4]);
		$this->rewardStdId = intval($row[5]);
		$this->rewardNum = intval($row[6]);
		//$this->rewardAccepted = $this->mState == 2 || $this->mState == 3;
	}
	/**
	 * 全送信型初期化
	 * @param int $mid メールID
	 * @param int $state ステータス
	 */
	public function initAll($mid,$state){
		$mid = intval($mid);
		$this->id = '2:'.$mid;
		$info = $this->get('Arpg.Logic.Util.Mail')->getInfo($mid);
		$this->body = $info['body'];
		$this->canDelete = intval($info['can_delete']);
		$end = new \Dcs\Arpg\Time();
		$end->setMySQLDateTime($info['end_date']);
		$this->mEnd = $end;
		$this->mState = intval($state);
		$this->rewardStdId = intval($info['reward_std_id']);
		$this->rewardNum = intval($info['reward_num']);
		//$this->rewardAccepted = $this->mState == 2 || $this->mState == 3;
	}
	/**
	 * ステートを返す
	 */
	public function state(){
		return $this->mState;
	}
	
	/**
	 * 終了時間を取得
	 * @return Dcs\Arpg\Time
	 */
	public function end(){
		return $this->mEnd;
	}
	
	
	private $mState = 0;
	private $mEnd;
}
?>