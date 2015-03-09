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
 * GAIA呼び出しサンプル(PurchaseBundle)用コントローラー
 *
 * @copyright Copyright (c) 2013 SEGA Networks Co., Ltd. All rights reserved.
 * @author Digital Connect Stadio
 */
class PurchaseController extends \Dcs\DcsController
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
        error_log('[Purchase]'.$func_name.': '.$msg);
    }

    /**
     * 課金リスト取得
     */
    private function getPurchaseList($data, $os_type) {
        $this->error_trace(__FUNCTION__, 'start');
        // セッションが確保されているか確認する
        $data = $this->getDecryptedParam($data, sec\Mode::X_OR());
        $con = $this->get('doctrine')->getConnection();
        $acc = $this->loginCheck($con , $data);
        if($acc == NULL) {
            $this->error_trace(__FUNCTION__, 'error acc not found.');
            return new Response();
        }
        // 課金リストを取得する
        $purchase_logic = $this->get('Dcs.PurchaseLogic');
        $purchase_list = $purchase_logic->getList($this->container, $os_type);
        if(!isset($purchase_list)){
            return new Response();
        }
        else {
            // 各アプリごとのデータ修正
            $dcs_purchase = $this->get('Dcs.Purchase');
            $purchase_list = $dcs_purchase->updatePurchaseList($this, $os_type, $purchase_list);
        }
        $json_list = array('purchase_list' => $purchase_list);
        return $this->outputEncryptedJson($json_list);
    }
    public function listIosAction($data) {
        return $this->getPurchaseList($data, \Logic\PurchaseLogic::OS_TYPE_IOS);
    }
    public function listAndroidAction($data) {
        return $this->getPurchaseList($data, \Logic\PurchaseLogic::OS_TYPE_ANDROID);
    }

    /**
     * レシート情報確認・保存
     */
    private function getReceiptReceive($request, $data, $os_type) {
        $this->error_trace(__FUNCTION__, 'start');
        // セッションが確保されているか確認する
        $mode = sec\Mode::RSA();
        $data = $this->getDecryptedParam($data, $mode);
        $con = $this->get('doctrine')->getConnection();
        $acc = $this->loginCheck($con , $data);
        if($acc == NULL) {
            $this->error_trace(__FUNCTION__, 'error acc not found.');
            return new Response();
        }
        $uid = $acc->getUid();
        // POST受け取り
        $receipt_data = $request->request->get('receiptData');
        $receipt_data = $this->getDecryptedParam($receipt_data, $mode);
        if($receipt_data == null) {
            return new Response();
        }
        $data = array_merge($data, $receipt_data);
        //
        $purchase_logic = $this->get('Dcs.PurchaseLogic');
        $verify_list = $purchase_logic->getReceive($this->container, $uid, $data, $os_type);
        $rpc = new dcs\Rpc();
        // レシートデータ確認後の処理
        if(isset($verify_list)) {
            $this->error_trace(__FUNCTION__, 'verify_list');
            $verify_list['os_type_id'] = $os_type;
            // 各アプリごとのデータ保存
            $dcs_purchase = $this->get('Dcs.Purchase');
            $dcs_purchase_status = $dcs_purchase->updatePayment($this, $uid, $verify_list);
            // 保存状態
            if($dcs_purchase_status == dcs\Purchase::DBERROR){
                // 失敗ならエラーを返す
                $rpc->err = 1;
            }else{
                // 成功なら認証データを渡す
                $rpc->data = $verify_list;
            }
        }
        else {
            // 認証ができなかったらエラーを返す
            $rpc->err = 1;
        }
        //
        return new Response($rpc->toJson(sec\Mode::X_OR()));
    }
    public function receiveIosAction(Request $request, $data) {
        return $this->getReceiptReceive($request, $data, \Logic\PurchaseLogic::OS_TYPE_IOS);
    }
    public function receiveAndroidAction(Request $request, $data) {
        return $this->getReceiptReceive($request, $data, \Logic\PurchaseLogic::OS_TYPE_ANDROID);
    }


    /**
     * 年齢認証確認処理
     */
    public function findAgeLimitAction(Request $request, $data) {
        $this->error_trace(__FUNCTION__, 'start');
        // セッションが確保されているか確認する
        $mode = sec\Mode::X_OR();
        $data = $this->getDecryptedParam($data, $mode);
        $con = $this->get('doctrine')->getConnection();
        $acc = $this->loginCheck($con , $data);
        if($acc == NULL) {
            $this->error_trace(__FUNCTION__, 'error acc not found.');
            return new Response();
        }
        $uid = $acc->getUid();
        //
        $purchase_logic = $this->get('Dcs.PurchaseLogic');
        $age_limit_list = $purchase_logic->getAgeLimitUserData($this->container, $uid);
        // 年齢認証確認後の処理
        $rpc = new dcs\Rpc();
        if(!empty($age_limit_list)) {
            $rpc->data = $age_limit_list;
        }
        else {
            $age_limit_info = $purchase_logic->getAgeLimitInfomation($con);
            if(!empty($age_limit_info)) {
                $rpc->data = $age_limit_info;
            }
            else {
                // 認証ができなかったらエラーを返す
                $rpc->err = 1;
            }
        }
        //
        return new Response($rpc->toJson(sec\Mode::X_OR()));
    }

    /**
     * 年齢認証入力処理
     */
    public function updateAgeLimitAction(Request $request, $data) {
        $this->error_trace(__FUNCTION__, 'start');
        // セッションが確保されているか確認する
        $mode = sec\Mode::X_OR();
        $data = $this->getDecryptedParam($data, $mode);
        $con = $this->get('doctrine')->getConnection();
        $acc = $this->loginCheck($con , $data);
        if($acc == NULL) {
            $this->error_trace(__FUNCTION__, 'error acc not found.');
            return new Response();
        }
        $uid = $acc->getUid();
        //
        $purchase_logic = $this->get('Dcs.PurchaseLogic');
        $update_flg = $purchase_logic->updateAgeLimitUserData($con, $this->container, $uid, $data);
        //
        $rpc = new dcs\Rpc();
        $age_limit_list = ($update_flg
            ? array('input_year' => $data['year'], 'input_month' => $data['month'])
            : null);
        // 年齢認証入力処理後の処理
        if(!empty($age_limit_list)) {
            $rpc->data = $age_limit_list;
        }
        else {
            // 認証ができなかったらエラーを返す
            $rpc->err = 1;
        }
        //
        return new Response($rpc->toJson(sec\Mode::X_OR()));
    }

    /**
     * 課金可能判断処理
     */
    public function couldBuyPaymentAction(Request $request, $data) {
        $this->error_trace(__FUNCTION__, 'start');
        // セッションが確保されているか確認する
        $mode = sec\Mode::X_OR();
        $data = $this->getDecryptedParam($data, $mode);
        $con = $this->get('doctrine')->getConnection();
        $acc = $this->loginCheck($con , $data);
        if($acc == NULL) {
            $this->error_trace(__FUNCTION__, 'error acc not found.');
            return new Response();
        }
        $uid = $acc->getUid();
        //
        $purchase_logic = $this->get('Dcs.PurchaseLogic');
        $could_buy_payment = $purchase_logic->getPaymentLimitStatusData($this->container, $uid, $data);
        //
        $rpc = new dcs\Rpc();
        // 年齢認証入力処理後の処理
        if(!empty($could_buy_payment)) {
            $rpc->data = $could_buy_payment;
        }
        else {
            // 認証ができなかったらエラーを返す
            $rpc->err = 1;
        }
        //
        return new Response($rpc->toJson(sec\Mode::X_OR()));
    }

    /**
     * 特定商取引法についての表示データ
     */
    public function getSctlParamAction(Request $request, $data) {
        $this->error_trace(__FUNCTION__, 'start');
        // セッションが確保されているか確認する
        $data = $this->getDecryptedParam($data, sec\Mode::X_OR());
        $con = $this->get('doctrine')->getConnection();
        $acc = $this->loginCheck($con , $data);
        if($acc == NULL) {
            $this->error_trace(__FUNCTION__, 'error acc not found.');
            return new Response();
        }
        //
        $purchase_logic = $this->get('Dcs.PurchaseLogic');
        $sctl_param = $purchase_logic->getSctlParam($this->container);
        if(!isset($sctl_param)){
            return new Response();
        }
        $json_list = array('sctl_param' => $sctl_param);
        return $this->outputEncryptedJson($json_list);
    }
}


?>