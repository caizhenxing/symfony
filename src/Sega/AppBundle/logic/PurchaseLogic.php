<?php
namespace Logic;

use DateTime;
use Doctrine\DBAL\Connection;
use Dcs as dcs;


/**
 * 課金ロジック
 *
 * @copyright Copyright (c) 2014 SEGA Networks Co., Ltd. All rights reserved.
 * @author Digital Connect Stadio
 */
class PurchaseLogic extends \Dcs\Arpg\Logic{

    const OS_TYPE_IOS     = 1;
    const OS_TYPE_ANDROID = 2;

    const SERVICE_NAME_REQUEST = 'request';

    const SERVICE_NAME_PURCHASE_IOS         = 'gaia.purchase.ios.service';
    const SERVICE_NAME_PURCHASE_ANDROID     = 'gaia.purchase.android.service';
    const SERVICE_NAME_PURCHASE_LIMIT_INFO  = 'gaia.purchase.limit_information_service';
    // このサービスは本来ならこの場で呼び出すのは不適切な気がする
    const SERVICE_NAME_PURCHASE_DISCOUNT    = 'gaia.dao.purchase.mst_purchase_discount';


    const RECEIPT_VERIFY_CODE_SUCCESSFUL                = 0;     // 成功
    const RECEIPT_VERIFY_CODE_BAD_JSON                  = 21000; // JSONコード不正
    const RECEIPT_VERIFY_CODE_MALFORMED                 = 21002; // レシートデータ内にミスがある
    const RECEIPT_VERIFY_CODE_AUTH_ERROR                = 21003; // ユーザー認証ができなかった
    const RECEIPT_VERIFY_CODE_AUTH_FAILED               = 21004; // 自動更新用のレシートだけど不正
    const RECEIPT_VERIFY_CODE_SERVICE_UNAVAILABLE       = 21005; // レシート認証ができなかった
    const RECEIPT_VERIFY_CODE_INACTIVE                  = 21006; // すでに一度認証された自動更新用のレシート
    const RECEIPT_VERIFY_CODE_SANDBOX_RECEIPT_IN_PROD   = 21007; // 接続先がサンドボックス環境（本番時でしか出ないコード）
    const RECEIPT_VERIFY_CODE_PROD_RECEIPT_IN_SANDBOX   = 21008; // サンドボックス用のレシートを本番で確認した

    const TEST_ANDROID_RSA_KEY  = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA75GtxgzNEIxp1f6ABnwf9107mRVilHamwj+jtbe6DcajDmURkcObKwPJRpyh7NCnQw2PQXpA7uMXZPuNloXA62J8dpvfAR24liNIAmXZSGafbnv7cXLJNK0DdD+nsksgiXvUDl9vBenfF7OQ2vYuIzNBo1kGVnAjlaJns39IkQzawDbQoQk+QwAsvr3ZFY+qaFM3IHW7dcSikEPgQDWeidXbSwnw1fAqDIl1cCT6NrER4DnZqbm76CPhlE0NY6mDk1/8IAfBnqWFEosVqzxs9TTtO+3AeirjjiYJMMpWKf1EZ2PnfZ1p8hUp04SCDusEAz2qz+Tez6/luKSy7SJhzQIDAQAB';

    // デバック用のエラー出力用
    function error_trace($func_name, $msg) {
        error_log('[Purchase]'.$func_name.': '.$msg);
    }
    public function getList($container, $os_type) {
        // リストを取得して表示する
        $service_os = ($os_type == self::OS_TYPE_IOS ? self::SERVICE_NAME_PURCHASE_IOS : self::SERVICE_NAME_PURCHASE_ANDROID);
        $service = $container->get($service_os);
        // 課金リストを取得する
        $purchase_list = $service->purchaseList();
        if(empty($purchase_list)){
            return array();
        }
        // 課金リストからおまけ情報を取得する
        $purchase_hash = array();
        $service = $container->get(self::SERVICE_NAME_PURCHASE_DISCOUNT);
        $date = date('Y-m-d H:i:s', time());
        foreach ($purchase_list as $key => $value) {
            $data_id = $value['purchase_item_data_id'];
            $discount_assets = array('discount_assets' => $service->selectPurchaseDiscountItemData($data_id, $date));
            $purchase_hash[] = array_merge($value, $discount_assets);
        }
        // おまけリストと課金リストを結合して新しいリストを生成する
        return $purchase_hash;
    }

    public function getReceive($container, $uid, $data, $os_type) {
        if(!isset($uid) || !isset($data)) {
            return null;
        }

        if($os_type == self::OS_TYPE_IOS) {
            // レシートデータを確認する
            $receipt = $data['receipt'];
            $this->error_trace(__FUNCTION__, 'receipt: '.$receipt);
            if(empty($receipt)) {
                return null;
            }

            $service = $container->get(self::SERVICE_NAME_PURCHASE_IOS);
            return $service->receive($uid, $receipt);
        }
        else {
            // レシートデータを確認する
            $signedData = $data['signedData'];
            $signature = $data['signature'];
            $this->error_trace(__FUNCTION__, 'signedData: '.$signedData);
            $this->error_trace(__FUNCTION__, 'signature: '.$signature);

            $service = $container->get(self::SERVICE_NAME_PURCHASE_ANDROID);
            return $service->receive($uid, $signedData, $signature);
        }
    }

