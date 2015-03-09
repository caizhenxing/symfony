<?php
namespace Dcs;

use \Dcs\Security\Mode as Mode;
// UnityフォルダのResources/Gaia/にAppBundle/public.txtをコピーする

class Security{
	/**
	 * 暗号化を行う
	 * @param Mode $mode 暗号化モード
	 * @param string $data 暗号化する文字列
	 * @param string $key 暗号化モードがRSAの場合に必要なパブリックキー　RSAでなければ使用されない
	 * @return string or false 成功した暗号化文字列 失敗した場合false
	 */
	public static function encrypt(Mode $mode, $data, $key=null){
		if($mode->equal(Mode::NONE())){
			return $data;
		}elseif($mode->equal(Mode::X_OR())){
			$key=self::loadPublicKey();
			$len = strlen($data);
			
			$seed = substr(str_repeat($key,intval($len/strlen($key))+1),0,$len);
			return str_replace('/','_',base64_encode($data ^ $seed));
		}elseif($mode->equal(Mode::RSA())){
			$key=openssl_pkey_get_public(base64_decode(str_replace('_','/',$key)));
			$maxlength = 117;
			$out = '';
			while($data){
				$input = substr($data,0,$maxlength);
				$data=substr($data,$maxlength);
				openssl_public_encrypt($input,$output,$key);
				$out .= $output;
			}
			return str_replace('/','_',base64_encode($out));
		}
		return false;
	}
	
	/**
	 * 復号化を行う
	 * @param Mode $mode 暗号化モード
	 * @param $data 暗号化されたデータ
	 * @return string or false 成功した復号化文字列 失敗した場合false
	 */
	public static function decrypt(Mode $mode, $data){
		if($mode->equal(Mode::NONE())){
			return $data;
		}elseif($mode->equal(Mode::X_OR())){
			$key=self::loadPublicKey();
			$data = base64_decode(str_replace('_','/',$data));
			$len = strlen($data);
			$seed = substr(str_repeat($key,intval($len/strlen($key))+1),0,$len);
			return $data ^ $seed;
		}elseif($mode->equal(Mode::RSA())){
			$key=openssl_pkey_get_private(self::loadPrivateKey());
			$data = base64_decode(str_replace('_','/',$data));
			$maxlength = 128;
			$out = '';
			while($data){
				$input = substr($data,0,$maxlength);
				$data=substr($data,$maxlength);
				openssl_private_decrypt($input,$output,$key);
				$out .= $output;
			}
			return $out;
			
		}
		return false;
	}
	
	
	public static function loadKey($key, $type = false)
	{
		if ($type === false) {
			$types = array(
					CRYPT_RSA_PUBLIC_FORMAT_RAW,
					CRYPT_RSA_PRIVATE_FORMAT_PKCS1,
					CRYPT_RSA_PRIVATE_FORMAT_XML,
					CRYPT_RSA_PRIVATE_FORMAT_PUTTY,
					CRYPT_RSA_PUBLIC_FORMAT_OPENSSH
			);
			foreach ($types as $type) {
				$components = $this->_parseKey($key, $type);
				if ($components !== false) {
					break;
				}
			}
	
		} else {
			$components = $this->_parseKey($key, $type);
		}
	
		if ($components === false) {
			return false;
		}
	
		if (isset($components['comment']) && $components['comment'] !== false) {
			$this->comment = $components['comment'];
		}
		$this->modulus = $components['modulus'];
		$this->k = strlen($this->modulus->toBytes());
		$this->exponent = isset($components['privateExponent']) ? $components['privateExponent'] : $components['publicExponent'];
		if (isset($components['primes'])) {
			$this->primes = $components['primes'];
			$this->exponents = $components['exponents'];
			$this->coefficients = $components['coefficients'];
			$this->publicExponent = $components['publicExponent'];
		} else {
			$this->primes = array();
			$this->exponents = array();
			$this->coefficients = array();
			$this->publicExponent = false;
		}
	
		return true;
	}
	private static function loadPublicKey(){
		$is_apc = extension_loaded('apcu');
		$key = 'Dcs.Security.LoadPublicKey';
		$ret = false;
		if($is_apc){
			$ret = apc_fetch($key);
		}
		if($ret === false){
			$ret = file_get_contents(__DIR__.'/../public.txt');
			apc_store($key,$ret,0);
		}
		return $ret;
	}
	private static function loadPrivateKey(){
		$is_apc = extension_loaded('apcu');
		$key = 'Dcs.Security.LoadPrivateKey';
		$ret = false;
		if($is_apc){
			$ret = apc_fetch($key);
		}
		if($ret === false){
			$ret = file_get_contents(__DIR__.'/../private.txt');
			apc_store($key,$ret,0);
		}
		return $ret;
	}
}
?>