<?php
namespace Dcs;

use Gaia\Bundle\ExternalInterfaceBundle\Atom\Service\AtomService;

/**
 * Atom友達招待
 *  Gaia\Bundle\ExternalInterfaceBundle\Atom\Service\AtomService をextendsして拡張
 */
class AtomInviteService extends AtomService {
  
  /** @var String $userType atomへ送信するログインユーザー情報 */
  protected $userType = '';
  
  /**
   * user_typeをセットする
   * @param string $secret atomから発行される秘密キー
   */
  public function setUserType($_userType)
  {
    $this->userType = $_userType;
  }
  
  /**
   * invite_create APIをコールする
   * @param int $user_id
   * @param string $cid atomから発行されるcampaign_id
   * @param string $secret atomから発行される秘密キー
   * @return array
   */
  public function requestInviteCreate($user_id, $cid, $secret)
  {
    $key = hash_hmac('sha1', $user_id.$cid.$secret, $secret);
    $response = $this->requestAtom($this->url['invite_create'], array(
      'cid'       => $cid,
      'uid'       => $user_id,
      'key'       => $key,
      'user_type' => $this->userType,
    ));
    $this->logger->debug('cid: '.$cid.', uid: '.$user_id.', key: '.$key);
    $this->eventDispatch($response);
    return $response;
  }
  
  /**
   * invite_check APIをコールする（招待用起動コードから、招待主user_idを得る）
   * @param string $serial 起動コード
   * @param string $secret atomから発行される秘密キー
   * @return array
   */
  public function requestInviteCheck($serial, $secret)
  {
    $key = hash_hmac('sha1', $serial.$secret, $secret);
    $response = $this->requestAtom($this->url['invite_check'], array(
      'serial'    => $serial,
      'key'       => $key,
      'user_type' => $this->userType,
    ));
    $this->logger->debug('serial: '.$serial.', key: '.$key);
    $this->eventDispatch($response);
    return $response;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validatePreRegisterCode($cid, $scode)
  {
    $response = $this->requestAtom($this->url['register_code'], array(
      'cid'    => $cid,
      'serial' => $scode,
      'user_type' => $this->userType,  // 追加
    ));
    $this->logger->debug('cid: '.$cid.', scode: '.$scode);
    $this->eventDispatch($response);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateCampaignCode($cid, $scode, $userType = '')
  {
    $response = $this->requestAtom($this->url['campaign_code'], array(
      'cid'        => $cid,
      'start_code' => $scode,
      'user_type' => $this->userType,  // 追加
    ));
    $this->logger->debug('cid: '.$cid.', scode: '.$scode);
    $this->eventDispatch($response);

    return $response;
  }
  
}
?>