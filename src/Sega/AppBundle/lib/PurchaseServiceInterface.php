<?php
namespace Dcs;


/**
 * 課金処理サービスインターフェース
 */
interface PurchaseServiceInterface {
    /**
     * 課金リスト取得時のアプリ別処理
     * @param DcsController $dcsController デジコネベースコントローラー
     * @param int $os_type 認証データ
     * @param array $purchase_list 課金リスト
     *      array(
     *          'purchase_item_data_id' => int,
     *          'os_type_id'            => int,
     *          'name'                  => string,
     *          'price'                 => double,
     *          'item_identifier'       => string,
     *          'asset_type_id'         => int,
     *          'asset_id'              => int,
     *          'asset_count'           => int,
     *          'effective_from'        => datetime,
     *          'effective_to'          => datetime,
     *          'discount_assets'       => array(
     *              'purchase_discount_id'  => int,
     *              'purchase_item_data_id' => int,
     *              'asset_type_id'         => int,
     *              'asset_id'              => int,
     *              'asset_count'           => int
     *              )
     *      )
     */
    public function onGetPurchaseListCompleted($dcsController, $os_type, $purchase_list);
    
    /**
     * レシート確認後のアプリ別処理
     * @param DcsController $dcsController デジコネベースコントローラー
     * @param int $uid ユーザID
     * @param array $verify 認証データ
     *      array(
     *          'purchase_item_data_id' => int,
     *          'os_type_id'            => int,
     *          'name'                  => string,
     *          'price'                 => double,
     *          'item_identifier'       => string,
     *          'asset_type_id'         => int,
     *          'asset_id'              => int,
     *          'asset_count'           => int,
     *          'current_time'          => datetime,
     *          'purchase_date_ms'      => int,
     *          'product_id'            => int,
     *          'transaction_id'        => int,
     *          'discount_assets'       => array(
     *              'purchase_discount_id'  => int,
     *              'purchase_item_data_id' => int,
     *              'asset_type_id'         => int,
     *              'asset_id'              => int,
     *              'asset_count'           => int
     *              )
     *      )
     */
    public function onPaymentVerifyCompleted($dcsController, $uid, $verify);
}

?>