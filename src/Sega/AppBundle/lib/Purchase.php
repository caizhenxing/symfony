<?php
namespace Dcs;


/**
 * 課金処理個別データ書き込み処理
 */
class Purchase {
	const SUCCESS = 0;	/// 成功
	const DBERROR = 1;	/// データベースエラー

	private $serv;	///< サービス
	
	/**
	 * コンストラクタ
	 */
	public function __construct(PurchaseServiceInterface $interface) {
		$this->serv = $interface;
	}

	/**
	 * 課金リスト
	 * @param array $purchase_list 課金リスト配列
	 * @return
	 */
	public function updatePurchaseList($dcsController, $os_type, $purchase_list){
		return $this->updatePurchaseListInner($dcsController, $os_type, 10, $purchase_list);
	}
	private function updatePurchaseListInner($dcsController, $os_type, $count, $purchase_list){
		if($count < 1) return Purchase::DBERROR;
		try{
			$purchase_list = $this->serv->onGetPurchaseListCompleted($dcsController, $os_type, $purchase_list);
		}catch(\Exception $e){
			error_log('[server]Dcs.Purchase - updatePurchaseList -> '.$e->getMessage()."\n");
			return $this->updatePurchaseListInner($dcsController, $os_type, $count-1, $purchase_list);
		}
		// while ($this->login($uid) != Purchase::SUCCESS){
		// 	sleep(3);
		// }
		return $purchase_list;
	}
	
	/**
	 * ユーザー生成
	 * @param array $verify レシート認証後の情報配列
	 * @return int アップデート状態
	 */
	public function updatePayment($dcsController, $uid, $verify){
		return $this->updatePaymentInner($dcsController, $uid, 10, $verify);
	}
	private function updatePaymentInner($dcsController, $uid, $count, $verify){
		if($count < 1) return Purchase::DBERROR;
		$uuid = '';
		$con = $dcsController->get('doctrine')->getConnection();
		try{
			if(isset($verify['already_inserted']) && $verify['already_inserted']){
				return Purchase::SUCCESS;
			}
			$this->serv->onPaymentVerifyCompleted($dcsController, $uid, $verify);
			if($con->isTransactionActive())
				$con->commit();
		}catch(\Exception $e){
			error_log('[server]Dcs.Purchase - updatePayment -> '.$count.': '.$e->getMessage()."\n");
			if($con->isTransactionActive())
				$con->rollBack();
			return $this->updatePaymentInner($dcsController, $uid, $count-1, $verify);
		}
		// while ($this->login($uid) != Purchase::SUCCESS){
		// 	sleep(3);
		// }
		return Purchase::SUCCESS;
	}
}
?>