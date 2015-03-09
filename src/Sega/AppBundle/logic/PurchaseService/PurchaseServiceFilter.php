<?php

namespace Logic\PurchaseService;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dcs\PurchaseServiceInterface;
use Dcs\Security as sec;
use Dcs as dcs;
use \Logic\Util\Mail;

class PurchaseServiceFilter implements PurchaseServiceInterface {
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
     *              array(
     *                  'purchase_discount_id'  => int,
     *                  'purchase_item_data_id' => int,
     *                  'asset_type_id'         => int,
     *                  'asset_id'              => int,
     *                  'asset_count'           => int
     *              )
     *          )
     *      )
     */
    public function onGetPurchaseListCompleted($dcsController, $os_type, $purchase_list) {
        // アセット情報は残さずに消した方がデータ管理上安全
        foreach ($purchase_list as $key => &$value) {
            unset($value['asset_type_id']);
            unset($value['asset_id']);
            //unset($value['asset_count']);
        }
        return $purchase_list;
    }

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
     *              array(
     *                  'purchase_discount_id'  => int,
     *                  'purchase_item_data_id' => int,
     *                  'asset_type_id'         => int,
     *                  'asset_id'              => int,
     *                  'asset_count'           => int
     *              )
     *          )
     *      )
     */
    public function onPaymentVerifyCompleted($dcsController, $uid, $verify) {
    	$PlayerStatus = $dcsController->get('Arpg.Logic.Util.PlayerStatus');
    	$Text = $dcsController->get('Arpg.Logic.Util.Text');
    	$os_cp = self::std_android_cp;
    	$cmn_cp = 0;
    	if(intval($verify['os_type_id']) == 1)
    		$os_cp = self::std_apple_cp;
    	if(isset($verify['discount_assets']) && is_array($verify['discount_assets'])){
    		foreach($verify['discount_assets'] as $ddata){
    			if(!isset($ddata['asset_count']) || !is_numeric($ddata['asset_count'])) continue;
    			$cmn_cp += intval($ddata['asset_count']);
    		}
    	}
    	$PlayerStatus->addMulti([
				[$uid,$os_cp,intval($verify['asset_count'])],
    			[$uid,self::std_cp,$cmn_cp]
    	]);
    	$dcsController->get('Arpg.Logic.Util.Mail')->send([
			[
				'type' => Mail::TYPE_MAIL,
    			'from' => $Text->getText(10400),
    			'to' => [$uid],
    			'subject' => $Text->getText(10503),
    			'message' => $Text->getText(10502,['[cp]' => (intval($verify['asset_count'])+$cmn_cp)]),
			]
    	]);
    }
    const std_cp = 10001;
	const std_apple_cp = 10010;
	const std_android_cp = 10011;
}

?>