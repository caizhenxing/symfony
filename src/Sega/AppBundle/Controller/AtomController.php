<?php

namespace Sega\AppBundle\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dcs\Security as sec;
use Dcs as dcs;
use Gaia\Bundle\CommonBundle\Exception\GaiaException;
use Gaia\Bundle\CommonBundle\Exception\ErrorCodes;

/**
 * atom処理
 */
class AtomController extends \Dcs\DcsController
{
  // 下記[BaseController]で作成されていた内容を移植
  // CmnAccountにloginCheckをしておく
  function loginCheck($con , $data) {
    $acc = $this->get("Dcs.CmnAccount");
    if( $acc->loginCheck( $data['sid'] ) == dcs\CmnAccount::SUCCESS) {
      return $acc;
    }
    return NULL;
  }
  // 受け取ったweb URLパラメタを連想配列へ展開
  function getDecryptedParam($data, $mode) {
    return json_decode( sec::decrypt($mode, $data),TRUE);
  }
  
  
  
  // レスポンスresultStatus
  const RESULT_SUCCESS                       = 1;
  const RESULT_CODE_USED                     = 2;
  const RESULT_INVALID_PARAM                 = 3;
  const RESULT_ERROR                         = 4;
  const RESULT_ALREADY_ACCEPTED_INVITE_CODE  = 5;
  const RESULT_MYSELF_INVITE_CODE            = 6;
  const RESULT_NOT_EXIST_INVITE_CAMPAIGN     = 7;
  const RESULT_ALREADY_PRESENT_RECIVED               = 8;
  
  /**
   * シリアルコードを受け取って、条件を満たしていたら報酬を付与する。
   */
  public function authAction(Request $request, $data) {
    $mode = sec\Mode::X_OR();
    $data = $this->getDecryptedParam($data, $mode);
    $con = $this->get('doctrine')->getConnection();
    $acc = $this->loginCheck($con , $data);
    if($acc == NULL) {
      return $this->InvalidParamResponse();
    }
    $uid = $acc->getUid();
    
/*
    // 招待debug
    // http://pre.sega-net.jp/invite/inputcode?cid=squads_invite_test&code=[invite_code] でserial取得
    $uid = 1;
    $data['schm'] = 'squads.atom';
    $data['cid'] = 'squads_invite_test';
    $data['serial'] = 'a5b2d585caf99e9c2a30d7c10faf2e560725c594';
*/
    
    if(!array_key_exists('schm', $data)) {
      return $this->InvalidParamResponse();
    }
    $scheme = $data['schm'];
    if(!array_key_exists('cid', $data)) {
      return $this->InvalidParamResponse();
    }
    $campaign_code = $data['cid'];
    if(!array_key_exists('serial', $data)) {
      return $this->InvalidParamResponse();
    }
    $serial_code = $data['serial'];
    
    $atom_service_filter = $this->get('Dcs.AtomServiceFilter');
    
    // schemeがatom用のものかどうか。
    if($atom_service_filter->IsValidUrlScheme($scheme) == false) {
      return $this->InvalidParamResponse();
    }
    
    //$serv = $this->get('gaia.campaign.atom_campaign_service');
    $serv = $this->get('Dcs.AtomInviteCampaignService');   // ←子クラスを指定
    
    $result_status = null;
    $auth_result = null;
    
    $con->beginTransaction();
    try {
      $result_status = $atom_service_filter->IsAuthCampaign($this, $uid, $campaign_code , $serial_code);
      if($result_status <= 0) {
        // atom側へ認証
        try {
          $auth_result = $serv->auth($uid, $campaign_code, $serial_code);
        }
        catch(GaiaException $e) {
          // 受取済などの場合
          error_log('[GaiaException]'.$e->getMessage());
          error_log('[GaiaException]'.$e->getCode());

          switch($e->getCode()) {

            // 招待関連のエラー ErrorCodes::getError()利用に切り替えたい
          case Dcs\AtomInviteCampaignService::ERROR_CODE_ALREADY_ACCEPTED_INVITE_CODE:
            $result_status = self::RESULT_ALREADY_ACCEPTED_INVITE_CODE;
            break;
          case Dcs\AtomInviteCampaignService::ERROR_CODE_MYSELF_INVITE_CODE:
            $result_status = self::RESULT_MYSELF_INVITE_CODE;
            break;
          case Dcs\AtomInviteCampaignService::ERROR_CODE_NOT_EXIST_INVITE_CAMPAIGN:
            $result_status = self::RESULT_NOT_EXIST_INVITE_CAMPAIGN;
            break;

          case ErrorCodes::getError('CAMPAIGN', 'CAMPAIGN_DATA_NOT_EXIST'):
            $result_status = self::RESULT_INVALID_PARAM;
            break;
          case ErrorCodes::getError('CAMPAIGN', 'AUTH_FAILURE'):
            $result_status = self::RESULT_INVALID_PARAM;
            break;
          case ErrorCodes::getError('CAMPAIGN', 'CAMPAIGN_CODE_USED'):
            $result_status = self::RESULT_CODE_USED;
            break;
          case ErrorCodes::getError('CAMPAIGN', 'CAMPAIGN_PRESENT_NOT_EXIST'):
          default:
            $result_status = self::RESULT_ERROR;
            break;
          case ErrorCodes::getError('CAMPAIGN', 'CAMPAIGN_ALREADY_PRESENT_RECIVED'):
            $result_status = self::RESULT_ALREADY_PRESENT_RECIVED;
            break;
          }
        }
      }
      //>IsAuthCampaign != 0 なら auth_result = null
      // present送付処理
      if(isset($auth_result['asset'])) {
        $ret = $atom_service_filter->SendReward($this, $uid, $auth_result);
        if($ret['succeed'] == true) {
          $result_status = self::RESULT_SUCCESS;
        }
        else {
          $result_status = self::RESULT_ERROR;
        }
      }
      
      // 招待キャンペーンの招待主へ報酬付与する処理（入力時点で条件満たしているかどうかの確認）
      $atom_service_filter->CheckAndSendRewardForInviteUser($this, $uid, true);
      
      $con->commit();
    } catch(\Exception $e) {
      $con->rollBack();
      error_log($e->getMessage());
      $result_status = self::RESULT_ERROR;
    }
    
    $rpc = new dcs\Rpc();
    $rpc->data = array(
      'resultStatus'      => $result_status,
      'campaignName'      => isset($auth_result['campaign_name'])      ? $auth_result['campaign_name'] : '',
      'acceptanceMessage' => isset($auth_result['acceptance_message']) ? $auth_result['acceptance_message'] : '',
    );
    return new Response($rpc->toJson(sec\Mode::X_OR()));
  }
  
