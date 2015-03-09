<?php
$app_config_path = __DIR__.'/src/Sega/AppBundle/lib/config.php';
if(file_exists($app_config_path)){
  require_once $app_config_path;
}

// console_my.org.phpを参考にプロジェクトごとに項目を作れる
// console_my.phpにリネームしてね

$my_console_path = __DIR__.'/console_my.php';
if(file_exists($my_console_path)){
	require_once $my_console_path;
}
$request_aggregate_path = __DIR__."/app/logs/RequestAggregate.log";
use \Dcs as dcs;
$type = array_key_exists('type',$_REQUEST)?$_REQUEST['type']:"";

function deleteDir($rootPath){
	exec("rm -r $rootPath");
	return;
	if(!file_exists($rootPath)) return;
	$strDir = opendir($rootPath);
	if($strDir === false) return;
	while($strFile = readdir($strDir)){
		if(strcmp($strFile,'.') != 0 && strcmp($strFile,'..') != 0){  //ディレクトリでない場合のみ
			$path = $rootPath.'/'.$strFile;
			if(!file_exists($path)){
				error_log("deleteDir: dont exists path $path");
			}elseif(is_dir($path)){
				deleteDir($path);
			}else{
				unlink($path);
			}
		}
	}
	closedir($strDir);
	if(file_exists($rootPath))
		rmdir($rootPath);
}
function findSvn($rootPath){
	if(!file_exists($rootPath)) return;
	$strDir = opendir($rootPath);
	while($strFile = readdir($strDir)){
		if(strcmp($strFile,'.') != 0 && strcmp($strFile,'..') != 0){  //ディレクトリでない場合のみ
			if(strcmp($strFile,'.svn') == 0)
				return $rootPath;
		}
	}
	rewinddir($strDir);
	while($strFile = readdir($strDir)){
		if(strcmp($strFile,'.') != 0 && strcmp($strFile,'..') != 0){  //ディレクトリでない場合のみ
			$path = $rootPath.'/'.$strFile;
			if(is_dir($path)){
				$path = findSvn($path);
				if($path != null)
					return $path;
			}
		}
	}
	closedir($strDir);
	return null;
}

function readDconf(){
	$obj = Spyc::YAMLLoad('app/config/config.yml');
	return $obj;
}
function writeDconf($conf){
	file_put_contents("app/config/config.yml", Spyc::YAMLDump($conf,4,0));
}

