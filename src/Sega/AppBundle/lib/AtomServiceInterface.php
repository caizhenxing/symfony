<?php
namespace Dcs;

/**
 * Atomサービスインターフェース
 */
interface AtomServiceInterface {
  /**
   * Atom用として有効なURLスキーム名かどうか確認する
   * @param string $input URLスキーム
   * @return bool
   */
  public function IsValidUrlScheme($input);
  
  /**
   * Atom用として認証するかどうかの判定（アプリ依存）
   * @param 
   * @return 0以下 = 認証開始 , それ以外は AtomController の$result_status
   */
  public function IsAuthCampaign(DcsController $ctrl, $user_id, $campaign_code , $serial);

  
  /**
   * 報酬assetをプレゼントBOXへ送付する
   * @param DcsController $ctrl
   * @param int $user_id
   * @param array $auth_result 報酬アセットの情報（AtomCampaignServiceInterface auth()の返値）
   *     {
   *         campaign_name      => string,
   *         acceptance_message => string,
   *         asset => [
   *             {
   *                 asset_type_id   => int,
   *                 asset_id        => int,
   *                 asset_count     => int,
   *                 present_message => string
   *             },
   *             ...
   *         ]
   *         response          => atom server responce (中身は確認してね)
   *     }
   * @return array
   *     {
   *         succeed => bool,
   *         data => array,  // 任意
   *     }
   */
  public function SendReward(DcsController $ctrl, $user_id, $auth_result);
  
  /**
   * 招待主へ報酬付与する。（招待主がいて、各種条件を満たしている場合に付与）
   * 招待されたユーザーが成立地点（チュートリアル突破等）を通過した時にコールする。
   * @param DcsController $ctrl
   * @param int $uid 招待されたユーザーのuser_id
   * @param bool $isJustInputed 招待コード入力時点でコールされた場合にtrue
   * @return null
   */
  public function CheckAndSendRewardForInviteUser(DcsController $ctrl, $uid, $isJustInputed = false);
}
?>