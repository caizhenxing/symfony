<?php
namespace Dcs;

use Gaia\Bundle\AtomCampaignBundle\Service\AtomCampaignService;

use Gaia\Bundle\AtomCampaignBundle\Constants\CampaignConst;
use Gaia\Bundle\CommonBundle\Exception\GaiaException;
use Gaia\Bundle\CommonBundle\Exception\ErrorCodes;
use Gaia\Bundle\DatabaseBundle\Campaign\AtomCampaignUsedHistoryDaoInterface;
use Gaia\Bundle\DatabaseBundle\Campaign\InviteDataDaoInterface;
use Gaia\Bundle\DatabaseBundle\Campaign\MstAtomCampaignDaoInterface;
use Gaia\Bundle\DatabaseBundle\Campaign\MstAtomCampaignPresentDaoInterface;
use Gaia\Bundle\DatabaseBundle\Campaign\MstInviteCampaignDaoInterface;
use Gaia\Bundle\DatabaseBundle\Campaign\MstInviteCampaignPresentDaoInterface;
use Gaia\Bundle\DatabaseBundle\Campaign\UserInviteDaoInterface;
use Gaia\Bundle\UserBundle\Service\UserServiceInterface;
use Psr\Log\LoggerInterface;

use Dcs\AtomInviteService;
use Gaia\Bundle\UserBundle\Service\UserService;

/**
 * Atom友達招待
 *  Gaia\Bundle\AtomCampaignBundle\Service\AtomCampaignService をextendsして拡張
 */
class AtomInviteCampaignService extends AtomCampaignService {

  /** @var Doctrine $doctrine */
  protected $doctrine;
  
  /** @var UserService $userService gaia.user.user_service */
  protected $userService;
  
  
  // DB connection
  protected $mSqlConnector;
  
  
  // エラーコード gaia取り込み時は、ErrorCodes::getError()で引けるように定義して$this->errで例外throwする想定
  const ERROR_CODE_ALREADY_ACCEPTED_INVITE_CODE = 101905;
  const ERROR_CODE_MYSELF_INVITE_CODE           = 101906;
  const ERROR_CODE_NOT_EXIST_INVITE_CAMPAIGN    = 101907;
  
  
  /**
   * コンストラクタ
   *
   * @param AtomInviteService $atomService Atomサービス
   * @param UserServiceInterface $userService ユーザサービス
   * @param MstAtomCampaignDaoInterface $mstAtomCampaignDao キャンペーンマスタDAO
   * @param MstAtomCampaignPresentDaoInterface $mstAtomCampaignPresentDao キャンペーンマスタDAO
   * @param MstInviteCampaignDaoInterface $mstInviteCampaignDao 招待キャンペーンマスタDAO
   * @param MstInviteCampaignPresentDaoInterface $mstInviteCampaignPresentDao 招待キャンペーンプレゼントマスタDAO
   * @param AtomCampaignUsedHistoryDaoInterface $atomCampaignUsedHistoryDao キャンペーン利用履歴DAO
   * @param InviteDataDaoInterface $InviteDataDao 招待キャンペーン情報DAO
   * @param UserInviteDaoInterface $userInviteDao ユーザ招待データDAO
   * @param LoggerInterface $logger ロガー
   * @param  $doctrine Doctrine
   * @param UserService $userService UserService
   */
  public function __construct(
    AtomInviteService $atomService,   // 型変更（子クラス）
    UserServiceInterface $userService,
    MstAtomCampaignDaoInterface $mstAtomCampaignDao,
    MstAtomCampaignPresentDaoInterface $mstAtomCampaignPresentDao,
    MstInviteCampaignDaoInterface $mstInviteCampaignDao,
    MstInviteCampaignPresentDaoInterface $mstInviteCampaignPresentDao,
    AtomCampaignUsedHistoryDaoInterface $atomCampaignUsedHistoryDao,
    InviteDataDaoInterface $InviteDataDao,
    UserInviteDaoInterface $userInviteDao,
    LoggerInterface $logger,
    // 以下を追加
    $doctrine,
    UserService $userService)
  {
    parent::__construct(
    		$atomService, 
    		$userService,
    		$mstAtomCampaignDao,
    		$mstAtomCampaignPresentDao,
    		$mstInviteCampaignDao,
    		$mstInviteCampaignPresentDao,
    		$atomCampaignUsedHistoryDao,
    		$InviteDataDao,
    		$userInviteDao,
    		$logger
	);
    
    $this->doctrine = $doctrine;
    $this->userService = $userService;
  }
  
  
  /**
   * 指定したユーザーの招待コード情報を取得
   * @param string $user_id
   * @return array or null
   */
  public function getInviteCodeInfo($user_id)
  {
    $invite_code = null;
    
    // 現在有効なcid/secretを取得
    $invite_campaign_info = $this->getEnableInviteCampaignMst();
    if($invite_campaign_info == false) {
      $this->logger->debug('有効な招待キャンペーンマスターがありません。');
      return null;
    }
    $atom_campaign_id = $invite_campaign_info['atom_campaign_id'];
    $atom_campaign_info = $this->getAtomCampaignMstById($atom_campaign_id);
    if($atom_campaign_info == false) {
      $this->logger->debug('有効なATOMキャンペーンマスターがありません。');
      return null;
    }
    if($atom_campaign_info['atom_campaign_type_id'] != CampaignConst::INVITE) {
      $this->logger->debug('ATOMキャンペーンマスターのtypeがINVITEではありません。');
      return null;
    }
    $cid = $atom_campaign_info['campaign_code'];
    $secret = $invite_campaign_info['secret_key'];
    
    $record = $this->getUserInvite($user_id, $atom_campaign_id);
    if($record) {
      // すでに発行されて記録済みであればそれを返す
      $invite_code = $record['invite_code'];
    }
    else {
      // 未発行の場合はatom側へ問い合わせる
      
      // user_id → public_id
      $public_id = $this->userService->getPublicId($user_id);
      
      $response = $this->atomService->requestInviteCreate($public_id, $cid, $secret);
      if($this->atomService->isResultOk($response) && isset($response['response_data']->invite_code)) {
        $invite_code = $response['response_data']->invite_code;
        $this->insertUserInvite($user_id, $atom_campaign_id, $invite_code);
      }
    }
    
    return array(
      'invite_code'    => $invite_code,
      'campaign_code'  => $atom_campaign_info['campaign_code'],
      'invite_message' => $invite_campaign_info['invite_message'],
      'reward_limit'   => intval($invite_campaign_info['reward_limit']),
    );
  }

