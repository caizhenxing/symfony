<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Gaia\Bundle\ManagementToolBundle\Constant\Sort;
use Gaia\Bundle\ManagementToolBundle\Constant\Display;
use Title\GaiaBundle\ManagementToolBundle\Util\Controller;
use Symfony\Component\HttpFoundation\Response;
use Gaia\Bundle\ManagementToolBundle\Util\SessionUtil;
use Gaia\Bundle\ManagementToolBundle\Constant\SessionKey;
use Title\GaiaBundle\ManagementToolBundle\Constant\EditLogSort;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

/**
 * 編集ログ
 * @author Takeda_Yoshihiro
 *
 */
class EditLogController extends Controller{
	/**
	 * メッセージ一覧の表示
	 */
	public function listAction(){

		$sortColumn = $this->getRequest()->get('sort_colmn');
		$sortOrder = $this->getRequest()->get('sort_order');
		$param['sort_colmn'] = is_null($sortColumn) ? EditLogSort::ID : $sortColumn;
		$param['sort_order'] = is_null($sortOrder) ? Sort::ORDER_DESC : $sortOrder;
		// ページャ処理用
		$offset = $this->get('request')->get('offset');
		$param['offset'] = is_null($offset) ? 0 : $offset;
		$param['limit'] = Display::DISPLAY_COUNT_USER_LIST;

		// フィルタ
		$param["filt_id"] = $this->getRequest()->get('filt_id');
		$param["filt_name"] = $this->getRequest()->get('filt_name');
		$param["filt_tag"] = $this->getRequest()->get('filt_tag');
		$param["filt_mes"] = $this->getRequest()->get('filt_mes');
		$param["filt_date"] = $this->getRequest()->get('filt_date');
		
		$param["filt_id"] = $param['filt_id']==null?"":$param["filt_id"];
		$param["filt_name"] = $param['filt_name']==null?"":$param["filt_name"];
		$param["filt_tag"] = $param['filt_tag']==null?"":$param["filt_tag"];
		$param["filt_mes"] = $param['filt_mes']==null?"":$param["filt_mes"];
		$param["filt_date"] = $param['filt_date']==null?"":$param["filt_date"];
		
		$result = $this->selectList($param);
		
		$pathParams = [
			'sort_colmn' => $param['sort_colmn'],
			'sort_order' => $param['sort_order']
		];
		foreach(["filt_id","filt_name","filt_tag","filt_mes","filt_date"] as $key){
			if($param[$key] != null)
				$pathParams[$key] = $param[$key];
		}
		
		// ページング
		$pager = $this->get('gaia.mng_tool.model.pager');
		$pager->setInc(Display::DISPLAY_COUNT_USER_LIST);
		$pager->setPath('title_edit_log', $pathParams);
		$pager->setParameters($this->getAllDataCount($param));
		$displayData = $pager->getDisplayData($result);
		
		// 出力パラメータ設定
		$param['pager'] = $pager->getParameters();
		$param['mes_info_list'] = $displayData;
		return $this->render('TitleManagementToolBundle:util:edit_log_list.html.twig', $param);
	}
	protected function selectList($param){
		$flter = $this->makeFilter($param);
		$ptmt = $this->getDoctrine()->getEntityManager()->getConnection()->prepare(
				"select l.id as id, l.admin_user_id as admin_user_id, u.login_id as name, l.tag as tag, l.mes as mes, l.date as date from log_management_tool as l left join GAIA_MNT_USER_ADMIN as u on l.admin_user_id = u.admin_user_id ".$flter[0].$this->makeSortSql($param).$this->makeLimitSql($param));
		$ptmt->execute($flter[1]);
		
		$ret = [];
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$row["mes"] = $row["mes"];
			$ret[] = $row;
		}
		return $ret;
	}
	protected function getAllDataCount($param){
		$flter = $this->makeFilter($param);
		$ptmt = $this->getDoctrine()->getEntityManager()->getConnection()->prepare("select count(id) from log_management_tool".$flter[0]);
		$ptmt->execute($flter[1]);
		$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
		return intval($rs[0][0]);
	}
	protected function makeFilter($param){
		$ret = null;
		$args = [];
		if(is_numeric($param["filt_id"])){
			$ret = "where id=? ";
			$args[] = intval($param["filt_id"]);
		}
		if($param["filt_name"] != null){
			if($ret == null)
				$ret = "where name = ? ";
			else
				$ret .= "and name = ? ";
			$args[] = $param["filt_name"];
		}
		if($param["filt_tag"] != null){
			if($ret == null)
				$ret = "where tag = ? ";
			else
				$ret .= "and tag = ? ";
			$args[] = $param["filt_tag"];
		}
		if($param["filt_mes"] != null){
			if($ret == null)
				$ret = "where mes like ? ";
			else
				$ret .= "and mes like ? ";
			$args[] = "%".$param["filt_mes"]."%";
		}
		if($param["filt_date"] != null){
			if($ret == null)
				$ret = "where date like ? ";
			else
				$ret .= "and date like ? ";
			$args[] = "%".$param["filt_date"]."%";
		}
		return [$ret,$args];
	}
	protected function makeSortSql($param){
		$sort = intval($param['sort_colmn']);
		$ord = intval($param['sort_order']);
		$ret = " order by ";
		switch($sort){
			case EditLogSort::USER:
				$ret .= "`admin_user_id` ";
				break;
			case EditLogSort::TAG:
				$ret .= "tag ";
				break;
			case EditLogSort::MES:
				$ret .= "mes ";
				break;
			case EditLogSort::DATE:
				$ret .= "date ";
				break;
			case EditLogSort::ID:
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
}