  function InvalidParamResponse() {
    $rpc = new dcs\Rpc();
    $rpc->data = array(
      'resultStatus' => self::RESULT_INVALID_PARAM,
    );
    return new Response($rpc->toJson(sec\Mode::X_OR()));
  }
  
  /**
   * 友達招待画面に必要な情報を返す。
   */
  public function inviteCodeAction(Request $request, $data) {
    $mode = sec\Mode::X_OR();
    $data = $this->getDecryptedParam($data, $mode);
    $con = $this->get('doctrine')->getConnection();
    $acc = $this->loginCheck($con , $data);
    if($acc == NULL) {
      $rpc = new dcs\Rpc();
      $rpc->err = 1;
      return new Response($rpc->toJson(sec\Mode::X_OR()));
    }
    $uid = $acc->getUid();
    
    // 招待コード
    $service_campaign = $this->get('Dcs.AtomInviteCampaignService');
    $invite_info = $service_campaign->getInviteCodeInfo($uid);
    
    // これまでに招待した人数（招待した人が判定通過点を通過して報酬受け取り済みの件数。未通過の人は含まず。）
    $invited_num = $service_campaign->getInviteSucceedNum($invite_info['campaign_code'],$uid);
    
    $rpc = new dcs\Rpc();
    $rpc->data = array(
      'inviteCode'      => is_null($invite_info) ? '現在実施していません' : $invite_info['invite_code'],
      'campaignCode'    => is_null($invite_info) ? '' : $invite_info['campaign_code'],
      'inviteMessage'   => is_null($invite_info) ? '' : $invite_info['invite_message'],
      'invitedNum'      => $invited_num,
      'invitedNumLimit' => $invite_info['reward_limit'],
    );
    return new Response($rpc->toJson(sec\Mode::X_OR()));
  }
  
  /**
   * コード入力一覧画面
   */
  public function inputCodeListAction(Request $request, $data) {
    $mode = sec\Mode::X_OR();
    $data = $this->getDecryptedParam($data, $mode);
    $con = $this->get('doctrine')->getConnection();
    $memcache = $this->getMemcached();
    $acc = $this->loginCheck($con , $data);
    
    $list = array();
    if($acc != NULL) {
      $now = date('Y-m-d H:i:s', time());
      $ptmt = $con->prepare('select banner_url, jump_url, text from `DCS_MST_CODE_INPUT_LIST` WHERE visible_from <= ? AND visible_to > ? order by sort');
      $ptmt->execute(array($now, $now));
      $input_list = $ptmt->fetchAll();
      
      $config_logic = new \Logic\ConfigLogic;
      $contents_url = $config_logic->getContentsUrl($request, $con, $memcache);
      
      foreach($input_list as $row) {
        $list[] = array(
          'bannerUrl' => $contents_url.$row['banner_url'],
          'jumpUrl'   => $row['jump_url'],
          'text'      => $row['text'],
        );
      }
    }
    
    $twig_params = array('list' => $list);
    return $this->render('SegaAppBundle:Atom:inputCodeList.html.twig' , $twig_params);
  }
}
?>