<?php
namespace Sega\AppBundle\Controller;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Logic\GameData as GameData;
use \Logic\CardData as CardData;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\Arpg\Time as Time;

class PresentController extends \Dcs\DcsController{
	/**
	 * プレゼントリスト取得
	 * リクエストデータ構造
	 * data: string セッションID
	 * RPC構造
	 * data:PresentData配列
	 */
	public function fetchPresentAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
		
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			
			$Present = $this->get(self::PRESENT_SERVICE);
			$Equip = $this->get('Arpg.Logic.Util.Equip');
			$Item = $this->get('Arpg.Logic.Util.StackItem');
			
			$list = $Present->getPresentList($uid);
			$datlist = [];
			foreach($list as $line){
				$dat = new PresentData($line);
				if($Equip->check($dat->mStdId)){
					$dat->mItemName=$Equip->getData($dat->mStdId)['name'];
				}elseif($Item->check($dat->mStdId)){
					$dat->mItemName=$Item->getData($dat->mStdId)['name'];
				}
				$datlist[] = $dat;
			}
			return $datlist;
		});
	}
	
	/**
	 * プレゼントを受け取る
	 * リクエストデータ構造
	 * data: string セッションID
	 * RPC構造
	 * data:PresentData配列
	 */
	public function acceptAllPresentAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
		
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();
			
			$Present = $this->get(self::PRESENT_SERVICE);
			
			$Equip = $this->get('Arpg.Logic.Util.Equip');
			$Item = $this->get('Arpg.Logic.Util.StackItem');
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			
			$box_size = $Equip->freeSpace($uid);
			
			$list = $Present->getPresentList($uid);
			$time = new Time();
			usort($list,function($a,$b) use($time){
				$at = $time->setMySQLDateTime($a['created_time'])->get();
				$bt = $time->setMySQLDateTime($b['created_time'])->get();
				if($at == $bt) return 0;
				return ($at < $bt) ? -1 : 1;
			});
			$present_ids = [];
			for($i=0,$len=count($list);$i<$len;++$i){
				$line = $list[$i];
				$std_id = intval($line['asset_type_id']);
				if($Equip->check($std_id)){
					if($Equip->std2type($std_id) == $Equip::TYPE_ETC){
						$num = intval($line['asset_count']);
						if($box_size >= $num){
							$present_ids[] = intval($line['present_box_id']);
							$box_size -= $num;
						}
					}elseif($box_size > 0){
						$present_ids[] = intval($line['present_box_id']);
						--$box_size;
					}
				}else{
					$present_ids[] = intval($line['present_box_id']);
				}
			}
			
			if(empty($present_ids)) return [];
			
			$ret = [];
			$this->useTransaction();
			$list = $Present->receivePresents($uid,$present_ids);
			$equips = [];
			$stacks = [];
			$pstate = [];
			$qus=[];
			$add_equip = [];
			$add_eqinfo = [];
			$ccc = 0;
			foreach($list as $line){
				$std_id = intval($line['asset_type_id']);
				$num = intval($line['asset_count']);
				if($Equip->check($std_id)){
					if($Equip->std2type($std_id) == $Equip::TYPE_ETC){
						$add_equip = array_merge($add_equip,array_fill(0,$num,[$std_id,$Equip::STATE_HAS]));
						$add_eqinfo[] = [$std_id,$num];
						$ccc += $num;
					}else{
						$eqid = intval($line['asset_id']);
						if($eqid == 0){
							// 倉庫にまだつくってない
							$add_equip[] = [$std_id,$Equip::STATE_HAS];
							$add_eqinfo[] = [$std_id,1];
							$ccc += 1;
						}else{
							$equips[] = $eqid;
							$qus[] = new Query(['='=>$eqid]);
						}
					}
				}elseif($Item->check($std_id)){
					$stacks[] =[$uid,$std_id,$num];
					$reward = $this->get('Arpg.Logic.GameData.Reward');
					$reward->init($std_id,$num);
					$ret[] = $reward;
				}elseif($Pstatus->check($std_id)){
					$pstate[] = [$uid,$std_id,$num];
					$reward = $this->get('Arpg.Logic.GameData.Reward');
					$reward->init($std_id,$num);
					$ret[] = $reward;
				}
			}
			if(!empty($add_equip)){// アイテム追加
				$ids = $Equip->addMulti($uid,$add_equip);
				$count = 0;
				for($i=0,$len=count($add_eqinfo);$i<$len;++$i){
					$ainfo = $add_eqinfo[$i];
					$num = $ainfo[1];
					$reward = $this->get('Arpg.Logic.GameData.Reward');
					$reward->init($ainfo[0],$num);
					for($j=0;$j<$num;++$j){
						$reward->card[$j]->id = $ids[$count+$j];
					}
					$ret[] = $reward;
					$count += $num;
				}
			}
			
			$Equip->changeEquipStates($uid,$Equip::STATE_PRESENT,$Equip::STATE_HAS,$equips);
			$Item->addMulti($stacks);
			$Pstatus->addMulti($pstate);
			
			if(!empty($qus)){// 単一カードをReward化
				$rss = $this->getHs()->selectMulti(
						new Table(CardData::DBTBL,CardData::$CLMS),
						$qus
				);
				foreach($rss as $rs){
					foreach($rs as $row){
						$card = $this->get('Arpg.Logic.CardData');
						$card->init($row);
						$card->state = $Equip::STATE_HAS;
						$reward = $this->get('Arpg.Logic.GameData.Reward');
						$reward->init($card->stdId,1);
						$reward->card[0] = $card;
						$ret[] = $reward;
					}
				}
			}
			return $ret;
		});
	}
	
	/**
	 * プレゼント受け取り
	 * リクエストデータ構造
	 * data:[
	 * 		'skey' => string セッションID,
	 * 		'pid' => 受け取るプレゼントID
	 * ]
	 * RPC構造
	 * data:PresentData配列
	 */
	public function acceptPresentAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
				
			$skey = $data['skey'];
			$preid = intval($data['pid']);
		
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();
			
			$Present = $this->get(self::PRESENT_SERVICE);
			$Equip = $this->get('Arpg.Logic.Util.Equip');
			$Item = $this->get('Arpg.Logic.Util.StackItem');
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			
			$box_size = $Equip->freeSpace($uid);
			
			$list = $Present->getPresentList($uid);
			$ret = null;
			$dat = null;
			foreach($list as $line){
				$present_box_id = intval($line['present_box_id']);
				if($present_box_id != $preid)continue;
				$dat = $present_box_id;
				$std_id = intval($line['asset_type_id']);
				if($Equip->check($std_id)){
					if($Equip->std2type($std_id) == $Equip::TYPE_ETC  && $box_size < intval($line['asset_count']))
						throw new ResError('full warehouse',2001);
					elseif($Equip->std2type($std_id) != $Equip::TYPE_ETC && $box_size < 1)
						throw new ResError('full warehouse',2001);
				}
				break;
			}
			if($dat == null){
				throw new ResError('donnt exist present',2000);
			}else{
				$this->useTransaction();
				$line = $Present->receivePresent($uid,$dat);
				$std_id = intval($line['asset_type_id']);
				$num = intval($line['asset_count']);
				if($Equip->check($std_id)){
					if($Equip->std2type($std_id) == $Equip::TYPE_ETC){
						$ids = $Equip->addMulti($uid,array_fill(0,$num,[$std_id,$Equip::STATE_HAS]));
						$reward = $this->get('Arpg.Logic.GameData.Reward');
						$reward->init($std_id,$num);
						for($i=0;$i<$num;++$i){
							$reward->card[$i]->id = $ids[$i];
						}
						$ret = $reward;
					}else{
						$eqid = intval($line['asset_id']);
						if($eqid == 0){
							// アイテムないから作る
							$id = $Equip->add($uid,$std_id);
							$reward = $this->get('Arpg.Logic.GameData.Reward');
							$reward->init($std_id,1);
							$reward->card[0]->id = $id;
							$ret = $reward;
						}else{
							$Equip->changeEquipStates($uid,$Equip::STATE_PRESENT,$Equip::STATE_HAS,[$eqid]);
							$rs = $this->getHs()->select(
									new Table(CardData::DBTBL,CardData::$CLMS),
									new Query(['='=>$eqid])
							);
							foreach($rs as $row){
								$card = $this->get('Arpg.Logic.CardData');
								$card->init($row);
								if($card->state == $Equip::STATE_DEL)
									throw new ResError('donnt exist card present',2000);
								$card->state = $Equip::STATE_HAS;
								$reward = $this->get('Arpg.Logic.GameData.Reward');
								$reward->init($card->stdId,1);
								$reward->card[0] = $card;
								$ret = $reward;
								break;
							}
						}
					}
				}elseif($Item->check($std_id)){
					$Stack->add($uid,$std_id,$num);
					$reward = $this->get('Arpg.Logic.GameData.Reward');
					$reward->init($std_id,$num);
					$ret = $reward;
				}elseif($Pstatus->check($std_id)){
					$Pstatus->add($uid,$std_id,$num);
					$reward = $this->get('Arpg.Logic.GameData.Reward');
					$reward->init($std_id,$num);
					$ret = $reward;
				}
			}
			return $ret;
		});
	}
	
	const std_card_box_max = 7;
	const PRESENT_SERVICE = 'gaia_present_service';
}

class PresentData{
	public $mId;
	public $mItemName;
	public $mDescription;
	public $mTime;
	public $mStdId;
	public $mNum;
	public function __construct($line){
		$this->mId = intval($line['present_box_id']);
		$this->mStdId = intval($line['asset_type_id']);
		$this->mDescription = $line['message'];
		$tm = new Time();
		$tm->setMySQLDateTime($line['created_time']);
		$this->mTime = $tm->get();
		$this->mNum = $line['asset_count'];
	}
	
}
?>