function createRandString($max){
	$list = explode(' ','a b c d e f g h i j k l m n o p q r s t u v w x y z A B C D E F G H I J K L M N O P Q R S T U V W X Y Z 0 1 2 3 4 5 6 7 8 9 _ @');
	$ret = "";
	for($i=0;$i<$max;++$i){
		$ret .= $list[array_rand($list)];
	}
	return $ret;
}
function clearSymfonyCache(&$output){
	try{
		set_time_limit(600);
		//exec("php app/console cache:clear --no-warmup --env=dev", $output);
		//exec("php app/console cache:clear --no-warmup --env=prod", $output);
		deleteDir("app/cache/dev");
		deleteDir("app/cache/prod");
		mb_language("Japanese");
		for($i=0;$i<count($output);++$i){
			$output[$i] = mb_convert_encoding($output[$i], "UTF-8","auto");
		}

		if(extension_loaded("apcu")){
			apc_clear_cache();
			apc_clear_cache("user");
			$output[]="<br>delete APC cache";
		}
		
		$conf = Spyc::YAMLLoad("app/config/kvs.yml");
		if(!empty($conf) &&
		array_key_exists("lsw_memcache",$conf) &&
		array_key_exists("clients",$conf["lsw_memcache"]) &&
		array_key_exists("default",$conf["lsw_memcache"]["clients"]) &&
		array_key_exists("hosts",$conf["lsw_memcache"]["clients"]["default"]))
		{
			if(extension_loaded("memcached")){
				$mc = new Memcached();
				foreach($conf["lsw_memcache"]["clients"]["default"]["hosts"] as $host){
					$mc->addServer($host["dsn"],intval($host["port"]));
				}
				$mc->flush();
				$output[]= "<br>memcached flush";
			}
		}
		
	}catch(\Exception $e){
		error_log($e->getMessage());
	}
}
function copyDir($from, $to, &$output){
	exec("rsync -av --exclude='.svn/' $from $to", $output);
}
if(function_exists("local_console") && local_console($type)){
	// ローカルコンソール実行されました
}elseif(strcmp($type, "clearCache") == 0){
	clearSymfonyCache($output);
	echo implode('<br>',$output);
}elseif(strcmp($type,"makeAssets") == 0){
	copyDir("src/Sega/AppBundle/Resources/public/","web/bundles/segaapp/",$output);
	copyDir("src/Title/GaiaBundle/ManagementToolBundle/Resources/public/","web/bundles/titlemanagementtool/",$output);
	echo implode('<br>',$output);
}elseif(strcmp($type,"apcInfo") == 0){
	if(extension_loaded("apcu")){
		$output = [];
		$info = apc_cache_info("user");
		$mem_size = intval($info["mem_size"]);
		if($mem_size > 100000000){
			$mem_size = sprintf("%.2fG",$mem_size/1000000000);
		}elseif($mem_size > 100000){
			$mem_size = sprintf("%.2fM",$mem_size/1000000);
		}elseif($mem_size > 100){
			$mem_size = sprintf("%.2fK",$mem_size/1000);
		}
		$output[]= "検索HIT:".$info["nhits"];
		$output[]= "検索MISS:".$info["nmisses"];
		$output[]= "エントリー:".$info["nentries"];
		$output[]= "使用メモリ:".$mem_size;
		echo implode('<br>',$output);
	}else{
		echo "APCが無効になっています";
	}
}elseif(strcmp($type,"setMemcache") == 0){
	$mem = $_POST["mem"];
	$rtime = $_POST["rtime"];
	$sep = explode(":",$mem);
	$url = $sep[0];
	$port = "";
	if(count($sep) > 1){
		$port = $sep[1]+0;
	}else{
		$port = 11211;
	}
	
	if(!is_numeric($port) || !is_int($port+0)){
		echo "ポートに整数値以外を入れないでください";
	}elseif($port+0 < 1){
		echo "ポートに負の値を入れないでください";
	}elseif(!is_numeric($rtime) || !is_int($rtime+0)){
		echo "RPC生存時間に整数値以外を入れないでください";
	}elseif($rtime+0 < 1){
		echo "RPC生存時間に負の値を入れないでください";
	}else{
		$lines = file(__DIR__.'/src/Sega/AppBundle/lib/config.php');

		$out = array();
		foreach($lines as $line){
			if(strpos($line,'const ResponsCacheLimit=') !== FALSE){
				$line = "	const ResponsCacheLimit=$rtime;\n";
			}
			$out[] = $line;
		}
		
		file_put_contents(__DIR__.'/src/Sega/AppBundle/lib/config.php',implode("",$out));

		$conf = Spyc::YAMLLoad("app/config/kvs.yml");
		$conf["lsw_memcache"]["clients"]["default"]["hosts"][0]["dsn"] = $url;
		$conf["lsw_memcache"]["clients"]["default"]["hosts"][0]["port"] = $port;
		file_put_contents("app/config/kvs.yml", Spyc::YAMLDump($conf,4,0));
		
		echo "MemcacheUrl : $url<br>";
		echo "MemcachePort: $port<br>";
		echo "RpcTimeOut: $rtime sec<br>";
		echo "設定完了";
	}
}elseif(strcmp($type,"setRouting") == 0){
	
	$rootpath = __DIR__."/src/Sega/AppBundle/Resources/routing";
	$dp = opendir($rootpath);
	$file = "# 絶対に手動で変更を加えないこと \n";
	while($strFile = readdir($dp)){
		if(strcmp($strFile,'.') != 0 && strcmp($strFile,'..') != 0){  //ディレクトリでない場合のみ
			$ary = explode(".",$strFile);
			if(strcasecmp($ary[count($ary)-1],"yml") != 0)
				continue;
			$path = $rootpath.'/'.$strFile;
			if(is_dir($path))
				continue;
			// リンク生成
			$sep = explode('.',$strFile);
			array_pop($sep);
			$basename = implode(".",$sep);
			$file .= "sega_app_".$basename.":\n";
			$file .= "    resource: @SegaAppBundle/Resources/routing/".$strFile."\n";
			$file .= "    prefix:  /\n\n";
		}
	}
	closedir($dp);
	
	
	file_put_contents(__DIR__."/src/Sega/AppBundle/Resources/config/routing.yml",$file);
	
	echo "ルーティングファイルのリンクを生成しました";	
}elseif(strcmp($type,"setCmnacc") == 0){
	$inp = $_POST["inp"]+0;
	$uid = $_POST["uid"];
	$once = $_POST["once"];
	$pass = $_POST["pass"];
	
	switch($inp){
		case 0:
			$inp="config::NEW_USER_INPUT_ALL";
			break;
		case 1:
			$inp="config::NEW_USER_INPUT_UID";
			break;
		case 2:
			$inp="config::NEW_USER_INPUT_AUTO";
			break;
		case 3:
			$inp="config::NEW_USER_INPUT_NONE";
			break;
	}
	
	
	$lines = file(__DIR__.'/src/Sega/AppBundle/lib/config.php');
	
	$out = array();
	foreach($lines as $line){
		if(strpos($line,'NewUserInput=') !== FALSE){
			$line = "	const NewUserInput=$inp;\n";
		}
		if(strpos($line,'const OnceIdLength=') !== FALSE){
			$line = "	const OnceIdLength=$once;\n";
		}
		if(strpos($line,'const AutoUidLength=') !== FALSE){
			$line = "	const AutoUidLength=$uid;\n";
		}
		if(strpos($line,'const AutoPassLength=') !== FALSE){
			$line = "	const AutoPassLength=$pass;\n";
		}
		
		$out[] = $line;
	}
	
	
	file_put_contents(__DIR__.'/src/Sega/AppBundle/lib/config.php',implode("",$out));
	echo "設定完了";
}elseif(strcmp($type,"setKey") == 0){
	//$pass = $_POST["pass"];
	$res = openssl_pkey_new(array(
		"private_key_bits" => 1024,
		"private_key_type" => OPENSSL_KEYTYPE_RSA,
	));
	openssl_pkey_export($res,$skey);
	$akey = openssl_pkey_get_details($res);
	
	$path = "src/Sega/AppBundle/";
	if(file_put_contents($path."public.txt",$akey["key"]) === FALSE){
		echo "公開鍵を保存できませんでした";
	}elseif(file_put_contents($path."private.txt",$skey)===FALSE){
		echo "秘密鍵を保存できませんでした";	
	}else
		
	echo $path."に、秘密鍵公開鍵を保存しました<br>";
	echo "UnityフォルダのResources/Dcs/に$path/public.txtをコピーしてください";
	/*
	$path = "src/Sega/AppBundle/";
	if(file_put_contents($path."public.txt",createRandString(64)) === FALSE){
		echo "公開鍵を保存できませんでした";
	}elseif(file_put_contents($path."private.txt",createRandString(256))===FALSE){
		echo "秘密鍵を保存できませんでした";
	}
	echo $path."に、秘密鍵公開鍵を保存しました<br>";
	echo "UnityフォルダのResources/Dcs/に$path/public.txtをコピーしてください";
	*/
}elseif(strcmp($type,'udDir') == 0){
	// SVNフォルダを更新する
	$path = findSvn("src");
	if($path == null){
		exec("svn update src", $output);
		exec("svn update web/bundles", $output);
		mb_language("Japanese");
		for($i=0;$i<count($output);++$i){
			$output[$i] = mb_convert_encoding($output[$i], "UTF-8","auto");
		}
		clearSymfonyCache($output);
		echo "svn update src<br><br>".implode('<br>',$output);
	}else{
		
		exec("svn update ".$path, $output);
		exec("svn update web/bundles", $output);
		mb_language("Japanese");
		for($i=0;$i<count($output);++$i){
			$output[$i] = mb_convert_encoding($output[$i], "UTF-8","auto");
		}
		clearSymfonyCache($output);
		echo "svn update ".$path."<br><br>".implode('<br>',$output);
	}
	
}elseif(strcmp($type,'mkModel')==0){
	// DBモデル作成
	deleteDir('src/Sega/AppBundle/Resources/config/doctrine');
	deleteDir('src/Sega/AppBundle/Entity');
	header("Content-type: text/html; charset=UTF-8");
	set_time_limit(600);
	exec("php app/console doctrine:mapping:convert yml ./src/Sega/AppBundle/Resources/config/doctrine/metadata/orm --from-database --force", $output);
	set_time_limit(600);
	exec("php app/console doctrine:mapping:import SegaAppBundle annotation", $output);
	set_time_limit(600);
	exec("php app/console doctrine:generate:entities SegaAppBundle", $output);
	
	if(count($output) > 0)
		echo implode('<br>',$output);
	else
		echo 'スクリプトが実行できませんでした<br>sshなどで接続してcreateModel.shを実行してください';
	
}elseif(strcmp($type,'setHost')==0){
	// DB設定
	header("Content-type: text/html; charset=UTF-8");
	$mst = $_POST["mst"];
	$slv = $_POST["slv"];
	
	$conf = Spyc::YAMLLoad("app/config/database.yml");
	$parameters = &$conf["parameters"];
	if($parameters == null)
		$parameters = [];
	$doctrine = &$conf["doctrine"];
	if($doctrine == null)
		$doctrine = [];
	
	$master = "";
	$mport = null;
	$slave = [];
	$sport = [];
	{
		$url = explode(":",$mst);
		$master = $url[0];
		$mport = count($url) > 1?is_int($url[1])?$url[1]:null:null;
		$slv = explode(",",$slv);
		for($i=0;$i<count($slv);++$i){
			if(strlen(trim($slv[$i])) < 1) continue;
			$url = explode(":",$slv[$i]);
			$slave[] = $url[0];
			$sport[] = count($url) > 1?is_int($url[1])?$url[1]:null:null;
		}
	}
	$shdl = [];
	
	$dbal = &$doctrine["dbal"];
	if(!isset($dbal["connections"]))
		$dbal = [];
	
	$dbal["default_connection"] = "default";
	
	$connections = &$dbal["connections"];
	if($connections == null)
		$connections = [];
	$connections["default"]=[
		"driver"=>"pdo_mysql",
		"dbname"=>"gaia",
		"host"=>$master,
		"port"=>$mport,
		"user"=>"dcs",
		"password"=>"gaia",
		"charset"=>"UTF8",
		"wrapper_class"=>empty($slave)?null:"Doctrine\DBAL\Connections\MasterSlaveConnection",
		"logging"=>'%kernel.debug%',
		"profiling"=>'%kernel.debug%',
	];
	$hs = &$parameters["gaia.handler_socket.parameter"];
	$hs = [
			"host" => $master,
			"port" => 9998,
			"port_wr" => 9999,
			"dbname" => "gaia",
			"password" => null,
			"password_wr" => null,
			"timeout" => 10,
	];

	for($i=0;$i<count($slave);++$i){
		if(!isset($connections["default"]["slaves"]))
			$connections["default"]["slaves"] = [];
		$connections["default"]["slaves"]["slave".($i+1)] = [
				"dbname"=>"gaia",
				"host"=>$slave[$i],
				"port"=>$sport[$i],
				"user"=>"dcs",
				"password"=>"gaia",
				"charset"=>"UTF8",
		];
		if(!isset($hs["slaves"]))
			$hs["slaves"] = [];
		$hs["slaves"]["slave".($i+1)] = [
				"host" => $slave[$i],
				"port" => 9998,
				"password" => null,
				"timeout" => 10
		];
	}
	
	$orm = &$doctrine["orm"];
	if(!isset($orm["entity_managers"]))
		$orm = [];
	$orm["default_entity_manager"] = null;
	$orm["auto_generate_proxy_classes"] = '%kernel.debug%';
	$orm["proxy_dir"] = '%kernel.cache_dir%/doctrine/orm/Proxies';
	$orm["proxy_namespace"] = "Proxies";
	$orm["resolve_target_entities"] = array();
	$orm["entity_managers"] = array(
			"default"=>array("mappings"=>array("SegaAppBundle"=>null)),
			"slaves"=>array(
					"connection"=>null,
					//"class_metadata_factory_name"=> 'Doctrine\ORM\Mapping\ClassMetadataFactory',
					"class_metadata_factory_name" => 'Doctrine\ORM\Mapping\ClassMetadataFactory',
					"default_repository_class"=>'Doctrine\ORM\EntityRepository',
					"mappings"=>array("SegaAppBundle"=>null)
			),
			
	);
	
	file_put_contents("app/config/database.yml", Spyc::YAMLDump($conf,4,0));

	
	echo " SetMaster : ".$master."<br>　　port : ".($mport==null?"3306":$mport)."<br>";
	for($i=0;$i<count($slave);++$i){
		echo " SetSlave".($i+1)." : ".$slave[$i]."<br>　　port : ".($sport[$i]==null?"3306":$sport[$i])."<br>";
	}
}elseif(strcmp($type,'setLHost')==0){
	// DB設定
	header("Content-type: text/html; charset=UTF-8");
	$mst = $_POST["mst"];
	$slv = $_POST["slv"];

	$params = Spyc::YAMLLoad("app/config/parameters.yml");
	
	$conf = Spyc::YAMLLoad("app/config/database.yml");
	$parameters = &$conf["parameters"];
	if($parameters == null)
		$parameters = [];
	$doctrine = &$conf["doctrine"];
	if($doctrine == null)
		$doctrine = [];
	
	$master = "";
	$mport = null;
	$slave = [];
	$sport = [];
	{
		$url = explode(":",$mst);
		$master = $url[0];
		$mport = count($url) > 1?is_int($url[1])?$url[1]:null:null;
		$slv = explode(",",$slv);
		for($i=0;$i<count($slv);++$i){
			if(strlen(trim($slv[$i])) < 1) continue;
			$url = explode(":",$slv[$i]);
			$slave[] = $url[0];
			$sport[] = count($url) > 1?is_int($url[1])?$url[1]:null:null;
		}
	}
	$shdl = [];
	
	$dbal = &$doctrine["dbal"];
	if(!isset($dbal["connections"]))
		$dbal = [];
	
	$dbal["default_connection"] = "default";
	
	$connections = &$dbal["connections"];
	if($connections == null)
		$connections = [];
	$connections["log"]=[
		"driver"=>"pdo_mysql",
		"dbname"=>"gaia_log",
		"host"=>$master,
		"port"=>$mport,
		"user"=>"dcs",
		"password"=>"gaia",
		"charset"=>"UTF8",
		"wrapper_class"=>empty($slave)?null:"Doctrine\DBAL\Connections\MasterSlaveConnection",
		"logging"=>'%kernel.debug%',
		"profiling"=>'%kernel.debug%',
	];
	$hs = &$parameters["gaia_log.handler_socket.parameter"];
	$hs = [
			"host" => $master,
			"port" => 9998,
			"port_wr" => 9999,
			"dbname" => "gaia_log",
			"password" => null,
			"password_wr" => null,
			"timeout" => 10,
	];

	for($i=0;$i<count($slave);++$i){
		if(!isset($connections["log"]["slaves"]))
			$connections["log"]["slaves"] = [];
		$connections["log"]["slaves"]["slave".($i+1)] = [
				"dbname"=>"gaia_log",
				"host"=>$slave[$i],
				"port"=>$sport[$i],
				"user"=>"dcs",
				"password"=>"gaia",
				"charset"=>"UTF8",
		];
		if(!isset($hs["slaves"]))
			$hs["slaves"] = [];
		$hs["slaves"]["slave".($i+1)] = [
				"host" => $slave[$i],
				"port" => 9998,
				"password" => null,
				"timeout" => 10
		];
	}
	
	$orm = &$doctrine["orm"];
	if(!isset($orm["entity_managers"]))
		$orm = [];
	$orm["default_entity_manager"] = null;
	$orm["auto_generate_proxy_classes"] = '%kernel.debug%';
	$orm["proxy_dir"] = '%kernel.cache_dir%/doctrine/orm/Proxies';
	$orm["proxy_namespace"] = "Proxies";
	$orm["resolve_target_entities"] = array();
	$orm["entity_managers"] = array(
			"default"=>array("mappings"=>array("SegaAppBundle"=>null)),
			"slaves"=>array(
					"connection"=>null,
					//"class_metadata_factory_name"=> 'Doctrine\ORM\Mapping\ClassMetadataFactory',
					"class_metadata_factory_name" => 'Doctrine\ORM\Mapping\ClassMetadataFactory',
					"default_repository_class"=>'Doctrine\ORM\EntityRepository',
					"mappings"=>array("SegaAppBundle"=>null)
			),
			
	);
	
	file_put_contents("app/config/database.yml", Spyc::YAMLDump($conf,4,0));

	
	echo " SetMaster : ".$master."<br>　　port : ".($mport==null?"3306":$mport)."<br>";
	for($i=0;$i<count($slave);++$i){
		echo " SetSlave".($i+1)." : ".$slave[$i]."<br>　　port : ".($sport[$i]==null?"3306":$sport[$i])."<br>";
	}
}elseif(strcmp($type,"setSlowRequestTime") == 0){
	 $time = intval($_POST["time"]);
	 if(strcmp($time."" , $_POST["time"]) != 0){
	 	echo "数値を設定してください";
	 }else{
		try{
		 	$lines = file(__DIR__.'/src/Sega/AppBundle/lib/config.php');
		 	
		 	$out = array();
		 	foreach($lines as $line){
		 		if(strpos($line,'const SlowRequestTime=') !== FALSE){
		 			$line = "	const SlowRequestTime=$time;\n";
		 		}
		 		$out[] = $line;
		 	}
		 	
		 	file_put_contents(__DIR__.'/src/Sega/AppBundle/lib/config.php',implode("",$out));
		 	if($time > 0)
			 	echo $time."msecに設定しました";
		 	else
		 		echo "無効化しました";
		}catch(\Exception $e){
			echo "ERROR : コンフィグファイルが存在しませんでした";
		}
	 }
}elseif(strcmp($type, "setRequestAggregate") == 0){
	$val = intval($_POST["val"]);
	$inp = "";
	if($val == 0){
		$inp = "false";
	}else{
		$inp = "true";
	}
	try{
		$lines = file(__DIR__.'/src/Sega/AppBundle/lib/config.php');
		
		$out = array();
		foreach($lines as $line){
			if(strpos($line,'const RequestAggregate=') !== FALSE){
				$line = "	const RequestAggregate=$inp;\n";
			}
			$out[] = $line;
		}
		
		file_put_contents(__DIR__.'/src/Sega/AppBundle/lib/config.php',implode("",$out));
		if($val == 0){
			echo "無効にしました";
		}else
			echo "有効にしました";
	}catch(\Exception $e){
		echo "ERROR : コンフィグファイルが存在しませんでした";
	}
}elseif(strcmp($type, "getRequestAggregate") == 0){
	if(is_file($request_aggregate_path)){
		$use_slow = \Dcs\config::SlowRequestTime>0;
		$fp = fopen($request_aggregate_path,'r');
		$ary = [];
		if($fp){
			while(($line = fgets($fp,4096)) !== false){
				$sep = explode(",",$line);
				if(count($sep) != 2) continue;
				$key = $sep[0];
				if(!isset($ary[$key]) )
					$ary[$key] = ["total"=>0,"count"=>0,"min" => 1000000,"max"=>-1,"slow"=>0];
				$num =  $sep[1] + 0;
				$ary[$key]["total"] += $num;
				$ary[$key]["count"] += 1;

				if($ary[$key]["max"] < $num)
					$ary[$key]["max"] = $num;
				if($ary[$key]["min"] > $num)
					$ary[$key]["min"] = $num;

				if($use_slow && $num >= \Dcs\config::SlowRequestTime)
					$ary[$key]["slow"] += 1;
					
			}
			fclose($fp);
		}
		$csv = "symfony route,count,min(ms),max(ms),ave(ms)".($use_slow?",time>".\Dcs\config::SlowRequestTime."ms rate(%)":"")."\n";
		foreach($ary as $key => $list){
			$min = 10000000;
			$max = -1;
			$ave = 0;
			$count = $list["count"];
			$slow = 0;
			if($count < 1) continue;
			foreach($list as $elem){
				if($elem < $min)
					$min = $elem;
				if($elem > $max)
					$max = $elem;
				$ave += $elem;
				if($use_slow && $elem >= \Dcs\config::SlowRequestTime)
					++$slow;
			}
			$ave = $list["total"] / $count;
			$min = intval($list["min"]*1000)/1000;
			$max = intval($list["max"]*1000)/1000;
			$ave = intval($ave*1000)/1000;
			if($use_slow)
				$slow = intval($list["slow"]/$count*100000)/1000;
			
			$csv .= "$key,$count,$min,$max,$ave".($use_slow?",".$slow:"")."\n";
		}
		echo $csv;
		
	}else{
		echo "dont find Aggregate file";
	}
}elseif(strcmp($type,"getRequestAggregateOrg") == 0){
	if(is_file($request_aggregate_path)){
		readfile($request_aggregate_path);
	}else{
		echo "dont find Aggregate file";
	}
}elseif(strcmp($type,"clearRequestAggregate") == 0){
	echo "過去の集計情報を削除しました";
	if(is_file($request_aggregate_path))
		unlink($request_aggregate_path);
}elseif(strcmp($type,"setSlowRequestDetail") == 0){
	$val = intval($_POST["val"]);
	$inp = "";
	if($val == 0){
		$inp = "false";
	}else{
		$inp = "true";
	}
	try{
		$lines = file(__DIR__.'/src/Sega/AppBundle/lib/config.php');
	
		$out = array();
		foreach($lines as $line){
			if(strpos($line,'const SlowRequestDetail=') !== FALSE){
				$line = "	const SlowRequestDetail=$inp;\n";
			}
			$out[] = $line;
		}
		file_put_contents(__DIR__.'/src/Sega/AppBundle/lib/config.php',implode("",$out));
		if($val != 0)
			echo "有効にしました";
		else
			echo "無効にしました";
	}catch(\Exception $e){
		echo "ERROR : コンフィグファイルが存在しませんでした";
	}
}elseif(strcmp($type,"setJmeterMode") == 0){
	$val = intval($_POST["val"]);
	$inp = "";
	if($val == 0){
		$inp = "false";
	}else{
		$inp = "true";
	}
	try{
		$lines = file(__DIR__.'/src/Sega/AppBundle/lib/config.php');
	
		$out = array();
		foreach($lines as $line){
			if(strpos($line,'const JmeterMode=') !== FALSE){
				$line = "	const JmeterMode=$inp;\n";
			}
			$out[] = $line;
		}
		file_put_contents(__DIR__.'/src/Sega/AppBundle/lib/config.php',implode("",$out));
		if($val != 0)
			echo "有効にしました";
		else
			echo "無効にしました";
	}catch(\Exception $e){
		echo "ERROR : コンフィグファイルが存在しませんでした";
	}
}else{
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Symfony2 Console</title>
	<style type="text/css">
	*{
		margin: 0px;
		padding: 0px;
		position: relative;
		line-height: 100%;
		z-index: 0;
	}
	input{
		margin-top:5px;
	}
	input[type=text]{
		width:200px;
	}
	body{
		overflow: hidden;
		width: 100%;
	}
	hr{
		color: #96FF78;
		margin-bottom: 12px;
		margin-top: 5px;
	}
	.title{
		color: green;
		font-size: 15px;
		font-weight: bold;
		width: 100%;
		text-align: center;
	}
	hr.end{
		margin-top: 0px;
		margin-bottom: 5px;
	}
	#loading{
		position:absolute;
		left:0;
		top:0;
		width: 100%;
		height: 100%;
		background-color: rgba(0,0,0,0.6);
		display: none;
		z-index: 10;
	}
	#ladingimg{
		position:absolute;
		left:50%;
		margin-left: -32px;
		top:50%;
		margin-top: -32px;
	}
	#log{
		position:absolute;
		overflow-y: auto;
		font-family: monospace;   
		font-size: 12px;
		width:20%;
		right: 0;
		top:0px;
		border: 1px solid black;
		height: 100%;
		box-sizing: border-box;
		-o-box-sizing: border-box;
		-ms-box-sizing: border-box;
		-moz-box-sizing: border-box;
		-webkit-box-sizing: border-box;
	}
	#main{
		position:absolute;
		width:80%;
		height:100%;
		left: 0px;
		top:0px;
		overflow: auto;
	}
	#loadingtitle{
		width: 100%;
		position: absolute;
		text-align: center;
		left:0px;
		top:50%;
		margin-top: 45px;
		color: white;
		font-family: monospace; 
		font-size: 20px;
		font-weight: bolder;
	}
	</style>
	<script type="text/javascript">
	var init=function(){
		var body = document.querySelector("body");
		var bcss = body.style;
		var resizer = function(){
			bcss.width = (window.innerWidth || document.documentElement.clientWidth || body.clientWidth) + "px";
			bcss.height = (window.innerHeight || document.documentElement.clientHeight || body.clientHeight)+"px";
		};
		resizer();
		window.addEventListener("resize",function(){
			resizer();
		});
	};
	window.addEventListener("load",function(){
		init();
	},false);

	/**@ignore*/
	var NewHttpObject = null;
	if(NewHttpObject == null){
		try{
			new XMLHttpRequest();
			/**@ignore*/
			NewHttpObject = function(){
				return new XMLHttpRequest();
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml2.XMLHTTP.6.0");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml2.XMLHTTP.6.0");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml2.XMLHTTP.3.0");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml2.XMLHTTP.3.0");
			};
		}catch(e){NewHttpObject = null;}
	}
	// 推奨非推奨ライン ↑推奨 ↓非推奨
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml2.XMLHTTP.5.0");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml2.XMLHTTP.5.0");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml2.XMLHTTP.4.0");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml2.XMLHTTP.4.0");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml2.XMLHTTP");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml2.XMLHTTP");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Msxml.XMLHTTP");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Msxml.XMLHTTP");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		try{
			new ActiveXObject("Microsoft.XMLHTTP");
			/**@ignore*/
			NewHttpObject = function(){
				return new ActiveXObject("Microsoft.XMLHTTP");
			};
		}catch(e){NewHttpObject = null;}
	}
	if(NewHttpObject == null){
		/**@ignore*/
		NewHttpObject = function(){return null;};
	}

	var connect = function(title,type,data,func){
		document.querySelector("#loading").style.display = "block";
		document.querySelector("#loadingtitle").innerHTML = title;
		if(!(data instanceof Object)){
			data = {};
		}
		var txt = "type="+type;
		var httpObj = NewHttpObject();
		httpObj.open("POST","console.php",true);
		httpObj.setRequestHeader("Content-Type" , "application/x-www-form-urlencoded");
		for(var i in data){
			txt+="&"+i+"="+data[i];
		}
		
		httpObj.onreadystatechange = function(evt){
			switch(this.readyState){
				case 2:
					break;
				case 4:
					var self = this;
					setTimeout(function(){
						if((200 <= self.status && self.status < 300) || (self.status == 304)){
							func(self.responseText);
						}else{
							alert("Connection Error:"+self.status);
						}
						document.querySelector("#loading").style.display = "none";
					},1000);
					break;
				default:
					break;
			};
		};
		httpObj.send(txt);
	};
	var addLog = function(log,title){
		var box = document.querySelector("#log");
		if(title != null)
			box.innerHTML +="<div class='title'>"+title+"</div><hr class='end'>";
		box.innerHTML +=log+"<hr>";
		box.scrollTop = box.scrollHeight;
	};


	mkModel = function(){
		connect("DBからPHPのクラスを生成","mkModel",null,function(data){
			addLog(data,"DBからPHPのクラスを生成");
		});
	};
	setHost = function(){
		connect("DB設定","setHost",{"mst":document.querySelector("#master").value,"slv":document.querySelector("#slave").value},function(data){
			addLog(data,"DB設定");
		});
	};
	setLHost = function(){
		connect("LOGDB設定","setLHost",{"mst":document.querySelector("#lmaster").value,"slv":document.querySelector("#lslave").value},function(data){
			addLog(data,"LOGDB設定");
		});
	};
	setKey = function(){
		if(confirm("古い暗号化キーは削除されます")){
			connect("暗号化キー作成","setKey",null/*{"pass":document.querySelector("#passkey").value}*/,function(data){
				addLog(data,"暗号化キー作成");
			});
		}
	};
	udDir = function(){
		connect("SVN update","udDir",null,function(data){
			addLog(data,"SVN update");
		});
	};
	setCmnacc = function(){
		connect("共通アカウント設定","setCmnacc",{
				"inp":document.querySelector("#cmnacc_input").value,
				"once":document.querySelector("#cmnacc_once_len").value,
				"uid":document.querySelector("#cmnacc_uid_len").value,
				"pass":document.querySelector("#cmnacc_pass_len").value,
			},function(data){
				addLog(data,"共通アカウント設定");
			}
		);
	};
	setRouting = function(){
		connect("ルーティングリンク","setRouting",null,function(data){
			addLog(data,"ルーティングリンク");
		});
	};
	setMemcache = function(){
		connect("Rpcキャッシュ設定","setMemcache",{"mem":document.querySelector("#memcache").value,"rtime":document.querySelector("#rpctime").value},function(data){
			addLog(data,"Rpcキャッシュ設定");
		});
	};
	clearCache = function(){
		connect("キャッシュクリア","clearCache",null,function(data){
			addLog(data,"キャッシュクリア");
		});
	};
	makeAssets = function(){
		connect("アセットデータを公開中","makeAssets",null,function(data){
			addLog(data,"アセットデータ公開");
		});
	};
	apcInfo = function(){
		connect("APCInfomation","apcInfo",null,function(data){
			addLog(data,"APC情報");
		});
	};
	setSlowRequestTime = function(){
		connect("スローリクエスト時間設定","setSlowRequestTime",{"time":document.querySelector("#slow_request_time").value},function(data){
			addLog(data,"スローリクエスト設定");
		});
	};
	setRequestAggregate = function(){
		connect("リクエスト時間集計設定","setRequestAggregate",{"val":document.querySelector("#request_aggregate").checked?1:0},function(data){
			addLog(data,"リクエスト時間集計設定");
		});
	};
	getRequestAggregate = function(){
		connect("リクエスト時間集計データ","getRequestAggregate",null,function(data){
			var a = document.createElement('a');
			a.download = "リクエスト時間集計.csv";
			a.href = "data:text/csv;,"+encodeURIComponent(data);
			var evt = document.createEvent("MouseEvents");
			evt.initEvent("click",false,true);
			a.dispatchEvent(evt);
		});
	};
	getRequestAggregateOrg = function(){
		connect("リクエスト時間ログ","getRequestAggregateOrg",null,function(data){
			var a = document.createElement('a');
			a.download = "リクエスト時間ログ.csv";
			a.href = "data:text/csv;,"+encodeURIComponent(data);
			var evt = document.createEvent("MouseEvents");
			evt.initEvent("click",false,true);
			a.dispatchEvent(evt);
		});
	};
	clearRequestAggregate = function(){
		if(confirm("現在までの集計情報が削除されます")){
			connect("リクエスト時間集計情報をクリア","clearRequestAggregate",null,function(data){
				addLog(data,"リクエスト時間集計情報をクリア");
			});
		}
	};
	setSlowRequestDetail = function(){
		connect("スローリクエスト詳細","setSlowRequestDetail",{"val":document.querySelector("#slow_request_detail").checked?1:0},function(data){
			addLog(data,"スローリクエスト詳細");
		});
	};
	setJmeterMode = function(){
		connect("Jmeterデータモード設定","setJmeterMode",{"val":document.querySelector("#jmeter_mode").checked?1:0},function(data){
			addLog(data,"Jmeterデータモード設定");
		});
	};
	</script>
	</head>
	<body>
	<div id="main">
	<?php
	$master = "";
	$mport=null;
	$slave = array();
	$sport = array();
	$conf = Spyc::YAMLLoad("app/config/database.yml");
	$data = $conf["doctrine"];
	if(isset($conf["doctrine"]["dbal"]["connections"]["default"])){
		// レプリケーション
		$master = $data["dbal"]["connections"]["default"]["host"];
		$port = $data["dbal"]["connections"]["default"]["port"];
		foreach($data["dbal"]["connections"]["default"]["slaves"] as $val){
			$slave[] = $val["host"];
			$sport[] = $val["port"];
		}
	}else{
		// マスターサーバーのみ
		$master = $data["dbal"]["host"];
		$mport = $data["dbal"]["port"];
	}
	if(is_int($mport))
		$master.=":".$mport;
	for($i=0;$i<count($slave);++$i){
		if(is_int($sport[$i]))
			$slave[$i] .= ":".$sport[$i];
	}
	?>
	DB設定 表記法 URL:PORT<br>デフォルトポート(3306)の場合、URLだけでOK<br>
	DBMaster<input type="text" id="master" value="<?php echo $master;?>"><br>
	DBSlave(カンマ区切り)<input type="text" id="slave" value="<?php echo implode(",",$slave);?>">
	<input style="margin-left:30px;" type="button" onclick="setHost()" value="適用"><br>
	<hr>
	<br>
	<?php
	$lmaster = "";
	$lmport=null;
	$lslave = array();
	$lsport = array();

	$conf = Spyc::YAMLLoad("app/config/database.yml");
	$data = $conf["doctrine"];
	if(isset($conf["doctrine"]["dbal"]["connections"]["log"])){
		// レプリケーション
		$lmaster = $data["dbal"]["connections"]["log"]["host"];
		$port = $data["dbal"]["connections"]["log"]["port"];
		foreach($data["dbal"]["connections"]["log"]["slaves"] as $val){
			$lslave[] = $val["host"];
			$lsport[] = $val["port"];
		}
	}else{
		$lmaster = "";
		$lmport = "";
	}
	if(is_int($mport))
		$lmaster.=":".$lmport;
	for($i=0;$i<count($slave);++$i){
		if(is_int($sport[$i]))
			$lslave[$i] .= ":".$lsport[$i];
	}
	?>
	LOGDB設定 表記法 URL:PORT<br>デフォルトポート(3306)の場合、URLだけでOK<br>
	DBMaster<input type="text" id="lmaster" value="<?php echo $lmaster;?>"><br>
	DBSlave(カンマ区切り)<input type="text" id="lslave" value="<?php echo implode(",",$lslave);?>">
	<input style="margin-left:30px;" type="button" onclick="setLHost()" value="適用"><br>
	<hr>
	<br>
	<br>
	<br>
	<?php 
	$conf = Spyc::YAMLLoad("app/config/kvs.yml");
	$memcache_url = $conf["lsw_memcache"]["clients"]["default"]["hosts"][0]["dsn"];
	$memcache_port = $conf["lsw_memcache"]["clients"]["default"]["hosts"][0]["port"]+0;
	?>
	表記法 URL:PORT<br>　　デフォルトポート(11211)の場合、URLだけでOK<br>
	Memcache<input type="text" id="memcache" value="<?php echo $memcache_url.($memcache_port==11211?"":":".$memcache_port);?>"><br>
	RPCキャッシュ時間(sec)<input type="text" id="rpctime" value="<?php echo dcs\config::ResponsCacheLimit;?>">
	<input style="margin-left:30px;" type="button" onclick="setMemcache()" value="適用"><br>
	<hr>
	<br>
	<!--
	<br>
	DQLなどのORマッピングを使うときだけ使用<br>
	DBからPHPのクラスを生成する(数分かかります).<input type="button" onclick="mkModel()" value="実行">
	<br>
	-->
	<br>
	バンドルフォルダをSVNUpdateする<input type="button" onclick="udDir()" value="実行">
	<hr>
	<br>
	<br>
	暗号化キーを生成する<!--<input type="text" id="passkey" value="">-->
	<input style="margin-left:30px;" type="button" onclick="setKey()" value="実行"><br>
	<hr>
	<!--
	<br>
	<br>
	共通アカウント設定<br>
	　新規アカウントパラメータ
	<select id="cmnacc_input">
		<option value="0" <?php echo dcs\config::NewUserInput==dcs\config::NEW_USER_INPUT_ALL?"selected":"";?>>UIDとパスワードを両方入力させる</option>
		<option value="1" <?php echo dcs\config::NewUserInput==dcs\config::NEW_USER_INPUT_UID?"selected":"";?>>UIDのみ入力させ、パスワードを自動生成する</option>
		<option value="2" <?php echo dcs\config::NewUserInput==dcs\config::NEW_USER_INPUT_AUTO?"selected":"";?>>UIDとパスワードを両方自動生成する</option>
		<option value="3" <?php echo dcs\config::NewUserInput==dcs\config::NEW_USER_INPUT_NONE?"selected":"";?>>UIDのみ自動生成し、パスワード項目は使用しない</option>
	</select><br>
	　セッションキーの長さ
	<select id="cmnacc_once_len">
	<?php
		$max = 5;
		$v = dcs\config::OnceIdLength;
		for($i=0;$i<$max;++$i){
			$l = pow(2,($max-$i+2));
			?>
			<option value="<?php echo $l;?>" <?php echo $v==$l?"selected":"";?>><?php echo $l;?></option>
			<?php
		}
	?>
	</select><br>
	　オートUIDの長さ
	<select id="cmnacc_uid_len">
	<?php
		$max = 5;
		$v = dcs\config::AutoUidLength;
		for($i=0;$i<$max;++$i){
			$l = pow(2,($max-$i+2));
			?>
			<option value="<?php echo $l;?>" <?php echo $v==$l?"selected":"";?>><?php echo $l;?></option>
			<?php
		}
	?>
	</select><br>
	　オートパスワードの長さ
	<select id="cmnacc_pass_len">
	<?php
		$max = 5;
		$v = dcs\config::AutoPassLength;
		for($i=0;$i<$max;++$i){
			$l = pow(2,($max-$i+2));
			?>
			<option value="<?php echo $l;?>" <?php echo $v==$l?"selected":"";?>><?php echo $l;?></option>
			<?php
		}
	?>
	</select><br>
	<input style="margin-left:30px;" type="button" onclick="setCmnacc()" value="実行"><br>
	<hr>
	-->
	<br>
	<br>
	Apcキャッシュ情報を取得
	<input style="margin-left:30px;" type="button" onclick="apcInfo()" value="実行"><br>
	<hr>
	<br>
	<br>
	Symfonyキャッシュをクリアする
	<input style="margin-left:30px;" type="button" onclick="clearCache()" value="実行"><br>
	<hr>
	<br>
	<br>
	アセットデータを公開する
	<input style="margin-left:30px;" type="button" onclick="makeAssets()" value="実行"><br>
	<hr>
	<br>
	<br>
	Resources/routing下のファイル構成を変更したら実行してください
	<input style="margin-left:30px;" type="button" onclick="setRouting()" value="実行"><br>
	<hr>
	<br>
	<br>
	Jmeterモード
	<input type="checkbox" id="jmeter_mode" value="true" <?php echo \Dcs\config::JmeterMode?"checked":"";?>>
	<input style="margin-left:30px;" type="button" onclick="setJmeterMode()" value="適用"><br>
	<hr>
	<br>
	<br>
	スローリクエスト判定時間(msec)　0以下で無効<br>
	<input type="text" id="slow_request_time" value="<?php echo \Dcs\config::SlowRequestTime;?>">
	<input style="margin-left:30px;" type="button" onclick="setSlowRequestTime()" value="適用"><br>
	スローリクエスト詳細
	<input type="checkbox" id="slow_request_detail" value="true" <?php echo \Dcs\config::SlowRequestDetail?"checked":"";?>>
	<input style="margin-left:30px;" type="button" onclick="setSlowRequestDetail()" value="適用"><br>
	<hr>
	<br>
	<br>
	リクエスト時間集計 
	<input type="checkbox" id="request_aggregate" value="true" <?php echo \Dcs\config::RequestAggregate?"checked":"";?>>
	<input style="margin-left:30px;" type="button" onclick="setRequestAggregate()" value="適用"><br>
	集計結果<input style="margin-left:30px;" type="button" onclick="getRequestAggregateOrg()" value="生データ"> <input style="margin-left:30px;" type="button" onclick="getRequestAggregate()" value="取得"><br>
	集計情報をクリア<input style="margin-left:30px;" type="button" onclick="clearRequestAggregate()" value="実行"><br>
	<hr>
