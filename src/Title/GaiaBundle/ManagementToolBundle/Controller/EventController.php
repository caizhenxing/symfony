<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Title\GaiaBundle\ManagementToolBundle\Util\Controller;
use Gaia\Bundle\ManagementToolBundle\Constant\Sort;
use Gaia\Bundle\ManagementToolBundle\Constant\Display;
use Gaia\Bundle\ManagementToolBundle\Constant\SessionKey;
use Gaia\Bundle\ManagementToolBundle\Util\StringUtil;
use Gaia\Bundle\ManagementToolBundle\Util\SessionUtil;
use Gaia\Bundle\ManagementToolBundle\Dao\Gacha\MstGachaDao;
use Title\GaiaBundle\ManagementToolBundle\Constant\EventDropSort;
use Title\GaiaBundle\ManagementToolBundle\Constant\EventTradeSort;
use Title\GaiaBundle\ManagementToolBundle\Constant\EventShopSort;
use Title\GaiaBundle\ManagementToolBundle\Constant\DungeonSort;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class EventController extends Controller
{
///////////////////////////////////
// 交換所系
	/**
	 * エディットモードの表示
	 */
	public function shopEditAction(){
		$req = $this->getRequest();
		$id = intval($req->get('id'));
		$action = $req->get('action');
		$insert = intval($req->get('mode')) != 1;

		$param = [
			"sort_colmn" => $this->getRequest()->get('sort_colmn'),
			"sort_order" => $this->getRequest()->get('sort_order'),
			"offset" => $this->getRequest()->get('offset'),
		];

		$TAG = "EventShopItem";
		
		$info = [];
		if(strcmp($action,"delete") == 0){
			// 削除モード
			$sql = $this->getSql();
			try{
				$this->useTransaction();
				$ptmt = $sql->prepare("delete from item_trade where std_id = ?");
				$ptmt->execute([$id]);
				$this->get("title.mng_tool.log")->out($TAG,["Delete",["id: $id"]]);
				$sql->commit();$sql=null;
			}catch(\Exception $e){
				\Dcs\Toybox::printException($e);
				if($sql != null)
					$sql->rollBack();
			}
			return $this->redirect($this->generateUrl('title_event_trade_list',$param));
		}else if(strcmp($action,"update") == 0){
			// 更新モード
			/*
			$param["mode"] = 1;
			*/
			$sql = $this->getSql();
			$Assets = $this->get("title.mng_tool.service.assets_selector");
			try{
				$this->useTransaction();
				$info["stdid"] = $id;
				$info["cat"] = intval($req->get("cat"));
				$info["pri"] = intval($req->get("pri"));
				$info["title"] = $req->get("title");
				$info["info"] = str_replace("\n","[br]",$req->get("info"));
				$info["display"] = intval($req->get("display"));
				$info["pay_std_id"] = intval($req->get("pay_std_id"));
				$info["pay_num"] = intval($req->get("pay_num"));
				$item_ids = $req->get("item_id");
				$item_num = $req->get("item_num");
				$items = [];
				$ilabel = [];
				$iinfo = [];
				for($i=0;$i<count($item_ids);++$i){
					$iid = intval($item_ids[$i]);
					$inum = intval($item_num[$i]);
					if($iid * $inum == 0) continue;
					$items[] = $iid.":".$inum;
					$ilabel[] = $Assets->assetName($iid)."×".$inum;
					$iinfo[] = [
						"id" => $iid,
						"type" => $this->assetTypeId($iid),
						"num" => $inum
					];
				}
				$info["items"] = $iinfo;
				$info["limit"] = intval($req->get("limit"));
				$info["banner"] = $req->get("banner");
				$info["message"] = str_replace("\n","[br]",$req->get("message"));
				$info["conf_mes"] = str_replace("\n","[br]",$req->get("conf_mes"));
				$info["err_mes"] = str_replace("\n","[br]",$req->get("err_mes"));
				$info["from"] = $req->get("from_date")." ".$req->get("from_time").":00";
				$info["to"] = $req->get("to_date")." ".$req->get("to_time").":00";
				$info["from_date"] = $req->get("from_date");
				$info["to_date"] = $req->get("to_date");
				$info["from_time"] = $req->get("from_time");
				$info["to_time"] = $req->get("to_time");
				$second_tag = "";
				if($insert){
					$param["mode"] = 0;
					// 追加
					$ptmt = $sql->prepare("insert into item_trade (std_id,type,category,category_priority,title,info,display,pay_std_id,pay_num,items,`limit`,banner,message,conf_mes,err_mes,effective_from,effective_to) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
					$ptmt->execute([
							$info["stdid"],
							4,
							$info["cat"],
							$info["pri"],
							$info["title"],
							$info["info"],
							$info["display"],
							$info["pay_std_id"],
							$info["pay_num"],
							implode(",",$items),
							$info["limit"],
							$info["banner"],
							$info["message"],
							$info["conf_mes"],
							$info["err_mes"],
							$info["from"],
							$info["to"],
					]);
					$second_tag="Create";
				}else{
					$param["mode"] = 1;
					// 編集
					$ptmt = $sql->prepare("update item_trade set category=?,category_priority=?,title=?,info=?,display=?,pay_std_id=?,pay_num=?,items=?,`limit`=?,banner=?,message=?,conf_mes=?,err_mes=?,effective_from=?,effective_to=? where std_id = ?");
					$ptmt->execute([
							$info["cat"],
							$info["pri"],
							$info["title"],
							$info["info"],
							$info["display"],
							$info["pay_std_id"],
							$info["pay_num"],
							implode(",",$items),
							$info["limit"],
							$info["banner"],
							$info["message"],
							$info["conf_mes"],
							$info["err_mes"],
							$info["from"],
							$info["to"],
							$id,
					]);
					$second_tag="Update";
				}
				$dmode = [
					2 => "バナー",
					3 => "コストフレーム",
					4 => "Wコストフレーム"
				];
				$this->get("title.mng_tool.log")->out($TAG,[
					$second_tag,
					[
						"id:".$id,
						"category:".$info["cat"],
						"category_priority:".$info["pri"],
						"title:".$info["title"],
						"info:",
							explode("[br]",$info["info"]),
						"dispmode:".(isset($dmode[$info["display"]])?$dmode[$info["display"]]:$info["display"]),
						"pay:".$Assets->assetName($info["pay_std_id"])."×".$info["pay_num"],
						"items:",
							$ilabel,
						"limit:".$info["limit"],
						"banner:".$info["banner"],
						"message:",
							explode("[br]",$info["message"]),
						"conf_mes:",
							explode("[br]",$info["conf_mes"]),
						"err_mes:",
							explode("[br]",$info["err_mes"]),
						"from:".$info["from"],
						"to:".$info["to"],
					],
				]);
				$sql->commit();$sql=null;
				return $this->redirect($this->generateUrl('title_event_shop_list',$param));
			}catch(\Exception $e){
				\Dcs\Toybox::printException($e);
				if($sql != null){
					$sql->rollBack();
					$param["errors"][]=$e->getMessage();
				}
			}
		}else{
			// 表示モード

			if(strcmp($action,"new") == 0){
				$param["mode"] = 0;
				if(!is_numeric($id)){
					$param["errors"][]="数値以外のID[$id]が指定されました。803000～803999内の数値で指定してください";
					return $this->redirect($this->generateUrl('title_event_trade_list',$param));
				}
				$rs = $this->getHs()->select(new Table("item_trade",["std_id"]),new Query(["="=>$id]));
				if(!empty($rs)){
					$param["errors"][]="すでに存在する交換ID[$id]が指定されました。別のIDを指定してください";
					return $this->redirect($this->generateUrl('title_event_trade_list',$param));
				}
				if($id < 803000 || 803999 <= $id){
					$param["errors"][]="範囲外のID[$id]が指定されました。803000～803999の範囲内で指定してください";
					return $this->redirect($this->generateUrl('title_event_trade_list',$param));
				}
				$info = null;
				$src_id = $req->get('src_id');
				if($src_id != null && is_numeric($src_id)){
					$info = $this->makeShopInfo($id);
					if($info == null){
						$param["errors"][]="存在しないID[$id]をコピーしました。";
						return $this->redirect($this->generateUrl('title_event_shop_list',$param));
					}
				}else{
					$info = [
						"cat" => 0,
						"pri" => 0,
						"stdid" => $id,
						"title" => "",
						"info" => "",
						"display" => 0,
						"pay_std_id" => 0,
						"pay_num" => 0,
						"items" => [],
						"limit" => 0,
						"banner" => "",
						"message" => "",
						"conf_mes" => "",
						"err_mes" => "",
						"from_date" => "2010-01-01",
						"from_time" => "00:00",
						"to_date" => "2050-01-01",
						"to_time" => "00:00",
					];
				}
			}else{
				// 編集モード
				$param["mode"] = 1;
				$info = $this->makeShopInfo($id);
				if($info == null){
					$param["errors"][]="存在しないID[$id]が指定されました。";
					return $this->redirect($this->generateUrl('title_event_shop_list',$param));
				}
			}
		}
		$param["info"] = $info;
		$param["displays"] = [
			2 => "バナー",
			3 => "コストフレーム",
			4 => "Wコストフレーム"
		];
		$param["stacks"] = $this->getStackList();
		$param["assets_selector"] = $this->get("title.mng_tool.service.assets_selector")->create();
		return $this->render('TitleManagementToolBundle:event:shop_edit.html.twig', $param);
	}
	protected function makeShopInfo($id){
		$rs = $this->getHs()->select(
				new Table("item_trade",["title","info","display","pay_std_id","pay_num","items","limit","banner","message","conf_mes","err_mes","effective_from","effective_to","category","category_priority"]),
				new Query(["="=>$id])
		);
		if(!empty($rs)){
			$row = $rs[0];
			$info = [];
			$info["stdid"] = $id;
			$info["title"] = $row[0];
			$info["info"] = str_replace("[br]","\n",$row[1]);
			$info["display"] = intval($row[2]);
			$info["pay_std_id"] = intval($row[3]);
			$info["pay_std_type"] = $this->assetTypeId($info["pay_std_id"]);
			$info["pay_num"] = intval($row[4]);
				
			$info["items"] = $row[5];
			$info["limit"] = intval($row[6]);
			$info["banner"] = $row[7];
			$info["message"] = str_replace("[br]","\n",$row[8]);
			$info["conf_mes"] = str_replace("[br]","\n",$row[9]);
			$info["err_mes"] = str_replace("[br]","\n",$row[10]);
		
			$sep = explode(" ",$row[11]);
			$info["from_date"] = $sep[0];
			$time = "00:00";
			if(count($sep)>1){
				$ts = explode(":",$sep[1]);
				if(count($ts)>1)
					$time = $ts[0].":".$ts[1];
			}
			$info["from_time"] = $time;
				
			$sep = explode(" ",$row[12]);
			$info["to_date"] = $sep[0];
			$time = "00:00";
			if(count($sep)>1){
				$ts = explode(":",$sep[1]);
				if(count($ts)>1)
					$time = $ts[0].":".$ts[1];
			}
			$info["to_time"] = $time;
		
			$info["cat"] = intval($row[13]);
			$info["pri"] = intval($row[14]);
				
			$sep = explode(",",$info["items"]);
			$items = [];
			foreach($sep as $item){
				if(strlen($item) < 3) continue;
				$in = explode(":",$item);
				if(count($in) != 2) continue;
				$items[] = [
				"id" => intval($in[0]),
				"type" => $this->assetTypeId($in[0]),
				"num" => intval($in[1])
				];
			}
			$info["items"] = $items;
			return $info;
		}
		return null;
	}
	public function ShopListAction(){
		$sortColumn = $this->getRequest()->get('sort_colmn');
		$sortOrder = $this->getRequest()->get('sort_order');
		$param['sort_colmn'] = is_null($sortColumn) ? EventShopSort::CAT: $sortColumn;
		$param['sort_order'] = is_null($sortOrder) ? Sort::ORDER_ASC : $sortOrder;
		// ページャ処理用
		$offset = $this->get('request')->get('offset');
		$param['offset'] = is_null($offset) ? 0 : $offset;
		$param['limit'] = Display::DISPLAY_COUNT_USER_LIST;
		
		// フィルタ
		$param["filt_cat"] = $this->getRequest()->get('filt_cat');
		$param["filt_pri"] = $this->getRequest()->get('filt_pri');
		$param["filt_id"] = $this->getRequest()->get('filt_id');
		$param["filt_title"] = $this->getRequest()->get('filt_title');
		$param["filt_limit"] = $this->getRequest()->get('filt_limit');
		$param["filt_time"] = $this->getRequest()->get('filt_time');
		
		$param["filt_cat"] = $param['filt_cat']==null?"":$param["filt_cat"];
		$param["filt_pri"] = $param['filt_pri']==null?"":$param["filt_pri"];
		$param["filt_id"] = $param['filt_id']==null?"":$param["filt_id"];
		$param["filt_title"] = $param['filt_title']==null?"":$param["filt_title"];
		$param["filt_limit"] = $param['filt_limit']==null?"":$param["filt_limit"];
		$param["filt_time"] = $param['filt_time']==null?"":$param["filt_time"];
	
		$result = $this->selectShopList($param);
	
		$pathParams = [
		'sort_colmn' => $param['sort_colmn'],
		'sort_order' => $param['sort_order']
		];
	
		// ページング
		$pager = $this->get('gaia.mng_tool.model.pager');
		$pager->setInc(Display::DISPLAY_COUNT_USER_LIST);
		$pager->setPath('title_dungeon_list', $pathParams);
		$pager->setParameters($this->getAllShopCount($param));
		$displayData = $pager->getDisplayData($result);
	
		// 出力パラメータ設定
		$param['pager'] = $pager->getParameters();
		$param['datalist'] = $displayData;
		
		$param["errors"] = $this->getRequest()->get('errors');
	
		return $this->render('TitleManagementToolBundle:event:shop_list.html.twig', $param);
	}
	protected function makeShopFilter($param){
		$ret = null;
		$args = [];
		if(is_numeric($param["filt_cat"])){
			$ret = "and category=? ";
			$args[] = intval($param["filt_cat"]);
		}
		if($param["filt_pri"] != null){
			$ret .= "and category_priority = ? ";
			$args[] = $param["filt_pri"];
		}
		if($param["filt_id"] != null){
			$ret .= "and std_id = ? ";
			$args[] = $param["filt_id"];
		}
		if($param["filt_title"] != null){
			$ret .= "and title like ? ";
			$args[] = "%".$param["filt_title"]."%";
		}
		if($param["filt_limit"] != null){
			$ret .= "and `limit` = ? ";
			$args[] = $param["filt_limit"];
		}
		if($param["filt_time"] != null){
			$ret .= "and effective_from <= ? and effective_to >= ? ";
			$args[] = $param["filt_time"];
			$args[] = $param["filt_time"];
		}
		return [$ret,$args];
	}
	protected function getAllShopCount($param){
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare("select count(std_id) from item_trade where type = 4");
		$ptmt->execute([]);
		$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
		return intval($rs[0][0]);
	}
	protected function selectShopList($param){
		$flter = $this->makeShopFilter($param);
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare(
				"select category,category_priority,std_id,title,pay_std_id,pay_num,items,`limit`,effective_from,effective_to from item_trade where type = 4 ".$flter[0].$this->makeShopSortSql($param).$this->makeLimitSql($param));
		$ptmt->execute($flter[1]);
		$ret = [];
		$Assets = $this->get("title.mng_tool.service.assets_selector");
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$row["pay_std_id"] = $Assets->assetName($row["pay_std_id"]);
			$sep = explode(",",$row["items"]);
			$items = [];
			foreach($sep as $item){
				if(strlen($item) < 3) continue;
				$in = explode(":",$item);
				if(count($in) != 2) continue;
				$items[] = $Assets->assetName($in[0])."×".intval($in[1]);
			}
			$row["items"] = $items;
			$ret[] = $row;
		}
		return $ret;
	}
	protected function makeShopSortSql($param){
		$sort = intval($param['sort_colmn']);
		$ord = intval($param['sort_order']);
		$ret = " order by ";
		switch($sort){
			case EventShopSort::CAT:
				$ret .= "category ";
				break;
			case EventShopSort::PRI:
				$ret .= "category_priority ";
				break;
			case EventShopSort::TITLE:
				$ret .= "title ";
				break;
			case EventShopSort::PAY:
				$ret .= "pay_std_id ";
				break;
			case EventShopSort::LIMIT:
				$ret .= "`limit` ";
				break;
			case EventShopSort::FROM:
				$ret .= "effective_from ";
				break;
			case EventShopSort::TO:
				$ret .= "effective_to ";
				break;
			case EventShopSort::STDID:
			default:
				$ret .= "std_id ";
				break;
		}
		$ret .= $ord == Sort::ORDER_DESC?" desc ":" asc ";
		return $ret;
	}
///////////////////////////////////
// 交換所系
	/**
	 * エディットモードの表示
	 */
	public function tradeEditAction(){
		$req = $this->getRequest();
		$id = intval($req->get('id'));
		$action = $req->get('action');
		$insert = intval($req->get('mode')) != 1;

		$param = [
			"sort_colmn" => $this->getRequest()->get('sort_colmn'),
			"sort_order" => $this->getRequest()->get('sort_order'),
			"offset" => $this->getRequest()->get('offset'),
		];

		$TAG = "EventTradeItem";
		
		$info = [];
		if(strcmp($action,"delete") == 0){
			// 削除モード
			$sql = $this->getSql();
			try{
				$this->useTransaction();
				$ptmt = $sql->prepare("delete from item_trade where std_id = ?");
				$ptmt->execute([$id]);
				$this->get("title.mng_tool.log")->out($TAG,["Delete",["id: $id"]]);
				$sql->commit();$sql=null;
			}catch(\Exception $e){
				\Dcs\Toybox::printException($e);
				if($sql != null)
					$sql->rollBack();
			}
			return $this->redirect($this->generateUrl('title_event_trade_list',$param));
		}else if(strcmp($action,"update") == 0){
			// 更新モード
			/*
			$param["mode"] = 1;
			*/
			$sql = $this->getSql();
			$Assets = $this->get("title.mng_tool.service.assets_selector");
			try{
				$this->useTransaction();
				$title = $req->get("title");
				$detail = str_replace("\n","[br]",$req->get("info"));
				$disp_mode = intval($req->get("display"));
				$pay_id = intval($req->get("pay_std_id"));
				$pay_num = intval($req->get("pay_num"));
				$item_ids = $req->get("item_id");
				$item_num = $req->get("item_num");
				$items = [];
				$ilabel = [];
				$iinfo = [];
				for($i=0;$i<count($item_ids);++$i){
					$iid = intval($item_ids[$i]);
					$inum = intval($item_num[$i]);
					if($iid * $inum == 0) continue;
					$items[] = $iid.":".$inum;
					$ilabel[] = $Assets->assetName($iid)."×".$inum;
					$iinfo[] = [
						"id" => $iid,
						"type" => $this->assetTypeId($iid),
						"num" => $inum
					];
				}
				$limit = intval($req->get("limit"));
				$banner = $req->get("banner");
				$message = str_replace("\n","[br]",$req->get("message"));
				$conf_mes = str_replace("\n","[br]",$req->get("conf_mes"));
				$err_mes = str_replace("\n","[br]",$req->get("err_mes"));
				$from = $req->get("from_date")." ".$req->get("from_time").":00";
				$to = $req->get("to_date")." ".$req->get("to_time").":00";
				$second_tag = "";

				$info = [
					"stdid" => $id,
					"title" => $title,
					"info" => $detail,
					"display" => $disp_mode,
					"pay_std_id" => $pay_id,
					"pay_num" => $pay_num,
					"items" => $iinfo,
					"limit" => $limit,
					"banner" => $banner,
					"message" => $message,
					"conf_mes" => $conf_mes,
					"err_mes" => $err_mes,
					"from_date" => $req->get("from_date"),
					"from_time" => $req->get("from_time"),
					"to_date" => $req->get("to_date"),
					"to_time" => $req->get("to_time"),
				];
				if($insert){
					$param["mode"] = 0;
					// 追加
					$ptmt = $sql->prepare("insert into item_trade (std_id,type,category,category_priority,title,info,display,pay_std_id,pay_num,items,`limit`,banner,message,conf_mes,err_mes,effective_from,effective_to) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
					$ptmt->execute([
							$id,
							3,
							$id,
							0,
							$title,
							$detail,
							$disp_mode,
							$pay_id,
							$pay_num,
							implode(",",$items),
							$limit,
							$banner,
							$message,
							$conf_mes,
							$err_mes,
							$from,
							$to,
					]);
					$second_tag="Create";
				}else{
					$param["mode"] = 1;
					// 編集
					$ptmt = $sql->prepare("update item_trade set title=?,info=?,display=?,pay_std_id=?,pay_num=?,items=?,`limit`=?,banner=?,message=?,conf_mes=?,err_mes=?,effective_from=?,effective_to=? where std_id = ?");
					$ptmt->execute([
							$title,
							$detail,
							$disp_mode,
							$pay_id,
							$pay_num,
							implode(",",$items),
							$limit,
							$banner,
							$message,
							$conf_mes,
							$err_mes,
							$from,
							$to,
							$id,
					]);
					$second_tag="Update";
				}
				$dmode = [
					2 => "バナー",
					3 => "コストフレーム",
					4 => "Wコストフレーム"
				];
				$this->get("title.mng_tool.log")->out($TAG,[
					$second_tag,
					[
						"id:".$id,
						"title:".$title,
						"info:",
							explode("[br]",$detail),
						"dispmode:".(isset($dmode[$disp_mode])?$dmode[$disp_mode]:$disp_mode),
						"pay:".$Assets->assetName($pay_id)."×".$pay_num,
						"items:",
							$ilabel,
						"limit:".$limit,
						"banner:".$banner,
						"message:",
							explode("[br]",$message),
						"conf_mes:",
							explode("[br]",$conf_mes),
						"err_mes:",
							explode("[br]",$err_mes),
						"from:".$from,
						"to:".$to,
					],
				]);
				$sql->commit();$sql=null;
				return $this->redirect($this->generateUrl('title_event_trade_list',$param));
			}catch(\Exception $e){
				\Dcs\Toybox::printException($e);
				if($sql != null){
					$sql->rollBack();
					$param["errors"][]=$e->getMessage();
				}
			}
		}else{
			// 表示モード

			if(strcmp($action,"new") == 0){
				$param["mode"] = 0;
				if(!is_numeric($id)){
					$param["errors"][]="数値以外のID[$id]が指定されました。803000～803999内の数値で指定してください";
					return $this->redirect($this->generateUrl('title_event_trade_list',$param));
				}
				$rs = $this->getHs()->select(new Table("item_trade",["std_id"]),new Query(["="=>$id]));
				if(!empty($rs)){
					$param["errors"][]="すでに存在する交換ID[$id]が指定されました。別のIDを指定してください";
					return $this->redirect($this->generateUrl('title_event_trade_list',$param));
				}
				if($id < 803000 || 803999 <= $id){
					$param["errors"][]="範囲外のID[$id]が指定されました。803000～803999の範囲内で指定してください";
					return $this->redirect($this->generateUrl('title_event_trade_list',$param));
				}
				$info = [
					"stdid" => $id,
					"title" => "",
					"info" => "",
					"display" => 0,
					"pay_std_id" => 0,
					"pay_num" => 0,
					"items" => [],
					"limit" => 0,
					"banner" => "",
					"message" => "",
					"conf_mes" => "",
					"err_mes" => "",
					"from_date" => "2010-01-01",
					"from_time" => "00:00",
					"to_date" => "2050-01-01",
					"to_time" => "00:00",
				];
			}else{
				// 編集モード
				$param["mode"] = 1;
				$rs = $this->getHs()->select(
						new Table("item_trade",["title","info","display","pay_std_id","pay_num","items","limit","banner","message","conf_mes","err_mes","effective_from","effective_to"]),
						new Query(["="=>$id])
				);
				if(!empty($rs)){
					$row = $rs[0];
					$info["stdid"] = $id;
					$info["title"] = $row[0];
					$info["info"] = str_replace("[br]","\n",$row[1]);
					$info["display"] = intval($row[2]);
					$info["pay_std_id"] = intval($row[3]);
					$info["pay_std_type"] = $this->assetTypeId($info["pay_std_id"]);
					$info["pay_num"] = intval($row[4]);
					
					$info["items"] = $row[5];
					$info["limit"] = intval($row[6]);
					$info["banner"] = $row[7];
					$info["message"] = str_replace("[br]","\n",$row[8]);
					$info["conf_mes"] = str_replace("[br]","\n",$row[9]);
					$info["err_mes"] = str_replace("[br]","\n",$row[10]);

					$sep = explode(" ",$row[11]);
					$info["from_date"] = $sep[0];
					$time = "00:00";
					if(count($sep)>1){
						$ts = explode(":",$sep[1]);
						if(count($ts)>1)
							$time = $ts[0].":".$ts[1];
					}
					$info["from_time"] = $time;
					
					$sep = explode(" ",$row[12]);
					$info["to_date"] = $sep[0];
					$time = "00:00";
					if(count($sep)>1){
						$ts = explode(":",$sep[1]);
						if(count($ts)>1)
							$time = $ts[0].":".$ts[1];
					}
					$info["to_time"] = $time;
					
					$sep = explode(",",$info["items"]);
					$items = [];
					foreach($sep as $item){
						if(strlen($item) < 3) continue;
						$in = explode(":",$item);
						if(count($in) != 2) continue;
						$items[] = [
							"id" => intval($in[0]),
							"type" => $this->assetTypeId($in[0]),
							"num" => intval($in[1])
						];
					}
					$info["items"] = $items;
					$ret[] = $row;
				}else{
					$param["errors"][]="存在しないID[$id]が指定されました。";
					return $this->redirect($this->generateUrl('title_event_trade_list',$param));
				}
			}
		}
		$param["info"] = $info;
		$param["displays"] = [
			2 => "バナー",
			3 => "コストフレーム",
			4 => "Wコストフレーム"
		];
		$param["stacks"] = $this->getStackList();
		$param["assets_selector"] = $this->get("title.mng_tool.service.assets_selector")->create();
		return $this->render('TitleManagementToolBundle:event:trade_edit.html.twig', $param);
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
	protected function getStackList(){
		$rs = $this->getHs()->select(new Table("item_data",["std_id","name"]),new Query([">"=>0],-1));
		$ret = [];
		foreach($rs as $row){
			$ret[intval($row[0])] = $row[1];
		}
		return $ret;
	}
	public function tradeListAction(){
		$sortColumn = $this->getRequest()->get('sort_colmn');
		$sortOrder = $this->getRequest()->get('sort_order');
		$param['sort_colmn'] = is_null($sortColumn) ? EventTradeSort::STDID : $sortColumn;
		$param['sort_order'] = is_null($sortOrder) ? Sort::ORDER_ASC : $sortOrder;
		// ページャ処理用
		$offset = $this->get('request')->get('offset');
		$param['offset'] = is_null($offset) ? 0 : $offset;
		$param['limit'] = Display::DISPLAY_COUNT_USER_LIST;
	
		$result = $this->selectTradeList($param);
	
		$pathParams = [
		'sort_colmn' => $param['sort_colmn'],
		'sort_order' => $param['sort_order']
		];
	
		// ページング
		$pager = $this->get('gaia.mng_tool.model.pager');
		$pager->setInc(Display::DISPLAY_COUNT_USER_LIST);
		$pager->setPath('title_dungeon_list', $pathParams);
		$pager->setParameters($this->getAllTradeCount($param));
		$displayData = $pager->getDisplayData($result);
	
		// 出力パラメータ設定
		$param['pager'] = $pager->getParameters();
		$param['datalist'] = $displayData;
		
		$param["errors"] = $this->getRequest()->get('errors');
	
		return $this->render('TitleManagementToolBundle:event:trade_list.html.twig', $param);
	}
	protected function getAllTradeCount($param){
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare("select count(std_id) from item_trade where type = 3");
		$ptmt->execute([]);
		$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
		return intval($rs[0][0]);
	}
	protected function selectTradeList($param){
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare(
				"select std_id,title,pay_std_id,pay_num,items,`limit`,effective_from,effective_to from item_trade where type = 3 ".$this->makeTradeSortSql($param).$this->makeLimitSql($param));
		$ptmt->execute([]);
		$ret = [];
		$Assets = $this->get("title.mng_tool.service.assets_selector");
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$row["pay_std_id"] = $Assets->assetName($row["pay_std_id"]);
			$sep = explode(",",$row["items"]);
			$items = [];
			foreach($sep as $item){
				if(strlen($item) < 3) continue;
				$in = explode(":",$item);
				if(count($in) != 2) continue;
				$items[] = $Assets->assetName($in[0])."×".intval($in[1]);
			}
			$row["items"] = $items;
			$ret[] = $row;
		}
		return $ret;
	}
	protected function makeTradeSortSql($param){
		$sort = intval($param['sort_colmn']);
		$ord = intval($param['sort_order']);
		$ret = " order by ";
		switch($sort){
			case EventTradeSort::TITLE:
				$ret .= "title ";
				break;
			case EventTradeSort::PAY:
				$ret .= "pay_std_id ";
				break;
			case EventTradeSort::LIMIT:
				$ret .= "`limit` ";
				break;
			case EventTradeSort::FROM:
				$ret .= "effective_from ";
				break;
			case EventTradeSort::TO:
				$ret .= "effective_to ";
				break;
			case EventTradeSort::STDID:
			default:
				$ret .= "std_id ";
				break;
		}
		$ret .= $ord == Sort::ORDER_DESC?" desc ":" asc ";
		return $ret;
	}
	
///////////////////////////////////
// ドロップ系
	/**
	 * エディットモードの表示
	 */
	public function dropEditAction(){
		$req = $this->getRequest();
		$id = intval($req->get('id'));
		$action = $req->get('action');
		$insert = $id < 0;
		$insert = intval($req->get('mode')) != 1;

		$param = [
			"sort_colmn" => $this->getRequest()->get('sort_colmn'),
			"sort_order" => $this->getRequest()->get('sort_order'),
			"offset" => $this->getRequest()->get('offset'),
		];

		$TAG = "EventDropItem";
		
		$info = [];
		if(strcmp($action,"delete") == 0){
			// 削除モード
			$sql = $this->getSql();
			try{
				$this->useTransaction();
				$ptmt = $sql->prepare("delete from item_drop_event where id = ?");
				$ptmt->execute([$id]);
				$this->get("title.mng_tool.log")->out($TAG,["Delete",["id: $id"]]);
				$sql->commit();$sql=null;
			}catch(\Exception $e){
				\Dcs\Toybox::printException($e);
				if($sql != null)
					$sql->rollBack();
			}
			return $this->redirect($this->generateUrl('title_event_drop_list',$param));
		}else if(strcmp($action,"update") == 0){
			// 更新モード
			/*
			$param["mode"] = 1;
			*/
			$sql = $this->getSql();
			$dlimit=$this->getDunLimitData();
			$rlimit=$this->getRankLimitData();
			$elimit=$this->getEnemyLimitData();
			$tlist=$this->getTboxData();
			$glist=$this->getGachaData();
			try{
				$this->useTransaction();
				$info["did"] = intval($req->get("dungeon_id"));
				$info["rank"] = intval($req->get("enemy_rank"));
				$info["eid"] = intval($req->get("enemy_id"));
				$info["tbox"] = intval($req->get("tbox"));
				$info["rate"] = $req->get("rate")+0;
				$info["gid"] = intval($req->get("gacha_id"));
				$info["from_date"] = $req->get("from_date");
				$info["from_time"] = $req->get("from_time");
				$info["to_date"] = $req->get("to_date");
				$info["to_time"] = $req->get("to_time");
				
				$from = $info["from_date"]." ".$info["from_time"].":00";
				$to = $info["to_date"]." ".$info["to_time"].":00";
				$second_tag = "";
				if($insert){
					// 追加
					$param["mode"] = 0;
					$ptmt = $sql->prepare("insert into item_drop_event (dungeon_id,enemy_rank,enemy_id,tbox,rate,gacha_id,effective_from,effective_to) values(?,?,?,?,?,?,?,?)");
					$ptmt->execute([
							$info["did"],
							$info["rank"],
							$info["eid"],
							$info["tbox"],
							$info["rate"],
							$info["gid"],
							$from,
							$to
					]);
					$id = intval($sql->lastInsertId());
				}else{
					// 編集
					$param["mode"] = 1;
					$info["id"] = $id;
					$ptmt = $sql->prepare("update item_drop_event set dungeon_id=?,enemy_rank=?,enemy_id=?,tbox=?,rate=?,gacha_id=?,effective_from=?,effective_to=? where id = ?");
					$ptmt->execute([
							$info["did"],
							$info["rank"],
							$info["eid"],
							$info["tbox"],
							$info["rate"],
							$info["gid"],
							$from,
							$to,
							$id
					]);
					$second_tag="Update";
				}

				$this->get("title.mng_tool.log")->out($TAG,[
					$second_tag,
					[
						"id:".$id,
						"limit:",
						[
							"dungeon:".(isset($dlimit[$info["did"]])?$dlimit[$info["did"]]:$info["did"]),
							"rank:".(isset($rlimit[$info["rank"]])?$rlimit[$info["rank"]]:$info["rank"]),
							"enemy:".(isset($elimit[$info["eid"]])?$elimit[$info["eid"]]:$info["eid"]),
						],
						"tbox:".(isset($tlist[$info["tbox"]])?$tlist[$info["tbox"]]:$info["tbox"]),
						"rate:".$info["rate"]."%".
						"gacha:".(isset($glist[$info["gid"]])?$glist[$info["gid"]]:$info["gid"]),
						"from:".$from,
						"to:".$to
					],
				]);
				$sql->commit();$sql=null;
				return $this->redirect($this->generateUrl('title_event_drop_list',$param));
			}catch(\Exception $e){
				\Dcs\Toybox::printException($e);
				if($sql != null){
					$sql->rollBack();
					$param["errors"][]=$e->getMessage();
				}
			}
		}else{
			$done = false;
			// 表示モード
			if($id >= 0){
				// 編集モード
				$param["mode"] = 1;
				$rs = $this->getHs()->select(
						new Table("item_drop_event",["dungeon_id","enemy_rank","enemy_id","tbox","rate","gacha_id","effective_from","effective_to"]),
						new Query(["="=>$id])
				);
				if(!empty($rs)){
					$row = $rs[0];
					$info["id"] = $id;
					$info["did"] = intval($row[0]);
					$info["rank"] = intval($row[1]);
					$info["eid"] = intval($row[2]);
					$info["tbox"] = intval($row[3]);
					$info["rate"] = $row[4]+0;
					$info["gid"] = intval($row[5]);

					$sep = explode(" ",$row[6]);
					$info["from_date"] = $sep[0];
					$time = "00:00";
					if(count($sep)>1){
						$ts = explode(":",$sep[1]);
						if(count($ts)>1)
							$time = $ts[0].":".$ts[1];
					}
					$info["from_time"] = $time;
					
					$sep = explode(" ",$row[7]);
					$info["to_date"] = $sep[0];
					$time = "00:00";
					if(count($sep)>1){
						$ts = explode(":",$sep[1]);
						if(count($ts)>1)
							$time = $ts[0].":".$ts[1];
					}
					$info["to_time"] = $time;
					$done = true;
				}
			}
			if(!$done){
				// 追加モード
				$param["mode"] = 0;
				$info["id"] = 1;
				$info["did"] = 0;
				$info["rank"] = 100;
				$info["eid"] = 0;
				$info["tbox"] = 0;
				$info["rate"] = 0;
				$info["gid"] = 0;
				$info["from_date"] = "2010-01-01";
				$info["from_time"] = "00:00";
				$info["to_date"] = "2050-01-01";
				$info["to_time"] = "00:00";
			}
		}
		$param["info"] = $info;

		$param["duns"]=$this->getDunLimitData();
		$param["ranks"]=$this->getRankLimitData();
		$param["eids"]=$this->getEnemyLimitData();
		$param["tboxes"]=$this->getTboxData();
		$param["gachas"]=$this->getGachaData();
		
		return $this->render('TitleManagementToolBundle:event:drop_edit.html.twig', $param);
	}
	public function dropListAction(){
		$sortColumn = $this->getRequest()->get('sort_colmn');
		$sortOrder = $this->getRequest()->get('sort_order');
		$param['sort_colmn'] = is_null($sortColumn) ? EventDropSort::ID : $sortColumn;
		$param['sort_order'] = is_null($sortOrder) ? Sort::ORDER_ASC : $sortOrder;
		// ページャ処理用
		$offset = $this->get('request')->get('offset');
		$param['offset'] = is_null($offset) ? 0 : $offset;
		$param['limit'] = Display::DISPLAY_COUNT_USER_LIST;
	
		$result = $this->selectDropList($param);
	
		$pathParams = [
		'sort_colmn' => $param['sort_colmn'],
		'sort_order' => $param['sort_order']
		];
	
		// ページング
		$pager = $this->get('gaia.mng_tool.model.pager');
		$pager->setInc(Display::DISPLAY_COUNT_USER_LIST);
		$pager->setPath('title_dungeon_list', $pathParams);
		$pager->setParameters($this->getAllDropCount($param));
		$displayData = $pager->getDisplayData($result);
	
		// 出力パラメータ設定
		$param['pager'] = $pager->getParameters();
		$param['datalist'] = $displayData;
	
		return $this->render('TitleManagementToolBundle:event:drop_list.html.twig', $param);
	}
	protected function getAllDropCount($param){
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare("select count(dungeon_id) from item_drop_event");
		$ptmt->execute([]);
		$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
		return intval($rs[0][0]);
	}
	protected function selectDropList($param){
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare(
				"select id, dungeon_id, enemy_rank,enemy_id, tbox, rate, gacha_id, effective_from, effective_to from item_drop_event ".$this->makeDropSortSql($param).$this->makeLimitSql($param));
		$ptmt->execute([]);
		$ret = [];
		$dlimit = $this->getDunLimitData();
		$rlimit = $this->getRankLimitData();
		$elimit = $this->getEnemyLimitData();
		$gdata = $this->getGachaData();
		$tdata = $this->getTboxData();
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$did = intval($row["dungeon_id"]);
			if(isset($dlimit[$did]))
				$row["label_did"] = $dlimit[$did];
			else
				$row["label_did"] = $did;
			
			$rank = intval($row["enemy_rank"]);
			if(isset($rlimit[$rank]))
				$row["label_rank"] = $rlimit[$rank];
			else
				$row["label_rank"] = $rank;

			$eid = intval($row["enemy_id"]);
			if(isset($elimit[$eid]))
				$row["label_eid"] = $elimit[$eid];
			else
				$row["label_eid"] = $eid;

			$gid = intval($row["gacha_id"]);
			if(isset($gdata[$gid]))
				$row["label_gid"] = $gdata[$gid];
			else
				$row["label_gid"] = $gid;

			$tbox = intval($row["tbox"]);
			if(isset($tdata[$tbox]))
				$row["label_tbox"] = $tdata[$tbox];
			else
				$row["label_tbox"] = $tbox;
			$ret[] = $row;
		}
		return $ret;
	}
	protected function getRankLimitData(){
		return [
			100 => "全ランク",
			0 => "ノーマル",
			1 => "リーダー",
			2 => "ボス",
			3 => "レア"
		];
	}
	/**
	 * @return array(std_id => name, ...)
	 */
	protected static $DunLimitData = null;
	protected function getDunLimitData(){
		if(self::$DunLimitData != null)
			return self::$DunLimitData;
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare("select 0 as std_id, '全ダンジョン' as name union select 1000000+id*10000 as std_id,concat('w:',title) as name from quest_world union select 1000000+10000*world_id+100*id as std_id, concat('a:',title) as name from quest_area union select 1000000+10000*world_id+100*area_id+id as std_id, concat('d:',title) as name from quest_dungeon");
		$ptmt->execute([]);
		$ret = [];
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$ret[intval($row["std_id"])] = $row["name"];
		}
		self::$DunLimitData = $ret;
		return $ret;
	}
	/**
	 * @return array(std_id => name, ...)
	 */
	protected static $GachaData = null;
	protected function getGachaData(){
		if(self::$GachaData != null)
			return self::$GachaData;
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare("select gacha_id as id, gacha_name as name from GAIA_MST_GACHA");
		$ptmt->execute([]);
		$ret = [];
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$ret[intval($row["id"])] = $row["name"];
		}
		self::$GachaData = $ret;
		return $ret;
	}
	/**
	 * @return array(std_id => name, ...)
	 */
	protected static $TboxData = null;
	protected function getTboxData(){
		if(self::$TboxData != null)
			return self::$TboxData;
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare("select rt.effect_id as id,ed.name as name from rarity_tbox as rt left join effect_data as ed on rt.effect_id = ed.id");
		$ptmt->execute([]);
		$ret = [];
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$ret[intval($row["id"])] = $row["name"];
		}
		self::$TboxData = $ret;
		return $ret;
	}
	/**
	 * @return array(std_id => name, ...)
	 */
	protected static $EnemyLimitData = null;
	protected function getEnemyLimitData(){
		if(self::$EnemyLimitData != null)
			return self::$EnemyLimitData;
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare("select 0 as id, '全敵' as name union select id, name from enemy where id != 0");
		$ptmt->execute([]);
		$ret = [];
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$ret[intval($row["id"])] = $row["name"];
		}
		self::$EnemyLimitData = $ret;
		return $ret;
	}
	protected function makeDropSortSql($param){
		$sort = intval($param['sort_colmn']);
		$ord = intval($param['sort_order']);
		$ret = " order by ";
		switch($sort){
			case EventDropSort::RANK:
				$ret .= "enemy_rank ";
				break;
			case EventDropSort::EID:
				$ret .= "enemy_id ";
				break;
			case EventDropSort::TBOX:
				$ret .= "tbox ";
				break;
			case EventDropSort::RATE:
				$ret .= "rate ";
				break;
			case EventDropSort::GID:
				$ret .= "gacha_id ";
				break;
			case EventDropSort::FROM:
				$ret .= "effective_from ";
				break;
			case EventDropSort::TO:
				$ret .= "effective_to ";
				break;
			case EventDropSort::DID:
				$ret .= "dungeon_id ";
				break;
			case EventDropSort::ID:
			default:
				$ret .= "id ";
				break;
		}
		$ret .= $ord == Sort::ORDER_DESC?" desc ":" asc ";
		return $ret;
	}
	
	
	
	
	
	
	
	
	
