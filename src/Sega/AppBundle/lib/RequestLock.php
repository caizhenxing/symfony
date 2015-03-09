<?php
namespace Dcs;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\DetailTimeLog as DTL;

/**
 * アクションログクラス
 * @author Takeda_Yoshihiro
 */
class RequestLock{
	// 上から順に早い
	const LOCK_NONE = 0;
	const LOCK_LV1 = 1;		// ユーザーID,キャッシュキーでロックを行う 対障害用
	const LOCK_LV2 = 2;		// ユーザーID,キャッシュキー,リクエストパスでロックを行う 対チート
	const LOCK_LV3 = 3;		// ユーザーIDでのみロックを行う 対チート
	
	/**
	 * ロックを取得する
	 * リクエストヘッダに、リトライ用のユニークっぽいキー SegaCacheKeyを入れること
	 * @param int $user_id ユーザーID
	 * @param int $lock_lv ロックレベル
	 * 
	 * @throws \Symfony\Component\HttpKernel\Exception\HttpException
	 * @throws AlreadyResponseException  すでにレスポンスが作成されている場合
	 */
	public function lock($user_id, $lock_lv){
		if(\Dcs\config::JmeterMode && $this->get('Dcs.Jmeter')->getPem() != null)
				return;	// Jmeterモードの場合ロックしない
		
		DTL::Lap('RequestLock.lock start');
		if($this->isLocked()){
			\Dcs\Log::e('this request is already calling "lock"method. ',true);
			return;
		}
		$ckey = null;
		$header = getallheaders();
		if(isset($header[self::HeaderKey]))
			$ckey = $header[self::HeaderKey];
		else{
			if($this->get('kernel')->isDebug()){
				$ckey = rand().'.'.time();
			}else{
				\Dcs\Log::e('dont find request header ['.self::HeaderKey.'].',true);
				return;
			}
		} 
		if(!is_numeric($user_id) || intval($user_id) < 1){
			\Dcs\Log::e('user_id is invalid value.',true);
			return;
		}
		
		DTL::Lap('enable check');
		
		$lock_lv = intval($lock_lv);
		$this->setParam($ckey,intval($user_id));

		$keys = $this->mLockKey;
		
		$buf_lock = [false,false,false];
		for($i=0;$i<3;++$i){
			$bl = $this->mCache->add($this->mCacheType,$keys[$i],$this->mRoute,$this->mDustTime);
			if(!$bl && $this->mCacheType == \Dcs\Cache::TYPE_MEMCACHE){
				$code = $this->mCache->memcacheCode();
				if($code != \Memcached::RES_NOTSTORED){
					throw new \Symfony\Component\HttpKernel\Exception\HttpException(500,'RequestLock memcache error code['.$code.'].');
				}
			}
			$buf_lock[$i] = $bl;
		}
		$this->mLock = $buf_lock;

		switch($lock_lv){
			case self::LOCK_LV3:
				if(!$buf_lock[2]){
					$key_str = json_encode($this->mLockKey);
					$this->delete();
					throw new \Symfony\Component\HttpKernel\Exception\HttpException(408,'RequestLock already locked Lv3. lock route is '.$this->mCache->get($this->mCacheType,$keys[2]).'. key is '.$key_str.'.');
				}
			case self::LOCK_LV2:
				if(!$buf_lock[1]){
					$key_str = json_encode($this->mLockKey);
					$this->delete();
					throw new \Symfony\Component\HttpKernel\Exception\HttpException(408,'RequestLock already locked Lv2. key is '.$key_str.'.');
				}
			case self::LOCK_LV1:
				if(!$buf_lock[0]){
					$key_str = json_encode($this->mLockKey);
					$this->delete();
					throw new \Symfony\Component\HttpKernel\Exception\HttpException(408,'RequestLock already locked Lv1. key is '.$key_str.'.');
				}
				break;
			default:
				throw new \Symfony\Component\HttpKernel\Exception\HttpException(500,'RequestLock lock level is invalid value.');
				$this->delete();
				return;
		}
		$res = null;
		switch($this->mResType){
			case \Dcs\Cache::TYPE_MEMCACHE:
				$res = $this->mCache->get($this->mCacheType,'res'.$this->mLockKey[0]);
				break;
			case \Dcs\Cache::TYPE_APC:
				$res = $this->mCache->get($this->mCacheType,'res'.$this->mLockKey[0]);
				break;
			case 2:	//DB
				$rs = $this->mHs->select(new Table('DCS_RESPONSE',['response']),new Query(['='=>'res'.$this->mLockKey[0]]));
				if(!empty($rs)){
					$res = $rs[0][0];
				}
				break;
			default:
				throw new \Symfony\Component\HttpKernel\Exception\HttpException(500,'RequestLock response type is invalid value.');
				$this->delete();
				break;
		}
		if($res == null)
			return;
		$this->delete();
		\Dcs\Log::i('Find ResponseCache lv'.$lock_lv);
		throw new Dcs\RequestLock\AlreadyResponseException($res);
	}
	/**
	 * ロック解除する
	 * @param string $response レスポンスデータ
	 * @throws \Symfony\Component\HttpKernel\Exception\HttpException
	 */
	public function unlock($response){
		if(\Dcs\config::JmeterMode && $this->get('Dcs.Jmeter')->getPem() != null)
			return;	// Jmeterモードの場合ロックしない
		
		if(!$this->isLocked()){
			\Dcs\Log::v('dont request locked.');
			return;
		}
		\Dcs\Log::v('request unlock.');
		if(!$this->mCon->isTransactionActive()){
			throw new \Symfony\Component\HttpKernel\Exception\HttpException(500,'RequestLock.unlock can not use outside of transaction.');
		}
		if($response === null)
			$response = '';
		
		switch($this->mResType){
			case \Dcs\Cache::TYPE_MEMCACHE:
				$this->mCache->set($this->mCacheType,'res'.$this->mLockKey[0],$response,$this->mResTime);
				break;
			case \Dcs\Cache::TYPE_APC:
				$this->mCache->set($this->mCacheType,'res'.$this->mLockKey[0],$response,$this->mResTime);
				break;
			case 2:	//DB書き込み
				$sql = 'insert into DCS_RESPONSE (`key`,response,create_date) values(?,?,?)';
				$this->mCon->executeUpdate($sql,['res'.$this->mLockKey[0],$response,date('Y-m-d H:i:s')]);
				break;
			default:
				throw new \Symfony\Component\HttpKernel\Exception\HttpException(500,'RequestLock response type is invalid value.');
				$this->delete();
				break;
		}
		
		$this->delete();
	}
	/**
	 * レスポンスを設定せずにロックを解除する
	 */
	public function delete(){
		if(!$this->isLocked()){
			\Dcs\Log::v('dont request locked.');
			return;
		}

		for($i=0;$i<3;++$i){
			if($this->mLock[$i]){
				$this->mCache->delete($this->mCacheType,$this->mLockKey[$i]);
				
			}
		}
		$this->mLock = [false,false,false];
		$this->mKey = null;
		$this->mUid = null;
	}
	
	
	/**
	 * ロックしているかどうかを判定
	 * @return boolean
	 */
	public function isLocked(){
		return $this->mUid != null && $this->mKey != null;
	}
	
	
	
	
	