<?php 
	if(function_exists("local_gui")){
		local_gui();
	}
?>
	
	</div>
	
	<div id="log"></div>
	<div id="loading">
		<img id="ladingimg" alt="" src="data:image/gif;base64,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">
		<div id="loadingtitle"></div>
	</div>
	</body>
	</html>
<?php 
}
?>

<?php
/**
   * Spyc -- A Simple PHP YAML Class
   * @version 0.5.1
   * @author Vlad Andersen <vlad.andersen@gmail.com>
   * @author Chris Wanstrath <chris@ozmm.org>
   * @link http://code.google.com/p/spyc/
   * @copyright Copyright 2005-2006 Chris Wanstrath, 2006-2011 Vlad Andersen
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   * @package Spyc
   */

if (!function_exists('spyc_load')) {
  /**
   * Parses YAML to array.
   * @param string $string YAML string.
   * @return array
   */
  function spyc_load ($string) {
    return Spyc::YAMLLoadString($string);
  }
}

if (!function_exists('spyc_load_file')) {
  /**
   * Parses YAML to array.
   * @param string $file Path to YAML file.
   * @return array
   */
  function spyc_load_file ($file) {
    return Spyc::YAMLLoad($file);
  }
}

if (!function_exists('spyc_dump')) {
  /**
   * Dumps array to YAML.
   * @param array $data Array.
   * @return string
   */
  function spyc_dump ($data) {
    return Spyc::YAMLDump($data, false, false, true);
  }
}

