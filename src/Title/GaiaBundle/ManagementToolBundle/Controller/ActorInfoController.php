<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Gaia\Bundle\ManagementToolBundle\Controller\Abstracts\WithSideAndTabMenuController;
use Gaia\Bundle\ManagementToolBundle\Exception\ErrorMessages;
use Title\GaiaBundle\ManagementToolBundle\Exception\TitleErrorMessages;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Arpg\Time as Time;

class ActorInfoController extends WithSideAndTabMenuController {
	use\Dcs\Base;
	
	
	static private $EDIT = [
		"gender" => ["gender",50023,"性別",[1=>"男性",2=>"女性"]],
		"face" => ["face",50027,"顔型",[0=>"青年",1=>"少年",2=>"壮年"]],
		"htype" => ["htype",50024,"髪型","select std_id, concat(if(std_id < 601000,'男髪:','女髪:'),name) as name from actor_model where std_id >= 600000 and std_id < 602000"],
		"hcolor" => ["hcolor",50025,"髪色",[0=>["茶","background-color:#5e2e1f;color:white;"],1=>["金","background-color:#cab65c;"],2=>["赤","background-color:#ad5145;color:white;"],3=>["青","background-color:#4181b0;color:white;"],4=>["緑","background-color:#75b553;"],5=>["紫","background-color:#9261aa;color:white;"],6=>["黒","background-color:#565555;color:white;"],7=>["灰","background-color:#d8d2b4;"]]],
		"scolor" => ["scolor",50026,"肌色",[5=>["5","background-color:#e5c5ad;"],0=>["0","background-color:#eec69a;"],1=>["1","background-color:#d7a177;"],2=>["2","background-color:#b8754c;"],3=>["3","background-color:#794f35;color:white;"],4=>["4","background-color:#594239;color:white;"]]],
		"eye" => ["eye",50028,"眼色",[0=>["青","background-color:#399fc2;"],1=>["茶","background-color:#c05c37;color:white;"],2=>["薄紫","background-color:#b4baee;"],3=>["赤","background-color:#e12b1a;color:white;"],4=>["緑","background-color:#78c639;"],5=>["紫","background-color:#de20c8;color:white;"],6=>["黄","background-color:#faf100;"],7=>["青緑","background-color:#20deb6;"]]],
	];
	/**
	 * ユーザ検索画面 表示アクション
	 *
	 * @return Response
	 */
	public function editAction() {
		$req = $this->getRequest();
		$userId = intval($req->get ( 'user_id' ));
		$aid = intval($req->get ( 'actor_id' ));
		$errors = [];

		$TAG = "ActorInfo";
		
		$action = $req->get("action");
		if(strcmp($action,"delete") == 0){
			$con = $this->getSql();
			$this->useTransaction();
			try{
				$ptmt = $con->prepare("update box_actor set state = 1,update_date = ? where actor_id = ?");
				$now = new Time();
				$ptmt->execute([$now->getMySQLDateTime(),$aid]);
				$ptmt = $con->prepare("select actor_id from box_actor where uid = ? and state = 0 limit 1");
				$ptmt->execute([$userId]);
				$caid = 0;
				while($row = $ptmt->fetch(\PDO::FETCH_NUM)){
					$caid = intval($row[0]);
					break;
				}
				$ptmt = $con->prepare("update box_player set current_actor = ? where uid = ?");
				$ptmt->execute([$caid, $userId]);
				$this->get("title.mng_tool.log")->out($TAG,[
						"Delete",
						["user_id: ".$userId,"actor_id: ".$aid],
				]);
				$con->commit();$con=null;
			}catch(\Exception $e){
				if($con != null)
					$con->rollBack();
				$errors[] = "アクター削除に失敗しました";
			}
		}elseif(strcmp($action,"restore") == 0){
			$con = $this->getSql();
			$this->useTransaction();
			try{
				$ptmt = $con->prepare("update box_actor set state = 0,update_date = ? where actor_id = ?");
				$now = new Time();
				$ptmt->execute([$now->getMySQLDateTime(),$aid]);

				$ptmt = $con->prepare("update box_player set current_actor = ? where uid = ? and current_actor = 0");
				$ptmt->execute([$aid, $userId]);

				$this->get("title.mng_tool.log")->out($TAG,[
						"Restore",
						["user_id: ".$userId,"actor_id: ".$aid],
				]);
				$con->commit();$con=null;
			}catch(\Exception $e){
				if($con != null)
					$con->rollBack();
				$errors[] = "アクター復帰に失敗しました";
			}
		}elseif(strcmp($action,"update") == 0){
			$con = $this->getSql();
			$this->useTransaction();
			try{
				$set = [];
				$log = ["user_id: ".$userId,"actor_id: ".$aid];
				foreach(self::$EDIT as $key => $val){
					$edit = $req->get($key);
					if($edit === null || !is_numeric($edit)) continue;
					$edit = intval($edit);
					$set[] = [$aid,$val[1],intval($edit)];

					// ログ生成
					if(is_array($val[3]))
						if(is_array($val[3][$edit]))
							$log[] = $val[2].": ".$val[3][$edit][0];
						else
							$log[] = $val[2].": ".$val[3][$edit];
					elseif(is_string($val[3])){
						$ptmt = $this->getSql()->prepare($val[3]);
						$ptmt->execute([]);
						$dat = null;
						while($row = $ptmt->fetch(\PDO::FETCH_NUM)){
							if($edit == intval($row[0])){
								$dat = $row[1];
								break;
							}
						}
						$log[] = $val[2].": ".$dat;
					}
				}
				$this->get("Arpg.Logic.Util.ActorStatus")->setMulti($set);

				$this->get("title.mng_tool.log")->out($TAG,[
						"Update",
						$log,
				]);
				
				$con->commit();$con=null;
			}catch(\Exception $e){
				if($con != null)
					$con->rollBack();
				$errors[] = "ステータス更新に失敗しました";
			}
		}
		$data = $this->getData($userId ,$aid);
		// execute
		return $this->render ( 'TitleManagementToolBundle:user:actor_info.html.twig', [ 
				'user_id' => $userId,
				'actor_id' => $aid,
				'param' => $data[0],
				'mode' => $data[1],
				'status' => $this->getStatus($userId,$aid),
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
	protected function getData($userId, $aid) {
		$data = [];

		$rs = $this->getHs ()->select (
				new Table ( "box_actor", ["actor_id","name","spirit","login_date","state","update_date" ] ),
				new Query ( [ "=" => $aid ])
		);
		$state = -1;
		foreach($rs as $row){
			$data["名前"] = $row[1];
			$data["武器"] = $row[2].".".$this->get ("Arpg.Logic.Util.Equip")->getData($row[2])["name"];
			$data["最終ログイン時間"] = $row[3];
			$state = intval($row[4]);
			if($state != 0)
				$data["状態"] = $row[5]."に削除済み";
			else
				$data["状態"] = "通常";
			break;
		}
		
		return [$data,$state];
	}
	/**
	 * @return array [
	 * 		["post_key",status_id,label,keyvaluelist,current]
	 * ]
	 */
	protected function getStatus($userId, $aid) {
		$data = [];
		$Astatus = $this->get("Arpg.Logic.Util.ActorStatus");
		foreach(self::$EDIT as $param){
			if(count($param) != 4) continue;
			if(is_string($param[3])){
				$ptmt = $this->getSql()->prepare($param[3]);
				$ptmt->execute([]);
				$buf = [];
				while($row = $ptmt->fetch(\PDO::FETCH_NUM)){
					$buf[intval($row[0])] = $row[1];
				}
				$param[3] = $buf;
			}
			$param[4] = $Astatus->getStatus($aid,$param[1]);
			$data[] = $param;
		}
		return $data;
	}
}