<?php
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\GameData\RecipeData as RecipeData;
use \Logic\Util\Equip as UEquip;
use \Dcs\Arpg\Time as Time;

class Mission extends \Dcs\Arpg\Logic{
	
	const SQL = 'select std_id,slot,title,info,message,open_min_lv,open_max_lv,parent_id,open_from,open_to,clear_condition,clear_target,clear_value,reward_std_id,reward_value,regular_day,regular_interval,regular_start,icon,open_content from mission';
	
	const HS_TBL = 'mission';
	public static $HS_FLD = [
			'std_id','slot','title','info','message',
			'open_min_lv','open_max_lv','parent_id','open_from','open_to',
			'clear_condition','clear_target','clear_value','reward_std_id','reward_value',
			'regular_day','regular_interval','regular_start','icon','open_content',
			'view_priority','client','clear_message','clear_button'
	];
	
	const STATE_ACTIVE = 2;
	const STATE_CLEAR = 3;
	
	public $id;
	public $priority;
	public $slot;
	public $time;
	public $stat;
	public $achieveNum;
	public $achieveMax;
	public $title;
	public $info;
	public $reward;
	public $client;
	public $message;
	public $clearMes;
	public $clearBtn;
	public $rewardStdId;
	public $rewardNum;
	public $fileIconS;
	public $open;

	const one = 86400;//一日の秒数
	const zero = 259200;// 開始日の秒数20100101からの秒数
	/**
	 * データ初期化
	 * @param array $row SQL定数で検索したデータをFETCH_NUMで取り出したもの または、HS_XX系定数でHS取得した物
	 */
	public function init($row){
		$this->id = intval($row[0]);
		$this->slot = $this->id < 611000?0:intval($row[1]);
		$this->title = $row[2];
		$time = new \Dcs\Arpg\Time();
		$time->setMySQLDateTime($row[9]);
		$this->time = $time->get();
		$this->info = $row[3];
		$this->message = $row[4];

		$this->mLvMin = intval($row[5]);
		$this->mLvMax = intval($row[6]);
		$this->mParent = intval($row[7]);
		$time->setMySQLDateTime($row[8]);
		$this->mOpen = $time->get();
		$this->mClose = $this->time;
		$this->mCond = intval($row[10]);
		$this->mTarget = intval($row[11]);
		$this->mValue = intval($row[12]);
		$this->rewardStdId = intval($row[13]);
		$this->rewardNum = intval($row[14]);
		if($this->id >= 611000){
			$now = (new Time())->get();
			$this->mRday = intval($row[15]);
			$this->mRint = intval($row[16]);
			$this->mRstart = intval($row[17]);
			if($this->mRday > 0){
				$day = intval(($now-self::zero)/self::one);
				$total = $this->mRday+$this->mRint;
				$count = intval(($day-$this->mRstart)/$total);
				
				$this->time = (self::zero+self::one*($count*$total+$this->mRday+$this->mRstart));
			}
		}
		$this->fileIconS = $this->makeIcon(intval($row[18]));
		$this->open = intval($row[19]);
		
		$this->priority = intval($row[20]);
		$this->client = $row[21];
		$this->clearMes = $row[22];
		$this->clearBtn = $row[23];
	}
	public function add(){
		$slot_time = $this->mOpen;
		if($this->mRday > 0){
			$now = (new Time())->get();
			$day = intval(($now-self::zero)/self::one);
			$total = $this->mRday+$this->mRint;
			$count = intval(($day-$this->mRstart)/$total);
			
			$slot_time = self::zero+self::one*($count*$total+$this->mRstart);
		}
		$this->get('Arpg.Logic.Util.Mission')->add($this->id,$this->mCond,$this->mTarget,$this->slot,$slot_time);
	}
	
	public function enable($ulv,$cleared){
		$now = (new Time())->get();
		if($this->mRday > 0){
			$day = intval(($now-self::zero)/self::one);
			$total = $this->mRday+$this->mRint;
			$local = ($day-$this->mRstart)%$total;
			$count = intval(($day-$this->mRstart)/$total);
			
			if($this->mRday <= $local){
				return false;
			}

			if(
				isset($cleared[$this->id]) &&
				(self::zero+self::one*($count*$total+$this->mRstart)) <= $cleared[$this->id] &&
				$cleared[$this->id] < (self::zero+self::one*($count*$total+$this->mRday+$this->mRstart))
			)
				return false;
		}elseif(isset($cleared[$this->id]) && $this->mOpen <= $now && $now <= $this->mClose)
			return false;
		if(!isset($cleared[$this->mParent]))
			return false;
		if($ulv < $this->mLvMin || $this->mLvMax < $ulv)
			return false;
		if($now < $this->mOpen || $this->mClose < $now)
			return false;
		return true;
	}
	/**
	 * 複数回行う場合、initを全部呼んでから、全部のexecを呼ぶこと
	 * @param int $uid
	 */
	public function exec($uid){
		$PStatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		$Equip = $this->get('Arpg.Logic.Util.Equip');
		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$Text = $this->get('Arpg.Logic.Util.Text');
		$Mission = $this->get('Arpg.Logic.Util.Mission');
		$Mission->exec($uid);
		$num = $Mission->num($this->mCond,$this->mTarget,$this->slot);
		$this->achieveNum = $num>$this->mValue?$this->mValue:$num;
		$this->achieveMax = $this->mValue;
		$name = '';
		$tid = 10011;
		if($Equip->check($this->rewardStdId)){
			$name = $Equip->getData($this->rewardStdId)['name'];
		}elseif($PStatus->check($this->rewardStdId)){
			$tid = 10012;
			$name = $PStatus->getData($this->rewardStdId)['name'];
		}elseif($Stack->check($this->rewardStdId)){
			$name = $Stack->getData($this->rewardStdId)['name'];
		}
		if($this->rewardNum < 1) $tid = 10013;
		$this->reward = $Text->getText($tid,['[item]'=>$name,'[num]'=>$this->rewardNum]);
		$this->stat = $num >= $this->mValue?self::STATE_CLEAR:self::STATE_ACTIVE;
	}
	
	private function makeIcon($id){
		$key = 'Arpg.Logic.PlayerData.Mission.makeIcon';
		$cache = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($cache == null){
			$cache = [];
			$rs = $this->getHs(false)->select(
					new Table('mission_icon',['id','file']),
					new Query(['>='=>0],-1)
			);
			foreach($rs as $row){
				$cache[intval($row[0])] = $row[1];
			}
			$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$cache);
		}
		return isset($cache[$id])?$cache[$id]:'';
	}
	private $mOpen = null;
	private $mClose = null;
	private $mLvMin = null;
	private $mLvMax = null;
	private $mParent = null;
	private $mCond = null;
	private $mTarget = null;
	private $mValue = null;
	private $mRday = 0;
	private $mRint = 0;
	private $mRstart = 0;
}

?>