/**
   * The Simple PHP YAML Class.
   *
   * This class can be used to read a YAML file and convert its contents
   * into a PHP array.  It currently supports a very limited subsection of
   * the YAML spec.
   *
   * Usage:
   * <code>
   *   $Spyc  = new Spyc;
   *   $array = $Spyc->load($file);
   * </code>
   * or:
   * <code>
   *   $array = Spyc::YAMLLoad($file);
   * </code>
   * or:
   * <code>
   *   $array = spyc_load_file($file);
   * </code>
   * @package Spyc
   */
class Spyc {

  // SETTINGS

  const REMPTY = "\0\0\0\0\0";

  /**
   * Setting this to true will force YAMLDump to enclose any string value in
   * quotes.  False by default.
   *
   * @var bool
   */
  public $setting_dump_force_quotes = false;

  /**
   * Setting this to true will forse YAMLLoad to use syck_load function when
   * possible. False by default.
   * @var bool
   */
  public $setting_use_syck_is_possible = false;



  /**#@+
  * @access private
  * @var mixed
  */
  private $_dumpIndent;
  private $_dumpWordWrap;
  private $_containsGroupAnchor = false;
  private $_containsGroupAlias = false;
  private $path;
  private $result;
  private $LiteralPlaceHolder = '___YAML_Literal_Block___';
  private $SavedGroups = array();
  private $indent;
  /**
   * Path modifier that should be applied after adding current element.
   * @var array
   */
  private $delayedPath = array();

