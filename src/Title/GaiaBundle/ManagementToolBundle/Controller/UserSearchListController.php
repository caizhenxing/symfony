<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Gaia\Bundle\ManagementToolBundle\Constant\Sort;
use Gaia\Bundle\ManagementToolBundle\Constant\Display;
use Gaia\Bundle\ManagementToolBundle\Controller\Abstracts\WithSideAndTabMenuController;
use Gaia\Bundle\ManagementToolBundle\Dao\User\UserDataDao;

class UserSearchListController extends WithSideAndTabMenuController
{
    /**
     * ユーザ情報画面 表示アクション
     *
     * @return Response
     */
    public function listAction()
    {
        $sortColumn = $this->getRequest()->get('sort_colmn');
        $sortOrder = $this->getRequest()->get('sort_order');
        $param['search_type'] = urldecode($this->getRequest()->get('search_type'));
        $param['search_id'] = urldecode($this->getRequest()->get('search_id'));
        $param['sort_colmn'] = is_null($sortColumn) ? UserDataDao::SORT_COLMN_USER_ID : $sortColumn;
        $param['sort_order'] = is_null($sortOrder) ? Sort::ORDER_DESC : $sortOrder;
        // ページャ処理用
        $offset = $this->get('request')->get('offset');
        $param['offset'] = is_null($offset) ? 0 : $offset;
        $param['limit'] = Display::DISPLAY_COUNT_USER_LIST;

        $service = $this->get('gaia.mng_tool.service.user_data');
        $result = $service->selectList($param);
        if (empty($result)) {
            return $this->redirect($this->generateUrl('user_search',
                    ['search_type' => $param['search_type'], 'search_id' => $param['search_id'], 'empty' => 1]));
        } else if (count($result) === 1 && $param['offset'] == 0) {
            return $this->redirect($this->generateUrl('user_info', ['user_id' => $result[0]['user_id']]));
        }

        $pathParams = [
            'search_type' => $param['search_type'],
            'search_id'   => $param['search_id'],
            'sort_colmn' => $param['sort_colmn'],
            'sort_order' => $param['sort_order']
        ];

        // ページング
        $pager = $this->get('gaia.mng_tool.model.pager');
        $pager->setInc(Display::DISPLAY_COUNT_USER_LIST);
        $pager->setPath('user_list', $pathParams);
        $pager->setParameters($service->getAllDataCount($param));
        $displayData = $pager->getDisplayData($result);
        
        // 出力パラメータ設定
        $param['pager'] = $pager->getParameters();
        $param['user_info_list'] = $displayData;
        return $this->render('TitleManagementToolBundle:user:user_search_list.html.twig', $param);
    }
}