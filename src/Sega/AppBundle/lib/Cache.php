<?php
namespace Dcs;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class Cache{
	//キャッシュタイプ
	const TYPE_MEMCACHE=0;	// Memcachedサーバーによるキャッシュ
	const TYPE_APC=1;		// APCuによる共有メモリキャッシュ
	
	public function __construct(\Symfony\Component\DependencyInjection\Container $services){
		$this->mSc = $services;
		$this->mEnableApc = extension_loaded('apcu');
	}

	/**
	 * APC使えるチェック
	 */
	public function enableAPC(){
		return $this->mEnableApc;
	}
	/**
	 * 最後に実行したMemcacheのリザルトコードを取得する
	 */
	public function memcacheCode(){
		return $this->mSc->get('memcache.default')->getResultCode();
	}
	/**
	 * キャッシュ取得
	 * Memcacheの場合、オブジェクト型でキャッシュしたものは、連想配列で返される
	 * APCの場合、オブジェクト型でキャッシュしたものは、オブジェクト型で返される
	 * @param int $type キャッシュタイプ TYPE_***定数参照
	 * @param string $key キャッシュキー
	 * @return mixed キャッシュデータ 存在しない場合 null
	 */
	public function get($type,$key){
		$key = $this->compKey($key);
		$ret = null;
		
		if($type==self::TYPE_APC && $this->mEnableApc){
			$ret = apc_fetch($key);
		}elseif(array_key_exists($key,self::$mLocalCache)){
			$ret = self::$mLocalCache[$key];
		}elseif($type==self::TYPE_MEMCACHE){
			$ret = $this->mSc->get('memcache.default')->get($key);
		}
		if($ret === false)
			$ret = null;
		return $ret;
	}
	
	/**
	 * キャッシュ設定
	 * @param int $type キャッシュタイプ TYPE_***定数参照
	 * @param string $key キャッシュキー
	 * @param mixed $value キャッシュデータ
	 * @param int $time キャッシュ時間 0以下の場合6時間 デフォルト値:0
	 */
	public function set($type, $key, $value, $time = 0){
		$key = $this->compKey($key);
		if($time <= 0) $time = 3600*6;
		
		if($type==self::TYPE_MEMCACHE || !$this->mEnableApc)
			self::$mLocalCache[$key] = $value;
		
		if($type==self::TYPE_MEMCACHE)
			$this->mSc->get('memcache.default')->set($key,$value,$time);
		elseif($this->mEnableApc)
			apc_store($key,$value,$time);
	}
	
	/**
	 * キャッシュ追加
	 * @param int $type キャッシュタイプ TYPE_***定数参照
	 * @param string $key キャッシュキー
	 * @param mixed $value キャッシュデータ
	 * @param int $time キャッシュ時間 0以下の場合6時間 デフォルト値:0
	 * @return bool 同一キーによる追加をした場合やなんか失敗した場合、falseが返る
	 */
	public function add($type, $key, $value, $time = 0){
		$key = $this->compKey($key);
		if($time <= 0) $time = 3600*6;
		
		if($type==self::TYPE_MEMCACHE || !$this->mEnableApc)
			self::$mLocalCache[$key] = $value;
		$ret = false;
		if($type==self::TYPE_MEMCACHE){
			$ret = $this->mSc->get('memcache.default')->add($key,$value,$time);
		}elseif($this->mEnableApc)
			$ret = apc_add($key,$value,$time);
		return $ret;
	}
	
	/**
	 * キャッシュ削除
	 * @param int $type キャッシュタイプ TYPE_***定数参照
	 * @param string|array $key キャッシュキー stringの場合単一削除 arrayの場合、内部の値で複数削除
	 */
	public function delete($type,$key){
		$key = $this->compKey($key);
		if($type==self::TYPE_MEMCACHE){
			if(is_array($key)){
				$this->mSc->get('memcache.default')->deleteMulti($key);
			}else{
				$this->mSc->get('memcache.default')->delete($key);
			}
		}elseif($type==self::TYPE_APC && $this->mEnableApc){
			if(is_array($key)){
				foreach($key as $k){
					apd_delete($k);
				}
			}else{
				apc_delete($key);
			}
		}
	}
	
	/**
	 * キャッシュを全削除する
	 * @param int $type キャッシュタイプ TYPE_***定数参照
	 */
	public function flush($type){
		if($type==self::TYPE_MEMCACHE){
			$this->mSc->get('memcache.default')->flush();
		}elseif($type==self::TYPE_APC && $this->mEnableApc){
			apc_clear_cache('user');
		}
	}
	
	private function compKey($key){
		return base64_encode(gzcompress($key,1));
	}
	
	
	private $mEnableApc=false;
	private $mSc;
	private static $mLocalCache = [];
}
?>