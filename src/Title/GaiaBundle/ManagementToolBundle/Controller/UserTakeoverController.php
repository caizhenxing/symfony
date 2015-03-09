<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Gaia\Bundle\ManagementToolBundle\Controller\Abstracts\WithSideAndTabMenuController;
use Gaia\Bundle\ManagementToolBundle\Model\AdminActionCertificateModelInterface;
use Gaia\Bundle\ManagementToolBundle\Constant\SessionKey;
use Gaia\Bundle\ManagementToolBundle\Util\SessionUtil;
use Title\GaiaBundle\ManagementToolBundle\Util\Controller;
use Gaia\Bundle\ManagementToolBundle\Util\StringCheckUtil;
use Gaia\Bundle\ManagementToolBundle\Exception\ErrorMessages;

class UserTakeoverController extends Controller
{
	private $adminActionCertificateModel;
    public function __construct(
        AdminActionCertificateModelInterface $adminActionCertificateModel){
    	$this->adminActionCertificateModel = $adminActionCertificateModel;
    }
    /**
     * 引継ぎ画面 表示アクション
     *
     * @return Response
     */
    public function takeoverAction()
    {
        return $this->info($this->getRequest()->get('user_id'));
    }

    /**
     * 引継ぎパスワード再設定アクション
     *
     * @return Response
     */
    public function takeoverPasswordingAction()
    {
    	
    	$admin_pass = urldecode($this->getRequest()->get('admin_password'));
   		$admin_id = SessionUtil::get(SessionKey::USER_ID, $this->getRequest());
		$result["errors"] = [];
    	if (StringCheckUtil::isNullOrBlank($admin_pass)) {
    		$result["errors"][] = ErrorMessages::get('COMMON', 'EMPTY', ['パスワード']);
    	} else if (!$this->adminActionCertificateModel->certify($admin_id, $admin_pass)) {
    		$result["errors"][] = ErrorMessages::get('COMMON', 'NOT_MATCH', ['パスワード']);
    	}
    	$userId = $this->getRequest()->get('user_id');
    	$result["user_id"] = $userId;
    	// タブパラメータ設定
    	$result['tab'] = ['path_param' => ['user_id' => $userId]];
    	
    	if(count($result["errors"]) < 1){
	    	$TakeOver = $this->get("Arpg.Logic.Util.TakeOver");
	    	$info = $TakeOver->offer($userId,$this->getRequest()->get('take_over_id'));
	    	$result["info"] = $info;
	    	$sql = $this->getSql();
	    	if($info == null){
	    		$result["errors"][] = "引継ぎ登録ができませんでした";
	    		if($sql->isTransactionActive()){
					$sql->rollBack();
	    		}
	    	}else{
	    		if($sql->isTransactionActive()){
	    			$sql->commit();
	    		}
	    	}
        	return $this->render('TitleManagementToolBundle:user:user_takeover.html.twig', $result);
    	}
    	

    	return $this->info($userId,$result["errors"]);
    }
    
    private function info($userId,$errors=[]){
    	$TakeOver = $this->get("Arpg.Logic.Util.TakeOver");
    	// アカウント情報取得
    	$info = $TakeOver->info($userId);
    	$result["user_id"] = $userId;
    	$result["info"] = $info;
    	// タブパラメータ設定
    	$result['tab'] = ['path_param' => ['user_id' => $userId]];
    	$result["errors"] = $errors;
    	
    	return $this->render('TitleManagementToolBundle:user:user_takeover.html.twig', $result);
    }
}