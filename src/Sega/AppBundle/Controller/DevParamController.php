<?php
// 開発用パラメータ取得
namespace Sega\AppBundle\Controller;


use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Gaia\Bundle\HandlerSocketBundle\Util\HandlerSocketUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Security as sec;

class DevParamController extends \Dcs\DcsController
{
	/**
	 * アプリケーション設定を取得
	 * リクエストデータ構造
	 * data: string // 
	 * RPC構造
	 * data:float
	 * err: {
	 * 		code: int 1:
	 * 		mes: 
	 * }
	 */
	public function getAction($data)
	{
		
		$rpc = new \Dcs\Rpc();
		$rs = $this->get('Arpg.Logic.Util.DevParam')->all();
		$rpc->data = [];
		foreach($rs as $id=>$val){
			$rpc->data[] = [
				'id' => $id,
				'val' => $val
			];
		}

		return new Response($rpc->toJson(sec\Mode::X_OR()));
	}
}
?>