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


/**
 * GAIA呼び出しサンプル(NoahBundle)用コントローラー
 *
 * @copyright Copyright (c) 2013 SEGA Networks Co., Ltd. All rights reserved.
 * @author Digital Connect Stadio
 */
class NoahController extends \Dcs\DcsController
{

    // 下記[BaseController]で作成されていた内容を移植
    // CmnAccountにloginCheckをしておく
    function loginCheck($con , $data) {
        $acc = $this->get('Dcs.CmnAccount');
        if( $acc->loginCheck( $data['sid'] ) == dcs\CmnAccount::SUCCESS) {
            return $acc;
        }
        return NULL;
    }
      
    // 受け取ったweb URLパラメタを連想配列へ展開
    function getDecryptedParam($data, $mode) {
        return json_decode( sec::decrypt($mode, $data),TRUE);
    }
    // 配列をjson文字列へ変更
    function getEncryptedParam($data) {
        $encode_data = json_encode($data);
        return sec::encrypt(sec\Mode::X_OR(),$encode_data);
    }
    // 配列をjsonレスポンスとして出力
    function outputEncryptedJson($data) {
        $encode_data = json_encode($data);
        $encode_data = sec::encrypt(sec\Mode::X_OR(),$encode_data);
        return $this->render('SegaAppBundle:System:jsonBase.html.twig' , array('data' => $encode_data ));
    }
    // デバック用のエラー出力用
    function error_trace($func_name, $msg) {
        error_log('[Noah]'.$func_name.': '.$msg);
    }

    /**
     * Noah サーバからのリクエスト受け取り
     *
     *
     * Noahへ返すHTTPステータス
     * 200 : 成功
     * 225 : 重複
     * 403 : 再試行が必要ないエラー
     * etc : 再試行
     */
    public function receiveAction( Request $request, $data )
    {
        $this->error_trace(__FUNCTION__, 'start');

/*
        // 本番ではこれはいらない
        // OAuth の認証を通してからここに来ているはず
        $mode = sec\Mode::RSA();
        $data = $this->getDecryptedParam($data, $mode);
*/

/*
        // Noah からののリクエストなのでログインの確認はできない
        $con = $this->get('doctrine')->getConnection();
        $acc = $this->loginCheck($con , $data);
        if($acc == NULL) {
            $this->error_trace(__FUNCTION__, 'error acc not found.');
            return new Response( '', 403 );
        }
        $uid = $acc->getUid();
*/

        // Noah に渡した guid は publicId なので user_id に変換
        //$uid = $data['guid'];
        $pid = $data['guid'];
        $user_service = $this->get('gaia.user.user_service');
        $uid = $user_service->getUserIdByPublicId($pid);

        //$this->error_trace(__FUNCTION__, 'pid :'.$pid );
        //$this->error_trace(__FUNCTION__, 'uid :'.$uid );

        // uid 確認
        if ( $uid == 0 ) {
            // public_id に該当するユーザは存在しない
            $this->error_trace(__FUNCTION__, 'Error publicId not found.');
            return new Response( '', 403 ); // 再試行が必要ないエラー?
        }

        // 
        $con = $this->get('doctrine')->getConnection();
        $con->beginTransaction(); // 失敗の可能性があるのでここから

        // 受け取りの基本処理(NOAH_OFFER_HISTORYへの確認と書き込み？)
        $noah_logic = new \Logic\NoahLogic;
        $receive_result = $noah_logic->getReceive($this->container, $uid, $data );

        $this->error_trace(__FUNCTION__, json_encode( $receive_result ) );

        // データ確認後の処理
        if(isset($receive_result)) {
            // プレゼントの付与 (各アプリごとの実装)

            // 報酬情報の補足
            // asset_count の情報がないので仮で'1'を設定
            $reward = array(array(
                'asset_type_id'   => $receive_result['asset_type_id'],
                'asset_id'        => $receive_result['asset_id'],
                'asset_count'     => (!empty($receive_result['asset_count'])) ? $receive_result['asset_count'] : 1 ,
            ));
            $present_message = (!empty($receive_result['present_message'])) ? $receive_result['present_message'] : $data['offer_name'];

            $this->error_trace(__FUNCTION__, json_encode( $reward ) );


            // 各アプリごとのデータ保存
            $dcs_noah = $this->get('Dcs.Noah');
            $reward_result = $dcs_noah->addOfferPresent( $this, $uid, $reward, $present_message );

            if ( $reward_result['succeed'] ){
                // 成功した場合の処理
            }
            else {
                // プレゼント付与に失敗
                // NOAH_OFFER_HISTORY も含めてここで元に戻す
                $con->rollBack();
                return new Response( '', 406 ); // 再試行？
            }
        }
        else {
            // 履歴に登録出来なかった(重複？)
            $this->error_trace(__FUNCTION__, 'Duplicated noah offer');

            // 念の為、元に戻す
            $con->rollBack();
            return new Response( '', 225 ); // 重複
        }

        $this->error_trace(__FUNCTION__, 'end');

        // ここまで来たら完了
        $con->commit();
        return new Response( '', 200 );
    }


    /*
     * NoahID の設定
     */
    public function setNoahIdAction( $data )
    {
        $this->error_trace(__FUNCTION__, 'start');

        // セッションが確保されているか確認する
        $mode = sec\Mode::RSA();
        $data = $this->getDecryptedParam($data, $mode );
        $con = $this->get('doctrine')->getConnection();
        $acc = $this->loginCheck($con, $data);
        if($acc == NULL) {
            $this->error_trace(__FUNCTION__, 'error acc not found.');
            return new Response();
        }

        // 直に突っこめる？
        $acc->setNoah( $data['noah_id'] );

         // 戻り値として
        $rpc = new dcs\Rpc();

        // Noah に guid として送信する為に GAIA_USER_DATA_ABOUT_FRIEND の Public_id 取得
        $public_id = $acc->getPublicId($acc->getUid());

        $res_data = array('noah_id' => $data['noah_id'],
                          'public_id' => $public_id
                          );

        $this->error_trace(__FUNCTION__, json_encode( $res_data ) );

        $rpc->data = $res_data;

//        return new Response( $rpc->toJson(sec\Mode::RSA()) );
        return new Response( $rpc->toJson(sec\Mode::X_OR()) );

/*
        // 自前でDBに
        // 何やら CmnAccount.php にある
        $uid = $acc->getUid();
        $noah_logic = new \Logic\NoahLogic;
        $noahId = $data['noah_id'];
        $noah_logic->setNoahId( $con, $uid, $noahId );
*/
    }

}


?>