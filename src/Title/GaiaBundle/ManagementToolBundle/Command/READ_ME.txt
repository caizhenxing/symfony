TODO 当ディレクトリにはタイトル独自のバッチ起動処理クラスを作成してください。
以下は、Gaia管理ツールの全ユーザアイテム補填バッチの作成手順を例とします。
1. バッチ処理を作成
   vendor\gaia\src\Gaia\Bundle\ManagementToolBundle\Command\AllUserItemFillCommand.php
2. バッチ起動用のシェルを追加
   vendor\gaia\itemFillBatch.sh
   ※ タイトル側で追加時は、タイトルパッケージのappディレクトリと同じ階層に配置する。
3. cronに上記2.のシェル起動スケジュールを登録