  /**#@+
  * @access public
  * @var mixed
  */
  public $_nodeId;

/**
 * Load a valid YAML string to Spyc.
 * @param string $input
 * @return array
 */
  public function load ($input) {
    return $this->__loadString($input);
  }

 /**
 * Load a valid YAML file to Spyc.
 * @param string $file
 * @return array
 */
  public function loadFile ($file) {
    return $this->__load($file);
  }

  /**
     * Load YAML into a PHP array statically
     *
     * The load method, when supplied with a YAML stream (string or file),
     * will do its best to convert YAML in a file into a PHP array.  Pretty
     * simple.
     *  Usage:
     *  <code>
     *   $array = Spyc::YAMLLoad('lucky.yaml');
     *   print_r($array);
     *  </code>
     * @access public
     * @return array
     * @param string $input Path of YAML file or string containing YAML
     */
  public static function YAMLLoad($input) {
    $Spyc = new Spyc;
    return $Spyc->__load($input);
  }

  /**
     * Load a string of YAML into a PHP array statically
     *
     * The load method, when supplied with a YAML string, will do its best
     * to convert YAML in a string into a PHP array.  Pretty simple.
     *
     * Note: use this function if you don't want files from the file system
     * loaded and processed as YAML.  This is of interest to people concerned
     * about security whose input is from a string.
     *
     *  Usage:
     *  <code>
     *   $array = Spyc::YAMLLoadString("---\n0: hello world\n");
     *   print_r($array);
     *  </code>
     * @access public
     * @return array
     * @param string $input String containing YAML
     */
  public static function YAMLLoadString($input) {
    $Spyc = new Spyc;
    return $Spyc->__loadString($input);
  }

  /**
     * Dump YAML from PHP array statically
     *
     * The dump method, when supplied with an array, will do its best
     * to convert the array into friendly YAML.  Pretty simple.  Feel free to
     * save the returned string as nothing.yaml and pass it around.
     *
     * Oh, and you can decide how big the indent is and what the wordwrap
     * for folding is.  Pretty cool -- just pass in 'false' for either if
     * you want to use the default.
     *
     * Indent's default is 2 spaces, wordwrap's default is 40 characters.  And
     * you can turn off wordwrap by passing in 0.
     *
     * @access public
     * @return string
     * @param array $array PHP array
     * @param int $indent Pass in false to use the default, which is 2
     * @param int $wordwrap Pass in 0 for no wordwrap, false for default (40)
     * @param int $no_opening_dashes Do not start YAML file with "---\n"
     */
  public static function YAMLDump($array, $indent = false, $wordwrap = false, $no_opening_dashes = false) {
    $spyc = new Spyc;
    return $spyc->dump($array, $indent, $wordwrap, $no_opening_dashes);
  }


