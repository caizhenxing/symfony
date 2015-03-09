<?php 
/**
 * コンソールのローカル機能を実装する
 * @param string $type 機能文字列
 * @return boolean true：機能あり false:機能がなかったためデフォルトのコンソールを実行
 */
function local_console($type){
	if(strcmp($type,"setLocalSample") == 0){
		
		echo  "phpから送信".(isset($_POST["mes"])?$_POST["mes"]:"unknown");// 表示結果がjavascript側のconnectの結果受け取り関数に送信される

		return true;
	}
	
	return false;
}

/**
 * コンソールのローカルGUIを実装する
 */
function local_gui(){
?>
<script>
/*

===========================================================
PHP側のlocal_consoleに接続を行う
@param string waitMessage 通信待ちの時に表示するメッセージ
@param string type php側のlocal_consoleに渡るtype文字列
@param object post php側の$_POSTになる連想配列
@param function callback 結果受け取り関数 function(string data) の形で取得できる
function connect(waitMessage,type,post,callback);

===========================================================
右のログ領域にログを出力する
@param string mes 表示するメッセージ
@param string title ログタイトル
addLog(mes,title);

 */
	setLocalSample = function(){
		connect("ローカルコンソールサンプル","setLocalSample",{"mes":document.querySelector("#local_sample").value},function(data){
			// ↑の$_POST["mes"]がdataに入って帰ってくる JSONにして処理するのもあり
			
			// 右のログ領域に表示する
			addLog(data,"サンプルメッセージ受信");
		});
	};
</script>
	<br>
	<br>
	ローカルコンソールサンプル機能<br>
	<input type="text" id="local_sample" value="SAMPLE MESSAGE"><br>
	<input style="margin-left:30px;" type="button" onclick="setLocalSample()" value="実行"><br>
	<hr>
<?php 
}
?>