    /**
    * 年齢認証情報を取得する
    * @param 
    * @return array
    */
    public function getAgeLimitUserData($container, $uid) {
        $service = $container->get(self::SERVICE_NAME_PURCHASE_LIMIT_INFO);
        $data = $service->selectUserBirthDate($uid);
        // 取得に成功している場合はここで情報を返して終了
        if(!empty($data)) {
            $split_array = explode('-', $data);

            $result = array(
                'input_year'    => $split_array[0],
                'input_month'   => $split_array[1],
                );
            return $result;
        }
        return array();
    }

    /**
    * 年齢認証確認文言を取得する
    * @param 
    * @return array
    */
    public function getAgeLimitInfomation(Connection $con) {
        // 年齢制限の設定状態を取得する
        $ptmt = $con->prepare('select * from `GAIA_MST_PURCHASE_AMOUNT_LIMIT_BY_AGE` where `start_date` <= ?');
        $ptmt->execute(array(date('Y-m-d H:i:s')));
        $data = $ptmt->fetchAll();
        $ptmt->closeCursor();
        $amount_msg = '';
        $Text = $this->get('Arpg.Logic.Util.Text');
        if(!empty($data)) {
            $age_sort_list = array();
            foreach ($data as $key => $value) {
                $age_sort_list[] = $value['age_under'];
            }
            array_multisort($age_sort_list, $data);

            $amount_msg = $Text->getText(10600);
            for ($i=0; $i<count($data) ;$i++) {
                if($i > 0) {
                    $amount_msg .= '、';
                }
                $amount_msg .= $Text->getText(10601,['[age]'=>$data[$i]['age_under'],'num'=>$data[$i]['amount_limit']]);
            }
            $amount_msg .= $Text->getText(10602);
        }
        $msg = $Text->getText(10603,['arg_mes'=>$amount_msg]);
        $result = array(
            'error'         => 1,
            'confrim_msg'   => $msg,
            );
        return $result;
    }

    /**
    * 年齢認証設定を保存する
    * @param 
    * @return boolean
    */
    public function updateAgeLimitUserData(Connection $con, $container, $uid, $data) {
        if(empty($data['year']) || empty($data['month'])) {
            return false;
        }

        // ユーザーデータを書き換える
        $birthday = $data['year'].'-'.$data['month'].'-01';
        $service = $container->get(self::SERVICE_NAME_PURCHASE_LIMIT_INFO);
        $service->setUserBirthDate($uid, new DateTime($birthday));

        return true;
    }

    /**
    * 年齢認証設定上で課金可能か検索する
    * @param 
    * @return array
    */
    public function getPaymentLimitStatusData($container, $uid, $data) {
        $service = $container->get(self::SERVICE_NAME_PURCHASE_LIMIT_INFO);
        $Text = $this->get('Arpg.Logic.Util.Text');
        $now_date = date('Y-m-d');
        // ユーザーの今月の使用金額を取得
        $sum_payment = $service->selectPurchaseAmount($uid, new DateTime($now_date));
        // ユーザーが使用制限内に入っているか調べる
        $age_limt_price = $service->selectPurchaseAmountLimit($uid);
        // 年齢制限の枠内なら専用の処理を行う
        if($age_limt_price > 0) {
            // ユーザーの状態から現在の制限状態を割り出す
            if($sum_payment > $age_limt_price || empty($data['os_type'])) {
                // 使用制限超過[エラー文言ができてないよ(購入不可の場合)]
                return array('error' => 1, 'dialog_msg' => $Text->getText(10604));
            }
            // 今回購入する商品の金額で制限を超過しないか調べる
            $data['os_type'] = ($data['os_type'] == 'iOS' ? self::OS_TYPE_IOS : self::OS_TYPE_ANDROID);
            $purchase_list = $this->getList($container, $data['os_type']);
            $product_price = null;
            foreach ($purchase_list as $key => $value) {
                if($value['item_identifier'] == $data['product_id']) {
                    $product_price = $value['price'];
                    break;
                }
            }
            if(empty($product_price)) {
                return array();
            }
            if($sum_payment + $product_price > $age_limt_price) {
                // 今回購入分を入れると超過[エラー文言ができてないよ(購入不可の場合)]
                return array('error' => 1, 'dialog_msg' => $Text->getText(10604));
            }
        }

        return array('payment_flg' => true);
    }


    /**
    * 特定商取引法についての表示データ(Specific Commercial Transactions Law)
    * 定義ファイルは AppBundle\Resources\config\const\purchase_messages.yml
    * @param 
    * @return array
    */
    public function getSctlParam($container) {
        $ymlLoader = new dcs\YmlFileLoader();
        $params = $ymlLoader->load('const/purchase_messages.yml');
        $list = $params['purchase_messages'];

        return $list['specific_commercial_transactions_law'];
    }

}
//-----------------------------------------------------------------------------
// End Of File
//-----------------------------------------------------------------------------
?>