  /**
     * Dump PHP array to YAML
     *
     * The dump method, when supplied with an array, will do its best
     * to convert the array into friendly YAML.  Pretty simple.  Feel free to
     * save the returned string as tasteful.yaml and pass it around.
     *
     * Oh, and you can decide how big the indent is and what the wordwrap
     * for folding is.  Pretty cool -- just pass in 'false' for either if
     * you want to use the default.
     *
     * Indent's default is 2 spaces, wordwrap's default is 40 characters.  And
     * you can turn off wordwrap by passing in 0.
     *
     * @access public
     * @return string
     * @param array $array PHP array
     * @param int $indent Pass in false to use the default, which is 2
     * @param int $wordwrap Pass in 0 for no wordwrap, false for default (40)
     */
  public function dump($array,$indent = false,$wordwrap = false, $no_opening_dashes = false) {
    // Dumps to some very clean YAML.  We'll have to add some more features
    // and options soon.  And better support for folding.

    // New features and options.
    if ($indent === false or !is_numeric($indent)) {
      $this->_dumpIndent = 2;
    } else {
      $this->_dumpIndent = $indent;
    }

    if ($wordwrap === false or !is_numeric($wordwrap)) {
      $this->_dumpWordWrap = 40;
    } else {
      $this->_dumpWordWrap = $wordwrap;
    }

    // New YAML document
    $string = "";
    if (!$no_opening_dashes) $string = "---\n";

    // Start at the base of the array and move through it.
    if ($array) {
      $array = (array)$array;
      $previous_key = -1;
      foreach ($array as $key => $value) {
        if (!isset($first_key)) $first_key = $key;
        $string .= $this->_yamlize($key,$value,0,$previous_key, $first_key, $array);
        $previous_key = $key;
      }
    }
    return $string;
  }

  /**
     * Attempts to convert a key / value array item to YAML
     * @access private
     * @return string
     * @param $key The name of the key
     * @param $value The value of the item
     * @param $indent The indent of the current node
     */
  private function _yamlize($key,$value,$indent, $previous_key = -1, $first_key = 0, $source_array = null) {
    if (is_array($value)) {
      if (empty ($value))
        return $this->_dumpNode($key, array(), $indent, $previous_key, $first_key, $source_array);
      // It has children.  What to do?
      // Make it the right kind of item
      $string = $this->_dumpNode($key, self::REMPTY, $indent, $previous_key, $first_key, $source_array);
      // Add the indent
      $indent += $this->_dumpIndent;
      // Yamlize the array
      $string .= $this->_yamlizeArray($value,$indent);
    } elseif (!is_array($value)) {
      // It doesn't have children.  Yip.
      $string = $this->_dumpNode($key, $value, $indent, $previous_key, $first_key, $source_array);
    }
    return $string;
  }

  /**
     * Attempts to convert an array to YAML
     * @access private
     * @return string
     * @param $array The array you want to convert
     * @param $indent The indent of the current level
     */
  private function _yamlizeArray($array,$indent) {
    if (is_array($array)) {
      $string = '';
      $previous_key = -1;
      foreach ($array as $key => $value) {
        if (!isset($first_key)) $first_key = $key;
        $string .= $this->_yamlize($key, $value, $indent, $previous_key, $first_key, $array);
        $previous_key = $key;
      }
      return $string;
    } else {
      return false;
    }
  }

  /**
     * Returns YAML from a key and a value
     * @access private
     * @return string
     * @param $key The name of the key
     * @param $value The value of the item
     * @param $indent The indent of the current node
     */
  private function _dumpNode($key, $value, $indent, $previous_key = -1, $first_key = 0, $source_array = null) {
    // do some folding here, for blocks
    if (is_string ($value) && ((strpos($value,"\n") !== false || strpos($value,": ") !== false || strpos($value,"- ") !== false ||
      strpos($value,"*") !== false || strpos($value,"#") !== false || strpos($value,"<") !== false || strpos($value,">") !== false || strpos ($value, '  ') !== false ||
      strpos($value,"[") !== false || strpos($value,"]") !== false || strpos($value,"{") !== false || strpos($value,"}") !== false) || strpos($value,"&") !== false || strpos($value, "'") !== false || strpos($value, "!") === 0 ||
      substr ($value, -1, 1) == ':')
    ) {
      $value = $this->_doLiteralBlock($value,$indent);
    } else {
      $value  = $this->_doFolding($value,$indent);
    }

    if ($value === array()) $value = '[ ]';
    if ($value === "") $value = '""';
    if (self::isTranslationWord($value)) {
      $value = $this->_doLiteralBlock($value, $indent);
    }
    if (trim ($value) != $value)
       $value = $this->_doLiteralBlock($value,$indent);

    if (is_bool($value)) {
       $value = $value ? "true" : "false";
    }

    if ($value === null) $value = '~';
    if ($value === "'" . self::REMPTY . "'") $value = null;

    $spaces = str_repeat(' ',$indent);

    //if (is_int($key) && $key - 1 == $previous_key && $first_key===0) {
    if (is_array ($source_array) && array_keys($source_array) === range(0, count($source_array) - 1)) {
      // It's a sequence
      $string = $spaces.'- '.$value."\n";
    } else {
      // if ($first_key===0)  throw new Exception('Keys are all screwy.  The first one was zero, now it\'s "'. $key .'"');
      // It's mapped
      if (strpos($key, ":") !== false || strpos($key, "#") !== false) { $key = '"' . $key . '"'; }
      $string = rtrim ($spaces.$key.': '.$value)."\n";
    }
    return $string;
  }

  /**
     * Creates a literal block for dumping
     * @access private
     * @return string
     * @param $value
     * @param $indent int The value of the indent
     */
  private function _doLiteralBlock($value,$indent) {
    if ($value === "\n") return '\n';
    if (strpos($value, "\n") === false && strpos($value, "'") === false) {
      return sprintf ("'%s'", $value);
    }
    if (strpos($value, "\n") === false && strpos($value, '"') === false) {
      return sprintf ('"%s"', $value);
    }
    $exploded = explode("\n",$value);
    $newValue = '|';
    $indent  += $this->_dumpIndent;
    $spaces   = str_repeat(' ',$indent);
    foreach ($exploded as $line) {
      $newValue .= "\n" . $spaces . ($line);
    }
    return $newValue;
  }

  /**
     * Folds a string of text, if necessary
     * @access private
     * @return string
     * @param $value The string you wish to fold
     */
  private function _doFolding($value,$indent) {
    // Don't do anything if wordwrap is set to 0

    if ($this->_dumpWordWrap !== 0 && is_string ($value) && strlen($value) > $this->_dumpWordWrap) {
      $indent += $this->_dumpIndent;
      $indent = str_repeat(' ',$indent);
      $wrapped = wordwrap($value,$this->_dumpWordWrap,"\n$indent");
      $value   = ">\n".$indent.$wrapped;
    } else {
      if ($this->setting_dump_force_quotes && is_string ($value) && $value !== self::REMPTY)
        $value = '"' . $value . '"';
      if (is_numeric($value) && is_string($value))
        $value = '"' . $value . '"';
    }


    return $value;
  }

  private function isTrueWord($value) {
    $words = self::getTranslations(array('true', 'on', 'yes', 'y'));
    return in_array($value, $words, true);
  }

  private function isFalseWord($value) {
    $words = self::getTranslations(array('false', 'off', 'no', 'n'));
    return in_array($value, $words, true);
  }

  private function isNullWord($value) {
    $words = self::getTranslations(array('null', '~'));
    return in_array($value, $words, true);
  }

  private function isTranslationWord($value) {
    return (
      self::isTrueWord($value)  ||
      self::isFalseWord($value) ||
      self::isNullWord($value)
    );
  }

  /**
     * Coerce a string into a native type
     * Reference: http://yaml.org/type/bool.html
     * TODO: Use only words from the YAML spec.
     * @access private
     * @param $value The value to coerce
     */
  private function coerceValue(&$value) {
    if (self::isTrueWord($value)) {
      $value = true;
    } else if (self::isFalseWord($value)) {
      $value = false;
    } else if (self::isNullWord($value)) {
      $value = null;
    }
  }

  /**
     * Given a set of words, perform the appropriate translations on them to
     * match the YAML 1.1 specification for type coercing.
     * @param $words The words to translate
     * @access private
     */
  private static function getTranslations(array $words) {
    $result = array();
    foreach ($words as $i) {
      $result = array_merge($result, array(ucfirst($i), strtoupper($i), strtolower($i)));
    }
    return $result;
  }

// LOADING FUNCTIONS

  private function __load($input) {
    $Source = $this->loadFromSource($input);
    return $this->loadWithSource($Source);
  }

  private function __loadString($input) {
    $Source = $this->loadFromString($input);
    return $this->loadWithSource($Source);
  }

