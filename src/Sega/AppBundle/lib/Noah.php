<?php
namespace Dcs;


/**
 * Noah 処理個別データ書き込み処理
 *
 */
class Noah {
    const SUCCESS = 0; /// 成功
    const DBERROR = 1; /// データベースエラー

    private $serv;  ///< サービス

    /**
     * コンストラクタ
     */
    public function __construct(NoahServiceInterface $interface) {
        $this->serv = $interface;
    }

    /**
     * オファープレゼントの付与処理
     * @param array $reward 報酬アセットの情報
     * @return
     */
    public function addOfferPresent($dcsController, $uid, $reward, $message ){
        return $this->addOfferPresentInner($dcsController, $uid, $reward, $message );
    }
    private function addOfferPresentInner($dcsController, $uid, $reward, $message ){

        $reward_result = $this->serv->SendPresentOfferReward( $dcsController, $uid, $reward, $message );
        return $reward_result;
    }

}
?>