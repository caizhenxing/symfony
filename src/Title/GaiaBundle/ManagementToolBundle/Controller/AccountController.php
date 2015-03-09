<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Title\GaiaBundle\ManagementToolBundle\Util\Controller;
use Symfony\Component\HttpFoundation\Response;
use Gaia\Bundle\ManagementToolBundle\Util\SessionUtil;
use Gaia\Bundle\ManagementToolBundle\Constant\SessionKey;

class AccountController extends Controller{
    public function loginAction(){
    	return $this->run(function($data,&$headers){
    		$param['login_id'] = $data["id"];
    		$param['password'] = $data["pass"];
    		
    		$service = $this->get('gaia.mng_tool.service.session');
    		$result = $service->login($param);
    		
    		if (isset($result['errors'])) {
    			// ログイン失敗
    			throw new \Exception(implode("<br>",$result['errors']),1);
    		}
    		
    		// session_idを取得
    		$param['session_id'] = SessionUtil::get(SessionKey::SESSION_ID, $this->getRequest());
    		// 同一ログインIDによるログインチェック
    		$param['admin_user_id'] = $result['admin_user_id'];
    		$service->checkLoginStatus($param);
    		if (isset($param['errors'])) {
    			// 重複ログインによりログイン失敗
    			throw new \Exception(implode("<br>",$param['errors']),1);
    		}
    		
    		$param['session_id'] = \sha1(\uniqid(\mt_rand(),true));
    		
    		// ログインユーザ情報を保存
    		$loginUser = [
    		SessionKey::USER_ID => $result['admin_user_id'],
    		SessionKey::LOGIN_ID => $result['login_id'],
    		SessionKey::ROLE_ID => $result['role_id'],
    		SessionKey::READ_ONLY => (isset($result['read_only'])? 1 : 0),
    		SessionKey::MANAGE_ACCOUNT => (isset($result['manage_account'])? 1 : 0),
    		SessionKey::SESSION_ID => $param['session_id']
    		];
    		// セッション有効期限の設定
    		$limitTime = time() + $this->getSessionLifeTime();
    		$cookies = SessionUtil::createCookies($loginUser, $limitTime);
    		$service->insertUpdateLoginStatus($param,$limitTime);
    		// ログインステータス更新
    		foreach ($cookies as $cookie) {
				if ($cookie->getExpiresTime() < 0) {
					$headers->clearCookie($cookie->getName());
					continue;
				}
    			$headers->setCookie($cookie);
    		}
    		return null;
    	});
    }
    /* セッション有効期限（デフォルト2時間） */
    const DEFAULT_SESSION_LIFETIME = 7200;
    /**
     * セッションの有効期限を取得する
     */
    protected function getSessionLifeTime()
    {
        $lifetime = self::DEFAULT_SESSION_LIFETIME;

        if ($this->container->hasParameter('gaia_mng_tool_session_life_time')) {
            $lifeTimeParam = $this->container->getParameter('gaia_mng_tool_session_life_time');
            if (is_numeric($lifeTimeParam)) $lifetime = $lifeTimeParam;
        }

        return $lifetime;
    }
}