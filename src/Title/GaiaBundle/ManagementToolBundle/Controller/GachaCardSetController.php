<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Title\GaiaBundle\ManagementToolBundle\Util\Controller;
use Gaia\Bundle\ManagementToolBundle\Constant\Sort;
use Gaia\Bundle\ManagementToolBundle\Constant\Display;
use Gaia\Bundle\ManagementToolBundle\Constant\SessionKey;
use Gaia\Bundle\ManagementToolBundle\Util\StringUtil;
use Gaia\Bundle\ManagementToolBundle\Util\SessionUtil;
use Gaia\Bundle\ManagementToolBundle\Dao\Gacha\MstGachaDao;
use Title\GaiaBundle\ManagementToolBundle\Constant\GachaShopSort;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class GachaCardSetController extends Controller
{
	protected function createGachaShopParam(){
		$sortColumn = $this->getRequest()->get('sort_colmn');
		$sortOrder = $this->getRequest()->get('sort_order');
		$param["errors"] = $this->getRequest()->get('errors');
		if($param["errors"] == null) $param["errors"] = [];
		$param['sort_colmn'] = is_null($sortColumn) ? GachaShopSort::CATEGORY : $sortColumn;
		$param['sort_order'] = is_null($sortOrder) ? Sort::ORDER_ASC : $sortOrder;
		// ページャ処理用
		$offset = $this->get('request')->get('offset');
		$param['offset'] = is_null($offset) ? 0 : $offset;
		$param['limit'] = Display::DISPLAY_COUNT_USER_LIST;
		
		// フィルタ
		$param["filt_cat"] = $this->getRequest()->get('filt_cat');
		$param["filt_disp"] = $this->getRequest()->get('filt_disp');
		$param["filt_order"] = $this->getRequest()->get('filt_order');
		$param["filt_now"] = $this->getRequest()->get('filt_now');
		
		$param["filt_cat"] = $param['filt_cat']==null?"":$param["filt_cat"];
		$param["filt_disp"] = $param['filt_disp']==null?"":$param["filt_disp"];
		$param["filt_order"] = $param['filt_order']==null?"":$param["filt_order"];
		$param["filt_now"] = $param['filt_now']==null?"":$param["filt_now"];
		
		return $param;
	}
	/**
	 * ガチャショップエディット
	 */
	public function gachaShopEditAction(){
		$fld = ["display","order","info","title","str_cost","str_num","cost_name","cost_std_id","cost_val","banner_image","func","effective_from","effective_to","category","category_priority"];
		$req = $this->getRequest();
		$id = intval($req->get('id'));
		$action = $req->get('action');
		$insert = intval($req->get('mode')) != 1;

		$TAG = "GachaShopEdit";
		
		$info = [];
		$Aselector = $this->get("title.mng_tool.service.assets_selector");

		$param = $this->createGachaShopParam();
		if(strcmp($action,"delete") == 0){
			// 削除モード
			$sql = $this->getSql();
			try{
				$this->useTransaction();
				$ptmt = $sql->prepare("delete from gacha_data where id = ?");
				$ptmt->execute([intval($id)]);
				$this->get("title.mng_tool.log")->out($TAG,["Delete",["id: $id"]]);
				$sql->commit();$sql=null;
			}catch(\Exception $e){
				\Dcs\Toybox::printException($e);
				if($sql != null)
					$sql->rollBack();
			}
			return $this->redirect($this->generateUrl('gacha_shop_list',$param));
		}else if(strcmp($action,"update") == 0){
			$sql = null;
			$arg = [];
			$mode = intval($req->get("mode"));
			$out_type = "";
			if($mode == 0){
				$out_type = "Insert";
				// insert 
				$sql = "insert into gacha_data (display,`order`,info,title,str_cost,str_num,cost_name,cost_std_id,cost_val,banner_image,func,effective_from,effective_to,category,category_priority,id) ";
				$sql .= "values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
				$arg[] = intval($req->get("display"));
				$arg[] = intval($req->get("order"));
				$arg[] = str_replace("\n","[br]",$req->get("info"));
				$arg[] = $req->get("title");
				$arg[] = $req->get("str_cost");
				$arg[] = $req->get("str_num");
				$arg[] = $req->get("cost_name");
				$arg[] = $req->get("cost_std_id");
				$arg[] = $req->get("cost_val");
				$arg[] = $req->get("banner_image");
				$func = [];
				for($i=0;;++$i){
					$fe = $req->get("func[$i]");
					if(!is_numeric($fe)) continue;
					$fe = intval($fe);
					if($fe > 0)
						$func[] = $fe;
				}
				$arg[] = implode(",",$func);
				$arg[] = $req->get("effective_from_date")." ".$req->get("effective_from_time").":00";
				$arg[] = $req->get("effective_to_date")." ".$req->get("effective_to_time").":00";
				$arg[] = $req->get("category");
				$arg[] = $req->get("category_priority");
				$arg[] = $id;
			}else{
				$out_type = "Update";
				// update 
				$sql = "update gacha_data set display=?,`order`=?,info=?,title=?,str_cost=?,str_num=?,cost_name=?,cost_std_id=?,cost_val=?,banner_image=?,func=?,effective_from=?,effective_to=?,category=?,category_priority=? where id = ?";
				$arg[] = intval($req->get("display"));
				$arg[] = intval($req->get("order"));
				$arg[] = str_replace("\n","[br]",$req->get("info"));
				$arg[] = $req->get("title");
				$arg[] = $req->get("str_cost");
				$arg[] = $req->get("str_num");
				$arg[] = $req->get("cost_name");
				$arg[] = $req->get("cost_std_id");
				$arg[] = $req->get("cost_val");
				$arg[] = $req->get("banner_image");
				$func = [];
				$flist = $req->get("func");
				error_log(json_encode($flist));
				for($i=0;;++$i){
					if(!isset($flist[$i])) break;
					$fe = $flist[$i];
					if(!is_numeric($fe)) continue;
					$fe = intval($fe);
					if($fe > 0)
						$func[] = $fe;
				}
				$arg[] = implode(",",$func);
				$arg[] = $req->get("effective_from_date")." ".$req->get("effective_from_time").":00";
				$arg[] = $req->get("effective_to_date")." ".$req->get("effective_to_time").":00";
				$arg[] = $req->get("category");
				$arg[] = $req->get("category_priority");
				$arg[] = $id;
			}
			$con = $this->getSql();
			try{
				$this->useTransaction();
				$ptmt = $con->prepare($sql);
				$ptmt->execute($arg);
				$glist = $this->getGachaList();
				$func = explode(",",$arg[10]);
				foreach($func as $i=>$val){
					$func[$i] = $glist[$val];
				}
				$this->get("title.mng_tool.log")->out($TAG,[
						$out_type,
						[
							"category: ".$arg[13],
							"category_priority: ".$arg[14],
							"id: $id",
							"display: ".($arg[0]==1?"バナー":$arg[0]==2?"フレーム":$arg[0]==3?"Noah":"不定"),
							"order: ".$arg[1],
							"title: ".$arg[3],
							"info: ",
								explode("[br]",$arg[2]),
							"str_cost: ".$arg[4],
							"str_num: ".$arg[5],
							"cost_name: ".$arg[6],
							"cost_std_id: ".$arg[7],
							"cost_val: ".$arg[8],
							"banner_image: ".$arg[9],
							"effective_from: ".$arg[11],
							"effective_to: ".$arg[12],
							"func: ",
								$func,
						]
				]);
				$con->commit();$con=null;
			}catch(\Exception $e){
				\Dcs\Toybox::printException($e);
				if($con != null){
					$con->rollBack();
					$param["errors"][]=$e->getMessage();
				}
			}
			return $this->redirect($this->generateUrl('gacha_shop_list',$param));
			
		}else{
			$param["mode"] = 0;
			$info = [];
			if(strcmp($action,"new") == 0){
				$param["mode"] = 0;
				if(is_numeric($id)){
					$param["errors"][]="数値以外のID[$id]が指定されました。700000～799999内の数値で指定してください";
					return $this->redirect($this->generateUrl('gacha_shop_list',$param));
				}
				$rs = $this->getHs()->select(new Table("gacha_data",["id"]),new Query(["="=>$id]));
				if(!empty($rs)){
					$param["errors"][]="すでに存在するショップID[$id]が指定されました。別のIDを指定してください";
					return $this->redirect($this->generateUrl('gacha_shop_list',$param));
				}
				if($id < 700000 || 800000 <= $id){
					$param["errors"][]="範囲外のID[$id]が指定されました。700000～799999の範囲内で指定してください";
					return $this->redirect($this->generateUrl('gacha_shop_list',$param));
				}
				$src_id = intval($req->get('src_id'));
				if($src_id > 0){
					$info = $this->getHs()->select(new Table("gacha_data",$fld),new Query(["="=>$src_id]))[0];
				}else{
					$info = [
							1,
							0,
							"ガチャの内容",
							"ガチャのタイトル",
							"表示用のコスト数値",
							"表示用の取得アイテム数",
							"表示用コスト名",
							0,
							0,
							"アセットバンドル名",
							"",
							"2010-01-01 00:00:00",
							"2050-01-01 00:00:00",
							0,
							0
					];
				}
			}else{
				$param["mode"] = 1;
				$info = $this->getHs()->select(new Table("gacha_data",$fld),new Query(["="=>$id]))[0];
			}
			$insert = ["id"=>$id];
			foreach($info as $i => $val){
				if(is_numeric($val)){
					if(strcmp(intval($val)."",$val) == 0){
						$val = intval($val);
					}else{
						$val = $val+0;
					}
				}
				$insert[$fld[$i]] = $val;
			}
			
			$sep = explode(" ",$insert["effective_from"]);
			$insert["effective_from_date"] = $sep[0];
			$time = "00:00";
			if(count($sep)>1){
				$ts = explode(":",$sep[1]);
				if(count($ts)>1)
					$time = $ts[0].":".$ts[1];
			}
			$insert["effective_from_time"] = $time;
			
			$sep = explode(" ",$insert["effective_to"]);
			$insert["effective_to_date"] = $sep[0];
			$time = "00:00";
			if(count($sep)>1){
				$ts = explode(":",$sep[1]);
				if(count($ts)>1)
					$time = $ts[0].":".$ts[1];
			}
			$insert["effective_to_time"] = $time;

			$insert["cost_std_id_type"] = $Aselector->assetTypeId($insert["cost_std_id"]);
			$glist = $this->getGachaList();
			
			$sep = explode(",",$insert["func"]);
			$func = [];
			for($i=0,$len=count($sep);$i<$len;++$i){
				if(!is_numeric($sep[$i])) continue;
				$gid = intval($sep[$i]);
				if(!isset($glist[$gid])) continue;
				$func[] = $gid;
			}
			$insert["func"] = $func;
			
			$param["info"] = $insert;
			$param["gacha_selector"] = $glist;
			$param["assets_selector"] = $Aselector->create();
		}
		return $this->render('TitleManagementToolBundle:gacha:gacha_shop_edit.html.twig', $param);
	}
	protected function getGachaList(){
		$rs = $this->getHs()->select(
				new Table("GAIA_MST_GACHA",["gacha_id","gacha_name"]),
				new Query([">="=>0],-1)
		);
		$ret = [];
		foreach($rs as $row){
			$ret[intval($row[0])] = $row[1];
		}
		return $ret;
	}
	/**
	 * ガチャショップリスト
	 */
	public function gachaShopListAction(){
		$param = $this->createGachaShopParam();
		
		$result = $this->selectShopList($param);
		
		$pathParams = [
		'sort_colmn' => $param['sort_colmn'],
		'sort_order' => $param['sort_order']
		];
		foreach(["filt_cat","filt_disp","filt_order"] as $key){
			if($param[$key] != null)
				$pathParams[$key] = $param[$key];
		}
		
		// ページング
		$pager = $this->get('gaia.mng_tool.model.pager');
		$pager->setInc(Display::DISPLAY_COUNT_USER_LIST);
		$pager->setPath('gacha_shop_list', $pathParams);
		$pager->setParameters($this->getAllShopDataCount($param));
		$displayData = $pager->getDisplayData($result);
		
		// 出力パラメータ設定
		$param['pager'] = $pager->getParameters();
		$param['shopList'] = $displayData;
		
		return $this->render('TitleManagementToolBundle:gacha:gacha_shop_list.html.twig', $param);
	}
	protected function selectShopList($param){
		$flter = $this->makeShopFilter($param);
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare(
				"SELECT category,category_priority,display,id,`order`,title,concat(cost_val,cost_name) as cost,func,effective_from as `from`, effective_to as `to` FROM gacha_data ".$flter[0].$this->makeShopSortSql($param).$this->makeLimitSql($param));
		$ptmt->execute($flter[1]);
		$ret = [];
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$row["display"] = $this->makeShopDisplay($row["display"]);
			$row["func"] = $this->makeShopFuncList($row["func"]);
			$ret[] = $row;
		}
		return $ret;
	}
	protected function makeShopDisplay($type){
		switch(intval($type)){
			case 2:
				return "フレーム";
			case 3:
				return "Noah";
			case 1:
			default:
				return "バナー";
		}
	}
	protected function getAllShopDataCount($param){
		$flter = $this->makeShopFilter($param);
		$ptmt = $this->getDoctrine()->getEntityManager()->
		         getConnection()->prepare("select count(id) from gacha_data ".$flter[0]);
		$ptmt->execute($flter[1]);
		$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
		return intval($rs[0][0]);
	}
	protected function makeShopFuncList($list_str){
		$key = "Title.ManagementToolBundle.GachaCardSetController.makeShopFuncList";
		$cache = $this->cache();
		$data = $cache->get(\Dcs\Cache::TYPE_APC,$key);
		if($data == null){
			$data = [];
			$rs = $this->getHs()->select(new Table("GAIA_MST_GACHA",["gacha_id","gacha_name"]),new Query([">="=>0],-1));
			foreach($rs as $row){
				$data[intval($row[0])] = $row[1];
			}
			$cache->set(\Dcs\Cache::TYPE_APC,$key,$data);
		}
		$sep = explode(",",$list_str);
		$ret = [];
		for($i=0,$len=count($sep);$i<$len;++$i){
			$id = intval($sep[$i]);
			if(isset($data[$id]))
				$ret[] = $data[$id];
		}
		return $ret;
	}
	protected function makeShopFilter($param){
		$ret = "where 1 ";
		$args = [];
		if(is_numeric($param["filt_cat"])){
			$ret .= "and category=? ";
			$args[] = intval($param["filt_cat"]);
		}
		if($param["filt_disp"] != null){
			$ret .= "and display = ? ";
			$args[] = $param["filt_disp"];
		}
		if($param["filt_order"] != null){
			$ret .= "and `order` = ? ";
			$args[] = $param["filt_order"];
		}
		if($param["filt_now"] != null){
			$ret .= "and effective_from <= ? and effective_to >= ? ";
			$args[] = $param["filt_now"]." 00:00:00";
			$args[] = $param["filt_now"]." 00:00:00";
		}
		return [$ret,$args];
	}
	protected function makeShopSortSql($param){
		$sort = intval($param['sort_colmn']);
		$ord = intval($param['sort_order']);
		$ret = " order by ";
		switch($sort){
			case GachaShopSort::CATEGORY_PRIORITY:
				$ret .= "category_priority ";
				break;
			case GachaShopSort::DISPLAY:
				$ret .= "display ";
				break;
			case GachaShopSort::ID:
				$ret .= "id ";
				break;
			case GachaShopSort::ORDER:
				$ret .= "order ";
				break;
			case GachaShopSort::FROM:
				$ret .= "`from` ";
				break;
			case GachaShopSort::TO:
				$ret .= "`to` ";
				break;
			case GachaShopSort::CATEGORY:
			default:
				$ret .= "category ";
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
     * ガチャマスタ一覧画面 表示アクション
     *
     * @return Response レンダリング
     */
    public function gachaMstListAction()
    {
        // パラメータ取得
        $gachaMstSortColmn = $this->getRequest()->get('gachaMstSortColmn');
        $gachaMstSortOrder = $this->getRequest()->get('gachaMstSortOrder');

        $output['gachaMstSortColmn'] =
            is_null($gachaMstSortColmn) ? MstGachaDao::SORT_COLMN_GACHA_EFFECTIVE_FROM : $gachaMstSortColmn;
        $output['gachaMstSortOrder'] =
            is_null($gachaMstSortColmn) ? Sort::ORDER_DESC: $gachaMstSortOrder;

        $offset = $this->get('request')->get('offset');
        $output['offset'] = is_null($offset) ? MstGachaDao::DEFAULT_OFFSET : $offset;
        $output['limit'] = Display::DISPLAY_COUNT_GACHA_MST_LIST;

        // execute
        $service = $this->get('gaia.mng_tool.service.gacha');
        $mstGachaList = $service->getGachaList(
            $output['gachaMstSortColmn'], $output['gachaMstSortOrder'], $output['limit'], $output['offset']);

        // ページング
        $pager = $this->get('gaia.mng_tool.model.pager');
        $pager->setInc(Display::DISPLAY_COUNT_GACHA_MST_LIST);

        $pathParams = [
            'gachaMstSortColmn' => $output['gachaMstSortColmn'],
            'gachaMstSortOrder' => $output['gachaMstSortOrder']
        ];

        $pager->setPath('gacha_mst_list', $pathParams);
        $pager->setParameters($service->getAllDataCount());

        $displayData = $pager->getDisplayData($mstGachaList);

        // 出力パラメータ設定
        $output['pager'] = $pager->getParameters();
        $output['mstGachaList'] = $displayData;

        return $this->render('TitleManagementToolBundle:gacha:gacha_mst_list.html.twig', $output);
    }

    /**
     * ガチャマスタ登録・更新画面 表示アクション
     *
     * @return Response レンダリング
     */
    public function gachaMstEditAction()
    {
        // パラメータ取得
        $input = $this->getGachaMstEditRequestParameter();
        $service = $this->get('gaia.mng_tool.service.gacha');

        // 編集モードかつ初回表示時は登録済のデータを取得し表示
        if ($input['mode'] == 1 && $input['init'] == 0) {
            // 編集データ取得
            $gachaMstData = $service->getGachaMstById($input['gachaId']);
            if (is_null($gachaMstData)) {
                // 排他的な事するならここで例外処理
            }
            // 登録してあるデータを初期表示
            $input = $this->setGachaMstRegisteredData($input, $gachaMstData);
        }

        $output = $input;
        $output['init'] = 1;
        $output['errors'] = '';
        $output['openBox'] = '0';

        return $this->render('TitleManagementToolBundle:gacha:gacha_mst_edit.html.twig', $output);
    }

    /**
     * ガチャマスタ登録・更新画面 登録更新確認アクション
     *
     * @return Response レンダリング
     */
    public function gachaMstEditConfirmAction()
    {
        // パラメータ取得
        $input = $this->getGachaMstEditRequestParameter();
        $service = $this->get('gaia.mng_tool.service.gacha');

        //入力チェック
        $errors = $service->validateGachaMst($input);

        // 出力パラメータ設定
        $output = $input;

        if (empty($errors)) {
            $output['errors'] = '';
            $output['openBox'] = '1';
        } else {
            $output['errors'] = $errors;
            $output['openBox'] = '0';
        }

        return $this->render('GaiaManagementToolBundle:gacha:gacha_mst_edit.html.twig', $output);
    }

    /**
     * ガチャマスタ登録・更新画面 登録更新実行
     *
     * @return レンダリング
     */
    public function gachaMstEditExecutionAction()
    {
        // パラメータ取得
        $input = $this->getGachaMstEditRequestParameter();
        $editParam = [
            'gacha_id'       => $input['gachaId'],
            'gacha_name'     => $input['gachaName'],
            'effective_from' => "2010-01-01 00:00:00",
            'effective_to'   => "2050-01-01 00:00:00",
            'uid'            => SessionUtil::get(SessionKey::USER_ID, $this->getRequest())
        ];

        $service = $this->get('gaia.mng_tool.service.gacha');

        // 新規モード時
        if ($input['mode'] == 0) {
            $service->newGacha($editParam);
        // 編集モード時
        } else if ($input['mode'] == 1) {
            $service->setGacha($editParam);
        }

        // 出力パラメータ設定
        $output = $input;
        $output['errors'] = '';
        $output['openBox'] = '2';

        return $this->render('GaiaManagementToolBundle:gacha:gacha_mst_edit.html.twig', $output);
    }

    /**
     * ガチャマスタ登録・更新画面 リクエストパラメータを取得する
     *
     * @return リクエストパラメータ配列
     */
    protected function getGachaMstEditRequestParameter()
    {
        $input['init'] = is_null($this->getRequest()->get('init')) ? 0 : 1;
        $input['mode'] = $this->getRequest()->get('mode');
        $input['offset'] = $this->getRequest()->get('offset');
        $input['gachaMstSortColmn'] = $this->getRequest()->get('gachaMstSortColmn');
        $input['gachaMstSortOrder'] = $this->getRequest()->get('gachaMstSortOrder');
        $input['gachaId'] = $this->getRequest()->get('gachaId');
        $input['gachaName'] = urldecode($this->getRequest()->get('gachaName'));

        return $input;
    }

    /**
     * ガチャマスタ登録・更新画面 登録済みのカードデータをセットする
     *
     * @param array $input 入力パラメータ
     * @param array $gachaMstData 登録してあるガチャデータ
     *
     * @return リクエストパラメータ配列
     */
    protected function setGachaMstRegisteredData($input, $gachaMstData)
    {
        $input['gachaId'] = $gachaMstData['gacha_id'];
        $input['gachaName'] = $gachaMstData['gacha_name'];

        return $input;
    }

    /**
     * ガチャランクグループマスタ一覧画面 表示アクション
     *
     * @return Response レンダリング
     */
    public function gachaGroupListAction()
    {
        // パラメータ取得
        $input = $this->getGachaGroupRequestParameter();

        // execute
        $service = $this->get('gaia.mng_tool.service.gacha');
        $mstGacha = $service->getGachaMstById($input['gachaId']);
        $mstGachaGroupList = $service->getGachaGroupList($input['gachaId']);

        // 出力パラメータ設定
        $output = $this->getGachaGroupOutputParameter($input, $mstGacha, $mstGachaGroupList);

        return $this->render('GaiaManagementToolBundle:gacha:gacha_group_list.html.twig', $output);
    }

    /**
     * ガチャランクグループマスタ一覧 リクエストパラメータを取得する
     *
     * @return リクエストパラメータ配列
     */
    protected function getGachaGroupRequestParameter()
    {
        $input['gachaId'] = $this->getRequest()->get('gachaId');
        $input['offset'] = $this->getRequest()->get('offset');
        $input['gachaMstSortColmn'] = $this->getRequest()->get('gachaMstSortColmn');
        $input['gachaMstSortOrder'] = $this->getRequest()->get('gachaMstSortOrder');
        $input['mode'] = $this->getRequest()->get('mode');
        $input['gachaGroupId'] = $this->getRequest()->get('gachaGroupId');

        return $input;
    }

    /**
     * ガチャランクグループマスタ一覧 出力パラメータを設定する
     *
     * @param array $input リクエストパラメータ
     * @param array $mstGacha ガチャマスタ
     * @param array $mstGachaGroupList ガチャランクグループマスタリスト
     * 
     * @return 出力パラメータ配列
     */
    protected function getGachaGroupOutputParameter($input, $mstGacha, $mstGachaGroupList)
    {
        $output['gachaName'] = $mstGacha['gacha_name'];
        $output['gachaId'] = $input['gachaId'];
        $output['offset'] = $input['offset'];
        $output['gachaMstSortColmn'] = $input['gachaMstSortColmn'];
        $output['gachaMstSortOrder'] = $input['gachaMstSortOrder'];
        $output['mode'] = $input['mode'];
        $output['gachaGroupId'] = $input['gachaGroupId'];
        $output['mstGachaGroupList'] = $mstGachaGroupList;

        return $output;
    }

    /**
     * ガチャランクグループマスタ登録・更新画面 アクション
     *
     * @return Response レンダリング
     */
    public function gachaGroupEditAction()
    {
        // パラメータ取得
        $input = $this->getGachaGroupEditRequestParameter();
        $service = $this->get('gaia.mng_tool.service.gacha');
        $mstGacha = $service->getGachaMstById($input['gachaId']);

        // 編集モードかつ初回表示時は登録済のデータを取得し表示
        if ($input['mode'] == 1 && $input['init'] == 0) {
            // 編集データ取得
            $gachaGroupData = $service->getGachaGroupById($input['gachaGroupId']);
            if (is_null($gachaGroupData)) {
                // 排他的な事するならここで例外処理
            }
            // 登録してあるデータを初期表示
            $input = $this->setGachaGroupRegisteredData($input, $gachaGroupData);
        }

        $output = $input;
        $output['init'] = 1;
        $output['errors'] = '';
        $output['openBox'] = '0';
        $output['gachaName'] = $mstGacha['gacha_name'];

        return $this->render('GaiaManagementToolBundle:gacha:gacha_group_edit.html.twig', $output);
    }

    /**
     * ガチャランクグループマスタ登録・更新確認アクション
     *
     * @return Response レンダリング
     */
    public function gachaGroupEditConfirmAction()
    {
        // パラメータ取得
        $input = $this->getGachaGroupEditRequestParameter();
        $service = $this->get('gaia.mng_tool.service.gacha');
        $mstGacha = $service->getGachaMstById($input['gachaId']);

        //入力チェック
        $errors = $service->validateGachaGroupMst($input);

        // 出力パラメータ設定
        $output = $input;

        if (empty($errors)) {
            $output['errors'] = '';
            $output['openBox'] = '1';
        } else {
            $output['errors'] = $errors;
            $output['openBox'] = '0';
        }

        $output['gachaName'] = $mstGacha['gacha_name'];

        return $this->render('GaiaManagementToolBundle:gacha:gacha_group_edit.html.twig', $output);
    }

    /**
     * ガチャランクグループマスタ登録・更新画面 登録更新実行
     *
     * @return レンダリング
     */
    public function gachaGroupEditExecutionAction()
    {
        $input = $this->getGachaGroupEditRequestParameter();
        $editParam = [
            'gacha_group_id' => $input['gachaGroupId'],
            'gacha_id'       => $input['gachaId'],
            'rarity'         => $input['rarity'],
            'rate'           => $input['rate'],
            'uid'            => SessionUtil::get(SessionKey::USER_ID, $this->getRequest())
        ];

        $service = $this->get('gaia.mng_tool.service.gacha');
        $mstGacha = $service->getGachaMstById($input['gachaId']);

        // 新規モード時
        if ($input['mode'] == 0) {
            $service->newGachaGroup($editParam);
        // 編集モード時
        } else if ($input['mode'] == 1) {
            $service->setGachaGroup($editParam);
        }

        // 出力パラメータ設定
        $output = $input;
        $output['errors'] = '';
        $output['openBox'] = '2';
        $output['gachaName'] = $mstGacha['gacha_name'];

        return $this->render('GaiaManagementToolBundle:gacha:gacha_group_edit.html.twig', $output);
    }

    /**
     * ガチャランクグループマスタ登録・更新画面 リクエストパラメータを取得する
     *
     * @return リクエストパラメータ配列
     */
    protected function getGachaGroupEditRequestParameter()
    {
        $input['init'] = is_null($this->getRequest()->get('init')) ? 0 : 1;
        $input['gachaId'] = $this->getRequest()->get('gachaId');
        $input['gachaGroupId'] = $this->getRequest()->get('gachaGroupId');
        $input['offset'] = $this->getRequest()->get('offset');
        $input['gachaMstSortColmn'] = $this->getRequest()->get('gachaMstSortColmn');
        $input['gachaMstSortOrder'] = $this->getRequest()->get('gachaMstSortOrder');
        $input['mode'] = $this->getRequest()->get('mode');
        $input['gachaGroupId'] = urldecode($this->getRequest()->get('gachaGroupId'));
        $input['rarity'] = urldecode($this->getRequest()->get('rarity'));
        $input['rate'] = urldecode($this->getRequest()->get('rate'));

        return $input;
    }

    /**
     * ガチャランクグループマスタ登録・更新画面 登録済みのグループデータをセットする
     *
     * @param array $input 入力パラメータ
     * @param array $gachaGroupData 登録してあるガチャグループデータ
     *
     * @return リクエストパラメータ配列
     */
    protected function setGachaGroupRegisteredData($input, $gachaGroupData)
    {
        $input['gachaGroupId'] = $gachaGroupData['gacha_group_id'];
        $input['rarity'] = $gachaGroupData['rarity'];
        $input['rate'] = $gachaGroupData['rate'];

        return $input;
    }

   /**
     * ガチャ排出カードマスタ一覧画面 表示アクション
     *
     * @return Response レンダリング
     */
    public function gachaCardListAction()
    {
        // パラメータ取得
        $input = $this->getGachaCardListRequestParameter();

        // execute
        $service = $this->get('gaia.mng_tool.service.gacha');
        $mstGacha = $service->getGachaMstById($input['gachaId']);
        $mstGachaCardList = $service->getGachaCardList($input['gachaGroupId']);

        // 出力パラメータ設定
        $output = $this->getGachaCardListOutputParameter($input, $mstGacha, $mstGachaCardList);

        return $this->render('GaiaManagementToolBundle:gacha:gacha_card_list.html.twig', $output);
    }

    /**
     * ガチャ排出カードマスタ登録・編集 表示アクション
     *
     * @return Response レンダリング
     */
    public function gachaCardEditAction()
    {
        // パラメータ取得
        $input = $this->getGachaCardEditRequestParameter();
        $service = $this->get('gaia.mng_tool.service.gacha');
        $mstGacha = $service->getGachaMstById($input['gachaId']);
        $Aselector = $this->get("title.mng_tool.service.assets_selector");

        // 編集モードかつ初回表示時は登録済のデータを取得し表示
        if ($input['mode'] == 1 && $input['init'] == 0) {
            // 編集データ取得
            $gachaCardData = $service->getGachaCard($input['gachaCardId']);
            if (is_null($gachaCardData)) {
                // 排他的な事するならここで例外処理
            }
            // 登録してあるデータを初期表示
            $input = $this->setGachaCardRegisteredData($input, $gachaCardData);
        }

        $output = $input;
        $output['init'] = 1;
        $output['errors'] = '';
        $output['openBox'] = '0';

        $asset = $this->setGachaCardListBoxParts($input, $service);
        $output['cardTypeList'] = $asset['asset_type_list'];
        $output['charaList'] = $asset['asset_list'];
        $output['gachaName'] = $mstGacha['gacha_name'];
        $output['cardType'] = $Aselector->assetTypeId($input["cardId"]);
        $output['cardId'] = $input["cardId"];
		$output["assets_selector"] = $Aselector->create();

        return $this->render('TitleManagementToolBundle:gacha:gacha_card_edit.html.twig', $output);
    }

    /**
     * ガチャ排出カードマスタ登録・更新確認アクション
     *
     * @return Response レンダリング
     */
    public function gachaCardEditConfirmAction()
    {
        // パラメータ取得
        $input = $this->getGachaCardEditRequestParameter();
        $service = $this->get('gaia.mng_tool.service.gacha');
        $mstGacha = $service->getGachaMstById($input['gachaId']);
        $Aselector = $this->get("title.mng_tool.service.assets_selector");

        //入力チェック
        $errors = $service->validateGachaCardMst($input);

        // 出力パラメータ設定
        $output = $input;

        if (empty($errors)) {
            $output['errors'] = '';
            $output['openBox'] = '1';
        } else {
            $output['errors'] = $errors;
            $output['openBox'] = '0';
        }

        $asset = $this->setGachaCardListBoxParts($input, $service);
        $output['cardTypeList'] = $asset['asset_type_list'];
        $output['charaList'] = $asset['asset_list'];
        $output['gachaName'] = $mstGacha['gacha_name'];
        $output['cardType'] = $Aselector->assetTypeId($input["cardId"]);
        $output['cardId'] = $input["cardId"];
		$output["assets_selector"] = $Aselector->create();

        return $this->render('TitleManagementToolBundle:gacha:gacha_card_edit.html.twig', $output);
    }

    /**
     * ガチャ排出カードマスタ登録・更新画面 登録更新実行
     *
     * @return レンダリング
     */
    public function gachaCardEditExecutionAction()
    {
        $input = $this->getGachaCardEditRequestParameter();
        $service = $this->get('gaia.mng_tool.service.gacha');
        $mstGacha = $service->getGachaMstById($input['gachaId']);
        $Aselector = $this->get("title.mng_tool.service.assets_selector");

        $editParam = [
            'gacha_group_id' => $input['gachaGroupId'],
            'asset_type_id'  => $input['cardType'],
            'asset_id'       => $input['cardId'],
            'asset_count'    => $input['cardCount'],
            'gacha_card_id'  => $input['gachaCardId'],
            'uid'            => SessionUtil::get(SessionKey::USER_ID, $this->getRequest())
        ];

        // 新規モード時
        if ($input['mode'] == 0) {
            $service->newGachaCard($editParam);
        // 編集モード時
        } else if ($input['mode'] == 1) {
            $service->setGachaCard($editParam);
        }

        // 出力パラメータ設定
        $output = $input;
        $output['errors'] = '';
        $output['openBox'] = '2';

        $asset = $this->setGachaCardListBoxParts($input, $service);
        $output['cardTypeList'] = $asset['asset_type_list'];
        $output['charaList'] = $asset['asset_list'];
        $output['gachaName'] = $mstGacha['gacha_name'];
        $output['cardType'] = $Aselector->assetTypeId($input["gachaId"]);
        $output['cardId'] = $input["cardId"];
		$output["assets_selector"] = $Aselector->create();

        
        return $this->render('TitleManagementToolBundle:gacha:gacha_card_edit.html.twig', $output);
    }

    /**
     * ガチャ排出カードマスタ一覧 リクエストパラメータを取得する
     *
     * @return リクエストパラメータ配列
     */
    protected function getGachaCardListRequestParameter()
    {
        $input['gachaId'] = $this->getRequest()->get('gachaId');
        $input['offset'] = $this->getRequest()->get('offset');
        $input['gachaMstSortColmn'] = $this->getRequest()->get('gachaMstSortColmn');
        $input['gachaMstSortOrder'] = $this->getRequest()->get('gachaMstSortOrder');
        $input['mode'] = $this->getRequest()->get('mode');
        $input['gachaGroupId'] = $this->getRequest()->get('gachaGroupId');
        $input['gachaCardId'] = $this->getRequest()->get('gachaCardId');

        return $input;
    }

    /**
     * ガチャ排出カードマスタ一覧 出力パラメータを設定する
     *
     * @param array $input リクエストパラメータ
     * @param array $mstGacha ガチャマスタ
     * @param array $mstGachaCardList ガチャ排出カードマスタリスト
     * 
     * @return 出力パラメータ配列
     */
    protected function getGachaCardListOutputParameter($input, $mstGacha, $mstGachaCardList)
    {
        $output['gachaName'] = $mstGacha['gacha_name'];
        $output['gachaId'] = $input['gachaId'];
        $output['offset'] = $input['offset'];
        $output['gachaMstSortColmn'] = $input['gachaMstSortColmn'];
        $output['gachaMstSortOrder'] = $input['gachaMstSortOrder'];
        $output['mode'] = $input['mode'];
        $output['gachaGroupId'] = $input['gachaGroupId'];
        $output['mstGachaCardList'] = $mstGachaCardList;

        return $output;
    }

    /**
     * ガチャ排出カードマスタ登録・編集 リクエストパラメータを取得する
     *
     * @return リクエストパラメータ配列
     */
    protected function getGachaCardEditRequestParameter()
    {
        $input['init'] = is_null($this->getRequest()->get('init')) ? 0 : 1;
        $input['gachaId'] = $this->getRequest()->get('gachaId');
        $input['offset'] = $this->getRequest()->get('offset');
        $input['gachaMstSortColmn'] = $this->getRequest()->get('gachaMstSortColmn');
        $input['gachaMstSortOrder'] = $this->getRequest()->get('gachaMstSortOrder');
        $input['mode'] = $this->getRequest()->get('mode');
        $input['gachaGroupId'] = $this->getRequest()->get('gachaGroupId');
        $input['gachaCardId'] = $this->getRequest()->get('gachaCardId');
        $input['cardType'] = $this->getRequest()->get('cardType');
        $input['cardId'] = $this->getRequest()->get('cardId');
        $input['cardCount'] = urldecode($this->getRequest()->get('cardCount'));

        return $input;
    }

    /**
     * ガチャ排出カードマスタ登録・編集 登録済みのグループデータをセットする
     *
     * @param array $input 入力パラメータ
     * @param array $gachaCardData 登録してあるガチャカードデータ
     *
     * @return リクエストパラメータ配列
     */
    protected function setGachaCardRegisteredData($input, $gachaCardData)
    {
        $input['gachaCardId'] = $gachaCardData['gacha_card_id'];
        $input['cardType'] = $gachaCardData['asset_type_id'];
        $input['cardId'] = $gachaCardData['asset_id'];
        $input['cardCount'] = $gachaCardData['asset_count'];

        return $input;
    }

    /**
     * ガチャ排出カードマスタ登録・編集 カードタイプリストとカードリストをセットする
     *
     * @param array $input 入力パラメータ
     * @param class $service サービスクラス
     *
     * @return カードタイプリスト・カードリスト
     */
    protected function setGachaCardListBoxParts($input, $service)
    {
        return $service->getAssetSelectBox($input['cardType']);
    }
}