	public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $services, $dust_time, $cache_type,$res_type, $res_time){
		$this->mContainer = $services;
		$this->mCon = $this->get('doctrine')->getConnection();
		$this->mHs = $this->get('gaia.handler_socket.default_connection');
		$this->mDustTime = intval($dust_time);
		if($this->mDustTime < 3)
			$this->mDustTime = 3;
		$this->mCache = $this->get('Dcs.Cache');
		$this->mCacheType = intval($cache_type);
		$this->mResType = intval($res_type);
		$this->mResTime = intval($res_time);
	}
	private function get($service_name){
		return $this->mContainer->get($service_name);
	}
	private function setParam($ckey,$uid){
		$this->mKey = $ckey;
		$this->mUid = $uid;

		$route = $this->get('request')->get('_route');
		$this->mLockKey = [
			'uniq'.$this->mKey.'.'.$this->mUid,
			'uniq'.$this->mUid.'.'.$route,
			'uniq'.$this->mUid,
		];
		$this->mRoute = $route;
	}
	public function getResTime(){
		return $this->mResTime;
	}
	const HeaderKey = 'SegaCacheKey';
	private $mContainer;
	private $mDustTime;
	private $mCacheType=0;
	private $mResType=0;
	private $mResTime=0;
	private $mCache;
	private $mCon;
	private $mHs;
	private $mUid=null;
	private $mKey=null;
	private $mLockKey = null;
	private $mLock = [false,false,false];
	private $mRoute = null;
}

?>