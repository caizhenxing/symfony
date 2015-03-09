<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Title\GaiaBundle\ManagementToolBundle\Util\Controller;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class ServerInfoController extends Controller{
	use \Dcs\Base;
	
    public function viewAction(){
        return $this->render('TitleManagementToolBundle:master:server_info.html.twig', [
        		"title" => "サーバー情報",
        		"data" => $this->makeJson(),
        		"ljs"=>"server_info.js",
        		"JUMP_URL"=>["server_info_save","server_info_data"]
		]);
    }
    public function dataAction(){
    	return $this->run(function($data){
    		return $this->makeData();
    	});
    }
    public function saveAction(){
    	return $this->run(function($data){
    		$con = $this->getSql();
    		$this->useTransaction();
    		$sql = "delete from game_info where type = ?";
    		$ptmt = $con->prepare($sql);
    		$ptmt->execute(['matching_server']);
    		
    		$sql = "insert into game_info (type,idx,data) values(?,?,?),(?,?,?),(?,?,?)";
    		$args = [
				"app_version",0,$data["apv"],
				"asset_bundle_server",0,$data["abs"],
				"asset_bundle_version",0,$data["abv"],
    		];
    		if(isset($data["mtc"])){
    			$i = 0;
	    		foreach($data["mtc"] as $dat){
	    			$sql .= ",(?,?,?)";
	    			$args[] = "matching_server";
	    			$args[] = $i;
	    			$args[] = $dat;
	    			++$i;
	    		}
    		}
    		$sql .= " on duplicate key update data = values(data)";
    		$ptmt = $con->prepare($sql);
    		$ptmt->execute($args);
    		
    		return true;
    	});
    }
    
    public function makeData(){

    	$app_ver = "1";
    	$ab_server = "";
    	$ab_version = "";
    	$matching = [];
    	$rs = $this->getHs()->select(
    			new Table("game_info",["type","idx","data"],"IDX_INDEX"),
    			new Query([">="=>0],-1)
    	);
    	foreach($rs as $row){
    		$idx = intval($row[1]);
    		if(strcmp("app_version",$row[0]) == 0){
    			if($idx == 0){
    				$app_ver = $row[2];
    			}
    		}elseif(strcmp("asset_bundle_server",$row[0]) == 0){
    			if($idx == 0){
    				$ab_server = $row[2];
    			}
    		}elseif(strcmp("asset_bundle_version",$row[0]) == 0){
    			if($idx == 0){
    				$ab_version = $row[2];
    			}
    		}elseif(strcmp("matching_server",$row[0]) == 0){
    			$matching[] = $row[2];
    		}
    	}
    	$data = [
    	"apv" => $app_ver,
    	"abs" => $ab_server,
    	"abv" => $ab_version,
    	"mtc" => $matching
    	];
    	return $data;
    }
    public function makeJson(){
    	return json_encode($this->makeData());
    }
}