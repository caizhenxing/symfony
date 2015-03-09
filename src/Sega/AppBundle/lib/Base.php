<?php
namespace Dcs;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

/**
 * Serviceを取得できるメソッド get(string $name) が存在することが前提
 */
trait Base{
	/**
	 * トランザクション使用チェック
	 * @return boolean
	 */
	protected function isTransactionActive(){
		if(Base_Static_Param::$mSqlConnector == null)
			Base_Static_Param::$mSqlConnector = $this->get('doctrine')->getConnection();
		return Base_Static_Param::$mSqlConnector->isTransactionActive();
	}
	/**
	 * トランザクションを使用
	 */
	protected function useTransaction(){
		if(!$this->isTransactionActive())
			Base_Static_Param::$mSqlConnector->beginTransaction();
	}
	/**
	 * SQLステートメントを作成する
	 * @param string $table_name テーブル名
	 * @param string $query クエリ文
	 * @return \Dcs\SqlStatement
	 */
	protected function sql($table_name,$query){
		if(Base_Static_Param::$mSqlConnector == null)
			Base_Static_Param::$mSqlConnector = $this->get('doctrine')->getConnection();
		return new SqlStatement($table_name,Base_Static_Param::$mSqlConnector,$query);
	}
	
	/**
	 * ハンドラ―ソケットのセレクトを行う
	 * キャッシュがある場合そちらから取得する
	 * @param Table $table
	 * @param Query $query
	 * @param int $time キャッシュ時間 0で無限 デフォルト値:0
	 */
	protected function selectHsCache(Table $table, Query $query, $time=0){
		$c = $this->cache();
		$key = 'Arpg.Logic.HsCacheKey.'.json_encode([$table,$query]);
		$ret = $c->get(\Dcs\Cache::TYPE_APC,$key);
		if($ret == null){
			$ret = $this->getHs()->select($table, $query);
			if($ret != null)
				$c->set(\Dcs\Cache::TYPE_APC,$key,$ret,$time);
		}
		return $ret;
	}
	
	/**
	 * ハンドラ―ソケットのマルチセレクトを行う
	 * キャッシュがある場合そちらから取得する
	 * @param Table $table
	 * @param array $querys Queryオブジェクトのリスト
	 * @param int $time キャッシュ時間 0で無限 デフォルト値:0
	 */
	protected function selectHsCacheMulti(Table $table, array $querys, $time=0){
		$c = $this->cache();
		if($c->enableAPC()){
			$ret = [];
			foreach($querys as $q){
				$ret[] = $this->selectHsCache($table,$q,$time);
			}
			return $ret;
		}else{
			$key = 'Arpg.Logic.HsCacheKey.'.json_encode([$table,$querys]);
			$ret = $c->get($key,false);
			if($ret == null){
				$ret = $this->getHs()->selectMulti($table, $querys);
				if($ret != null)
					$c->set(\Dcs\Cache::TYPE_APC,$key,$ret,$time);
			}
			return $ret;
		}
	}
	
	/**
	 * SQLのセレクトを行う
	 * キャッシュがある場合そちらから取得する
	 * @param string $sql
	 * @param array $values
	 * @param int $time キャッシュ時間 0で無限 デフォルト値:0
	 * @param int $type Fetchタイプ \PDO::FETCH_***
	 * @return array 結果の連想配列の配列
	 */
	protected function selectSqlCache($sql,array $values=array(), $time=0,$type=\PDO::FETCH_ASSOC){
		$c = $this->cache();
		$key = 'Arpg.Logic.SqlCacheKey.'.json_encode([$sql,$values]);
		$ret = $c->get(\Dcs\Cache::TYPE_MEMCACHE,$key);
		if($ret == null){
			if(Base_Static_Param::$mSqlConnector == null)
				Base_Static_Param::$mSqlConnector = $this->get('doctrine')->getConnection();
			$ptmt = Base_Static_Param::$mSqlConnector->prepare($sql);
			$ptmt->execute($values);
			$ret = $ptmt->fetchAll($type);
			if($ret != null)
				$c->set(\Dcs\Cache::TYPE_MEMCACHE,$key,$ret,$time);
		}
		return $ret;
	}
	
	/**
	 * ハンドラ―ソケットを取得する
	 * @see \Gaia\Bundle\HandlerSocketBundle\Service\HandlerSocketService
	 * @param boolean $master マスターサーバーにのみ接続するフラグ　default:false
	 * @return object
	 */
	public function getHs($master=true){
		if($master){
			if(Base_Static_Param::$mMasterHs == null)
				Base_Static_Param::$mMasterHs = $this->get('dcs.handler_socket.master_connection');
			return Base_Static_Param::$mMasterHs;
		}else{
			if(Base_Static_Param::$mAutoHs == null)
				Base_Static_Param::$mAutoHs = $this->get('dcs.handler_socket.default_connection');
			return Base_Static_Param::$mAutoHs;
		}
	}
	
	/**
	 * Memcacheコネクタを取得
	 * @return \Memcached
	 */
	public function getMemcached(){
		if(Base_Static_Param::$mMemcache == null){
			Base_Static_Param::$mMemcache = $this->get('memcache.default');
		}
		return Base_Static_Param::$mMemcache;
	}
	
	/**
	 * キャッシュサービス取得
	 * @return \Dcs\Cache
	 */
	protected function cache(){
		if($this->mCache == null){
			$this->mCache = $this->get('Dcs.Cache');
		}
		return $this->mCache;
	}
	private $mCache=null;
	/**
	 * アクション成功ログ出力
	 * @param long $uid
	 * @param string $log
	 * @param array $tag 上限4つ
	 * @return boolean
	 */
	protected function addSuccessLog($uid,$log,array $tag=null){
		if($this->mAlog == null)
			$this->mAlog = $this->get('Dcs.ActionLog');
		return $this->mAlog->addSuccess($uid,$log,$tag);
	}
	/**
	 * アクション失敗ログ出力
	 * @param long $uid
	 * @param string $log
	 * @param array $tag 上限4つ
	 * @return boolean
	 */
	protected function addErrorLog($uid,$log,array $tag=null){
		if($this->mAlog == null)
			$this->mAlog = $this->get('Dcs.ActionLog');
		return $this->mAlog->addError($uid,$log,$tag);
	}
	private $mAlog=null;
	/**
	 * 共通アカウントクラスを生成
	 * @return \Dcs\CmnAccount
	 */
	public function createCmnAccount(){
		return $this->get('Dcs.CmnAccount');
	}
}
class Base_Static_Param{
	static public $mMasterHs=null;
	static public $mAutoHs=null;
	static public $mSqlConnector=null;
	static public $mMemcache=null;
}
?>