  /**
   * 【override】
   */
  public function auth($uid, $campaignCode, $serial)
  {
    $response_data = null;
    if (!$campaignInfo = $this->selectAssetFromCode($campaignCode)) {
      $this->err(
        $uid,
        $campaignCode,
        $serial,
        '[GAIA_MST_ATOM_CAMPAIGN] data does not exist.',
        'CAMPAIGN_DATA_NOT_EXIST'
      );
    } /*else if (!$this->validateSerialWithAtom_new($campaignCode, $serial, $campaignInfo['atom_campaign_type_id'], $uid, $invite_info , $response_data)) {  // ← override
      $this->err(
        $uid,
        $campaignCode,
        $serial,
        'Authentication failure.',
        'AUTH_FAILURE'
      );
    } else if ($this->isAlreadyUsed($uid, $serial, $campaignInfo)) {
      $this->err(
        $uid,
        $campaignCode,
        $serial,
        'Campaign Code already used.',
        'CAMPAIGN_CODE_USED'
      );
    }*/ else if (!$campaignPresent = $this->getCampaignPresentFromId($campaignInfo['atom_campaign_id'])) {
      $this->err(
        $uid,
        $campaignCode,
        $serial,
        '[GAIA_MST_ATOM_CAMPAIGN_PRESENT] data does not exist.',
        'CAMPAIGN_PRESENT_NOT_EXIST'
      );
    }

    //キャンペーン履歴に登録
    $this->insertCampaignHistory($uid, $campaignInfo['atom_campaign_id'], $serial);

    // [追加]
    if($campaignInfo['atom_campaign_type_id'] == CampaignConst::INVITE && $invite_info != null) {
      // 誰が誰を招待したかテーブルに書き込み
      $this->insertInviteData($uid, $invite_info['invite_from_user_id'], $invite_info['invite_campaign_id']);
    }

    return $this->createResponseWithResponse($campaignInfo, $campaignPresent , $response_data);
  }

