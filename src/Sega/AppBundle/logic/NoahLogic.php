<?php
namespace Logic;

use DateTime;
use Gaia\Bundle\CommonBundle\Exception\GaiaException;
use Doctrine\DBAL\Connection;
use Dcs as dcs;

/**
 * @copyright Copyright (c) 2014 SEGA Networks Co., Ltd. All rights reserved.
 * @author Digital Connect Stadio
 */

/**
 * Gaia向け Noah ロジック
 *
 */
class NoahLogic {

    const SERVICE_NAME_REQUEST = 'request';
    const SERVICE_NAME_NOAH    = 'gaia.noah.service';

    // デバック用のエラー出力用
    function error_trace($func_name, $msg) {
        error_log('[Noah]'.$func_name.': '.$msg);
    }


    //-----------------------------------------------------------------------------
    // Noahからの受け取り
    // HISTORY への登録と重複チェック
    // Gaia側の eventDispatch() が生きているので注意が必要？
    //-----------------------------------------------------------------------------
    /*
     * @param int $uid
     * @param array $data オファー情報(Noahサーバからの情報)
     *      array(
     *         guid        => int,   // アプリからNoahサーバへ SetGUID()したID
     *         action_id   =>    ,  // リワード元のID。インストールされたアプリのアクションID が通知されます。
     *         points      =>    ,  // 獲得ポイント。獲得額はNoah 管理画面で設定を行います。
     *         user_act_id =>    ,  // リワード通知管理ID。Noah 内部でリワード処理を管理しているID です。コールバック通知に関して何か問題が発生した場合は、こちらのID を合わせてご連絡ください。
     *         offer_name  =>    ,  // ワード元のオファー名称。user_action_id に紐づいてNoah 管理画面で設定された文字列です。ユーザーに対する通知などに利用できます。
     *         app_name    =>    ,  // リワード元のアプリの名称です。ユーザーに対する通知などに利用できます。
     *         vc_id       =>    ,  // points の基準となる仮想通貨のID です。Noah 管理画面で設定し、空の場合もあり得ます。
     *      )
     * @return array $receive_result 報酬アセットの情報（NoahOfferServiceInterface receive()の返値）// GAIA_MST_NOAH_OFFER_PRESENT のパラ
     *      array(
     *         asset_type_id   => int,     // 
     *         asset_id        => int,     // 
     *         asset_count     => int,     // 現状無い
     *         present_message => string   // 現状無い
     *      )
     */
    public function getReceive($container, $uid, $data )
    {
        if(!isset($uid) || !isset($data)) {
            return null;
        }

        $actionId     = $data['action_id'];
        $userActionId = $data['user_action_id'];

        //$this->error_trace(__FUNCTION__, 'action_id: '.$actionId );
        //$this->error_trace(__FUNCTION__, 'user_action_id: '.$userActionId );

        // Gaia Vendor History の記述など
        $reward;
        $service = $container->get(self::SERVICE_NAME_NOAH);
        try {
            $reward = $service->receive($uid, $actionId, $userActionId);
            //$this->error_trace(__FUNCTION__, 'OK?' );
        } catch(GaiaException $e) {
            // 重複登録しようとしたりすると例外発生？
            $this->error_trace(__FUNCTION__, $e->getMessage() );
/*
            // 現状エラーは一つしかない
            // 受取済などの場合
            error_log('[GaiaException]'.$e->getMessage());
            error_log('[GaiaException]'.$e->getCode());
            switch($e->getCode()) {
                case ErrorCodes::getError('NOAH', 'ALREADY_OFFERED'):
                    $result_status = self::RESULT_INVALID_PARAM;
                break;
           }
*/
            return null;
        }

        return $reward;
    }

    //-----------------------------------------------------------------------------
    // Noahからの受け取りを元に
    // Offer_Present
    //-----------------------------------------------------------------------------
    public function addOfferPresent( &$controller, $con, $uid, $reward, $message )
    {
        if(!isset($uid) || !isset($reward)) {
            return null;
        }
/*
        $presents = array(array(
          'asset_type_id' => $reward['asset_type_id'],
          'asset_id'      => $reward['asset_id'],
          'asset_count'   => 1,
        ));
*/
        $presents = array(
          'asset_type_id' => $reward['asset_type_id'],
          'asset_id'      => $reward['asset_id'],
          'asset_count'   => 1,
        );


        // プレゼント付与
        $present_logic = new \Logic\PresentLogic( $controller, $con );
        $result;
        try {
            $this->error_trace(__FUNCTION__, json_encode( $presents ) );

            $result = null;
//            $result = $present_logic->addPresentbox( $uid, $presents, $message );

        } catch(\Exception $e) {
            $this->error_trace(__FUNCTION__, $e->getMessage() );
            return null;
        }

        return $result;
    }


}
//-----------------------------------------------------------------------------
// End Of File
//-----------------------------------------------------------------------------
?>