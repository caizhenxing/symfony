<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\DungeonData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\Util\Quest as Quest;
use \Dcs\Arpg\ResError as ResError;
use \Logic\Util\GameParam as GameParam;
use \Dcs\DetailTimeLog as DTL;
use \Logic\DungeonData\Bonus;

class Result extends \Dcs\Arpg\Logic{
	const RANK_S=1;
	const RANK_A=2;
	const RANK_B=3;
	const RANK_C=4;

	const BONUS_FLORA = 0;
	const BONUS_EXP = 1;
	const BONUS_COMP = 2;
	const BONUS_DROP = 3;
	
	public $playerBeforeExpPer;
	public $playerAfterExpPer;
	
	public $addPlayerExp;
	public $restPlayerExp;
	
	public $afterMoney=0;
	public $beforeMoney=0;
	public $clearBonus=0;
	
	public $rewardBoxes=[];
	
	public $rank=4;
	public $beforeHunt=0;
	public $afterHunt=0;
	
	public $xlink;
	public $state;
	
	public $useElixir=0;
	
	public $isKbox=0;
	public $kboxIcon;
	public $kboxTitle;
	
	public $bonus;
	
	/**
	 * データ初期化
	 * @param int $uid アカウントデータ
	 * @param int $did ダンジョンSTDID
	 * @param bool $clear クリアフラグ
	 * @param array $item 使用したアイテムリスト
	 * @param array $tbox ローカルガチャID
	 * @param int $time クリア時間
	 * @param int $kill 撃破数
	 * @param array $xlink クロスリンク用STDIDリスト
	 * @param int $elixir エリクサー使用回数
	 * @return int 返り値用const
	 * 
	 */
	public function init($uid,$tryd, $clear, array $item, array $tbox, $time, $kill, $xlink,$elixir){
		$ret = null;
		DTL::Lap('DungeonData.Result start');

		$ActorStatus = $this->get('Arpg.Logic.Util.ActorStatus');

		$aid = $ActorStatus->getActorId($uid);

		DTL::Lap('create actor id');
		
		$this->state = $this->get('Arpg.Logic.PlayerData.State');

		$Stack = $this->get('Arpg.Logic.Util.StackItem');
		$PlayerStatus = $this->get('Arpg.Logic.Util.PlayerStatus');
		// ユーザーステータス取得
		$pstat = $PlayerStatus->getStatusMulti($uid,[
				self::warehouse,
				self::std_gp,
				self::stp_time,
				self::std_cp,
				self::try_dungeon,
				self::try_ticket,
				self::player_level,
				self::player_exp,
				self::money,
				self::std_quest_bonus_type,
				self::std_quest_bonus_value,
				self::std_use_stp,
				self::try_time,
				self::std_use_boost,
				self::std_use_boost_limit,
				
		]);

		DTL::Lap('fetch player status');
		
		$ticket = $pstat[self::try_ticket];
		$this->state->cp = $pstat[self::std_cp];
		$this->state->gachaPoint = $pstat[self::std_gp];
		$this->state->wareHouseSize = $pstat[self::warehouse];
		$this->state->boostTitle = $Stack->boostData($pstat[self::std_use_boost])['name'];
		$this->state->boostTime = $pstat[self::std_use_boost_limit];
		
		$friend = $this->get('gaia.friend.friend_management_service');
		$this->state->friendMax = $friend->getFriendLimit($uid);
		$this->state->nbFriend = count($friend->friendIds($uid));
		
		if($clear){
			$rs = $this->getHs()->select(new Table('action_ticket',['dungeon_id']),new Query(['='=>$ticket]));
			$save_dun = -1;
			foreach($rs as $row){
				$save_dun = intval($row[0]);
				break;
			}
	
			if($tryd == 0 || $tryd != $pstat[self::try_dungeon] || $save_dun != $tryd){
				throw new ResError("you dont same dungeon. $save_dun != $tryd",100);
			}
		}
		$bulv = $pstat[self::player_level] < 1? 1:$pstat[self::player_level];
		$buexp = $pstat[self::player_exp];
		$this->beforeMoney = $pstat[self::money];
		$this->state->level = $bulv;
		$this->state->exp = $buexp;
		$lvdata = $PlayerStatus->getLvData($bulv);
		$this->state->expMax = $lvdata['exp'];
		$this->state->stpMax = $lvdata['stp'];
		$this->state->costMax = $lvdata['cost'];
		$this->state->baseHp = $lvdata['hp'];
		$this->state->money = $this->beforeMoney;
		$this->playerBeforeExpPer = intval($bulv*100 + $buexp*100/$this->state->expMax);

		DTL::Lap('set player status');
		
		
		// エリクサー消費前処理
		$elixir = intval($elixir);
		$use_elixir = 0;
		$use_elixir_cp = 0;
		if($elixir > 0){
			$elixir_num = $Stack->getNum($uid,self::std_elixir);
			if($elixir_num >= $elixir)
				$use_elixir = $elixir;
			else{
				$use_elixir = $elixir_num;
				$elixir -= $use_elixir;
				$use_elixir_cp = $this->get('Arpg.Logic.Util.DevParam')->param(80)*$elixir;
				if($this->state->cp < $use_elixir_cp){
					throw new ResError('too low cp to use direct elixir',100);
				}
				$this->state->cp -= $use_elixir_cp;
			}
		}

		$wld = intval($tryd / 10000) % 100;
		$ara = intval($tryd / 100) % 100;
		$dun = $tryd % 100;
		
		DTL::Lap('fetch actor status');
		$set_pstate = [];
		$add_pstate = [];
		$done_lvup = false;
		if($clear){
			$bonus = $this->get('Arpg.Logic.DungeonData.Bonus');
			$Quest = $this->get('Arpg.Logic.Util.Quest');
			$rs = $Quest->getDungeonInfo($wld,$ara,$dun);
			if(count($rs) < 1){
				throw new ResError('dont find dungeoninfo.',100);
			}
			$info = $rs[0];
			$child = $info;
			if(!empty($info->tower)){
				$qlv = $Quest->getData($uid,$tryd);
				if(isset($qlv['nb_clear'])){
					$qlv = $qlv['nb_clear']+1;
				}else{
					$qlv = 1;
				}
				if(isset($info->tower[$qlv])){
					$rs = $Quest->getDungeonInfoByStdID($info->tower[$qlv]);
					if(!empty($rs))
						$child = $rs[0];
				}
			}
			DTL::Lap('get dungeon info');
			
			$kill_border = $info->kill_border;

			// ブースト
			$exp_boost = 1;
			$money_boost = 1;
			
			// アイテムブースト
			$rs = $this->getHs()->select(new Table('action_boost',['boost_std_id']),new Query(['='=>$ticket],-1));
			$add_exp_boost = 1;
			$add_money_boost = 1;
			foreach($rs as $row){
				$bst = $Stack->boostData($row[0]);
				if($add_exp_boost < $bst['exp'])
					$add_exp_boost = $bst['exp'];
				if($add_money_boost < $bst['flr'])
					$add_money_boost = $bst['flr'];
			}
			$exp_boost += $add_exp_boost-1;
			$money_boost += $add_money_boost-1;
			DTL::Lap('exec item boost');
			
			// ボーナス期間ブースト
			$bonus_value = $pstat[self::std_quest_bonus_value]/100;
			if($pstat[self::std_quest_bonus_type] == 1)//EXP
				$exp_boost += $bonus_value-1;
			elseif($pstat[self::std_quest_bonus_type] == 3)
				$money_boost += $bonus_value-1;
			
			// レスポンス用ボーナス
			$bonus->set(Bonus::T_FLORA,$money_boost);
			$bonus->set(Bonus::T_EXP,$exp_boost);
			
			$this->addPlayerExp = intval($child->player_exp*$exp_boost);
			$buexp += $this->addPlayerExp;
			$addmoney = intval($child->clear_money*$money_boost);
			
			DTL::Lap('exec dungeon boost');
			
			for(;;++$bulv){
				$exp = $PlayerStatus->getLvData($bulv)['exp'];
				if($exp < 1){
					$this->restPlayerExp = 0;
					$buexp = 0;
					break;
				}
				if($buexp < $exp){
					$this->restPlayerExp = $exp - $buexp;
					break;
				}
				$buexp -= $exp;
				$done_lvup=true;
			}

			$this->state->level = $bulv;
			$this->state->exp = $buexp;
			$lvdata = $PlayerStatus->getLvData($bulv);
			$this->state->expMax = $lvdata['exp'];
			$this->state->stpMax = $lvdata['stp'];
			$this->state->costMax = $lvdata['cost'];
			$this->state->baseHp = $lvdata['hp'];
			
			DTL::Lap('exec lvup');
			$this->playerAfterExpPer = intval($bulv*100 + $buexp*100/$this->state->expMax);

			$this->clearBonus = $addmoney;

			$this->afterMoney=$this->beforeMoney+$this->clearBonus;
			
			
			// メインスピリットを取得する
			$get_main = false;
			$main_spirit = $Quest->getAreaInfo($wld,$ara);
			
			if(empty($main_spirit)){
				$main_spirit = -1;
			}else{
				$main_spirit = $main_spirit[0]->main_spirit;
			}

			// アイテムドロップ
			$qus = [];
			foreach($tbox as $lgid){
				$tbox_d[$lgid] = true;
			}
			$rs = $this->getHs()->select(new Table('action_gacha',['local_dungeon_id','gacha_func_id','tbox_id','id','type'],'LDUN'),new Query(['='=>$ticket],-1));
			$fids=[[],[]];
			$box_icon=[[],[]];
			$cheet = false; // 宝箱チート
			foreach($rs as $row){
				if(intval($row[0]) != $ticket){
					$cheet = true;
					continue;
				}
				$lgid = intval($row[3]);
				if(!isset($tbox_d[$lgid])){
					$cheet = true;
					continue;
				}
				$type = intval($row[4]);
				if($type != 0 && $type != 1) continue;
				
				$fids[$type][]=intval($row[1]);
				$box_icon[$type][]=intval($row[2]);
			}
			$gres_list = array_merge($this->dropBox($uid,$fids[0]),$this->get('Arpg.Logic.Util.Gacha')->drawByFuncMulti($uid,$fids[1]));
			$box_icon = array_merge($box_icon[0],$box_icon[1]);

			DTL::Lap('try gacha');
				
			$reward_money = 0;
			$rew = [];
			for($i=0,$len=count($gres_list);$i<$len;++$i){
				$gres = $gres_list[$i];
				if($gres->stdId == $main_spirit){
					$get_main = true;
				}
				if($gres->stdId == self::money){
					$reward_money += $gres->num;
				}
				if($gres->stdId == self::std_cp){
					$this->state->cp += $gres->num;
				}
				if($gres->stdId == self::std_gp){
					$this->state->gachaPoint += $gres->num;
				}
				
				$gres->tboxType = $box_icon[$i];
				$gres_list[$i] = $gres;	// Reward拡張
			}
			$this->rewardBoxes = $gres_list;
			
			DTL::Lap('create gacha reward');
			

			// クリアランク
			$rs = $this->getHs()->select(
					new Table('box_quest',['hunt']),
					new Query(['='=>[$uid,$wld,$ara,$dun]])
			);
			if(!empty($rs))
				$this->beforeHunt = intval($rs[0][0]);
			$point  = $this->createRank($kill_border,$time-$info->time_border,$kill);

			if($this->beforeHunt > $info->comp_max)
				$this->beforeHunt = $info->comp_max;
			$this->afterHunt = $this->beforeHunt + $point;
			if($this->afterHunt > $info->comp_max){
				$this->afterHunt = $info->comp_max;
				$point = $this->afterHunt - $this->beforeHunt;
			}

			DTL::Lap('create clear rank');
			
			// 到達率アイテム付与
			$add_item = [];
			if($this->beforeHunt < $info->comp_open1 && $info->comp_open1 <= $this->afterHunt){
				$add_item[] = [$info->comp_stdid1,$info->comp_num1];

				if($info->comp_stdid1 == $main_spirit){
					$get_main = true;
				}
			}
			if($this->beforeHunt < $info->comp_max && $info->comp_max <= $this->afterHunt){
				$add_item[] = [$info->comp_stdid2,$info->comp_num2];
				if($info->comp_stdid2 == $main_spirit){
					$get_main = true;
				}
			}
			if(!empty($add_item)){
				$add_item = $this->get('Arpg.Logic.GameData.Reward')->add($uid,$add_item,10007);
				foreach($add_item as $comp_item){
					$comp_item->tboxType = $this->get('Arpg.Logic.Util.DevParam')->param(89);
					$comp_item->kind = 1;	// とりあえず１をつけとく　TODO
					$this->rewardBoxes[] = $comp_item;
				}
			}
			$this->beforeHunt = $this->beforeHunt*100 / $info->comp_max;
			$this->afterHunt = $this->afterHunt*100 / $info->comp_max;

			DTL::Lap('add complete item');
			
			// 鍵付ガチャ
			if(mt_rand(0,99) < $child->kbox_rate){
				$this->isKbox = 1;
				$this->kboxIcon = $child->kbox_icon;
				$this->kboxTitle = $child->kbox_title;
			}
			
			DTL::Lap('set kgacha');
			
			$set_pstate[] = [$uid,self::money,$this->afterMoney+$reward_money];
			$set_pstate[] = [$uid,self::player_level,$bulv];
			$set_pstate[] = [$uid,self::player_exp,$buexp];
			$set_pstate[] = [$uid,self::std_quest_bonus_type,0];
			$set_pstate[] = [$uid,self::std_quest_bonus_value,0];
			$add_pstate[] = [$uid, self::try_dungeon,-$pstat[self::try_dungeon]];
			if($this->isKbox > 0)
				$set_pstate[] = [$uid,self::std_kbox_id,$child->std_id()];
				

			$this->state->money = $this->afterMoney+$reward_money;

			DTL::Lap('update player actor status');
			
			$Quest->update($uid,$tryd,$get_main?Quest::FLAG_GETMAIN:Quest::FLAG_CLEAR,$point);
			
			DTL::Lap('update box_quest');
			
			$this->bonus = $bonus;
			// Xlink チェック
			$rs = $this->getHs()->select(new Table('box_actor',['spirit']),new Query(['='=>$aid]));
			$this->xlink = [];
			if(!empty($rs) && !empty($xlink)){
				$spirit1 = intval($rs[0][0]);
				$rs = $this->getHs()->select(new Table('box_quest',['world_id','area_id','dungeon_id']),new Query(['='=>$uid],-1));
				$opened = [];
				foreach($rs as $row){
					$opened[100000+intval($row[0])*10000+intval($row[1])*100+intval($row[2])] = true;
				}
				$qus = [];
				foreach($xlink as $spirit2){
					$qus[] = new Query(['='=>[$spirit1,$spirit2]]);
					$qus[] = new Query(['='=>[$spirit2,$spirit1]]);
				}
				$rss = $this->getHs(false)->selectMulti(
						new Table('quest_cross_link',['open_dungeon','info','spirit1','spirit2']),
						$qus
				);

				DTL::Lap('fetch xlink');
					
				$opener = [];
				$Equip = $this->get('Arpg.Logic.Util.Equip');
				foreach($rss as $rs)foreach($rs as $row){
					$did = intval($row[0]);
					if(isset($opened[$did])) continue;
					$opener[] = [$uid,$did];
					$stdid1 = intval($row[2]);
					$stdid2 = intval($row[3]);
					$this->xlink[] = [
							'spiritStdId1' => $stdid1,
							'spiritName1' => $Equip->getData($stdid1)['name'],
							'spiritStdId2' => $stdid2,
							'spiritName2' => $Equip->getData($stdid2)['name'],
							'info' => $row[1],
					];
				}
				$Quest->createMulti($opener);

				DTL::Lap('open xlink');
			}
		}else{
			$this->playerAfterExpPer = $this->playerBeforeExpPer;
			$this->afterMoney = $this->beforeMoney;
			// 挑戦中フラグを回収
			$set_pstate[] = [$uid,self::try_dungeon,0];
			DTL::Lap('dont clear');
		}

		// 装備消耗品取得
		$used = [];
		foreach($item as $std_id){
			$used[] = [$uid,intval($std_id),-1];
		}
		
		// エリクサー消費後処理
		if($use_elixir > 0){
			$used[] = [$uid,self::std_elixir,-$use_elixir];
		}
		if($use_elixir_cp > 0){
			$PlayerStatus->add($uid,self::std_cp,-$use_elixir_cp);
		}
		$useElixir = $use_elixir;

		$Stack->addMulti($used);
		DTL::Lap('use supplies ');
		
		// スタミナ消費
		$begin_time = $pstat[self::try_time];
		$shs = $this->get('Arpg.Logic.Util.GameParam')->getParam(GameParam::STP_HEAL_SEC);
		if($shs < 1) $shs = 1;
		$this->state->stpTime = $begin_time;
		$this->state->stpHealSec = $shs;
		$max_stp = $PlayerStatus->getLvData($bulv)['stp'];
		if($done_lvup){
			// Lvupしたので全快
			$set_pstate[] = [$uid,self::stp_time,0];
			$nowstp = intval(($begin_time - 0) / $shs);
			$this->state->stp = $nowstp>$max_stp?$max_stp:$nowstp;
		}else{
			if($shs < 1) $shs = 1;
			$nowstp = intval(($begin_time - $pstat[self::stp_time]) / $shs);
			$nowstp = $nowstp>$max_stp?$max_stp:$nowstp;
			if($pstat[self::std_use_stp] > $nowstp)
				throw new ResError('too low stp',100);
			$nowstp = $nowstp-$pstat[self::std_use_stp];
			$this->state->stp = $nowstp;
			$set_pstate[] = [$uid,self::stp_time,$begin_time-$nowstp*$shs];
		}
		DTL::Lap('use stp');

		$PlayerStatus->setMulti($set_pstate);
		$PlayerStatus->addMulti($add_pstate);
		DTL::Lap('DungeonData.Result end');
	}
	private function findNum($rs,$std_id,$default=null){
		if($std_id == 0) return $default;
		
		foreach($rs as $row){
			if(intval($row[0]) == $std_id)
				return intval($row[1]);
		}
		return $default;
	}
	
