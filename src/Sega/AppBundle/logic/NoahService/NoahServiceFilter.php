<?php

namespace Logic\NoahService;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\HttpFoundation\Response;
use Gaia\Bundle\CommonBundle\Exception\GaiaException;

use Dcs\NoahServiceInterface;
use Dcs\DcsController;
use Dcs\Security as sec;
use Dcs as dcs;

class NoahServiceFilter implements NoahServiceInterface {

  const SERVICE_NAME_PRESENT = 'gaia_present_service';
  const REASON_CODE_DUMMY = 0;

  /**
   * Offer報酬assetをプレゼントBOXへ送付する
   * @param DcsController $ctrl
   * @param int $user_id
   * @param array $reward 報酬アセットの情報（NoahOfferServiceInterface receive()の返値）// GAIA_MST_NOAH_OFFER_PRESENT のパラ
   *      array(
   *         asset_type_id   => int,     // 
   *         asset_id        => int,     // 
   *         asset_count     => int,     // 現状無い
   *      )
   * @param string $message 報酬用msg
   *
   * @return array
   *     {
   *         succeed => bool,
   *         data => array,  // 任意
   *     }
   */
  public function SendPresentOfferReward( DcsController $ctrl, $user_id, $reward, $message ) {
      $con = $ctrl->get('doctrine')->getConnection();
      $succeed = false; // 成否入れ

      // present送付処理
      //$con->beginTransaction();

      $reasonCode = self::REASON_CODE_DUMMY;

      try {
          $present_service = $ctrl->get(self::SERVICE_NAME_PRESENT);
          $succeed = count($present_service->addPresentBox($user_id, $reward, $reasonCode, $message));

          //$con->commit();
          $succeed = true;
      } catch(\Exception $e) {
          // プレゼント送付で失敗
          //$con->rollBack();
          error_log($e->getMessage());
      }

      return array(
        'succeed' => $succeed,
        'data' => null,
      );
  }



}

?>