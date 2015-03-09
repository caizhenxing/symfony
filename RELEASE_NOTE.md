リリースノート - Gaia
=====================

version 1.1.3
-----------------

* HandlerSocketBundle
    * Table, Query, HandlerSocketService で __toString の多次元配列対応
* UserBundle
    * UserService の以下の API をスタブ実装から本実装に変更
        * getUserIdByPublicId
        * getPublicId
        * getAccountData
        * getAccountDataByUUID
        * getAccountDataByPublicId
* PresentBundle
    * PresentService の以下の API をスタブ実装から本実装に変更
        * selectPresentInformations (戻り値に "used_time" 追加)
        * getPresentHoldLimit (1.1.2 の時点で本実装でした)
    * PresentService に以下の API を追加
        * selectPresentCount
    * PresentService#getPresentList の戻り値に "used_time" 追加 
    ※ used_time: プレゼントボックスから取り出された日時、created_time: プレゼントボックスに追加された日時
* BbsBundle
    * NonThreadBbsService からブラックリストチェック機能を外部化
        * BlackListService#isBlackListedUserId() を追加
        * NonThreadBbsService の API から不要となった引数を削除

version 1.1.2
-----------------

* CommonBundle
    * CryptMock が CryptInterface を implements していなかったのを修正
* PresentBundle
    * addPresentReserve の戻り値でPKを返すように変更
    * PresentService に以下の新規 API 群のスタブ実装を提供(src/Gaia/Bundle/PresentBundle/Service/PresentServiceInterface.php 参照)
        * selectPresentInformations: 指定されたステータスに合致するプレゼントの情報を参照
        * getPresentHoldLimit: プレゼントボックスの最大保持件数を取得
* FriendBundle
    * 既存のフレンドからのオファーを承認した際のチェックが最も先になるように変更
    * 存在しないフレンドをremoveした場合の例外を次の様に変更
        * GaiaException -> FriendNotFoundException(GaiaException のサブクラス)
    * フレンド履歴、フレンドオファー履歴に関する新規 API 群のスタブ実装を提供
        * 詳細、利用方法については以下のインターフェース、テストを参照のこと
            * src/Gaia/Bundle/FriendBundle/Service/FriendHistoryServiceInterface.php
            * src/Gaia/Bundle/FriendBundle/Service/FriendOfferHistoryServiceInterface.php
            * src/Gaia/Bundle/FriendBundle/Tests/Service/FriendHistoryServiceTest.php
            * src/Gaia/Bundle/FriendBundle/Tests/Service/FriendOfferHistoryServiceTest.php
* AtomCampaignBundle
    * キャンペーンコードチェックの方法にミスがあったのを修正
* UserBundle
    * 引き継ぎIDの設定を HS を利用するように変更
    * チェイン・クロニクルの負荷テストを受けて GAIA_USER_SESSION 更新を HS から SQL 利用に変更
    * UserService に以下の新規 API 群のスタブ実装を提供(src/Gaia/Bundle/UserBundle/Service/UserServiceInterface.php 参照)
        * getUserIdByPublicId: public_id をキーとして ユーザIDを取得
        * getPublicId: ユーザIDをキーとして公開IDを取得
        * getAccountData: ユーザIDをキーとしてユーザアカウント情報を取得
        * getAccountDataByUUID: UUID をキーとしてユーザアカウント情報を取得
        * getAccountDataByPublicId: public_id をキーにユーザアカウント情報を取得
* BbsBundle: 新規追加
    * スレッド方式でないBBSサービスのインターフェース、スタブ実装を提供
    * 詳細、使用方法については以下のインターフェース、テストを参照のこと
        * src/Gaia/Bundle/BbsBundle/Service/NonThreadBbsServiceInterface.php
        * src/Gaia/Bundle/BbsBundle/Service/BlackListServiceInterface.php
        * src/Gaia/Bundle/BbsBundle/Tests/Service/NonThreadBbsServiceStubTest.php
        * src/Gaia/Bundle/BbsBundle/Tests/Service/BlackListServiceStubTest.php