//////////////////////////////////////////////	
// 	イベントダンジョン系
	/**
	 * エディットモードの表示
	 */
	public function dungeonEditAction(){
		$req = $this->getRequest();
		$std_id = intval($req->get('std_id'));
		$action = $req->get('action');

		$param = [
			"sort_colmn" => $this->getRequest()->get('sort_colmn'),
			"sort_order" => $this->getRequest()->get('sort_order'),
			"offset" => $this->getRequest()->get('offset'),
		];

		$TAG = "EventDungeon";
		
		$info = [];
		if(strcmp($action,"update") == 0){
			// 更新モード
			$sql = $this->getSql();
			try{
				$this->useTransaction();
				$std_id = intval($req->get('dungeon_id'));
				$name = $req->get("dungeon_name");
				$wid = intval($std_id / 10000) % 100;
				$aid = intval($std_id / 100) % 100;
				$did = $std_id % 100;
				$fdate = $req->get("dungeon_from_date");
				$ftime = $req->get("dungeon_from_time");
				$tdate = $req->get("dungeon_to_date");
				$ttime = $req->get("dungeon_to_time");
				$ow = $req->get("dungeon_open_week");
				$from = $fdate." ".$ftime.":00";
				$to = $tdate." ".$ttime.":00";
				$open = [];
				$open_label = [];
				$week = ["日","月","火","水","木","金","土"];
				foreach($ow as $wd){
					if(!is_numeric($wd)) continue;
					$wd = intval($wd);
					if(!isset($week[$wd])) continue;
					if(in_array($wd,$open)) continue;
					$open[] = $wd;
					$open_label[] = $week[$wd];
				}
				$info["id"] = $std_id;
				$info["name"] = $name;
				
				$info["from_date"] = $fdate;
				$info["from_time"] = $ftime;
				$info["to_date"] = $tdate;
				$info["to_time"] = $ttime;
				$info["open_week"] = $open;
			
				// 編集
				$ptmt = $sql->prepare("update quest_dungeon set effective_from=?,effective_to=?,open_week=? where world_id = ? and area_id = ? and id = ?");
				$ptmt->execute([
						$from,
						$to,
						implode(",",$open),
						$wid,
						$aid,
						$did,
				]);
				
				$this->get("title.mng_tool.log")->out($TAG,[
						"Update",
						[
							"std_id:".$std_id,
							"name:".$name,
							"from:".$from,
							"to:".$to,
							"wday:".(empty($open_label)?"毎日":implode(",",$open_label)),
						],
				]);
				$sql->commit();$sql=null;
				return $this->redirect($this->generateUrl('title_dungeon_list',$param));
			}catch(\Exception $e){
				\Dcs\Toybox::printException($e);
				if($sql != null){
					$sql->rollBack();
					$param["errors"][]=$e->getMessage();
				}
			}
		}else{
			"select 1000000+world_id*10000+area_id*100+id as std_id,title, effective_from, effective_to, open_week from quest_dungeon where world_id = 98";
			$wid = 98;
			$aid = intval($std_id/100) % 100;
			$did = intval($std_id) % 100;
			// 表示モード
			$rs = $this->getHs()->select(
					new Table("quest_dungeon",["world_id","area_id","id","title","effective_from","effective_to","open_week"]),
					new Query(["="=>[$wid,$aid,$did]])
			);
			if(!empty($rs)){
				$row = $rs[0];
				$info["id"] = $std_id;
				$info["name"] = $row[3];
				
				$sep = explode(" ",$row[4]);
				$info["from_date"] = $sep[0];
				$time = "00:00";
				if(count($sep)>1){
					$ts = explode(":",$sep[1]);
					if(count($ts)>1)
						$time = $ts[0].":".$ts[1];
				}
				$info["from_time"] = $time;

				$sep = explode(" ",$row[5]);
				$info["to_date"] = $sep[0];
				$time = "00:00";
				if(count($sep)>1){
					$ts = explode(":",$sep[1]);
					if(count($ts)>1)
						$time = $ts[0].":".$ts[1];
				}
				$info["to_time"] = $time;
				$sep = explode(",",$row[6]);
				$ow = [];
				for($i=0,$len=count($sep);$i<$len;++$i){
					if(is_numeric($sep[$i])){
						$ow[] = intval($sep[$i]);
					}
				}
				$info["open_week"] = $ow;
			}
		}
		$param["dungeon"] = $info;
		
		$param["week"]=["日","月","火","水","木","金","土"];
		return $this->render('TitleManagementToolBundle:event:dungeon_edit.html.twig', $param);
	}
	/**
	 * ダンジョンリスト
	 */
	public function dungeonListAction(){
		$sortColumn = $this->getRequest()->get('sort_colmn');
		$sortOrder = $this->getRequest()->get('sort_order');
		$param['sort_colmn'] = is_null($sortColumn) ? DungeonSort::SORT_ID : $sortColumn;
		$param['sort_order'] = is_null($sortOrder) ? Sort::ORDER_DESC : $sortOrder;
		// ページャ処理用
		$offset = $this->get('request')->get('offset');
		$param['offset'] = is_null($offset) ? 0 : $offset;
		$param['limit'] = Display::DISPLAY_COUNT_USER_LIST;
		
		$result = $this->selectDungeonList($param);
		
		$pathParams = [
			'sort_colmn' => $param['sort_colmn'],
			'sort_order' => $param['sort_order']
		];
		
		// ページング
		$pager = $this->get('gaia.mng_tool.model.pager');
		$pager->setInc(Display::DISPLAY_COUNT_USER_LIST);
		$pager->setPath('title_dungeon_list', $pathParams);
		$pager->setParameters($this->getAllDungeonCount($param));
		$displayData = $pager->getDisplayData($result);
		
		// 出力パラメータ設定
		$param['pager'] = $pager->getParameters();
		$param['datalist'] = $displayData;
		
		return $this->render('TitleManagementToolBundle:event:dungeon_list.html.twig', $param);
	}
	protected function getAllDungeonCount($param){
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare("select count(id) from quest_dungeon where world_id = 98 ");
		$ptmt->execute([]);
		$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
		return intval($rs[0][0]);
	}
	protected function selectDungeonList($param){
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare(
				"select 1000000+world_id*10000+area_id*100+id as std_id,title, effective_from, effective_to, open_week from quest_dungeon where world_id = 98 ".$this->makeDunSortSql($param).$this->makeLimitSql($param));
		$ptmt->execute([]);
		$ret = [];
		$wday = ["日","月","火","水","木","金","土"];
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$sep = explode(",",$row["open_week"]);
			$list = [];
			foreach($sep as $ow){
				if(!is_numeric($ow))continue;
				$list[intval($ow)] = true;
			}
			$sep=null;
			foreach($list as $ow=>$bool){
				if(!isset($wday[$ow])) continue;
				if($sep != null)
					$sep .= ",";
				$sep .= $wday[$ow];
			}
			if($sep == null)
				$sep = "毎日";
			$row["open_week"] = $sep;
			
			$ret[] = $row;
		}
		return $ret;
	}
	protected function makeDunSortSql($param){
		$sort = intval($param['sort_colmn']);
		$ord = intval($param['sort_order']);
		$ret = " order by ";
		switch($sort){
			case DungeonSort::SORT_FROM:
				$ret .= "effective_from ";
				break;
			case DungeonSort::SORT_TO:
				$ret .= "effective_to ";
				break;
			case DungeonSort::SORT_NAME:
				$ret .= "title ";
				break;
			case DungeonSort::SORT_ID:
			default:
				$ret .= "std_id ";
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
}
