<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Gaia\Bundle\ManagementToolBundle\Exception\ErrorMessages;
use Title\GaiaBundle\ManagementToolBundle\Exception\TitleErrorMessages;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\Time as Time;

class UserInfoController extends \Gaia\Bundle\ManagementToolBundle\Controller\UserInfoController {
	use \Dcs\Base;
	const NUMBER = 0;
	const DATETIME = 1;
	static private $EDIT = [
		"lv"	=> ["lv",1,"レベル",self::NUMBER],
		"exp"	=> ["exp",2,"経験値",self::NUMBER],
		"stpe"	=> ["stpe",3,"スタミナ算出時間",self::DATETIME],
		"eset"	=> ["eset",6,"装備セット数",self::NUMBER],
		"size"	=> ["size",7,"倉庫上限",self::NUMBER],
		"flv"	=> ["flv",100,"薬屋レベル","select level_open,level_open from factory_product_list where type = 1 order by level_open"],
		"mlv"	=> ["mlv",120,"魔法屋レベル","select level_open,level_open from factory_product_list where type = 3 order by level_open"],
		"flr"	=> ["flr",10000,"フロラ",self::NUMBER],
		"gp"	=> ["gp",10003,"ガチャポイント",self::NUMBER],
		"cp"	=> ["cp",10001,"無料CP",self::NUMBER],
		"icp"	=> ["icp",10010,"iPhoneCP",self::NUMBER],
		"acp"	=> ["acp",10011,"AndroidCP",self::NUMBER],
		
	];
	
	/**
	 * ユーザ検索画面 表示アクション
	 *
	 * @return Response
	 */
	public function infoAction() {
		$req = $this->getRequest();
		$userId = intval($req->get ( 'user_id' ));
		$data = $this->getData ( $userId );
		$errors = [];

		$action = $req->get("action");
		if(strcmp($action,"update") == 0){
			$TAG = "UserInfo";
			$con = $this->getSql();
			$this->useTransaction();
			try{
				$sql = null;
				$arg = [];
				$log = ["id:".$userId];
				foreach(self::$EDIT as $key => $val){
					$edit = null;
					if($val[3] == self::DATETIME){
						$d = $req->get($key."_date");
						$t = $req->get($key."_time");
						if($d != null && $t != null){
							$dt = new Time();
							$dt->setMySQLDateTime($d." ".$t.":00");
							$edit = $dt->get();
						}
					}else{
						$edit = $req->get($key);
					}
					if($edit === null || !is_numeric($edit)) continue;
					if($sql == null)
						$sql = "insert into box_player_status (uid,std_id,num) values(?,?,?)";
					else
						$sql .= ",(?,?,?)";
					$arg[] = $userId;
					$arg[] = $val[1];
					$num = intval($edit);
					if($num > 0xffffffff) $num = 0xffffffff;
					if($num < 0) $num = 0;
					$arg[] = $num;
					
					// ログ生成
					if(is_array($val[3]))
						$log[] = $val[2].": ".$val[3][intval($num)];
					elseif(is_string($val[3])){
						$ptmt = $this->getSql()->prepare($val[3]);
						$ptmt->execute([]);
						$dat = null;
						$num = intval($num);
						while($row = $ptmt->fetch(\PDO::FETCH_NUM)){
							if($num == intval($row[0])){
								$dat = $row[1];
								break;
							}
						}
						$log[] = $val[2].": ".$dat;
					}elseif($val[3] == self::NUMBER){
						$log[] = $val[2].": ".$num;
					}elseif($val[3] == self::DATETIME){
						$dt = new Time();
						$dt->set($num);
						$log[] = $val[2].": ".$dt->getMySQLDateTime();
					}
				}
				if($sql != null){
					$sql .= " on duplicate key update num = values(num)";
					$ptmt = $con->prepare($sql);
					$ptmt->execute($arg);
					
					$this->get("title.mng_tool.log")->out($TAG,[
							"Update",
							$log,
					]);
				}
				$con->commit();$con=null;
			}catch(\Exception $e){
				if($con != null)
					$con->rollBack();
				$errors[] = "ステータスの更新に失敗しました";
			}
		}
		// execute
		return $this->render ( 'TitleManagementToolBundle:user:user_info.html.twig', [ 
				'user_id' => $userId,
				'values' => $data,
				'status' => $this->getStatus($userId),
				'errors' => $errors,
				'tab' => [ 
						'path_param' => [ 
								'user_id' => $userId 
						] 
				] 
		] );
	}
	