version 1.1.1
-----------------

* UserBundle で以下を修正・変更
    * ユーザ作成時に GAIA_USER_ACCOUNT.updated_time が設定されなかったのを修正
    * GAIA_USER_ACCOUNT.created_time カラムを追加、ユーザ作成日時を設定するように変更
    * 引き継ぎでアカウントの移動元、先が同じ UUID の場合に失敗していたのを修正


version 1.1.0
-----------------

* 以下のバンドルでprod環境等で誤りとなる設定ファイルが含まれていたのを修正
    * ExternalInterfaceBundle
    * PurchaseBundle
* 各サービスの設定サンプルを以下に配置
    * app/config/gaia.yml
    * app/config/gacha.yml
    * app/config/apollo.yml
    * app/config/pnote.yml
    * app/config/present.yml
    * app/config/response_format.yml
* マスタメンテナンスに関連する以下のテーブルを追加
    * GAIA_MST_MNT_USER_ADMIN_ROLE
    * GAIA_MST_MNT_USER_ADMIN_PRIVILEGE
    * GAIA_MNT_USER_ADMIN
    * GAIA_MNT_USER_ADMIN_ROLE
    * GAIA_MNT_USER_ADMIN_ROLE_PLIVILEGE
    * GAIA_USER_BAN_HISTORY
* CommonBundle の以下のサンプルリスナの利用を任意に変更。利用する際の設定サンプルをCommonBundleから分離
    * リスナクラス： Gaia\Bundle\CommonBundle\Listeners\UserIdRequestListener
    * 設定サンプル： app/config/gaia.yml
* HandlerSocketBundle で以下を対応
    * パフォーマンス向上のため、1リクエスト内では同一のコネクションを利用するように変更
* MemcacheBundle で以下を対応
    * 引数を Array に対応可能に変更
    * ignore を指定した場合、プロキシ対象クラスを呼び出していなかったのを修正
    * 空配列のキャッシュに対応できていなかったのを修正
* HandlerSocketBundle で以下を対応
    * debug 環境で実行時間のプロファイリングを追加
* UserBundle で以下を対応
    * ユーザ登録時に生成される引き継ぎIDの長さ10文字に変更
    * UserTakeOverService#takeOver 実施時にUUID、セッションIDを削除するように修正
    * UserTakeOverService#takeOver の引数に誤りがあったのを修正
        -    function takeOver($uid, $password, $newUuid)
        +    function takeOver($takeOverId, $password, $newUuid)
* PresentBoxBundle で以下を対応
    * 利用済みのプレゼントはレスポンスに含めないように修正
* PurchaseBundle で以下を対応
    * 購入可能アイテムをマスタテーブルから取得するように修正
* AtomCampaignBundle で以下を対応
    * AtomCampaignService#auth でレスポンスに"present_message" を追加
    * コードチェックとシリアルチェックが片方しか実施できなくなっていたのを修正


version 1.0.4
--------------

* 誤って GAIA_USER_OPEN_ID を利用していた箇所を修正


version 1.0.3
--------------

* 暗号化関連のロジックを GaiaSecurityBundle に分離
* GaiaPurchaseBundle で商品の有効期間を追加
* 不要な GAIA_USER_OPEN_ID を削除
* 次のテーブルに初期データを追加
    * GAIA_MST_ATOM_CAMPAIGN_TYPE
    * GAIA_MST_OS_TYPE


version 1.0.2
--------------

* GaiaMemcachedBundle の不具合修正


version 1.0.1
--------------

* テーブル名の命名規約変更
    1, プレフィックス "GAIA_" を追加
    2, テーブル名は英数字大文字で設定
* test, prod 環境での設定ファイルの定義漏れの修正

copyright
---------

copyright Copyright (c) 2013 SEGA Networks Co., Ltd. All rights reserved.
