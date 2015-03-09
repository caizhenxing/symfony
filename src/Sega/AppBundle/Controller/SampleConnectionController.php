<?php
namespace Sega\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Security as sec;
use \Dcs as dcs;

class SampleConnectionController extends \Dcs\DcsController{
	
	/* Unity側サンプル SecuritySample */

	// RPCオブジェクトとMemcacheの使い方
	public function rpcmemcacheAction($data){
		
		// 二十更新防止のためのキャッシュ
		$is_use_cache = true;
		$memcache = null;
		if($is_use_cache){
			$memcache = $this->getMemcached();
		}
		
		$rpc = new dcs\Rpc($memcache);
		
		// このキャッシュは、Unity側で同じHttpHandlerを使用したときだけ有効になる
		// 使用方法としては、サーバーからクライアントへの通信時にエラーが起きると
		// 2重更新の恐れがあるため
		// readしかしない場合は、必要ない
		$cache = $rpc->getCache(true);
		if($cache !== FALSE){
			return new Response($cache);
		}
		
		// Memcache
		$mem = $this->getMemcached();
		
		$index = $mem->get("SampleConnectionKey");// データ取得
		if($index === FALSE){
			$index = 0;
		}else{
			$index += 1;
		}
		$mem->set("SampleConnectionKey",$index,0,60);// インクリメントした値を保存
		
		
		$rpc->data = $data." index:".$index;
		
		
		return new Response($rpc->toJson(sec\Mode::NONE()));
	}	
};