  /**
   * 招待主に報酬付与できるかどうか確認し、条件成立している場合はプレセントリスト情報を返す
   * @param string $user_id
   * @return array （条件成立していない場合はnull）
   *     {
   *         presents => [
   *             {
   *                 asset_type_id   => int,
   *                 asset_id        => int,
   *                 asset_count     => int,
   *                 present_message => string
   *             },
   *             ...
   *         ]
   *         invited_from_user_id => int,  招待主のuser_id
   *         target_user_id       => int,  招待成立した相手のuser_id
   *         num                  => int,  何人目？
   *     }
   */
  public function checkRewardForInviteUser($uid)
  {
    $invite_data = $this->getInviteDataByUserId($uid);
    if($invite_data == false) {
      // 招待コード未入力＝招待主がいない
      return null;
    }
    if($invite_data['reward_send_date'] != '0000-00-00 00:00:00') {
      // 報酬付与済み
      $this->logger->debug(sprintf('[checkRewardForInviteUser] 報酬付与済み uid:%s invite_from:%s reward_send_date:%s', $uid, $invite_data['invited_from'], $invite_data['reward_send_date']));
      return null;
    }
    $invited_from_user_id = $invite_data['invited_from'];
    $invite_campaign_id = $invite_data['invite_campaign_id'];
    
    // 招待キャンペーン情報
    $invite_campaign_info = $this->getInviteCampaignMstByInviteCampaignId($invite_campaign_id);
    if($invite_campaign_info == false) return;
    $atom_campaign_id = $invite_campaign_info['atom_campaign_id'];
    $reward_limit     = $invite_campaign_info['reward_limit'];
    $effective_from   = strtotime($invite_campaign_info['effective_from']);
    $effective_to     = strtotime($invite_campaign_info['effective_to']);
    
    $now = time();
    if($now < $effective_from || $now >= $effective_to) {
      // 有効期間外
      $this->logger->debug(sprintf('[checkRewardForInviteUser] 有効期間外 uid:%s invite_from:%s atom_campaign_id:%s invite_campaign_id:%s', $uid, $invited_from_user_id, $atom_campaign_id, $invite_campaign_id));
      return null;
    }
    
    // 招待主の、これまでの報酬獲得件数
    $num = $this->getInviteSucceedNum($invite_campaign_id,$invited_from_user_id);
    if($num >= $reward_limit) {
      // 上限回数超え
      $this->logger->debug(sprintf('[checkRewardForInviteUser] 上限回数超え uid:%s invite_from:%s num:%s limit:%s', $uid, $invited_from_user_id, $num, $reward_limit));
      return null;
    }
    
    // 招待主への報酬マスター
    $present_mst = $this->getInviteCampaignPresentMst($atom_campaign_id);
    if($present_mst == false) {
      // 報酬が未定義
      return null;
    }
    
    // 報酬成立時刻を記録
    $this->updateInviteRewardSendDate($uid, $invited_from_user_id, $invite_campaign_id);
    
    // 報酬プレゼント情報
    $presents = array();
    foreach($present_mst as $row) {
      $presents[] = array(
        'asset_type_id'   => $row['asset_type_id'],
        'asset_id'        => $row['asset_id'],
        'asset_count'     => $row['asset_count'],
        'present_message' => $row['present_message'],
      );
    }
    
    $result_param = array(
      'presents' => $presents,
      'invited_from_user_id' => $invited_from_user_id,
      'target_user_id'       => $uid,
      'num'                  => $num + 1,
    );
    $this->logger->debug(var_export($result_param, true));
    
    return $result_param;
  }

  /**
   * 【既存拡張】キャンペーンコード・シリアルコードをAtomに問い合わせ使用可能かチェックする。
   * 
   * @param string $campaignCode キャンペーンコード
   * @param string $serial シリアルコード
   * @param string $campaignType キャンペーンタイプID
   * @param int $uid ログイン中ユーザーのuser_id     // ← 引数追加
   * @param array &$invite_info 招待に関する情報を返す    // ← 引数追加
   * @return boolean true:認証成功 false:認証失敗
   */
  protected function validateSerialWithAtom_new($campaignCode, $serial, $campaignType, $uid, &$invite_info , &$response_data)
  {
    // atom送信情報
    $user_type = $this->getUserType($uid);
    $this->atomService->setUserType($user_type);
    
    $response = null;
    if ($campaignType == CampaignConst::PRE_REGIST) {
      $response = $this->atomService->validatePreRegisterCode($campaignCode, $serial);
    } else if ($campaignType == CampaignConst::NORMAL) {
      $response = $this->atomService->validateCampaignCode($campaignCode, $serial);
    } else if ($campaignType == CampaignConst::INVITE) {
      $response = $this->validateInviteCampaignCode($campaignCode, $serial, $uid, $invite_info);
    }
    
    $response_data = $response;
    
    return isset($response['response_data']->result) && ($response['response_data']->result === 'OK');
  }
  
