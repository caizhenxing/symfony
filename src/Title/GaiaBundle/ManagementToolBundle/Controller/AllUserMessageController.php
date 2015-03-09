<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Gaia\Bundle\ManagementToolBundle\Constant\Sort;
use Gaia\Bundle\ManagementToolBundle\Constant\Display;
use Title\GaiaBundle\ManagementToolBundle\Util\Controller;
use Symfony\Component\HttpFoundation\Response;
use Gaia\Bundle\ManagementToolBundle\Util\SessionUtil;
use Gaia\Bundle\ManagementToolBundle\Constant\SessionKey;
use Title\GaiaBundle\ManagementToolBundle\Constant\MessageSort;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

/**
 * 全ユーザーに送信するメッセージを設定する
 * @author Takeda_Yoshihiro
 *
 */
class AllUserMessageController extends Controller{
	/**
	 * メッセージ一覧の表示
	 */
	public function listAction(){

		$sortColumn = $this->getRequest()->get('sort_colmn');
		$sortOrder = $this->getRequest()->get('sort_order');
		$param['sort_colmn'] = is_null($sortColumn) ? MessageSort::SORT_ID : $sortColumn;
		$param['sort_order'] = is_null($sortOrder) ? Sort::ORDER_DESC : $sortOrder;
		// ページャ処理用
		$offset = $this->get('request')->get('offset');
		$param['offset'] = is_null($offset) ? 0 : $offset;
		$param['limit'] = Display::DISPLAY_COUNT_USER_LIST;
		
		$result = $this->selectList($param);
		
		$pathParams = [
			'sort_colmn' => $param['sort_colmn'],
			'sort_order' => $param['sort_order']
		];
		
		// ページング
		$pager = $this->get('gaia.mng_tool.model.pager');
		$pager->setInc(Display::DISPLAY_COUNT_USER_LIST);
		$pager->setPath('all_user_message', $pathParams);
		$pager->setParameters($this->getAllDataCount($param));
		$displayData = $pager->getDisplayData($result);
		
		// 出力パラメータ設定
		$param['pager'] = $pager->getParameters();
		$param['mes_info_list'] = $displayData;
		return $this->render('TitleManagementToolBundle:user:all_message_list.html.twig', $param);
	}
	protected function selectList($param){
		$Pstatus = $this->get("Arpg.Logic.Util.PlayerStatus");
		$Equip = $this->get("Arpg.Logic.Util.Equip");
		$Stack = $this->get("Arpg.Logic.Util.StackItem");
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare(
				"select id,type,`from`,subject as title, send_date as send, end_date as `end`, reward_std_id,reward_num from mail_all ".$this->makeSortSql($param).$this->makeLimitSql($param));
		$ptmt->execute([]);
		$ret = [];
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$row["type"] = $this->makeTypeStr($row["type"]);
			$num = intval($row["reward_num"]);
			$std_id = intval($row["reward_std_id"]);
			if($num > 0){
				if($Pstatus->check($std_id)){
					$row["reward"] = $Pstatus->getData($std_id)["name"]."×".$num;
				}elseif($Equip->check($std_id)){
					$row["reward"] = $Equip->getData($std_id)["name"]."×".$num;
				}elseif($Stack->check($std_id)){
					$row["reward"] = $Stack->getData($std_id)["name"]."×".$num;
				}
			}
			$ret[] = $row;
		}
		return $ret;
	}
	protected function getAllDataCount($param){
		$ptmt = $this->getDoctrine()->getEntityManager()->getConnection()->prepare("select count(id) from mail_all");
		$ptmt->execute([]);
		$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
		return intval($rs[0][0]);
	}
	protected function makeTypeStr($type){
		$type = intval($type);
		switch($type){
			case 1:
				$type = ["通常","box_blue"];
				break;
			case 2:
				$type = ["緊急","box_red"];
				break;
			case 3:
				$type = ["お詫び","box_green"];
				break;
			case 0:
			default:
				$type = ["なし","box_black"];
				break;
		}
		return $type;
	}
	protected function makeSortSql($param){
		$sort = intval($param['sort_colmn']);
		$ord = intval($param['sort_order']);
		$ret = " order by ";
		switch($sort){
			case MessageSort::SORT_TYPE:
				$ret .= "type ";
				break;
			case MessageSort::SORT_FROM:
				$ret .= "`from` ";
				break;
			case MessageSort::SORT_TITLE:
				$ret .= "subject ";
				break;
			case MessageSort::SORT_SEND:
				$ret .= "send_date ";
				break;
			case MessageSort::SORT_END:
				$ret .= "end_date ";
				break;
			case MessageSort::SORT_ID:
			default:
				$ret .= "id ";
				break;
		}
		$ret .= $ord == Sort::ORDER_DESC?" desc ":" asc ";
		return $ret;
	}
	protected function makeLimitSql($param){
		$limit = intval($param["limit"]);
		$offset = intval($param["offset"]);
		return " limit $offset,$limit ";
	}
	
	/**
	 * エディットモードの表示
	 */
	public function editAction(){
		$req = $this->getRequest();
		$mes_id = intval($req->get('mes_id'));
		$action = $req->get('action');
		$insert = intval($req->get('mode')) != 1;

		$param = [
			"sort_colmn" => $this->getRequest()->get('sort_colmn'),
			"sort_order" => $this->getRequest()->get('sort_order'),
			"offset" => $this->getRequest()->get('offset'),
		];

		$TAG = "AllUserMessage";
		$Aselector = $this->get("title.mng_tool.service.assets_selector");
		
		$info = [];
		if(strcmp($action,"delete") == 0){
			// 削除モード
			$sql = $this->getSql();
			try{
				$this->useTransaction();
				$ptmt = $sql->prepare("delete from mail_all where id = ?");
				$ptmt->execute([intval($mes_id)]);
				$this->get("title.mng_tool.log")->out($TAG,["Delete",["id: $mes_id"]]);
				$sql->commit();$sql=null;
			}catch(\Exception $e){
				\Dcs\Toybox::printException($e);
				if($sql != null)
					$sql->rollBack();
			}
			return $this->redirect($this->generateUrl('all_user_message',$param));
		}else if(strcmp($action,"update") == 0){
			// 更新モード
			/*
			$param["mode"] = 1;
			*/
			$sql = $this->getSql();
			try{
				$this->useTransaction();
				$info["type"] = intval($req->get('mestype'));
				$info["from"] = $req->get('mesfrom');
				$info["title"] = $req->get('mestitle');
				$info["message"] = $req->get('message');
				$info["send_date"] = $req->get('senddate');
				$info["send_time"] = $req->get('sendtime');
				$info["end_date"] = $req->get('enddate');
				$info["end_time"] = $req->get('endtime');
				$info["del"] = $req->get('mesdel');
				$info["reward_type"] = intval($req->get('rewtype'));
				$info["reward_item"] = intval($req->get('rewitem'));
				$info["num"] = intval($req->get('rewnum'));
				if($insert){
					// 追加
					$ptmt = $sql->prepare("insert into mail_all (type,`from`,subject,body,send_date,end_date,can_delete,reward_std_id,reward_num) values(?,?,?,?,?,?,?,?,?)");
					$ptmt->execute([
							$info["type"],
							$info["from"],
							$info["title"],
							str_replace("\n","[br]",$info["message"]),
							$info["send_date"]." ".$info["send_time"].":00",
							$info["end_date"]." ".$info["end_time"].":00",
							$info["del"],
							$info["reward_item"],
							$info["num"],
					]);
					$info["id"] = intval($sql->lastInsertId());

					$this->get("title.mng_tool.log")->out($TAG,[
							"Create",
							[
								"id:".$info["id"],
								"type:".$this->makeTypeStr($info["type"])[0],
								"from:".$info["from"],
								"title:".$info["title"],
								"message:",
									explode("\n",$info["message"]),
								"send:".$info["send_date"]." ".$info["send_time"].":00",
								"end:".$info["end_date"]." ".$info["end_time"].":00",
								"deletable:".($info["del"]==0?"false":"true"),
								"reward:".$Aselector->assetName($info["reward_item"])."×".$info["num"],
							],
					]);
				}else{
					// 編集
					$info["id"] = $mes_id;
					$ptmt = $sql->prepare("update mail_all set type=?,`from`=?,subject=?,body=?,send_date=?,end_date=?,can_delete=?,reward_std_id=?,reward_num=? where id = ?");
					$ptmt->execute([
							$info["type"],
							$info["from"],
							$info["title"],
							str_replace("\n","[br]",$info["message"]),
							$info["send_date"]." ".$info["send_time"].":00",
							$info["end_date"]." ".$info["end_time"].":00",
							$info["del"],
							$info["reward_item"],
							$info["num"],
							$mes_id
					]);
					$this->get("title.mng_tool.log")->out($TAG,[
							"Update",
							[
								"id:".$info["id"],
								"type:".$this->makeTypeStr($info["type"])[0],
								"from:".$info["from"],
								"title:".$info["title"],
								"message:",
									explode("\n",$info["message"]),
								"send:".$info["send_date"]." ".$info["send_time"].":00",
								"end:".$info["end_date"]." ".$info["end_time"].":00",
								"deletable:".($info["del"]==0?"false":"true"),
								"reward:".$Aselector->assetName($info["reward_item"])."×".$info["num"],
							],
					]);
				}
				$sql->commit();$sql=null;
			}catch(\Exception $e){
				\Dcs\Toybox::printException($e);
				if($sql != null)
					$sql->rollBack();
			}
			return $this->redirect($this->generateUrl('all_user_message',$param));
		}else{
			$done = false;
			// 表示モード
			if($mes_id >= 0){
				// 編集モード
				$param["mode"] = 1;
				$rs = $this->getHs()->select(
						new Table("mail_all",["type","from","subject","body","send_date","end_date","can_delete","reward_std_id","reward_num"]),
						new Query(["="=>$mes_id])
				);
				if(!empty($rs)){
					$row = $rs[0];
					$info["id"] = $mes_id;
					$info["type"] = intval($row[0]);
					$info["from"] = $row[1];
					$info["title"] = $row[2];
					$info["message"] = $row[3];
					$sep = explode(" ",$row[4]);
					$info["send_date"] = $sep[0];
					$time = "00:00";
					if(count($sep)>1){
						$ts = explode(":",$sep[1]);
						if(count($ts)>1)
							$time = $ts[0].":".$ts[1];
					}
					$info["send_time"] = $time;
					$sep = explode(" ",$row[5]);
					$info["end_date"] = $sep[0];
					$time = "00:00";
					if(count($sep)>1){
						$ts = explode(":",$sep[1]);
						if(count($ts)>1)
							$time = $ts[0].":".$ts[1];
					}
					$info["end_time"] = $time;
					$info["del"] = intval($row[6]);
					$std_id = intval($row[7]);
					
					$info["reward_type"] = $this->assetTypeId($std_id);
					$info["reward_item"] = $std_id;
					$info["num"] = intval($row[8]);
					$done = true;
				}
			}
			if(!$done){
				// 追加モード
				$param["mode"] = 0;
				$info["type"] = 1;
				$info["from"] = $this->get("Arpg.Logic.Util.Text")->getText(10400);
				$info["title"] = "";
				$info["message"] = "";
				$info["send_date"] = "2010-01-01";
				$info["send_time"] = "00:00";
				$info["end_date"] = "2050-01-01";
				$info["end_time"] = "00:00";
				$info["del"] = 1;
				$info["reward_type"] = 0;
				$info["reward_item"] = 0;
				$info["num"] = 0;
			}
		}
		$param["mes_info"] = $info;
		
		$param["types"]=[
			1 => "通常",
			2 => "緊急",
			3 => "お詫び",
		];
		$param["assets_selector"] = $this->get("title.mng_tool.service.assets_selector")->create();
		return $this->render('TitleManagementToolBundle:user:all_message_edit.html.twig', $param);
	}
	protected function assetTypeId($aid){
		$aid = intval($aid);
		$assets_type = $this->get("gaia.mng_tool.model.asset_master")->selectTypes();
		$buf = [];
		foreach($assets_type as $id => $val){
			$buf[] = intval($id);
		}
		usort($buf,function($a,$b){
			if($a == $b) return 0;
			return ($a < $b) ? -1 : 1;
		});
		$pre = 0;
		for($i=0,$len=count($buf);$i<$len;++$i){
			if($aid < $buf[$i]){
				return $pre;
			}
			$pre = $buf[$i];
		}
		return 0;
	}
}