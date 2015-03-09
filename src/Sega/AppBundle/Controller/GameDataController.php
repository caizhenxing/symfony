<?php

namespace Sega\AppBundle\Controller;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Logic\GameData as GameData;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;
use \Logic\GameData\FactoryItem as FactoryItem;
use \Dcs\Arpg\Time as Time;
use \Logic\Util\Mail as Mail;
use \Logic\ActionTutorial\Tips as Tips;

class GameDataController extends \Dcs\DcsController{
	public function convertHs2Object(array $row,array $fld){
		$ret = [];
		for($i=0,$len=count($fld);$i<$len;++$i){
			$dat = $row[$i];
			if(is_numeric($dat)){
				$it = intval($dat);
				$ft = $dat+0;
				if($it == $ft)
					$dat = $it;
				else
					$dat = $ft;
			}
			$ret[$fld[$i]] = $dat;
		}
		return $ret;
	}
	public function split($data, $int){
		$sep = explode(',',$data);
		$list = [];
		for($i=0,$len=count($sep);$i<$len;++$i){
			if(is_numeric($sep[$i])){
				$list[] = $int?intval($sep[$i]):($sep[$i]+0);
			}
		}
		return $list;
	}
	/**
	 * 初期化用データ
	 * リクエストデータ構造
	 * 		 null
	 * レスポンスデータ構造
	 * 		GameData.InitData
	 */
	public function getInitDataAction($data){
		$key = 'Arpg.GameDataController.getInitData';
		$ret = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($ret == null){
			$ret = $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
				$ret = [];
				// テキストデータ
				$text = [];
				$rs = $this->getHs(false)->select(
						new Table('lang_text_'.\Dcs\Arpg\Config::Lang,['name','text'],'TYPE'),
						new Query(['='=>0],-1)
				);
				foreach($rs as $row){
					$text[] =[
					'n' => $row[0],
					't' => $row[1]
					];
				}
				$ret['text'] = $text;
				
				// CWデータ
				$cw = [];
				$rs = $this->getHs(false)->select(
						new Table('lang_cw_'.\Dcs\Arpg\Config::Lang,['mode','type','head_str','body_str','btn1_str','btn2_str']),
						new Query(['>='=>0],-1)
				);
				foreach($rs as $row){
					$cw[] =[
					'mode' => intval($row[0]),
					'type' => $row[1],
					'headStr' => $row[2],
					'bodyStr' => $row[3],
					'btn1Str' => $row[4],
					'btn2Str' => $row[5],
					];
				}
				$ret['cw'] = $cw;
				
				$dev = []; // 開発用汎用パラメータ
				$rs = $this->get('Arpg.Logic.Util.DevParam')->all();
				foreach($rs as $key => $val){
					$dev[] = [
						'key' => $key,
						'val' => $val
					];
				}
				$ret['dev'] = $dev;
				
				return $ret;
			});
			$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$ret);
		}
		return $ret;
	}

	/**
	 * 初期化用データ
	 * リクエストデータ構造
	 * 		 null
	 * レスポンスデータ構造
	 * 		GameData.InitData
	 */
	public function getMasterDataAction($data){
		$key = 'Arpg.GameDataController.getMasterData';
		$ret = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($ret == null){
			$ret = $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
				$ret = [];
				// スキルアタック
				$skill = [];
				$fld = [
				'id','anim','power','collision','hit_time','speed',
				'move_time','move_type','force','col_state','col_scale',
				'effect','origin','delay',
				'anim0','time0','effect0','origin0','delay0',
				'anim1','time1','effect1','origin1','delay1',
				'camera'
						];
				$rs = $this->getHs(false)->select(
						new Table('skill_attack',$fld),
						new Query(['>='=>0],-1)
				);
				foreach($rs as $row){
					$m = $this->convertHs2Object($row,$fld);
					$m['collision'] = $this->split($m['collision'],true);
					$m['hit_time'] = $this->split($m['hit_time'],false);
					$m['force'] = $this->split($m['force'],false);
					$m['col_scale'] = $this->split($m['col_scale'],false);
					$m['camera'] = $this->split($m['camera'],false);
					$skill[] = $m;
				}
				$ret['skill'] = $skill;
	
				// 魔法データ
				$magic = [];
				$fld = ['id','name','icon','info','effect','effect2','effect3','text','attribute','power','mp','type','range','offset','angle','delay','data','data2','data3','status','st_rate','st_time','st_data','attack'];
				$rs = $this->getHs(false)->select(
						new Table('magic',$fld),
						new Query(['>='=>0],-1)
				);
				foreach($rs as $row){
					$m = $this->convertHs2Object($row,$fld);
					if(strlen($m['attribute']) < 1 ){
						$m['attribute'] = [];
					}else{
						$sep = explode(',',$m['attribute']);
						$list = [];
						for($i=0,$len=count($sep);$i<$len;++$i){
							if(is_numeric($sep[$i])){
								$list[] = intval($sep[$i]);
							}
						}
						$m['attribute'] = $list;
					}
					$magic[] = $m;
				}
				$ret['magic'] = $magic;
	
				// アドオンデータ
				$list = [];
				$fld = ['std_id','name','type','rarity','power','switch'];
				$rs = $this->getHs(false)->select(
						new Table('equip_addon',$fld),
						new Query(['>='=>0],-1)
				);
				foreach($rs as $row){
					$row[4] = intval($row[4]*100);
					$list[] = $this->convertHs2Object($row,$fld);
				}
				$ret['addon'] = $list;
				// アドオンタイプでーた
				$list = [];
				$fld = ['id','detail'];
				$rs = $this->getHs(false)->select(
						new Table('equip_addon_type',$fld),
						new Query(['>='=>0],-1)
				);
				foreach($rs as $row){
					$list[] = $this->convertHs2Object($row,$fld);
				}
				$ret['atype'] = $list;
				// アドオンスイッチでーた
				$list = [];
				$fld = ['id','stone0','stone1','stone2','stone3','stone4','stone5','price'];
				$rs = $this->getHs(false)->select(
						new Table('equip_addon_switch',$fld),
						new Query(['>='=>0],-1)
				);
				foreach($rs as $row){
					$list[] = $this->convertHs2Object($row,$fld);
				}
				$ret['asw'] = $list;
				// シリーズデータ
				$list = [];
				$fld = ['std_id','name','lv2','lv3','lv4','lv5'];
				$rs = $this->getHs(false)->select(
						new Table('equip_series',$fld),
						new Query(['>='=>0],-1)
				);
				foreach($rs as $row){
					$m = $this->convertHs2Object($row,$fld);
					for($i=2;$i<=5;++$i){
						$sep = explode(',',$m['lv'.$i]);
						$sel=[];
						foreach($sep as $s){
							$s = explode(':',$s);
							if(count($s) < 2) continue;
							$sel[] = ['id'=>intval($s[0]),'pow'=>intval($s[1]*100)];
						}
						$m['lv'.$i] = $sel;
					}
					$list[] = $m;
				}
				$ret['series'] = $list;
				
				
				// Effectデータ
				$effect = [];
				$fld = ['id','name','file','time','se_id','se_delay','script','type'];
				$rs = $this->getHs(false)->select(
						new Table('effect_data',$fld),
						new Query(['>='=>0],-1)
				);
				foreach($rs as $row){
					$effect[] = $this->convertHs2Object($row,$fld);
				}
				$ret['effect'] = $effect;
	
				// Tipsデータ
				$tips = [];
				$rs = $this->getHs(false)->select(
						new Table(Tips::TBL,Tips::$FLD),
						new Query(['>='=>0],-1)
				);
				foreach($rs as $row){
					$d = $this->get('Arpg.Logic.ActionTutorial.Tips');
					$d->init($row);
					$tips[] = $d;
				}
				$ret['ltips'] = $tips;
	
	
				// プレイヤーATK
				$rs = $this->getHs(false)->select(
						new Table('player_attack',['id','anim','power','just_power','collision','hit_time','blend_time','just_time','just_span','combo_span']),
						new Query(['>='=>0],-1)
				);
				$patk = [];	// 単体攻撃データ
				foreach($rs as $row){
					$col = explode(',',$row[4]);
					for($i=0,$len=count($col);$i<$len;++$i){
						$col[$i] = intval($col[$i]);
					}
					$hti = explode(',',$row[5]);
					for($i=0,$len=count($hti);$i<$len;++$i){
						$hti[$i] = $hti[$i]+0;
					}
					$patk[intval($row[0])]=[
					'anim' => $row[1],
					'power' => ($row[2]+0),
					'justPower' => ($row[3]+0),
					'collision' => $col,
					'hitTime' => $hti,
					'blendTime' => ($row[6]+0),
					'justTime' => ($row[7]+0),
					'justSpan' => ($row[8]+0),
					'comboSpan' => ($row[9]+0),
					];
				}
	
				$rs = $this->getHs(false)->select(
						new Table('player_combo',['id','attacks']),
						new Query(['>='=>0],-1)
				);
				$cmb = [];	// コンボデータ
				foreach($rs as $row){
					$sep = explode(',',$row[1]);
					$dat = [];
					for($i=0,$len=count($sep);$i<$len;++$i){
						$id = intval($sep[$i]);
						if(isset($patk[$id]))
							$dat[] = $patk[$id];
					}
					$cmb[intval($row[0])] = $dat;
				}
	
				$rs = $this->getHs(false)->select(
						new Table('player_anim_common',['id','idle','guard','damage','down','dead','standup','walk_f','walk_b','walk_r','walk_l','run_f','run_b','run_r','run_l','magic','item','fitting','escape','stiff','kenrei','good']),
						new Query(['>='=>0],-1)
				);
				$atk = [];	// 共通アニメデータ
				foreach($rs as $row){
					$cmn = [];
					for($i=1,$len=count($row);$i<$len;++$i){
						$cmn[$i-1] = $row[$i];
					}
					$id = intval($row[0]);
					$atk[] = [
					'type' => $id,
					'combo' => isset($cmb[$id])?$cmb[$id]:[],
					'common' => $cmn
					];
				}
	
				$ret['patk'] = $atk;
	
				return $ret;
			});
			$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$ret);
		}
		return $ret;
	}
	/**
	 * 工場設定情報を取得
	 * リクエストデータ構造
	 * data: {type:int}
	 * RPC構造
	 * data:[Arpg.Logic.GameData.FactoryItem ]
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getFactoryItemAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$type = $data['type'];
			
			$rs = $this->selectHsCache(
					new Table(FactoryItem::DBTBL,FactoryItem::$FLD),
					new Query(['='=>$type],-1)
			);
			
			$dat = array();
			foreach($rs as $row){
				$sitem = $this->get('Arpg.Logic.GameData.FactoryItem');
				$sitem->init($row);
				$dat[] = $sitem;
			}
			return $dat;
		});
	}
	/**
	 * ロード中のTipsを取得する
	 * リクエストデータ構造
	 * 		 null
	 * レスポンスデータ構造
	 * 		array [ActionTutorial.Tips]
	 */
	public function getLoadingTipsAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$key = 'Arpg.GameDataController.getloadingTips';
			$ret = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
			if($ret == null){
				$ret = [];
				$rs = $this->getHs(false)->select(
						new Table(Tips::TBL,Tips::$FLD),
						new Query(['>='=>0],-1)
				);
				foreach($rs as $row){
					$d = $this->get('Arpg.Logic.ActionTutorial.Tips');
					$d->init($row);
					$ret[] = $d;
				}
				$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$ret);
			}
			return $ret;
		});
	}
	/**
	 * ナビキャラ設定情報を取得
	 * リクエストデータ構造
	 * data: null
	 * RPC構造
	 * data:Arpg.Logic.GameData.NaviChara
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getNaviCharaAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$type = intval($data['type']);
			$rs = $this->selectHsCache(
					new Table('navi_chara', GameData\NaviChara::$CHKEY),
					new Query(array('=' => $type))
			);
			$rs = $rs[0];
			$dat = $this->get('Arpg.Logic.GameData.NaviChara');
			$dat->init($rs);

			return $dat;
		});
	}
	/**
	 * チュートリアル情報取得
	 * リクエストデータ構造
	 * 		 null
	 * レスポンスデータ構造
	 * 		array [GameData.ActTutorial]
	 */
	public function actTutorialAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$key = 'Sega.AppBundle.Controller.actTutorialAction';
			$ret = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
			if($ret == null){
				$ret = [];
				$rs = $this->getHs(false)->select(
						new Table('lang_act_tutorial_'.\Dcs\Arpg\Config::Lang,['type','title','info','image']),
						new Query(['>'=>0],-1)
				);
				foreach($rs as $row){
					$ret[]=[
							'type' => intval($row[0]),
							'title' => $row[1],
							'info' => $row[2],
							'image' => $row[3],
					];
				}
				$this->cache()->get(\Dcs\Cache::TYPE_APC,$key,$ret);
			}
			
			return $ret;
		});
	}
	/**
	 * シーケンスチュートリアル情報取得
	 * リクエストデータ構造
	 * 		 null
	 * レスポンスデータ構造
	 * 		array [GameData.SeqTutorial]
	 */
	public function seqTutorialAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$key = 'Sega.AppBundle.Controller.seqTutorialAction';
			$ret = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
			if($ret == null){
				$ret = [];
				$rs = $this->getHs(false)->select(
						new Table('lang_seq_tutorial_'.\Dcs\Arpg\Config::Lang,['type','priority','title','content','image','focus','open_content','flag','start_type','start_std_id','start_num']),
						new Query(['>'=>0],-1)
				);
				foreach($rs as $row){
					$types = explode(',',$row[8]);
					$stdids = explode(',',$row[9]);
					$nums = explode(',',$row[10]);
					$start = [];
					for($i=0,$len=count($types);$i<$len;++$i){
						if(!isset($start[$i])){
							$start[$i]=[
								'type' => 0,
								'stdId' => 0,
								'num' => 0
							];
						}
						$start[$i]['type'] = intval($types[$i]);
					}
					for($i=0,$len=count($stdids);$i<$len;++$i){
						if(!isset($start[$i])){
							$start[$i]=[
								'type' => 0,
								'stdId' => 0,
								'num' => 0
							];
						}
						$start[$i]['stdId'] = intval($stdids[$i]);
					}
					for($i=0,$len=count($nums);$i<$len;++$i){
						if(!isset($start[$i])){
							$start[$i]=[
								'type' => 0,
								'stdId' => 0,
								'num' => 0
							];
						}
						$start[$i]['num'] = intval($nums[$i]);
					}
					$ret[]=[
							'type' => intval($row[0]),
							'priority' => intval($row[1]),
							'title' => $row[2],
							'content' => $row[3],
							'image' => $row[4],
							'focus' => intval($row[5]),
							'open' => intval($row[6]),
							'flag' => $row[7],
							'start' => $start,
					];
				}
				$this->cache()->get(\Dcs\Cache::TYPE_APC,$key,$ret);
			}
			
			return $ret;
		});
	}
	/**
	 * テキストデータ取得
	 * リクエストデータ構造
	 * 		 null
	 * レスポンスデータ構造
	 * 		[['n'=>名前,'t'=>テキスト], ...]
	 */
	public function textAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$key = 'Arg.GameDataController.text';
			$ret = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
			if($ret == null){
				$ret = [];
				$rs = $this->getHs(false)->select(
						new Table('lang_text_'.\Dcs\Arpg\Config::Lang,['name','text'],'TYPE'),
						new Query(['='=>0],-1)
				);
				foreach($rs as $row){
					$ret[] =[
						'n' => $row[0],
						't' => $row[1]
					];
				}
				$this->cache()->get(\Dcs\Cache::TYPE_APC,$key,$ret);
			}
			return $ret;
		});
	}
	/**
	 * 共通ウィンドウデータ取得
	 * リクエストデータ構造
	 * 		 null
	 * レスポンスデータ構造
	 * 		List<CommonWindow.Data>
	 */
	public function getCwAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$key = 'Arg.GameDataController.getCwAction';
			$ret = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
			if($ret == null){
				$ret = [];
				$rs = $this->getHs(false)->select(
						new Table('lang_cw_'.\Dcs\Arpg\Config::Lang,['mode','type','head_str','body_str','btn1_str','btn2_str']),
						new Query(['>='=>0],-1)
				);
				foreach($rs as $row){
					$ret[] =[
						'mode' => intval($row[0]),
						'type' => $row[1],
						'headStr' => $row[2],
						'bodyStr' => $row[3],
						'btn1Str' => $row[4],
						'btn2Str' => $row[5],
					];
				}
				$this->cache()->get(\Dcs\Cache::TYPE_APC,$key,$ret);
			}
			return $ret;
		});
	}
	/**
	 * 
	 * @param unknown $data
	 * @return \Symfony\Component\HttpFoundation\Response|multitype:multitype:boolean NULL object
	 */
	public function itemBaseAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$list = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$ret = [];
			$Pstatus = $this->get('Arpg.Logic.Util.PlayerStatus');
			$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
			$Equip = $this->get('Arpg.Logic.Util.Equip');
			$Item = $this->get('Arpg.Logic.Util.StackItem');
			foreach($list as $std_id){
				$std_id = intval($std_id);
				$dat = ['type'=>0,'card'=>null,'item'=>null,'status'=>null];
				if($Pstatus->check($std_id)){
					$d = $Pstatus->getData($std_id);
					if($d['info'] != null && mb_strlen($d['info']) > 0){
						$dat['type'] = 1;
						$dat['status'] = [
							'stdId' => $std_id,
							'name' => $d['name'],
							'info' => $d['info']
						];
					}
				}elseif($Astatus->check($std_id)){
					$d = $Astatus->getData($std_id);
					if($d['info'] != null && mb_strlen($d['info']) > 0){
						$dat['type'] = 2;
						$dat['status'] = [
							'stdId' => $std_id,
							'name' => $d['name'],
							'info' => $d['info']
						];
					}
				}elseif($Equip->check($std_id)){
					$dat['type'] = 4;
					$cd = $this->get('Arpg.Logic.CardData');
					$cd->initData($std_id);
					$dat['card'] = $cd;
				}elseif($Item->check($std_id)){
					$dat['type'] = 3;
					$it = $this->get('Arpg.Logic.ItemData');
					$it->initData($std_id);
					$dat['item'] = $it;
				}else{
					continue;
				}
				$ret[] = $dat;
			}
			return $ret;
		});
	}
	/**
	 * 裏バッチ実行
	 * いろいろバッチ走らせる用
	 * 1時間に1回ペースで実行される
	 * タイムアウトながめ
	 * リクエストデータ構造
	 * 		 セッションキー
	 * レスポンスデータ構造
	 * 		int 時間
	 */
	public function bgBatchAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV2);
			$uid = $user->getUid();
			$Text = $this->get('Arpg.Logic.Util.Text');
			$Pstate = $this->get('Arpg.Logic.Util.PlayerStatus');
			$Mail = $this->get('Arpg.Logic.Util.Mail');
			$Dev = $this->get('Arpg.Logic.Util.DevParam');
			$ps = $Pstate->getStatusMulti($uid,[
					self::std_ghost_gacha_p,
					self::std_ghost_gacha_time,
					self::std_ghost_gacha_num,
					self::std_good_gacha_p,
					self::std_good_gacha_time,
					self::std_good_gacha_num,
			],false);
			$now = new Time();
			$now = $now->get();
			$mail = [];
			$psset = [];
			// read
			// ゴーストボーナスバッチ
			if($ps[self::std_ghost_gacha_time] <= $now && $ps[self::std_ghost_gacha_p] > 0){
				$mail[]=[
					'type'=>Mail::TYPE_REWARD,
					'from'=>$Text->getText(10400),
					'to'=>[$uid],
					'subject'=>$Text->getText(10501),
					'message'=>$Text->getText(10500,['[n]'=>$ps[self::std_ghost_gacha_num],'[p]'=>$ps[self::std_ghost_gacha_p]]),
					'reward'=>self::std_gacha_p,
					'reward_num'=>$ps[self::std_ghost_gacha_p],
					'limit'=>intval($Dev->param(65)),
				];
				$psset[] = [$uid,self::std_ghost_gacha_time,$now+3600*23];
				$psset[] = [$uid,self::std_ghost_gacha_p,0];
				$psset[] = [$uid,self::std_ghost_gacha_num,0];
			}
			// GOODボーナスバッチ			
			if($ps[self::std_good_gacha_time] <= $now && $ps[self::std_good_gacha_p] > 0){
				$mail[]=[
					'type'=>Mail::TYPE_REWARD,
					'from'=>$Text->getText(10400),
					'to'=>[$uid],
					'subject'=>$Text->getText(10511),
					'message'=>$Text->getText(10510,['[n]'=>$ps[self::std_good_gacha_num],'[p]'=>$ps[self::std_good_gacha_p]]),
					'reward'=>self::std_gacha_p,
					'reward_num'=>$ps[self::std_good_gacha_p],
					'limit'=>intval($Dev->param(88)),
				];
				$psset[] = [$uid,self::std_good_gacha_time,$now+3600*23];
				$psset[] = [$uid,self::std_good_gacha_p,0];
				$psset[] = [$uid,self::std_good_gacha_num,0];
			}
			
			// write
			$Pstate->setMulti($psset,false);
			$Mail->send($mail);
			
			return $this->get('Arpg.Logic.Util.DevParam')->param(64);
		});
	}
	
	public function getGameInfoAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$key = 'Arpg.Controller.GameDataController.getGameInfoAction';
			$ret = null;// $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
			if($ret == null){
				$rs = $this->getHs(false)->select(
						new Table('game_info',['type','idx','data'],'IDX_INDEX'),
						new Query(['>='=>0],-1)
				);
				
				$ret = [
					'appVersion' => null,
					'serverVersion' => null,
					'assetbundleServer' => null,
					'assetbundleVersion' => null,
					'unityMatching' => [],
					'unityNat' => [],
					'unityProxy' => [],
					'unityTest' => [],
				];
				$abs = -1;
				$abv = -1;
				foreach($rs as $row){
					$idx = intval($row[1]);
					if(strcmp('asset_bundle_server',$row[0]) == 0){
						if($idx == 0){
							$abs = $idx;
							$ret['assetbundleServer'] = $row[2];
						}
						continue;
					}
					if(strcmp('asset_bundle_version',$row[0]) == 0){
						if($idx == 0){
							$abv = $idx;
							$ret['assetbundleVersion'] = $row[2];
						}
						continue;
					}
					if(strcmp('server_version',$row[0]) == 0){
						if($idx == 0){
							$abv = $idx;
							$ret['serverVersion'] = $row[2];
						}
						continue;
					}
					if(strcmp('app_version',$row[0]) == 0){
						if($idx == 0){
							$abv = $idx;
							$ret['appVersion'] = $row[2];
						}
						continue;
					}
					if(strcmp('unity_matching',$row[0]) == 0){
						$ret['unityMatching'][] = $row[2];
					}
					if(strcmp('unity_nat',$row[0]) == 0){
						$ret['unityNat'][] = $row[2];
					}
					if(strcmp('unity_proxy',$row[0]) == 0){
						$ret['unityProxy'][] = $row[2];
					}
					if(strcmp('unity_test',$row[0]) == 0){
						$ret['unityTest'][] = $row[2];
					}
				}
				$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$ret);
			}
			return $ret;
		});
	}
	
	
	public function getInitActorPartsAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){

			$key = 'Arpg.Controller.GameDataController.getInitActorParts';
				
			$ret = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
			if(true || $ret == null){
				$stmt = $this->sql('actor_create_model','select model from actor_create_model order by id');
				$rs = $stmt->selectAll([],\PDO::FETCH_NUM);
				$ret = [
					'maleHair'=>[],
					'femaleHair'=>[],
					'maleFace'=>[],
					'femaleFace'=>[],
				];
				for($i=0,$len=count($rs);$i<$len;++$i){
					$row = $rs[$i];
					$std_id = intval($row[0]);
					if($std_id < 600000)
						continue;
					elseif($std_id < 601000)
						$ret['maleHair'][] = $std_id;
					elseif($std_id < 602000)
						$ret['femaleHair'][] = $std_id;
					elseif($std_id < 603000)
						$ret['maleFace'][] = $std_id;
					elseif($std_id < 604000)
						$ret['femaleFace'][] = $std_id;
				}
				$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$ret);
			}
			\Dcs\Log::e(json_encode($ret));
			return $ret;
		});
	}
	const std_ghost_gacha_p = 1030;
	const std_ghost_gacha_time = 1031;
	const std_ghost_gacha_num = 1032;
	const std_good_gacha_p = 1040;
	const std_good_gacha_time = 1041;
	const std_good_gacha_num = 1042;
	const std_gacha_p = 10003;
}
?>