<?php
/**
 * メモ
 * 地道に高速化１を実装ずみ
 */
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\Time as Time;
use \Dcs\Arpg\ResError as ResError;
use \Logic\Util\Equip as UEquip;

class Basic extends \Dcs\Arpg\Logic{
	public $id;
	public $serverTime;
	public $searchId;
	//public $tutorialStep;
	public $tutorialSteps;
	public $mail;
	
	// public $localTime; サーバーでは不要 追加忘れじゃないということを主張
	
	/**
	 * データ初期化
	 * @param int $uid;
	 * @return object エラーオブジェクト nullの場合エラーなし
	 */
	public function init($uid){
		if($uid == null) 
			throw new ResError('user id is invalid value.',100);
		
		$this->uid = $uid;
		
		
		$this->id = $this->get('Arpg.Logic.Util.PlayerStatus')->getPublicId($uid);
		if($this->id === FALSE)
			throw new ResError('use data is invalid value.',100);
		
		$rs = $this->getHs()->select(
				new Table('box_player', array('iname','mail','current_actor')),
				new Query(array('=' => $this->uid))
		);
		foreach($rs as $row){
			$this->searchId = $row[0];
			$this->mail = $row[1];
			$this->cActor = intval($row[2]);
			break;
		}
		$rs = $this->getHs()->select(
				new Table('box_tutorial', array('tag','step')),
				new Query(array('=' => $this->uid),-1)
		);
		$tset = [];
		foreach($rs as $row){
			$tset[] = ['tag'=>intval($row[0]),'step'=>intval($row[1])];
		}
		$this->tutorialSteps = ['__tset'=>$tset];
		if(empty($this->searchId)){
			$this->createUserData();
		}
		
		$this->serverTime = (new \Dcs\Arpg\Time())->get();
		$this->insertStatus();
		
	}
	
	/**
	 * 現在のアクターIDを取得する
	 */
	public function getCurrentActor(){
		return $this->cActor;
	}
	
	private $cActor = 0;
	
	private $uid = null;//ユーザーID
	private $newUser = false;
	/**
	 * uidのデータがないんで作る
	 */
	private function createUserData(){
		$this->newUser = true;
		set_time_limit(60);
		$this->makeUserName();
	}
	
	/**
	 * ユーザー名を自動生成する
	 * @return boolean 生成成功値
	 */
	private function makeUserName(){
		$run = true;
		while($run){
			try{
				$this->searchId = $this->createRandString(9);
				$this->useTransaction();
				$this->sql('box_player','insert into box_player(uid,iname) values(?,?)')->insert([$this->uid,$this->searchId]);
				$run = false;
			}catch(\Exception $e){
				\Dcs\Log::e('dont make box_player '.$e->getMessage(),true);
			}
		}
	}

	/**
	 * 初期ステータスを注入
	 * @return boolean
	 */
	private function insertStatus(){
		{
			$rs = $this->selectHsCache(
					new Table('player_init', array('std_id','num')),
					new Query(array('>' => 0),-1)
			);
			$uid = $this->uid;

			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			$Quest = $this->get('Arpg.Logic.Util.Quest');
			$Present = $this->get('Arpg.Logic.Util.Present');

			$qlist=[];
			$plist=[];
			$collection = [];
			foreach($rs as $row){
				$std_id = $row[0];
				$num = $row[1];
				$collection[] =$std_id;
				if($Quest->check($std_id)){
					$qlist[]=[$uid,$std_id];
				}elseif($Pstatus->check($std_id)){
					$plist[]=[$uid,$std_id,$num];
				}
			}
			
			$this->get('Arpg.Logic.Util.Collection')->createMulti($uid,$collection);
			$qus=[];
			foreach($qlist as $val){
				$std_id=intval($val[1]);	
				$wid = intval($std_id/10000) % 100;
				$aid = intval($std_id/100) % 100;
				$did = $std_id % 100;
				$qus[] = new Query(['='=>[$uid,$wid,$aid,$did]]);
			}
			$rss = $this->getHs()->selectMulti(
					new Table('box_quest',['world_id','area_id','dungeon_id']),
					$qus
			);
			foreach($rss as $rs){
				if(empty($rs))continue;
				$row=$rs[0];
				$std_id=intval(1000000+$row[0]*10000+$row[1]*100+$row[2]);
				foreach($qlist as $key=>$val){
					if(intval($val[1])==$std_id)
						unset($qlist[$key]);
				}
			}
			
			$qus=[];
			foreach($plist as $val){
				$qus[] = new Query(['='=>[$uid,$val[1]]]);
			}
			$rss = $this->getHs()->selectMulti(
					new Table('box_player_status',['std_id']),
					$qus
			);
			foreach($rss as $rs){
				if(empty($rs))continue;
				$std_id=$rs[0][0];
				foreach($plist as $key=>$val){
					if(intval($val[1])==$std_id)
						unset($plist[$key]);
				}
			}
		}
		
		$Quest->createMulti($qlist);
		$Pstatus->createMulti($plist);
	}
	
	private function createRandString($max){
		$list = explode(' ','A B C D E F G H I J K L M N O P Q R S T U V W X Y Z 0 1 2 3 4 5 6 7 8 9');
		$ret = '';
		for($i=0;$i<$max;++$i){
			$ret .= $list[array_rand($list)];
		}
		return $ret;
	}
	const std_tutorial = 200;
}

?>