  // type=inviteの場合の検証処理
  protected function validateInviteCampaignCode($campaignCode, $serial, $uid, &$invite_info)
  {
    // すでに招待コード入力済み
    $invite_data = $this->getInviteDataByUserId($uid);
    if($invite_data) {
      throw new GaiaException(
        "既に招待コードを入力済みです。 uid: $uid, ccode: $campaignCode, scode: $serial",
        self::ERROR_CODE_ALREADY_ACCEPTED_INVITE_CODE
      );
    }
    
    // URLスキームから受け取ったcampaign_codeから、secret_keyを取得
    $campaignInfo = $this->selectAssetFromCode($campaignCode);
    if($campaignInfo == false) return null;
    if($campaignInfo['atom_campaign_type_id'] != CampaignConst::INVITE) return null;
    $invite_campaign_info = $this->getEnableInviteCampaignMst();
    if($invite_campaign_info == false || $invite_campaign_info['atom_campaign_id'] != $campaignInfo['atom_campaign_id']) {
      throw new GaiaException(
        "現在招待キャンペーンを実施していません。 uid: $uid, ccode: $campaignCode, scode: $serial",
        self::ERROR_CODE_NOT_EXIST_INVITE_CAMPAIGN
      );
    }
    $secret = $invite_campaign_info['secret_key'];
    
    // atom側へ問い合わせ
    $response = $this->atomService->requestInviteCheck($serial, $secret);
    $this->logger->debug(var_export($response, true));
    if($this->atomService->isResultOk($response) && isset($response['response_data']->user_id)) {
      // public_id → user_id
      $public_id = $response['response_data']->user_id;
      $invite_from_user_id = $this->userService->getUserIdByPublicId($public_id);
      
      // 自分の招待コードはダメ
      if($uid == $invite_from_user_id) {
        throw new GaiaException(
          "自分の招待コードは利用できません。 uid: $uid, ccode: $campaignCode, scode: $serial",
          self::ERROR_CODE_MYSELF_INVITE_CODE
        );
      }
      
      // 返却情報
      $invite_info['invite_from_user_id'] = $invite_from_user_id;
      $invite_info['invite_campaign_id'] = $invite_campaign_info['invite_campaign_id'];
    }
    return $response;
  }
  
  // user_type決定
  private function getUserType($uid)
  {
    $user = $this->userService->getAccountData($uid);
    $now = time();
    $range = 60*60*24;  // 24時間
    return ($now - strtotime($user['created_time']) <= $range) ? 'new' : 'old';
  }
  
  
  
  
  
  // 以下、DB関連
  
  private function getSql() {
    if($this->mSqlConnector == null) {
      $this->mSqlConnector = $this->doctrine->getConnection();
    }
    return $this->mSqlConnector;
  }
  
  /** 現在有効な招待キャンペーンマスターを読み込み */
  private function getEnableInviteCampaignMst() {
    $con = $this->getSql();
    
    $now = time();
    return DBUtil::select($con, array(
      'table' => 'DCS_MST_INVITE_CAMPAIGN',
      'where' => array('effective_from <= ? and effective_to > ?', array(date('Y-m-d H:i:s', $now), date('Y-m-d H:i:s', $now))),
      'orderby' => 'order by invite_campaign_id',
    ));
  }
  
  /** 招待キャンペーンマスターを読み込み */
  private function getInviteCampaignMstByInviteCampaignId($invite_campaign_id) {
    $con = $this->getSql();
    return DBUtil::select($con, array(
      'table' => 'DCS_MST_INVITE_CAMPAIGN',
      'where' => array('invite_campaign_id = ?', array($invite_campaign_id)),
    ));
  }
  
  /** キャンペーンマスターをatom_campaign_idから読み込み */
  private function getAtomCampaignMstById($atom_campaign_id) {
    $con = $this->getSql();
    return DBUtil::select($con, array(
      'table' => 'GAIA_MST_ATOM_CAMPAIGN',
      'where' => array('atom_campaign_id = ?', array($atom_campaign_id)),
    ));
  }
  
  /** 招待主への報酬マスター取得 */
  private function getInviteCampaignPresentMst($atom_campaign_id) {
    $con = $this->getSql();
    return DBUtil::select($con, array(
      'table'  => 'GAIA_MST_INVITE_CAMPAIGN_PRESENT',
      'where'  => array('atom_campaign_id = ?', array($atom_campaign_id)),
      'orderby' => 'order by atom_campaign_invite_present_id',
    ), true);
  }

  

    /**
     * レスポンス(キャンペーン特典アイテム)を作成する。
     * 
     * @param mixed $campaignInfo キャンペーンマスタ情報
     * @param mixed $presents キャンペーン特典マスタ情報
     * @param mixed $response Atomからのレスポンス
     *
     * @return array レスポンス
     */
    public function createResponseWithResponse($campaignInfo, $presents , $response)
    {
      $auth_result = $this->createResponse($campaignInfo , $presents);
      $auth_result['response'] = $response;
      return $auth_result;
    }
}
?>