  private function loadWithSource($Source) {
    if (empty ($Source)) return array();
    if ($this->setting_use_syck_is_possible && function_exists ('syck_load')) {
      $array = syck_load (implode ("\n", $Source));
      return is_array($array) ? $array : array();
    }

    $this->path = array();
    $this->result = array();

    $cnt = count($Source);
    for ($i = 0; $i < $cnt; $i++) {
      $line = $Source[$i];

      $this->indent = strlen($line) - strlen(ltrim($line));
      $tempPath = $this->getParentPathByIndent($this->indent);
      $line = self::stripIndent($line, $this->indent);
      if (self::isComment($line)) continue;
      if (self::isEmpty($line)) continue;
      $this->path = $tempPath;

      $literalBlockStyle = self::startsLiteralBlock($line);
      if ($literalBlockStyle) {
        $line = rtrim ($line, $literalBlockStyle . " \n");
        $literalBlock = '';
        $line .= ' '.$this->LiteralPlaceHolder;
        $literal_block_indent = strlen($Source[$i+1]) - strlen(ltrim($Source[$i+1]));
        while (++$i < $cnt && $this->literalBlockContinues($Source[$i], $this->indent)) {
          $literalBlock = $this->addLiteralLine($literalBlock, $Source[$i], $literalBlockStyle, $literal_block_indent);
        }
        $i--;
      }

      // Strip out comments
      if (strpos ($line, '#')) {
          $line = preg_replace('/\s*#([^"\']+)$/','',$line);
      }

      while (++$i < $cnt && self::greedilyNeedNextLine($line)) {
        $line = rtrim ($line, " \n\t\r") . ' ' . ltrim ($Source[$i], " \t");
      }
      $i--;

      $lineArray = $this->_parseLine($line);

      if ($literalBlockStyle)
        $lineArray = $this->revertLiteralPlaceHolder ($lineArray, $literalBlock);

      $this->addArray($lineArray, $this->indent);

      foreach ($this->delayedPath as $indent => $delayedPath)
        $this->path[$indent] = $delayedPath;

      $this->delayedPath = array();

    }
    return $this->result;
  }

  private function loadFromSource ($input) {
    if (!empty($input) && strpos($input, "\n") === false && file_exists($input))
      $input = file_get_contents($input);

    return $this->loadFromString($input);
  }

  private function loadFromString ($input) {
    $lines = explode("\n",$input);
    foreach ($lines as $k => $_) {
      $lines[$k] = rtrim ($_, "\r");
    }
    return $lines;
  }

  /**
     * Parses YAML code and returns an array for a node
     * @access private
     * @return array
     * @param string $line A line from the YAML file
     */
  private function _parseLine($line) {
    if (!$line) return array();
    $line = trim($line);
    if (!$line) return array();

    $array = array();

    $group = $this->nodeContainsGroup($line);
    if ($group) {
      $this->addGroup($line, $group);
      $line = $this->stripGroup ($line, $group);
    }

    if ($this->startsMappedSequence($line))
      return $this->returnMappedSequence($line);

    if ($this->startsMappedValue($line))
      return $this->returnMappedValue($line);

    if ($this->isArrayElement($line))
     return $this->returnArrayElement($line);

    if ($this->isPlainArray($line))
     return $this->returnPlainArray($line);


    return $this->returnKeyValuePair($line);

  }

  /**
     * Finds the type of the passed value, returns the value as the new type.
     * @access private
     * @param string $value
     * @return mixed
     */
  private function _toType($value) {
    if ($value === '') return "";
    $first_character = $value[0];
    $last_character = substr($value, -1, 1);

    $is_quoted = false;
    do {
      if (!$value) break;
      if ($first_character != '"' && $first_character != "'") break;
      if ($last_character != '"' && $last_character != "'") break;
      $is_quoted = true;
    } while (0);

    if ($is_quoted) {
      $value = str_replace('\n', "\n", $value);
      return strtr(substr ($value, 1, -1), array ('\\"' => '"', '\'\'' => '\'', '\\\'' => '\''));
    }

    if (strpos($value, ' #') !== false && !$is_quoted)
      $value = preg_replace('/\s+#(.+)$/','',$value);

    if ($first_character == '[' && $last_character == ']') {
      // Take out strings sequences and mappings
      $innerValue = trim(substr ($value, 1, -1));
      if ($innerValue === '') return array();
      $explode = $this->_inlineEscape($innerValue);
      // Propagate value array
      $value  = array();
      foreach ($explode as $v) {
        $value[] = $this->_toType($v);
      }
      return $value;
    }

    if (strpos($value,': ')!==false && $first_character != '{') {
      $array = explode(': ',$value);
      $key   = trim($array[0]);
      array_shift($array);
      $value = trim(implode(': ',$array));
      $value = $this->_toType($value);
      return array($key => $value);
    }

    if ($first_character == '{' && $last_character == '}') {
      $innerValue = trim(substr ($value, 1, -1));
      if ($innerValue === '') return array();
      // Inline Mapping
      // Take out strings sequences and mappings
      $explode = $this->_inlineEscape($innerValue);
      // Propagate value array
      $array = array();
      foreach ($explode as $v) {
        $SubArr = $this->_toType($v);
        if (empty($SubArr)) continue;
        if (is_array ($SubArr)) {
          $array[key($SubArr)] = $SubArr[key($SubArr)]; continue;
        }
        $array[] = $SubArr;
      }
      return $array;
    }

    if ($value == 'null' || $value == 'NULL' || $value == 'Null' || $value == '' || $value == '~') {
      return null;
    }

    if ( is_numeric($value) && preg_match ('/^(-|)[1-9]+[0-9]*$/', $value) ){
      $intvalue = (int)$value;
      if ($intvalue != PHP_INT_MAX)
        $value = $intvalue;
      return $value;
    }

    $this->coerceValue($value);

    if (is_numeric($value)) {
      if ($value === '0') return 0;
      if (rtrim ($value, 0) === $value)
        $value = (float)$value;
      return $value;
    }

    return $value;
  }

  /**
     * Used in inlines to check for more inlines or quoted strings
     * @access private
     * @return array
     */
  private function _inlineEscape($inline) {
    // There's gotta be a cleaner way to do this...
    // While pure sequences seem to be nesting just fine,
    // pure mappings and mappings with sequences inside can't go very
    // deep.  This needs to be fixed.

    $seqs = array();
    $maps = array();
    $saved_strings = array();
    $saved_empties = array();

    // Check for empty strings
    $regex = '/("")|(\'\')/';
    if (preg_match_all($regex,$inline,$strings)) {
      $saved_empties = $strings[0];
      $inline  = preg_replace($regex,'YAMLEmpty',$inline);
    }
    unset($regex);

    // Check for strings
    $regex = '/(?:(")|(?:\'))((?(1)[^"]+|[^\']+))(?(1)"|\')/';
    if (preg_match_all($regex,$inline,$strings)) {
      $saved_strings = $strings[0];
      $inline  = preg_replace($regex,'YAMLString',$inline);
    }
    unset($regex);

    // echo $inline;

    $i = 0;
    do {

    // Check for sequences
    while (preg_match('/\[([^{}\[\]]+)\]/U',$inline,$matchseqs)) {
      $seqs[] = $matchseqs[0];
      $inline = preg_replace('/\[([^{}\[\]]+)\]/U', ('YAMLSeq' . (count($seqs) - 1) . 's'), $inline, 1);
    }

    // Check for mappings
    while (preg_match('/{([^\[\]{}]+)}/U',$inline,$matchmaps)) {
      $maps[] = $matchmaps[0];
      $inline = preg_replace('/{([^\[\]{}]+)}/U', ('YAMLMap' . (count($maps) - 1) . 's'), $inline, 1);
    }

    if ($i++ >= 10) break;

    } while (strpos ($inline, '[') !== false || strpos ($inline, '{') !== false);

    $explode = explode(',',$inline);
    $explode = array_map('trim', $explode);
    $stringi = 0; $i = 0;

    while (1) {

    // Re-add the sequences
    if (!empty($seqs)) {
      foreach ($explode as $key => $value) {
        if (strpos($value,'YAMLSeq') !== false) {
          foreach ($seqs as $seqk => $seq) {
            $explode[$key] = str_replace(('YAMLSeq'.$seqk.'s'),$seq,$value);
            $value = $explode[$key];
          }
        }
      }
    }

    // Re-add the mappings
    if (!empty($maps)) {
      foreach ($explode as $key => $value) {
        if (strpos($value,'YAMLMap') !== false) {
          foreach ($maps as $mapk => $map) {
            $explode[$key] = str_replace(('YAMLMap'.$mapk.'s'), $map, $value);
            $value = $explode[$key];
          }
        }
      }
    }


    // Re-add the strings
    if (!empty($saved_strings)) {
      foreach ($explode as $key => $value) {
        while (strpos($value,'YAMLString') !== false) {
          $explode[$key] = preg_replace('/YAMLString/',$saved_strings[$stringi],$value, 1);
          unset($saved_strings[$stringi]);
          ++$stringi;
          $value = $explode[$key];
        }
      }
    }


    // Re-add the empties
    if (!empty($saved_empties)) {
      foreach ($explode as $key => $value) {
        while (strpos($value,'YAMLEmpty') !== false) {
          $explode[$key] = preg_replace('/YAMLEmpty/', '', $value, 1);
          $value = $explode[$key];
        }
      }
    }

    $finished = true;
    foreach ($explode as $key => $value) {
      if (strpos($value,'YAMLSeq') !== false) {
        $finished = false; break;
      }
      if (strpos($value,'YAMLMap') !== false) {
        $finished = false; break;
      }
      if (strpos($value,'YAMLString') !== false) {
        $finished = false; break;
      }
      if (strpos($value,'YAMLEmpty') !== false) {
        $finished = false; break;
      }
    }
    if ($finished) break;

    $i++;
    if ($i > 10)
      break; // Prevent infinite loops.
    }


    return $explode;
  }

  private function literalBlockContinues ($line, $lineIndent) {
    if (!trim($line)) return true;
    if (strlen($line) - strlen(ltrim($line)) > $lineIndent) return true;
    return false;
  }

  private function referenceContentsByAlias ($alias) {
    do {
      if (!isset($this->SavedGroups[$alias])) { echo "Bad group name: $alias."; break; }
      $groupPath = $this->SavedGroups[$alias];
      $value = $this->result;
      foreach ($groupPath as $k) {
        $value = $value[$k];
      }
    } while (false);
    return $value;
  }

  private function addArrayInline ($array, $indent) {
      $CommonGroupPath = $this->path;
      if (empty ($array)) return false;

      foreach ($array as $k => $_) {
        $this->addArray(array($k => $_), $indent);
        $this->path = $CommonGroupPath;
      }
      return true;
  }

