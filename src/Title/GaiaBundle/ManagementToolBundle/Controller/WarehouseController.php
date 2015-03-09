<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Gaia\Bundle\ManagementToolBundle\Constant\Sort;
use Gaia\Bundle\ManagementToolBundle\Constant\Display;
use Title\GaiaBundle\ManagementToolBundle\Util\Controller;
use Symfony\Component\HttpFoundation\Response;
use Gaia\Bundle\ManagementToolBundle\Util\SessionUtil;
use Gaia\Bundle\ManagementToolBundle\Constant\SessionKey;
use Title\GaiaBundle\ManagementToolBundle\Constant\WarehouseSort;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Logic\Util\Equip;
use Logic\Util\StackItem;
/**
 * ユーザーに送信するメッセージを設定する
 * @author Takeda_Yoshihiro
 *
 */
class WarehouseController extends Controller{
	public function equipAction(){
		$uid = intval($this->getRequest()->get('user_id'));
		$sortColumn = $this->getRequest()->get('sort_colmn');
		$sortOrder = $this->getRequest()->get('sort_order');
		$param["user_id"] = $uid;
		$param['sort_colmn'] = is_null($sortColumn) ? WarehouseSort::ID : $sortColumn;
		$param['sort_order'] = is_null($sortOrder) ? Sort::ORDER_DESC : $sortOrder;
		// ページャ処理用
		$offset = $this->get('request')->get('offset');
		$param['offset'] = is_null($offset) ? 0 : $offset;
		$param['limit'] = Display::DISPLAY_COUNT_USER_LIST;
		
		$result = $this->selectEquipList($param);
		
		$pathParams = [
		'sort_colmn' => $param['sort_colmn'],
		'sort_order' => $param['sort_order'],
		'user_id' => $uid
		];
		
		// ページング
		$pager = $this->get('gaia.mng_tool.model.pager');
		$pager->setInc(Display::DISPLAY_COUNT_USER_LIST);
		$pager->setPath('box_equip', $pathParams);
		$pager->setParameters($this->getAllEquipDataCount($param));
		$displayData = $pager->getDisplayData($result);
		
		// 出力パラメータ設定
		$param['pager'] = $pager->getParameters();
		$param['data_list'] = $displayData;
		
		$param['tab'] = ['path_param' => ['user_id' => $uid]];
		
		return $this->render('TitleManagementToolBundle:user:equip_list.html.twig', $param);
	}
	protected function getAllEquipDataCount($param){
		$ptmt = $this->getSql()->prepare("select count(id) from box_equip where uid = ?");
		$ptmt->execute([$param["user_id"]]);
		$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
		return intval($rs[0][0]);
	}
	protected function selectEquipList($param){
		$Equip = $this->get("Arpg.Logic.Util.Equip");
		$Skill = $this->get("Arpg.Logic.Util.EquipSkill");
		$Addon = $this->get("Arpg.Logic.Util.EquipAddon");
		$ptmt = $this->getSql()->prepare(
				"select ".
					"b.uid as uid,".
					"b.id as id,".
					"b.std_id as std_id,".
					"b.std_id as std_id,".
					"b.level as level,".
					"b.exp as exp,".
					"g.exp as mexp,".
					"b.evo as evo,".
					"b.skill as skill,".
					"b.addon as addon,".
					"b.state as state,".
					"b.lock as `lock`,".
					"b.create_date as create_date,".
					"e.name as name,".
					"e.rarity as rarity,".
					"r.name as rstr,".
					"e.atk*g.rate+b.evo_bonus_atk as atk,".
					"e.def*g.rate+b.evo_bonus_def as def,".
					"e.matk*g.rate+b.evo_bonus_matk as matk,".
					"e.mdef*g.rate+b.evo_bonus_mdef as mdef".
				" from ".
					"box_equip as b left join ".
					"equip_data as e on b.std_id = e.std_id left join ".
					"rarity as r on e.rarity = r.id left join ".
					"equip_grow_func as g on e.grow_type = g.grow_type and b.level = g.lv ".
				"where uid = ? ".$this->makeSortSql($param).$this->makeLimitSql($param)
		);
		$ptmt->execute([$param["user_id"]]);
		$ret = [];
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$sep = explode(",",$row["skill"]);
			$total = [];
			foreach($sep as $skill){
				if(!is_numeric($skill)) continue;
				$skill = $Skill->getData($skill);
				if(!isset($skill["name"])) continue;
				$total[] = $skill["name"];
			}
			$row["skill"] = $total;
			
			$sep = explode(",",$row["addon"]);
			$total = [];
			foreach($sep as $addon){
				if(!is_numeric($addon)) continue;
				$addon = $Addon->getName($addon);
				if(empty($addon)) continue;
				$total[] = $addon;
			}
			$row["addon"] = $total;
			
			$row["exp"] = $row["exp"]."/".$row["mexp"];
			$row["lock"] = intval($row["lock"]);
			$row["state"] = intval($row["state"]);
			$row["type"] = $this->makeEquipTypeStr($row["std_id"]);
			$ret[] = $row;
		}
		return $ret;
	}
	protected function makeEquipTypeStr($std_id){
		switch(Equip::std2type($std_id)){
			case Equip::TYPE_WEAPON:{
				switch(Equip::std2wtype($std_id)){
					case Equip::WEAPON_SWORD:
						return "両手剣";
					case Equip::WEAPON_HAMMER:
						return "ハンマー";
					case Equip::WEAPON_ROD:
						return "本・杖";
					default:
						return "不明";
				}
			}
			case Equip::TYPE_ETC:
				return "素材";
			case Equip::TYPE_HEADGEAR:
				return "ヘッドギア";
			case Equip::TYPE_COSTUME:
				return "鎧";
			case Equip::TYPE_AMULET:
				return "ネックレス";
			case Equip::TYPE_RING:
				return "指輪";
			default:
				$type = "不明";
				break;
		}
		return $type;
	}
	protected function makeStateStr($state){
		$state = intval($state);
		switch($state){
			case 1:
				$state = "削除";
				break;
			case 2:
				$state = "プレボ内";
				break;
			case 3:
				$state = "プレボ外";
				break;
			case 0:
			default:
				$state = "倉庫内";
				break;
		}
		return $state;
	}
	
	
	
	
	

	public function itemAction(){
		$uid = intval($this->getRequest()->get('user_id'));
		$sortColumn = $this->getRequest()->get('sort_colmn');
		$sortOrder = $this->getRequest()->get('sort_order');
		$param["user_id"] = $uid;
		$param['sort_colmn'] = is_null($sortColumn) ? WarehouseSort::ID : $sortColumn;
		$param['sort_order'] = is_null($sortOrder) ? Sort::ORDER_DESC : $sortOrder;
		// ページャ処理用
		$offset = $this->get('request')->get('offset');
		$param['offset'] = is_null($offset) ? 0 : $offset;
		$param['limit'] = Display::DISPLAY_COUNT_USER_LIST;
	
		$result = $this->selectItemList($param);
	
		$pathParams = [
			'sort_colmn' => $param['sort_colmn'],
			'sort_order' => $param['sort_order'],
			'user_id' => $uid
		];
	
		// ページング
		$pager = $this->get('gaia.mng_tool.model.pager');
		$pager->setInc(Display::DISPLAY_COUNT_USER_LIST);
		$pager->setPath('box_item', $pathParams);
		$pager->setParameters($this->getAllEquipDataCount($param));
		$displayData = $pager->getDisplayData($result);
	
		// 出力パラメータ設定
		$param['pager'] = $pager->getParameters();
		$param['data_list'] = $displayData;
	
		$param['tab'] = ['path_param' => ['user_id' => $uid]];
	
		return $this->render('TitleManagementToolBundle:user:item_list.html.twig', $param);
	}
	protected function getAllItemDataCount($param){
		$ptmt = $this->getSql()->prepare("select count(id) from box_stack_item where uid = ?");
		$ptmt->execute([$param["user_id"]]);
		$rs = $ptmt->fetchAll(\PDO::FETCH_NUM);
		return intval($rs[0][0]);
	}
	protected function selectItemList($param){
		$ptmt = $this->getSql()->prepare(
				"select b.std_id as std_id, b.num as num, i.name as name, r.name as rstr from box_stack_item as b left join item_data as i on b.std_id = i.std_id left join rarity as r on i.rarity = r.id where uid = ? ".$this->makeSortSql($param).$this->makeLimitSql($param)
		);
		$ptmt->execute([$param["user_id"]]);
		$ret = [];
		while($row = $ptmt->fetch(\PDO::FETCH_ASSOC)){
			$row["type"] = $this->makeItemTypeStr($row["std_id"]);
			$ret[] = $row;
		}
		return $ret;
	}

	protected function makeItemTypeStr($std_id){
		switch(StackItem::std2type($std_id)){
			case StackItem::TYPE_MATERIAL:
				return "素材";
			case StackItem::TYPE_UPGRADE:
				return "進化用";
			case StackItem::TYPE_TICKET:
				return "チケット";
			case StackItem::TYPE_DRAG:
				return "回復薬";
			case StackItem::TYPE_MDRAG:
				return "魔法薬";
			case StackItem::TYPE_BOOST:
				return "ブースト";
			case StackItem::TYPE_SPECIAL:
				return "特殊";
			case StackItem::TYPE_EVENT:
				return "イベント";
			case StackItem::TYPE_NEVENT:
				return "非表示イベント";
			default:
				$type = "不明";
				break;
		}
		return $type;
	}
	
	
	
	
	
	protected function makeSortSql($param){
		$sort = intval($param['sort_colmn']);
		$ord = intval($param['sort_order']);
		$ret = " order by ";
		switch($sort){
			case WarehouseSort::TYPE:
				$ret .= "std_id ";
				break;
			case WarehouseSort::STDID:
				$ret .= "std_id ";
				break;
			case WarehouseSort::NAME:
				$ret .= "name ";
				break;
			case WarehouseSort::RARITY:
				$ret .= "rarity ";
				break;
				
			case WarehouseSort::LV:
				$ret .= "level ";
				break;
			case WarehouseSort::EXP:
				$ret .= "exp ";
				break;
			case WarehouseSort::EVO:
				$ret .= "evo ";
				break;
			case WarehouseSort::ATK:
				$ret .= "atk ";
				break;
			case WarehouseSort::MATK:
				$ret .= "matk ";
				break;
			case WarehouseSort::DEF:
				$ret .= "def ";
				break;
			case WarehouseSort::MDEF:
				$ret .= "mdef ";
				break;
			case WarehouseSort::CREATE:
				$ret .= "create_date ";
				break;
			case WarehouseSort::STATE:
				$ret .= "state ";
				break;
			case WarehouseSort::LOCK:
				$ret .= "lock ";
				break;
				
			case WarehouseSort::NUM:
				$ret .= "num ";
				break;
				
			case WarehouseSort::ID:
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