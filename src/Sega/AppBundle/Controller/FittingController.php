<?php
namespace Sega\AppBundle\Controller;


use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Gaia\Bundle\HandlerSocketBundle\Util\HandlerSocketUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Security as sec;
use \Logic\Util\Equip as Equip;
use \Logic\CardData as CardData;

class FittingController extends \Dcs\DcsController{

	/**
	 * モデルシート取得
	 * Rpc設定
	 * 	data{
	 * 		// ベースパーツリスト
	 * 		bp:[
	 * 			{mdl:モデル名,tex:テクスチャ名,tgt:テクスチャターゲット名}
	 * 		],
	 * 		// テクスチャのみパーツ
	 * 		tp:[
	 * 			{tex:テクスチャ名,tgt:テクスチャターゲット名}
	 * 		],
	 * 		// 左手武器
	 * 		lw:{
	 * 			mdl:モデル名,
	 * 			tex:テクスチャ名,
	 * 			tgt:モデルターゲット名
	 * 		},
	 * 		// 右手武器
	 * 		rw:{
	 * 			mdl:モデル名,
	 * 			tex:テクスチャ名,
	 * 			tgt:モデルターゲット名
	 * 		},
	 * 		// アニメーションキー
	 * 		am:{
	 * 			mdl:アニメ用モデル名
	 * 		}
	 * 	}
	 *
	 * 	err
	 * 		1:データがない
	 */
	public function modelAction($data){
		$rpc = new \Dcs\Rpc();
		try{
			$this->make($rpc,json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true));
		}catch(\Exception $e){
			\Dcs\Log::e($e,true);
			$rpc->err = ['code'=>1,'mes'=>$e->getMessage()];
		}
		return new Response($rpc->toJson(sec\Mode::X_OR()));
	}

	/**
	 * モデルシート取得
	 * Rpc設定
	 * 	data{
	 * 		// ベースパーツリスト
	 * 		bp:[
	 * 			{mdl:モデル名,tex:テクスチャ名,tgt:テクスチャターゲット名}
	 * 		],
	 * 		// テクスチャのみパーツ
	 * 		tp:[
	 * 			{tex:テクスチャ名,tgt:テクスチャターゲット名}
	 * 		],
	 * 		// 左手武器
	 * 		lw:{
	 * 			mdl:モデル名,
	 * 			tex:テクスチャ名,
	 * 			tgt:モデルターゲット名
	 * 		},
	 * 		// 右手武器
	 * 		rw:{
	 * 			mdl:モデル名,
	 * 			tex:テクスチャ名,
	 * 			tgt:モデルターゲット名
	 * 		},
	 * 		// アニメーションキー
	 * 		am:{
	 * 			mdl:アニメ用モデル名
	 * 		}
	 * 	}
	 *
	 * 	err
	 * 		1:データがない
	 */
	public function modelByActorAction($data){
		$rpc = new \Dcs\Rpc();
		try{
			$aid = intval(json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true));

			$AStatus = $this->get('Arpg.Logic.Util.ActorStatus');
			$sid = $AStatus->getStatus($aid,self::std_eset);

			$std_ids = [self::std_sex,self::std_hair,self::std_hclr,self::std_skin,self::std_face,self::std_eye];
			$std_ids[] = self::std_eset_w + $sid*10;
			$std_ids[] = self::std_eset_h + $sid*10;
			$std_ids[] = self::std_eset_c + $sid*10;

			$stat = $AStatus->getStatusMulti($aid,$std_ids);
			$rss = $this->getHs()->selectMulti(
					new Table('box_equip',CardData::$CLMS,'IUS'),
					[
						new Query(['='=>$stat[self::std_eset_w+$sid*10]]),
						new Query(['='=>$stat[self::std_eset_h+$sid*10]]),
						new Query(['='=>$stat[self::std_eset_c+$sid*10]]),
					]
			);
// 			$table = "box_equip";
// 			$culmun = "`id`,`level`,`exp`,`skill`,`addon`,`evo`,`std_id`,`evo_bonus_atk`,`evo_bonus_matk`,`evo_bonus_def`,`evo_bonus_mdef`,`state`,`lock`";
// 			$where = "".$stat[self::std_eset_w+$sid*10].",".$stat[self::std_eset_h+$sid*10].",".$stat[self::std_eset_c+$sid*10];
// 			$sql = "select $culmun from $table where id IN ($where)";
// 			$stmt = $this->sql($table,$sql);
// 			$rs = $stmt->selectAll(array(),\PDO::FETCH_NUM);

// 			$rss = array();

// 			$id_list = array(
// 					$stat[self::std_eset_w+$sid*10],
// 					$stat[self::std_eset_h+$sid*10],
// 					$stat[self::std_eset_c+$sid*10]
// 				);
// 			foreach ($id_list as $key => $value) {
// 				$data = array();
// 				foreach ($rs as $rs_key => $rs_value) {
// 					if($rs_value[0] == $value){
// 						$data[] = $rs_value;
// 					}
// 				}
// 				$rss[] = $data;
// 			}

			$sheet = [
				'is_male'	=> $stat[self::std_sex]==1,
				'skin'		=> $stat[self::std_skin],
				'face'		=> $stat[self::std_face],
				'eye'		=> $stat[self::std_eye],
				'hair'		=> $stat[self::std_hair],
				'hair_color'=> $stat[self::std_hclr],
			];
			$wcard = null;

			foreach($rss as $rs)foreach($rs as $row){
				$card = $this->get('Arpg.Logic.CardData');
				$card->init($row);
				$std_id = $card->stdId;
				switch(Equip::std2type($std_id)){
					case Equip::TYPE_WEAPON:
						$wcard = $card;
						$sheet['weapon'] = $std_id;
						break;
					case Equip::TYPE_COSTUME:
						$sheet['costume'] = $std_id;
						break;
					case Equip::TYPE_HEADGEAR:
						$sheet['head_gear'] = $std_id;
						break;
					default:
						break;
				}
			}
			$this->make($rpc,$sheet);

		}catch(\Exception $e){
			\Dcs\Log::e($e,true);
			$rpc->err = ['code'=>1,'mes'=>$e->getMessage()];
		}

		return new Response($rpc->toJson(sec\Mode::X_OR()));
	}
	private function hash($data){
		return
			intval($data['is_male']).'|'.
			intval($data['skin']).'|'.
			intval($data['face']).'|'.
			intval($data['eye']).'|'.
			intval($data['hair']).'|'.
			intval($data['hair_color']).'|'.
			intval($data['costume']).'|'.
			intval($data['weapon']).'|'.
			intval($data['head_gear']);
	}
	private function make(&$rpc,$data){
		$time = 600;
		if(array_key_exists('no_cache',$data) && intval($data['no_cache'])!=0)
			$time=0;

		$hash = $this->hash($data);
		if($time > 0){
			$mem = $this->getMemcached();
			$cache = $mem->get($hash);
			if($cache != null){
				$cache = json_decode($cache,true);
				$rpc->data = $cache;
				return;
			}
		}

		$skin = intval($data['skin']);
		$is_male = $data['is_male'];
		$mw = $is_male?'m':'w';

		// 基礎パーツ
		$bparts = array();

		$rss = $this->selectHsCacheMulti(
				new Table('equip_data', array('model_m','texture_m','model_w','texture_w','model_flag','cloth_bone','anim_model')),
				[
					new Query(array('=' => intval($data['head_gear']))),
					new Query(array('=' => intval($data['costume']))),
					new Query(array('=' => intval($data['weapon'])))
				]
		);
		$hg_rs = $rss[0];
		$cs_rs = $rss[1];
		$wp_rs = $rss[2];

		$rss = $this->selectHsCacheMulti(
				new Table('actor_model', ['model','cloth_bone']),
				[
					new Query(['='=>intval($data['face'])]),
					new Query(['='=>intval($data['hair'])])
				]
		);
		$fc_rs = $rss[0];
		$hr_rs = $rss[1];

		// ヘッドギア
		$hair_flag = 'all';
		$head_flag = true;
		$ax = 0;	// 骨タイプ
		if(count($hg_rs) > 0){
			$rs = $hg_rs[0];
			$bparts[] = array(
					'mdl' => $is_male?$rs[0]:$rs[2],
					'tex' => $is_male?$rs[1]:$rs[3],
					'tgt' => 'hat',
			);
			switch(intval($rs[4])){
				case 1:
					$hair_flag = 'bnd';
					break;
				case 2:
					$hair_flag = 'hat';
					break;
				case 3:
					$hair_flag = 'met';
					break;
				case 4:
					$hair_flag = false;
					break;
				case 5:
					$head_flag = false;
					$hair_flag = false;
					break;
				default:
					$hair_flag = 'all';
					break;
			}
			$ax = intval($rs[5]);
		}

		// 顔
		if($head_flag !== false){
			foreach($fc_rs as $row){
				$bparts[] = array(
						'mdl' => $row[0],
						'tex' => $row[0].'_'.intval($skin),
						'tgt' => 'fac',
				);
			}
		}

		// 髪
		$hx = 0;//骨タイプ
		if($hair_flag !== false){
			foreach($hr_rs as $row){
				$bparts[] = array(
						'mdl' => $row[0].'_'.$hair_flag,
						'tex' => $row[0].'_'.intval($data['hair_color']),
						'tgt' => 'har',
				);
				$hx = intval($row[1]);
			}
		}

		// コスチューム
		if(count($cs_rs) < 1){
			// データ不備により中断
			\Dcs\Log::e('dont find costume '.$data['costume']);

			$rpc->err = 1;
			return new Response($rpc->toJson(sec\Mode::X_OR()));
		}
		$rs = $cs_rs[0];
		$bparts[] = array(
				'mdl' => $is_male?$rs[0]:$rs[2],
				'tex' => $is_male?$rs[1]:$rs[3],
				'tgt' => 'clt',
		);
		$cx = intval($rs[5]);	// 骨タイプ


		// テクスチャのみパーツ
		$tparts = array();
		// 体
		$tparts[] = array(
				'tex' =>  sprintf('bod000_a00_%s_%d', $mw, $skin),
				'tgt' => 'bod'
		);
		// 目
		if($head_flag !== false){
			$tparts[] = array(
					'tex' =>  sprintf('eye001_a00_%s_%d', $mw, intval($data['eye'])),
					'tgt' => 'eye'
			);
		}

		// 武器
		if(count($wp_rs) < 1){
			// データ不備により中断
			\Dcs\Log::e('dont find weapon '.$data['weapon']);
			$rpc->err = 1;
			return new Response($rpc->toJson(sec\Mode::X_OR()));
		}
		$rs = $wp_rs[0];
		$rweapon = null;
		if($rs[0] != null && strlen($rs[0]) > 3){
			$rweapon = array(
					'mdl' => $rs[0],
					'tex' => $rs[1],
					'tgt' => 'dj_bu_r_dy',
			);
		}
		$lweapon = null;
		if($rs[2] != null && strlen($rs[2]) > 3){
			$lweapon = array(
					'mdl' => $rs[2],
					'tex' => $rs[3],
					'tgt' => 'dj_bu_l_dy',
			);
		}
		$anim = array('mdl'=>$rs[6],'im'=>$is_male?1:0);


		$rpc->data = array(
				'hash' => $hash,
				'bp' => $bparts,
				'tp' => $tparts,
				'am' => $anim,
				'hx' => $hx,
				'ax' => $ax,
				'cx' => $cx,
		);
		if($rweapon != null)
			$rpc->data['rw'] = $rweapon;
		if($lweapon != null)
			$rpc->data['lw'] = $lweapon;

		if($time > 0){
			$mem->set($hash,json_encode($rpc->data),$time);
		}
	}


	const std_equip_set = 6;
	//std_id
	const std_sex	= 50023;
	const std_hair	= 50024;
	const std_hclr	= 50025;
	const std_skin	= 50026;
	const std_face	= 50027;
	const std_eye	= 50028;

	const std_eset = 50050;
	const std_eset_w = 50051;
	const std_eset_h = 50052;
	const std_eset_c = 50053;
	const std_eset_n = 50054;
	const std_eset_r = 50055;
	const std_eset_i = 50056;
}