  private function addArray ($incoming_data, $incoming_indent) {

   // print_r ($incoming_data);

    if (count ($incoming_data) > 1)
      return $this->addArrayInline ($incoming_data, $incoming_indent);

    $key = key ($incoming_data);
    $value = isset($incoming_data[$key]) ? $incoming_data[$key] : null;
    if ($key === '__!YAMLZero') $key = '0';

    if ($incoming_indent == 0 && !$this->_containsGroupAlias && !$this->_containsGroupAnchor) { // Shortcut for root-level values.
      if ($key || $key === '' || $key === '0') {
        $this->result[$key] = $value;
      } else {
        $this->result[] = $value; end ($this->result); $key = key ($this->result);
      }
      $this->path[$incoming_indent] = $key;
      return;
    }



    $history = array();
    // Unfolding inner array tree.
    $history[] = $_arr = $this->result;
    foreach ($this->path as $k) {
      $history[] = $_arr = $_arr[$k];
    }

    if ($this->_containsGroupAlias) {
      $value = $this->referenceContentsByAlias($this->_containsGroupAlias);
      $this->_containsGroupAlias = false;
    }


    // Adding string or numeric key to the innermost level or $this->arr.
    if (is_string($key) && $key == '<<') {
      if (!is_array ($_arr)) { $_arr = array (); }

      $_arr = array_merge ($_arr, $value);
    } else if ($key || $key === '' || $key === '0') {
      if (!is_array ($_arr))
        $_arr = array ($key=>$value);
      else
        $_arr[$key] = $value;
    } else {
      if (!is_array ($_arr)) { $_arr = array ($value); $key = 0; }
      else { $_arr[] = $value; end ($_arr); $key = key ($_arr); }
    }

    $reverse_path = array_reverse($this->path);
    $reverse_history = array_reverse ($history);
    $reverse_history[0] = $_arr;
    $cnt = count($reverse_history) - 1;
    for ($i = 0; $i < $cnt; $i++) {
      $reverse_history[$i+1][$reverse_path[$i]] = $reverse_history[$i];
    }
    $this->result = $reverse_history[$cnt];

    $this->path[$incoming_indent] = $key;

    if ($this->_containsGroupAnchor) {
      $this->SavedGroups[$this->_containsGroupAnchor] = $this->path;
      if (is_array ($value)) {
        $k = key ($value);
        if (!is_int ($k)) {
          $this->SavedGroups[$this->_containsGroupAnchor][$incoming_indent + 2] = $k;
        }
      }
      $this->_containsGroupAnchor = false;
    }

  }

  private static function startsLiteralBlock ($line) {
    $lastChar = substr (trim($line), -1);
    if ($lastChar != '>' && $lastChar != '|') return false;
    if ($lastChar == '|') return $lastChar;
    // HTML tags should not be counted as literal blocks.
    if (preg_match ('#<.*?>$#', $line)) return false;
    return $lastChar;
  }

  private static function greedilyNeedNextLine($line) {
    $line = trim ($line);
    if (!strlen($line)) return false;
    if (substr ($line, -1, 1) == ']') return false;
    if ($line[0] == '[') return true;
    if (preg_match ('#^[^:]+?:\s*\[#', $line)) return true;
    return false;
  }

  private function addLiteralLine ($literalBlock, $line, $literalBlockStyle, $indent = -1) {
    $line = self::stripIndent($line, $indent);
    if ($literalBlockStyle !== '|') {
        $line = self::stripIndent($line);
    }
    $line = rtrim ($line, "\r\n\t ") . "\n";
    if ($literalBlockStyle == '|') {
      return $literalBlock . $line;
    }
    if (strlen($line) == 0)
      return rtrim($literalBlock, ' ') . "\n";
    if ($line == "\n" && $literalBlockStyle == '>') {
      return rtrim ($literalBlock, " \t") . "\n";
    }
    if ($line != "\n")
      $line = trim ($line, "\r\n ") . " ";
    return $literalBlock . $line;
  }

   function revertLiteralPlaceHolder ($lineArray, $literalBlock) {
     foreach ($lineArray as $k => $_) {
      if (is_array($_))
        $lineArray[$k] = $this->revertLiteralPlaceHolder ($_, $literalBlock);
      else if (substr($_, -1 * strlen ($this->LiteralPlaceHolder)) == $this->LiteralPlaceHolder)
	       $lineArray[$k] = rtrim ($literalBlock, " \r\n");
     }
     return $lineArray;
   }

  private static function stripIndent ($line, $indent = -1) {
    if ($indent == -1) $indent = strlen($line) - strlen(ltrim($line));
    return substr ($line, $indent);
  }

  private function getParentPathByIndent ($indent) {
    if ($indent == 0) return array();
    $linePath = $this->path;
    do {
      end($linePath); $lastIndentInParentPath = key($linePath);
      if ($indent <= $lastIndentInParentPath) array_pop ($linePath);
    } while ($indent <= $lastIndentInParentPath);
    return $linePath;
  }


  private function clearBiggerPathValues ($indent) {


    if ($indent == 0) $this->path = array();
    if (empty ($this->path)) return true;

    foreach ($this->path as $k => $_) {
      if ($k > $indent) unset ($this->path[$k]);
    }

    return true;
  }


  private static function isComment ($line) {
    if (!$line) return false;
    if ($line[0] == '#') return true;
    if (trim($line, " \r\n\t") == '---') return true;
    return false;
  }

  private static function isEmpty ($line) {
    return (trim ($line) === '');
  }


  private function isArrayElement ($line) {
    if (!$line || !is_scalar($line)) return false;
    if (substr($line, 0, 2) != '- ') return false;
    if (strlen ($line) > 3)
      if (substr($line,0,3) == '---') return false;

    return true;
  }

  private function isHashElement ($line) {
    return strpos($line, ':');
  }

  private function isLiteral ($line) {
    if ($this->isArrayElement($line)) return false;
    if ($this->isHashElement($line)) return false;
    return true;
  }


  private static function unquote ($value) {
    if (!$value) return $value;
    if (!is_string($value)) return $value;
    if ($value[0] == '\'') return trim ($value, '\'');
    if ($value[0] == '"') return trim ($value, '"');
    return $value;
  }

  private function startsMappedSequence ($line) {
    return (substr($line, 0, 2) == '- ' && substr ($line, -1, 1) == ':');
  }

  private function returnMappedSequence ($line) {
    $array = array();
    $key         = self::unquote(trim(substr($line,1,-1)));
    $array[$key] = array();
    $this->delayedPath = array(strpos ($line, $key) + $this->indent => $key);
    return array($array);
  }

  private function checkKeysInValue($value) {
    if (strchr('[{"\'', $value[0]) === false) {
      if (strchr($value, ': ') !== false) {
          throw new Exception('Too many keys: '.$value);
      }
    }
  }

  private function returnMappedValue ($line) {
    $this->checkKeysInValue($line);
    $array = array();
    $key         = self::unquote (trim(substr($line,0,-1)));
    $array[$key] = '';
    return $array;
  }

  private function startsMappedValue ($line) {
    return (substr ($line, -1, 1) == ':');
  }

  private function isPlainArray ($line) {
    return ($line[0] == '[' && substr ($line, -1, 1) == ']');
  }

  private function returnPlainArray ($line) {
    return $this->_toType($line);
  }

  private function returnKeyValuePair ($line) {
    $array = array();
    $key = '';
    if (strpos ($line, ': ')) {
      // It's a key/value pair most likely
      // If the key is in double quotes pull it out
      if (($line[0] == '"' || $line[0] == "'") && preg_match('/^(["\'](.*)["\'](\s)*:)/',$line,$matches)) {
        $value = trim(str_replace($matches[1],'',$line));
        $key   = $matches[2];
      } else {
        // Do some guesswork as to the key and the value
        $explode = explode(': ', $line);
        $key     = trim(array_shift($explode));
        $value   = trim(implode(': ', $explode));
        $this->checkKeysInValue($value);
      }
      // Set the type of the value.  Int, string, etc
      $value = $this->_toType($value);
      if ($key === '0') $key = '__!YAMLZero';
      $array[$key] = $value;
    } else {
      $array = array ($line);
    }
    return $array;

  }


  private function returnArrayElement ($line) {
     if (strlen($line) <= 1) return array(array()); // Weird %)
     $array = array();
     $value   = trim(substr($line,1));
     $value   = $this->_toType($value);
     if ($this->isArrayElement($value)) {
       $value = $this->returnArrayElement($value);
     }
     $array[] = $value;
     return $array;
  }


  private function nodeContainsGroup ($line) {
    $symbolsForReference = 'A-z0-9_\-';
    if (strpos($line, '&') === false && strpos($line, '*') === false) return false; // Please die fast ;-)
    if ($line[0] == '&' && preg_match('/^(&['.$symbolsForReference.']+)/', $line, $matches)) return $matches[1];
    if ($line[0] == '*' && preg_match('/^(\*['.$symbolsForReference.']+)/', $line, $matches)) return $matches[1];
    if (preg_match('/(&['.$symbolsForReference.']+)$/', $line, $matches)) return $matches[1];
    if (preg_match('/(\*['.$symbolsForReference.']+$)/', $line, $matches)) return $matches[1];
    if (preg_match ('#^\s*<<\s*:\s*(\*[^\s]+).*$#', $line, $matches)) return $matches[1];
    return false;

  }

  private function addGroup ($line, $group) {
    if ($group[0] == '&') $this->_containsGroupAnchor = substr ($group, 1);
    if ($group[0] == '*') $this->_containsGroupAlias = substr ($group, 1);
    //print_r ($this->path);
  }

  private function stripGroup ($line, $group) {
    $line = trim(str_replace($group, '', $line));
    return $line;
  }
}

// Enable use of Spyc from command line
// The syntax is the following: php Spyc.php spyc.yaml

do {
  if (PHP_SAPI != 'cli') break;
  if (empty ($_SERVER['argc']) || $_SERVER['argc'] < 2) break;
  if (empty ($_SERVER['PHP_SELF']) || FALSE === strpos ($_SERVER['PHP_SELF'], 'Spyc.php') ) break;
  $file = $argv[1];
  echo json_encode (spyc_load_file ($file));
} while (0);
?>