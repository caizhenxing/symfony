<?php

namespace Title\GaiaBundle\ManagementToolBundle\Service;

use Gaia\Bundle\ManagementToolBundle\Constant\SearchType;
use Gaia\Bundle\ManagementToolBundle\Dao\User\UserDataDaoInterface;
use Gaia\Bundle\ManagementToolBundle\Util\NumberCheckUtil;
use Gaia\Bundle\ManagementToolBundle\Util\StringCheckUtil;
use Gaia\Bundle\ManagementToolBundle\Exception\ErrorMessages;
use Title\GaiaBundle\ManagementToolBundle\Constant\TitleSearchType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ユーザ情報サービスクラス
 *
 * @copyright Copyright (c) 2013 SEGA Networks Co., Ltd. All rights reserved.
 * @author Itec Hokkaido
 */
class UserDataService extends \Gaia\Bundle\ManagementToolBundle\Service\UserDataService
{
	use \Dcs\Base;
	
    protected $container;

    public function __construct(
    		UserDataDaoInterface $userDataDao,
    		ContainerInterface $container
    ){
       parent::__construct($userDataDao);
       $this->container = $container;
    }

    /**
     * Gets a service by id.
     *
     * @param string $id The service id
     *
     * @return object The service
     */
    public function get($id)
    {
    	return $this->container->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm($param)
    {
    	// 入力値チェック
    	switch ( $param['search_type'] ) {
    		case TitleSearchType::USER_CHARA_ID:
    			if (StringCheckUtil::isNullOrBlank($param['search_id'])) {
    				return [ErrorMessages::get('COMMON', 'EMPTY', ['キャラクタID'])];
    			} else if (!StringCheckUtil::checkMaxLength($param['search_id'], 9)) {
    				return [ErrorMessages::get('COMMON', 'OVER_LENGTH_NUMBER', ['キャラクタID', 9])];
    			}
    			break;
    
    		case TitleSearchType::USER_ACTOR_NAME:
    			if (StringCheckUtil::isNullOrBlank($param['search_id'])) {
    				return [ErrorMessages::get('COMMON', 'EMPTY', ['アクター名'])];
    			} else if (!StringCheckUtil::checkMaxLength($param['search_id'], 20)) {
    				return [ErrorMessages::get('COMMON', 'OVER_LENGTH_STRING', ['アクター名', 20])];
    			}
    			break;
    		default:
    	}
    	return parent::validateForm($param);
    }
}