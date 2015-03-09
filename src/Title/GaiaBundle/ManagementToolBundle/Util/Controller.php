<?php

namespace Title\GaiaBundle\ManagementToolBundle\Util;

use Gaia\Bundle\ManagementToolBundle\Controller\Abstracts\WithSideAndTabMenuController;
use Symfony\Component\HttpFoundation\Response;

class Controller extends WithSideAndTabMenuController{
	use \Dcs\Base;
	
	protected function run(callable $logic){
		$ret = ["code"=>0,"data"=>null];
		$header = getallheaders ();

		if(!isset($header["AJAX"])){
			$ret["code"] = -7;
			$ret["data"] = "AJAX通信ではありません";
			return new Response(json_encode($ret));	
		}
		$res = new Response();
		$sql = $this->getSql();
		try{
			$ret["data"] = $logic(isset($_POST["data"])?json_decode($_POST["data"],true):null,$res->headers);
			if($sql->isTransactionActive()){
				$sql->commit();
				$sql = null;
			}
		}catch(\Exception $e){
			\Dcs\Toybox::printException($e);
			$code = $e->getCode();
			if($code == 0)
				$code = -2;
			$ret["code"] = $code;
			$ret["data"] = $e->getMessage();
			if($sql != null && $sql->isTransactionActive()){
				$sql->rollBack();
			}
		}
		$res->setContent(json_encode($ret));
		return $res;
	}
}

?>