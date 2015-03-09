<?php
namespace Dcs;

use \Dcs\Security as sec;

class Rpc{
	public $data = null;
	public $err = null;
	
	/**
	 * コンストラクタ
	 * @param Memcached $serv Memcache接続サービス 指定しない場合キャッシュを無効化
	 */
	public function __construct(){
	}
	/**
	 * Json化する
	 * @param sec\Mode $enc	暗号化形式
	 * @param string $key RSAの場合必要な暗号化キー RSA出ない場合は、nullを入れること
	 * @return Json＋暗号化の結果
	 */
	public function toJson(sec\Mode $enc, $key = null){
		$buff = array();
		if($this->err != null){
			$buff['err'] = $this->err;
		}else{
			$buff['data'] = $this->data;
		}
		$ret = json_encode($buff);
		
		if(\Dcs\config::JmeterMode){
			\Dcs\Jmeter::setRpc($this);
		}
		
		$ret = sec::encrypt($enc,$ret,$key);
		return $ret;
	}
}
?>