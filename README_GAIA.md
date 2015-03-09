Gaia インストール済み Symfony パッケージ
========================================

当パッケージは Symfony Standard Edition 2.3.12 に以下をインストールしたものです。

* Gaia v1.3.13
* Gaia が依存するバンドル
* Gaia インストール動作確認用バンドル(InstallBundle)
* Gaia 管理ツール利用サンプルバンドル(ManagementToolBundle  -- ★タイトル側の管理ツールバンドル)

Gaiaパッケージ 実行環境作成方法
----------------

1. Dev Guide を参考の上、前提となるミドルウェアをインストールしてください。
2. 当パッケージを解凍し、Apache 上で動作する場所に配置してください。
3. 次のDDLを実行し、サンプルで利用する "gaia" データベースを作成してください。
   "vendor/gaia/ddl/gaia.ddl"
4. 次のymlを編集し、上記手順3. で作成した接続先DB情報に編集してください。
   "app/config/ddl/parameters.yml"
5. ブラウザより次の URL にアクセスしてGaiaが正常にインストールされている事を確認してください。
   "http://[パッケージの web ディレクトリへの URL]/app_dev.php/install/check"
   1) 成功の場合："GAIAインストール済みSymfonyパッケージのインストールが成功しました。"
   2) 失敗の場合："GAIA_USER_ACCOUNTテーブルが見つかりませんでした。"
                  再度、上記手順1.～4.をご確認ください。

管理ツールサンプル実行方法
----------------

1. Gaia 実行環境作成方法の手順を行う。
2. 管理ツールのTop画面は"http://[パッケージの web ディレクトリへの URL]/app_dev.php/session/login" でアクセス可能です。
   初期ログインID:admin
   パスワード:88888888
3. タイトル独自の管理ツールを拡張する場合は、ManagementToolBundle を改修してください。
   別途、ご提供させて頂いております Developer_Guide をご参照お願いいたします。
4. リソースファイルフォルダ内のcss、image、jsファイル等を編集した場合はパッケージのルートからコマンドラインより
   `app/console assets:install` を実行することでインストール可能です。

パッケージ構成
---------------

パッケージ構成は以下の通りです。
なお、Gaia を利用するにあたって設定が必要なパラメータは "app/config/gaia.yml" および、
Gaia 利用サンプルバンドルの "Resources/config" 以下のファイルを確認してください。

   .
  ｜
  ├─app
  ｜  └─config   -- アプリケーション全体のコンフィグ
  ｜      ├─config.yml      -- 管理ツールのセッション有効期限、タイトル名の編集等
  ｜      └─parameters.yml  -- 接続先DB情報の編集等
  ├─src
  ｜  ├─Acmes
  ｜  ｜  └─DemoBundle   -- Symfony2 のサンプルバンドル
  ｜  └─Title
  ｜     └─GaiaBundle
  ｜        └─InstallBundle          -- Gaiaのインストール動作確認用バンドル
  ｜        └─ManagementToolBundle   -- ★タイトル側の管理ツールバンドル
  ｜            ├─Command     -- バッチクラスフォルダ
  ｜            ├─Constant    -- 定数クラスフォルダ
  ｜            ├─Controller  -- コントローラフォルダ
  ｜            ├─Dao         -- Daoクラスフォルダ
  ｜            ├─DependencyInjection
  ｜            ｜  └─TitleMamnagementToolExtension.php  -- ymlファイル追加時に対象をロードするために追記
  ｜            ├─Exception
  ｜            ｜  └─TitleErrorMessages.php             -- エラーメッセージの設定クラス
  ｜            ├─Model    -- モデルクラスフォルダ
  ｜            ｜  └─AssetMasterModel.php               -- アセット情報取得用モデル
  ｜            ├─Resources
  ｜            ｜  ├─config  -- 設定ファイルフォルダ
  ｜            ｜  ｜  ├─gaia_model.yml            -- GAIA管理ツールのモデルを上書き追記
  ｜            ｜  ｜  ├─gaia_rouging.yml          -- GAIA管理ツールのコントローラのルーティングを上書き追記
  ｜            ｜  ｜  ├─gaia_services.yml         -- GAIA管理ツールのサービスを上書き追記
  ｜            ｜  ｜  ├─gaia_view_parameters.yml  -- GAIA管理ツールのサイドバー、タブメニューを上書き追記
  ｜            ｜  ｜  ├─title_controllers.yml     -- タイトル側に追加するコントローラを追記
  ｜            ｜  ｜  ├─title_error_messages.yml  -- タイトル独自のエラーメッセージを追記
  ｜            ｜  ｜  ├─title_model.yml           -- 追加したモデルを追記
  ｜            ｜  ｜  ├─title_rouging.yml         -- コントローラのルーティングを追記
  ｜            ｜  ｜  ├─title_services.yml        -- 追加したサービスを追記
  ｜            ｜  ｜  └─title_view_parameters.yml -- サイドバー、タブメニューの追記
  ｜            ｜  ├─public  -- リソースファイルフォルダ
  ｜            ｜  ｜  ├─css     -- cssファイルフォルダ
  ｜            ｜  ｜  ├─images  -- imageファイルフォルダ
  ｜            ｜  ｜  └─js      -- jsファイルフォルダ
  ｜            ｜  └─views   -- twigファイルフォルダ
  ｜            ｜      └─sample  -- 実装サンプルtwigフォルダ
  ｜            ├─Service  -- サービスクラスフォルダ
  ｜            ├─Tests    -- テストクラスフォルダ
  ｜            └─Util     -- ユーティリティクラスを追加
  ├─vender
  │  ├─composer
  │  ├─doctrine
  ｜  ├─gaia
  ｜  ｜  └─src
  ｜  ｜      └─Gaia
  ｜  ｜          └─Bundle
  ｜  ｜              ├─xxxxBundle
  ｜  ｜              ├─ManagementToolBundle(Gaia管理ツールバンドル)
  ｜  ｜              └─zzzzBundle
  │  ├─ hwi
  │  ├─ incenteev
  │  ├─ jdorn
  │  ├─ jms
  │  ├─ kriswallsmith
  │  ├─ leaseweb
  │  ├─ monolog
  │  ├─ psr
  │  ├─ sensio
  │  ├─ swiftmailer
  │  └─ symfony
  │  └─ twig
  └─ web

copyright Copyright (c) 2013 SEGA Networks Co., Ltd. All rights reserved.

