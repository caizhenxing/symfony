<?php 
$app_config_path = __DIR__.'/src/Sega/AppBundle/lib/Arpg/Config.php';
if(file_exists($app_config_path)){
  require_once $app_config_path;
}
/**
 * コンソールのローカル機能を実装する
 * @param string $type 機能文字列
 * @return boolean true：機能あり false:機能がなかったためデフォルトのコンソールを実行
 */
function local_console($type){
	if(strcmp($type,"setLocalMdcTime") == 0){
		 $time = intval($_POST["time"]);
		 if(strcmp($time."" , $_POST["time"]) != 0){
		 	echo "数値を設定してください";
		 }else{
			try{
			 	$lines = file(__DIR__.'/src/Sega/AppBundle/lib/Arpg/Config.php');
			 	
			 	$out = array();
			 	foreach($lines as $line){
			 		if(strpos($line,'const MasterDBCacheTime=') !== FALSE){
			 			$line = "	const MasterDBCacheTime=$time;\n";
			 		}
			 		$out[] = $line;
			 	}
			 	
			 	file_put_contents(__DIR__.'/src/Sega/AppBundle/lib/Arpg/Config.php',implode("",$out));
			 	if($time > 0)
				 	echo "マスターデータキャッシュを".$time."秒に設定しました";
			 	else
			 		echo "マスターデータキャッシュを無効化しました";
			}catch(\Exception $e){
				echo "ERROR : コンフィグファイルが存在しませんでした";
			}
		 }
		return true;
	}
	
	if(strcmp($type,"setLocalMdlcTime") == 0){
		 $time = intval($_POST["time"]);
		 if(strcmp($time."" , $_POST["time"]) != 0){
		 	echo "数値を設定してください";
		 }else{
			try{
			 	$lines = file(__DIR__.'/src/Sega/AppBundle/lib/Arpg/Config.php');
			 	
			 	$out = array();
			 	foreach($lines as $line){
			 		if(strpos($line,'const MasterDBLongCacheTime=') !== FALSE){
			 			$line = "	const MasterDBLongCacheTime=$time;\n";
			 		}
			 		$out[] = $line;
			 	}
			 	
			 	file_put_contents(__DIR__.'/src/Sega/AppBundle/lib/Arpg/Config.php',implode("",$out));
			 	if($time > 0)
				 	echo "マスターデータロングキャッシュを".$time."秒に設定しました";
			 	else
			 		echo "マスターデータロングキャッシュを無効化しました";
			}catch(\Exception $e){
				echo "ERROR : コンフィグファイルが存在しませんでした";
			}
		 }
		return true;
	}
	if(strcmp($type,"setSubTime") == 0){
		 $time = intval($_POST["time"]);
		 if(strcmp($time."" , $_POST["time"]) != 0){
		 	echo "数値を設定してください";
		 }else{
			try{
			 	$lines = file(__DIR__.'/src/Sega/AppBundle/lib/Arpg/Config.php');
			 	
			 	$out = array();
			 	foreach($lines as $line){
			 		if(strpos($line,'const SubTime=') !== FALSE){
			 			$line = "	const SubTime=$time;\n";
			 		}
			 		$out[] = $line;
			 	}
			 	
			 	file_put_contents(__DIR__.'/src/Sega/AppBundle/lib/Arpg/Config.php',implode("",$out));
			 	if($time > 0)
				 	echo $time."msecに設定しました";
			 	else
			 		echo "無効化しました";
			}catch(\Exception $e){
				echo "ERROR : コンフィグファイルが存在しませんでした";
			}
		}
		return true;
	}
	
	
	// アセットバンドル更新
	if(strcmp($type,"updateAssetbundle") == 0){
		exec("svn update ".__DIR__.'/../AssetBundle/',$out);
		echo implode("\n",$out);
		
		return true;
	}
	if(strcmp($type, "setDebug") == 0){
		$val = intval($_POST["val"]);
		$inp = "";
		if($val == 0){
			$inp = "false";
		}else{
			$inp = "true";
		}
		try{
			$lines = file(__DIR__.'/src/Sega/AppBundle/lib/Arpg/Config.php');
			
			$out = array();
			foreach($lines as $line){
				if(strpos($line,'const Debug=') !== FALSE){
					$line = "	const Debug=$inp;\n";
				}
				$out[] = $line;
			}
			
			file_put_contents(__DIR__.'/src/Sega/AppBundle/lib/Arpg/Config.php',implode("",$out));
			if($inp)
				echo "有効にしました";
			else
				echo "無効にしました";
		}catch(\Exception $e){
			echo "ERROR : コンフィグファイルが存在しませんでした";
		}
		return true;
	}
	// マスターダンプ
	if(strcmp($type,"dumpMaster") == 0){
		exec("php app/console arpg:master_dump");
		header("Content-type: application/octet-stream; charset=UTF-8");
		readfile(__DIR__."/data/master_dump.sql");
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
	setDBCacheTime = function(){
		connect("マスターデータキャッシュ時間設定","setLocalMdcTime",{"time":document.querySelector("#local_mdc_time").value},function(data){
			addLog(data,"MDC設定");
		});
	};
	setDBLCacheTime = function(){
		connect("マスターデータロングキャッシュ時間設定","setLocalMdlcTime",{"time":document.querySelector("#local_mdlc_time").value},function(data){
			addLog(data,"MDLC設定");
		});
	};
	setSubTime = function(){
		connect("時間強制変更設定","setSubTime",{"time":document.querySelector("#sub_time").value},function(data){
			addLog(data,"時間強制変更設定");
		});
	};

	updateAssetbundle = function(){
		connect("アセットバンドル更新","updateAssetbundle",{},function(data){
			addLog(data,"アセットバンドル更新");
		});
	};
	setDebug = function(){
		connect("デバッグモード設定","setDebug",{"val":document.querySelector("#full_open_dungeon").checked?1:0},function(data){
			addLog(data,"デバッグモード設定");
		});
	};
	dumpMaster = function(){
		connect("マスターデータダンプ","dumpMaster",{},function(data){
			var a = document.createElement('a');
			a.download = "MasterData.sql";
			a.href = "data:application/octet-stream ;,"+encodeURIComponent(data);
			var evt = document.createEvent("MouseEvents");
			evt.initEvent("click",false,true);
			a.dispatchEvent(evt);
		});
	};
</script>
	
	<br>
	<br>
	アセットバンドルを最新に更新　<input style="margin-left:30px;" type="button" onclick="updateAssetbundle()" value="実行"><br>
	<hr>
	<br>
	<br>
	デバッグモード
	<input type="checkbox" id="full_open_dungeon" value="true" <?php echo \Dcs\Arpg\Config::Debug?"checked":"";?>>
	<input style="margin-left:30px;" type="button" onclick="setDebug()" value="実行"><br>
	<hr>
	<br>
	<br>
	デバッグ:時間強制変更　今の時間に追加する秒数<br>
	<input type="text" id="sub_time" value="<?php echo \Dcs\Arpg\Config::SubTime;?>"><input style="margin-left:30px;" type="button" onclick="setSubTime()" value="実行"><br>
	<hr>
	<br>
	<br>
	マスターデータダンプ　<input style="margin-left:30px;" type="button" onclick="dumpMaster()" value="実行"><br>
	<hr>
	<br>
	<br>
	
<?php 
	include "ocp.php";
}
?>