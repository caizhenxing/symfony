<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Gaia\Bundle\ManagementToolBundle\Constant\SearchType;
use Gaia\Bundle\ManagementToolBundle\Exception\ErrorMessages;
use Title\GaiaBundle\ManagementToolBundle\Constant\TitleSearchType;


class UserSearchController extends \Gaia\Bundle\ManagementToolBundle\Controller\UserSearchController{
	
	protected function searchParam()
	{
		$ret = parent::searchParam();
		array_unshift($ret["items"], ["id"=>TitleSearchType::USER_ACTOR_NAME, "name"=>"アクター名を検索"]);
		array_unshift($ret["items"], ["id"=>TitleSearchType::USER_CHARA_ID, "name"=>"キャラクターIDを検索"]);
		$ret["selected"] = TitleSearchType::USER_CHARA_ID;
		return $ret;
	}
}