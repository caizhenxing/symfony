<?php
namespace Dcs;


/**
 * Noah サービスインターフェース
 */
interface NoahServiceInterface {

  /**
   * Offer報酬assetをプレゼントBOXへ送付する
   * @param DcsController $ctrl
   * @param int $user_id
   * @param array $reward 報酬アセットの情報（NoahOfferServiceInterface receive()の返値）
   *      array(
   *         asset_type_id   => int,     // 
   *         asset_id        => int,     // 
   *         asset_count     => int,     // 状無い
   *      )
   *
   * @param string $message   // 現状無い (offer_name?)
   *
   * @return array
   *     {
   *         succeed => bool,
   *         data => array,  // 任意
   *     }
   */
  public function SendPresentOfferReward( DcsController $ctrl, $user_id, $reward, $message );


}

?>