	/**
	 * クリアランク生成
	 * @param int $border
	 * @param unknown $sub_time
	 * @param unknown $kill
	 */
	private function createRank($border, $sub_time, $kill){
		$kill_rate = 0;
		if($border > 0){
			$kill_rate = $kill*100 / $border;
		}else{
			$kill_rate = 100;
		}
		if($kill_rate > 100) $kill_rate = 100;
		
		$rss = $this->selectHsCacheMulti(new Table('quest_clear_rank',['max_rate','point']),[new Query(['='=>0],-1),new Query(['='=>2],-1)]);
		
		$kpoint = 0;
		$cpoint = 0;
		$min = 200;
		$rs = $rss[0];
		usort($rs,function($a,$b){
			if(intval($a[0]) == intval($b[0])) return 0;
			return (intval($a[0]) < intval($b[0])) ? 1 : -1;
		});
		foreach($rs as $row){
			$max = intval($row[0]);
			$point = intval($row[1]);
			if($max > $min) continue;
			if($min <= $kill_rate) continue;
			$min = $max;
			$kpoint = $point;
		}
		$rs = $rss[1];
		usort($rs,function($a,$b){
			if(intval($a[0]) == intval($b[0])) return 0;
			return (intval($a[0]) < intval($b[0])) ? 1 : -1;
		});
		foreach($rs as $row){
			$max = intval($row[0]);
			$point = intval($row[1]);
			if($sub_time > $max) break;
			$cpoint = $point;
		}
		$tpoint = $kpoint+$cpoint;
		
		$Gparam = $this->get('Arpg.Logic.Util.GameParam');
		if($Gparam->getParam(GameParam::CLEAR_BORDER_S) <= $tpoint){
			$this->rank = self::RANK_S;
			return $Gparam->getParam(GameParam::CLEAR_POINT_S);
		}
		if($Gparam->getParam(GameParam::CLEAR_BORDER_A) <= $tpoint){
			$this->rank = self::RANK_A;
			return $Gparam->getParam(GameParam::CLEAR_POINT_A);
		}
		if($Gparam->getParam(GameParam::CLEAR_BORDER_B) <= $tpoint){
			$this->rank = self::RANK_B;
			return $Gparam->getParam(GameParam::CLEAR_POINT_B);
		}
		if($Gparam->getParam(GameParam::CLEAR_BORDER_C) <= $tpoint){
			$this->rank = self::RANK_C;
			return $Gparam->getParam(GameParam::CLEAR_POINT_C);
		}
		$this->rank = self::RANK_C;
		return $Gparam->getParam(GameParam::CLEAR_POINT_C);
	}
	const RATE_MAX = 1000000;
	private function dropBox($uid,$drop_ids){
		$key = 'Arpg.Logic.DungeonData.Result.dropBox';
		$data = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($data == null){
			$data = [];
			$rs = $this->getHs(false)->select(
					new Table('item_drop_rate',['item_drop_id','std_id','num','rate']),
					new Query(['>='=>0],-1)
			);
			foreach($rs as $row){
				$did = intval($row[0]);
				if(!isset($data[$did])){
					$data[$did] = [];
				}
				$num = intval($row[2]);
				if($num < 1) continue;
				$data[$did][] = [
					'std_id' => intval($row[1]),
					'num' => $num,
					'rate' => $row[3]+0
				];
			}
			// 確率を正規化
			foreach($data as &$list){
				$total = 0;
				foreach($list as $rate){
					$total += $rate['rate'];
				}
				if($total == 0) continue;
				foreach($list as &$elem){
					$elem['rate'] = intval(self::RATE_MAX * ($elem['rate'] / $total));
				}
				unset($elem);
			}
			unset($list);
			$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$data);
		}
		$get = [];
		for($i=0,$len=count($drop_ids);$i<$len;++$i){
			$did = intval($drop_ids[$i]);
			if(!isset($data[$did])) continue;
			$rate = mt_rand(0,self::RATE_MAX);
			$std_id = 0;
			$num = 0;
			foreach($data[$did] as $dat){
				if($rate < 0)
					break;
				$std_id = $dat['std_id'];
				$num = $dat['num'];
				$rate -= $dat['rate'];
			}
			$get[] = [$std_id,$num];
		}
		
		return $this->get('Arpg.Logic.GameData.Reward')->add($uid,$get,10230);
	}
	
	// STD_ID
	const player_level = 1;
	const player_exp = 2;
	const stp_time = 3;
	const std_cp = 10001;
	const std_gp = 10003;
	const money = 10000;
	const try_dungeon = 1010;
	const try_ticket = 1011;
	const try_time = 1012;
	const std_quest_bonus_type = 1013;
	const std_quest_bonus_value = 1014;
	const std_use_stp = 1015;
	const warehouse	= 7;
	const std_elixir = 203001;
	const std_try_boost = 1016;
	const std_kbox_id = 1017;
	const std_use_boost = 300;
	const std_use_boost_limit = 301;
	
}
?>