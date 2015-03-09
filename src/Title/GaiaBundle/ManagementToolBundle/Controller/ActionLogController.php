<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Gaia\Bundle\ManagementToolBundle\Constant\Sort;
use Gaia\Bundle\ManagementToolBundle\Constant\Display;
use Title\GaiaBundle\ManagementToolBundle\Util\Controller;
use Symfony\Component\HttpFoundation\Response;
use Gaia\Bundle\ManagementToolBundle\Util\SessionUtil;
use Gaia\Bundle\ManagementToolBundle\Constant\SessionKey;
use Title\GaiaBundle\ManagementToolBundle\Constant\ActionLogSort;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

/**
 * 行動ログ
 * @author Takeda_Yoshihiro
 *
 */
class ActionLogController extends Controller{
	/**
	 * メッセージ一覧の表示
	 */
	public function listAction(){
		$uid = intval($this->getRequest()->get('user_id'));
		$sortColumn = $this->getRequest()->get('sort_colmn');
		$sortOrder = $this->getRequest()->get('sort_order');
		$param['sort_colmn'] = is_null($sortColumn) ? ActionLogSort::ID : $sortColumn;
		$param['sort_order'] = is_null($sortOrder) ? Sort::ORDER_DESC : $sortOrder;
		// ページャ処理用
		$offset = $this->get('request')->get('offset');
		$param['offset'] = is_null($offset) ? 0 : $offset;
		$param['limit'] = Display::DISPLAY_COUNT_USER_LIST;
		$param["user_id"] = $uid;

		// フィルタ
		$param["filt_id"] = $this->getRequest()->get('filt_id');
		$param["filt_tag0"] = $this->getRequest()->get('filt_tag0');
		$param["filt_tag1"] = $this->getRequest()->get('filt_tag1');
		$param["filt_tag2"] = $this->getRequest()->get('filt_tag2');
		$param["filt_tag3"] = $this->getRequest()->get('filt_tag3');
		$param["filt_log"] = $this->getRequest()->get('filt_log');
		$param["filt_date"] = $this->getRequest()->get('filt_date');
		
		$param["filt_id"] = $param['filt_id']==null?"":$param["filt_id"];
		$param["filt_tag0"] = $param['filt_tag0']==null?"":$param["filt_tag0"];
		$param["filt_tag1"] = $param['filt_tag1']==null?"":$param["filt_tag1"];
		$param["filt_tag2"] = $param['filt_tag2']==null?"":$param["filt_tag2"];
		$param["filt_tag3"] = $param['filt_tag3']==null?"":$param["filt_tag3"];
		$param["filt_log"] = $param['filt_log']==null?"":$param["filt_log"];
		$param["filt_date"] = $param['filt_date']==null?"":$param["filt_date"];
		
		$result = $this->selectList($param);
		
		$pathParams = [
			'sort_colmn' => $param['sort_colmn'],
			'sort_order' => $param['sort_order'],
			'user_id' => $uid
		];
		foreach(["filt_id","filt_tag0","filt_tag1","filt_tag2","filt_tag3","filt_log","filt_date"] as $key){
			if($param[$key] != null)
				$pathParams[$key] = $param[$key];
		}
		
		// ページング
		$pager = $this->get('gaia.mng_tool.model.pager');
		$pager->setInc(Display::DISPLAY_COUNT_USER_LIST);
		$pager->setPath('title_action_log_list', $pathParams);
		$pager->setParameters($this->getAllDataCount($param));
		$displayData = $pager->getDisplayData($result);
		
		// 出力パラメータ設定
		$param['pager'] = $pager->getParameters();
		$param['mes_info_list'] = $displayData;
		$param['flt_select'] = $this->makeSelectList($param);
		$param['tab'] = ['path_param' => ['user_id' => $uid]];
		return $this->render('TitleManagementToolBundle:user:action_log_list.html.twig', $param);
	}
	protected function selectList($param){
		$sql = $this->get("doctrine")->getConnection("log");
		$flter = $this->makeFilter($param);
		$ptmt = $sql->prepare(
				"select id, tag0,tag1,tag2,tag3, tag4 as error, log, create_date as date from action where uid = ? ".$flter[0].$this->makeSortSql($param).$this->makeLimitSql($param));
		$ptmt->execute($flter[1]);
		
		$ret = [];
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			if(preg_match('/[^0-9+-.]+/',$row["log"])){
				$log = json_decode($row["log"],true);
				if(json_last_error() == JSON_ERROR_NONE){
					$log = $this->ary2str($log,"");
				}else{
					$log = $row["log"];
				}
				$row["log"] = $log;
			}
			$ret[] = $row;
		}
		return $ret;
	}
	protected function ary2str($obj,$inc){
		if(is_array($obj)){
			$ret = "";
			foreach($obj as $key => $value){
				if(is_array($value)){
					$ret .= $inc.$key.":\n".$this->ary2str($value,$inc."  ");
				}elseif(is_string($value)){
					$sep = explode("\n",$value);
					if(count($sep) > 1){
						$ret .= $inc.$key.":\n";
						for($i=0,$len=count($sep);$i<$len;++$i){
							$ret .= $inc."  ".$sep[$i]."\n";
						}
					}else{
						$ret .= $inc.$key.": ".$value."\n";
					}
				}else{
					$ret .= $inc.$key.": ".$value."\n";
				}
			}
			return $ret;
		}
		return $inc.$obj;
	}
	protected function getAllDataCount($param){
		$sql = $this->get("doctrine")->getConnection("log");
		$flter = $this->makeFilter($param);
		$ptmt = $sql->prepare("select count(id) from action where uid = ? ".$flter[0]);
		$ptmt->execute($flter[1]);
		$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
		return intval($rs[0][0]);
	}
	protected function makeFilter($param){
		$ret = null;
		$args = [$param["user_id"]];
		if(is_numeric($param["filt_id"])){
			$ret = "and id=? ";
			$args[] = intval($param["filt_id"]);
		}
		if($param["filt_tag0"] != null){
			$ret .= "and tag0 = ? ";
			$args[] = $param["filt_tag0"];
		}
		if($param["filt_tag1"] != null){
			$ret .= "and tag1 = ? ";
			$args[] = $param["filt_tag1"];
		}
		if($param["filt_tag2"] != null){
			$ret .= "and tag2 = ? ";
			$args[] = $param["filt_tag2"];
		}
		if($param["filt_tag3"] != null){
			$ret .= "and tag3 = ? ";
			$args[] = $param["filt_tag3"];
		}
		if($param["filt_log"] != null){
			$ret .= "and log like ? ";
			$args[] = "%".$param["filt_log"]."%";
		}
		if($param["filt_date"] != null){
			$ret .= "and create_date like ? ";
			$args[] = "%".$param["filt_date"]."%";
		}
		return [$ret,$args];
	}
	protected function makeSortSql($param){
		$sort = intval($param['sort_colmn']);
		$ord = intval($param['sort_order']);
		$ret = " order by ";
		switch($sort){
			case ActionLogSort::TAG0:
				$ret .= "tag0 ";
				break;
			case ActionLogSort::TAG1:
				$ret .= "tag1 ";
				break;
			case ActionLogSort::TAG2:
				$ret .= "tag2 ";
				break;
			case ActionLogSort::TAG3:
				$ret .= "tag3 ";
				break;
			case ActionLogSort::ERROR:
				$ret .= "tag4 ";
				break;
			case ActionLogSort::LOG:
				$ret .= "log ";
				break;
			case ActionLogSort::DATE:
				$ret .= "create_date ";
				break;
			case ActionLogSort::ID:
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
	protected function makeSelectList($param){
		$keys = ["tag0","tag1","tag2","tag3"];
		$ret = [];
		$sql = $this->get("doctrine")->getConnection("log");
		error_log("uid is ".$param["user_id"]);
		foreach($keys as $key){
			$ptmt = $sql->prepare("select $key from action where uid = ? group by $key");
			$ptmt->execute([$param["user_id"]]);
			$list = [""];
			
			while($row = $ptmt->fetch(\PDO::FETCH_NUM)){
				$row = $row[0];
				if($row == null) continue;
				if(strlen($row) < 1) continue;
				$list[] = $row;
			}
			$ret[$key] = $list;
		}
		return $ret;
	}
}