	// SAMPLE GAIA の Controller を継承してタイトル側の拡張を行う場合のサンプル
	//
	// protected function getData($userId)
	// {
	// $result =
	// $this->get('title.master.dao.user.user_data')->selectByUserId($userId);
	// $data['ユーザ ID'] = $result['user_id'];
	// $data['フレンド ID'] = $result['public_id'];
	// $data['公開名'] = $result['user_name'];
	// $data['主人公名'] = $result['hero_name'];
	// $data['Rank'] = $result['rank'];
	// $data['ゲーム開始日時'] = $result['created_time'];
	// $data['最終プレイ日時'] = $result['last_play_time'];
	// $data['出撃パーティ'] = '保留';
	// $data['引継ぎ ID'] = $result['take_over_id'];
	// $data['NoahID'] = $result['noah_id'];
	// return $data;
	// }
	protected function getData($userId) {
		$userId = intval ( $userId );
		$data = parent::getData ( $userId );
		foreach($data as $k => $v){
			$data[$k] = [
				"link" => null,
				"val" => $v
			];
		}
		$rs = $this->getHs ()->select ( 
				new Table ("box_player", [ "iname","login_date","current_actor" ] ), 
				new Query (["=" => $userId])
		);
		$row = ["----","----",0];
		if (! empty ( $rs ))
			$row = $rs [0];
		$data ["キャラクターID"] = ["val"=>$row[0],"link" => null];
		$data ["最終ログイン時間"] = ["val"=>$row[1],"link" => null];
		$cact = intval ( $row [2] );
		
		$rs = $this->getHs ()->select (
				new Table ( "box_actor", ["actor_id","name","spirit","login_date","state","update_date"], "UID" ),
				new Query ( [ "=" => $userId ], - 1 )
		);
		$Equip = $this->get ( "Arpg.Logic.Util.Equip" );
		$index = 1;
		foreach ( $rs as $row ) {
			$aid = intval($row[0]);
			$val = [
				"名前:" . $row [1],
				"武器:" . $row [2] . "." . $Equip->getData ( $row [2] )["name"],
				"最終ログイン". $row[3]
			];
			if(intval($row[4]) != 0){
				$val[] = $row[5]."に削除済み";
			}
			if($cact == $aid)
				$val[] = "最終利用アクター";
			$data ['アクター'.$index] = [
				"val" => $val,
				"link" => ["actor_info_path",["user_id" => $userId, "actor_id" => $aid]]
			];
			++ $index;
		}
		return $data;
	}
	/**
	 * @return array [
	 * 		["post_key",status_id,label,keyvaluelist,current]
	 * ]
	 */
	protected function getStatus($userId) {
		$data = [];
		$rs = $this->getHs()->select(
			new Table("box_player_status",["std_id","num"]),
			new Query(["="=>$userId],-1)
		);
		$status = [];
		foreach($rs as $row){
			$status[intval($row[0])] = intval($row[1]);
		}
		foreach(self::$EDIT as $param){
			if(count($param) != 4) continue;
			if($param[3] == self::DATETIME){
				$dt = new Time();
				$dt->set(isset($status[$param[1]])?$status[$param[1]]:0);
				$dt = $dt->getMySQLDateTime();
				list($date,$time) = explode(" ",$dt);
				list($h,$m,$s) = explode(":",$time);
				$param[4] = $date;
				$param[5] = $h.":".$m;
			}else{
				if(is_string($param[3])){
					$ptmt = $this->getSql()->prepare($param[3]);
					$ptmt->execute([]);
					$buf = [];
					while($row = $ptmt->fetch(\PDO::FETCH_NUM)){
						$buf[intval($row[0])] = $row[1];
					}
					$param[3] = $buf;
				}
				$param[4] = isset($status[$param[1]])?$status[$param[1]]:0;
			}
			$data[] = $param;
		}
		return $data;
	}
}