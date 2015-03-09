<?php
namespace Dcs;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

/**
 * アクションログクラス
 * @author Takeda_Yoshihiro
 */
class ActionLog{
	/**
	 * システム情報キーを取得する
	 * @return string
	 */
	public static function system(){
		if(self::$mSystem === null){
			$ip = null;
			if(extension_loaded('apcu')){
				$ip = apc_fetch(self::SADDR_KEY);
				if(!$ip){
					$ip = $_SERVER['SERVER_ADDR'];
					apc_store(self::SADDR_KEY,$ip);
				}
			}else{
				$ip = $_SERVER['SERVER_ADDR'];
			}
			self::$mSystem = time().'-'.getmypid().'-'.$ip;
		}
		return self::$mSystem;
	}
	private static $mSystem=null;
	const SADDR_KEY = 'DcsActionLogSERVER_ADDR';
	/**
	 * 成功ログ追加
	 * @param long $uid
	 * @param string $log
	 * @param array $tag 上限4つ
	 * @return boolean
	 */
	public function addSuccess($uid,$log,array $tag=null){
		if($tag != null){
			for($i=count($tag);$i<4;++$i){
				$tag[$i]=null;
			}
			$tag[4] = null;
		}
		return $this->add($uid,$log,$tag);
	}
	/**
	 * 失敗ログ追加
	 * @param long $uid
	 * @param string $log
	 * @param array $tag 上限4つ
	 * @return boolean
	 */
	public function addError($uid,$log,array $tag=null){
		if($tag != null){
			for($i=count($tag);$i<4;++$i){
				$tag[$i]=null;
			}
			$tag[4] = 'Error';
		}
		return $this->add($uid,$log,$tag);
	}
	static public function TempLogPath(){
		return __DIR__.'/../../../../data/';
	}
	static public function TempLogBase(){
		return self::TempLogPath().'temp_action_%date%.log';
	}
	private function add($uid,$log,array $tag){
		$date = date('YmdHi');
		$uid = intval($uid);
		$log = empty($log)?'':$log;

		if($tag == null)
			$tag = [];
		$line = [];
		$line[] = self::system();
		$line[] = intval($uid);
		for($i=0;$i<5;++$i){
			if(isset($tag[$i]) && $tag[$i] != null)
				$line[] = $tag[$i];
			else
				$line[] = null;
		}
		$line[] = empty($log)?'':$log;
		$line[] = date('Y-m-d H:i:s');
		$ret = false;
		try{
			error_log(base64_encode(json_encode($line))."\n",3,str_replace('%date%',$date,self::TempLogBase()));
			$ret = true;
		}catch(\Exception $e){
			\Dcs\Log::e($e,true);
		}
		\Sega\AppBundle\Command\Util\ActionLogSendCommand::RunBackGround();
		return $ret;
	}
	/**
	 * SQLのLIMITとOFFSETによる制限オブジェクトを生成する
	 * @param int $limit 取得上限 0以下で全取得 デフォルト値 -1
	 * @param int $offset 取得オフセット $limitが-1の場合無視される デフォルト値 0
	 * @return \Dcs\ActionLogLimit
	 */
	public function limitoffset($limit=-1,$offset=0){
		$ret = new ActionLogLimit();
		$ret->type = 0;
		$ret->arg1 = $limit;
		$ret->arg2 = $offset;
		return $ret;
	}
	/**
	 * ログ生成日時による制限オブジェクトを生成する
	 * @param string $from 'Y-m-d H:i:s'フォーマットの開始日時
	 * @param string $to 'Y-m-d H:i:s'フォーマットの終了日時
	 * @return \Dcs\ActionLogLimit
	 */
	public function fromto($from='2010-01-01 00:00:00',$to='2100-01-01 00:00:00'){
		$ret = new ActionLogLimit();
		$ret->type = 1;
		$ret->arg1 = $from;
		$ret->arg2 = $to;
		return $ret;
	}
	
	/**
	 * ログを新しい順に取得する
	 * @param \Dcs\ActionLogLimit $limit リミットオブジェクト
	 * @return array 条件に該当するログを後述の形式で取得
	 * 		[
	 * 			[
	 * 				'uid' => long ユーザーID,
	 * 				'tag' => [タグ0,タグ1,タグ2,タグ3,タグ4],
	 * 				'log' => string ログ内容,
	 * 				'date' => string 'Y-m-d H:i:s'フォーマットの日付文字列,
	 * 				
	 * 			],
	 * 			...
	 * 		]
	 */
	public function get(\Dcs\ActionLogLimit $limit=null){
		if($this->mSqlConnector == null)
			$this->mSqlConnector = $this->mSc->get('doctrine')->getConnection('log');
		
		$where = '';
		$lioff = '';
		if(isset($limit->type)){
			if($limit->type == 0)
				$lioff = $limit->limitoffset();
			elseif($limit->type == 1)
				$where = 'where '.$limit->fromto();
		}
		$ptmt = $this->mSqlConnector->ptmt('select '.self::fields." from action $where order by create_date desc $lioff");
		$ptmt->execute([$uid]);
		return $this->resFormat($ptmt);
	}
	
	/**
	 * ログを新しい順にユーザー指定で取得する
	 * @param long $uid ユーザーID
	 * @param \Dcs\ActionLogLimit $limit リミットオブジェクト
	 * @return array 条件に該当するログを後述の形式で取得
	 * 		[
	 * 			[
	 * 				'uid' => long ユーザーID,
	 * 				'tag' => [タグ0,タグ1,タグ2,タグ3,タグ4],
	 * 				'log' => string ログ内容,
	 * 				'date' => string 'Y-m-d H:i:s'フォーマットの日付文字列,
	 * 				
	 * 			],
	 * 			...
	 * 		]
	 */
	public function getByUid($uid,$limit=null){
		$uid = intval($uid);
		if($this->mSqlConnector == null)
			$this->mSqlConnector = $this->mSc->get('doctrine')->getConnection('log');
		
		
		$where = 'where uid=?';
		$lioff = '';
		if(isset($limit->type)){
			if($limit->type == 0)
				$lioff = $limit->limitoffset();
			elseif($limit->type == 1)
				$where .= ' and '.$limit->fromto();
		}
		$ptmt = $this->mSqlConnector->ptmt('select '.self::fields." from action $where order by create_date desc $lioff");
		$ptmt->execute([$uid]);
		return $this->resFormat($ptmt);
	}
	/**
	 * ログを新しい順にタグ指定で取得する
	 * @param array $tag [タグ0,タグ1,タグ2,タグ3,タグ4]形式で nullや空文字はチェックしない。["a",null,"b"]の場合、tag0が"a"かつtag2が'b'のものがヒットする
	 * @param \Dcs\ActionLogLimit $limit リミットオブジェクト
	 * @return array 条件に該当するログを後述の形式で取得
	 * 		[
	 * 			[
	 * 				'uid' => long ユーザーID,
	 * 				'tag' => [タグ0,タグ1,タグ2,タグ3,タグ4],
	 * 				'log' => string ログ内容,
	 * 				'date' => string 'Y-m-d H:i:s'フォーマットの日付文字列,
	 * 				
	 * 			],
	 * 			...
	 * 		]
	 */
	public function getByTag(array $tag=[],$limit=null){
		$arg = [];
		if($this->mSqlConnector == null)
			$this->mSqlConnector = $this->mSc->get('doctrine')->getConnection('log');
		
		$where = null;
		if(!is_array($tag))
			$tag = [];
		
		for($i=0;$i<5;++$i){
			if(isset($tag[$i]) && $tag[$i] != null){
				if($where == null)
					$where = "where tag$i=?";
				else
					$where .= " and tag$i=?";
				$arg[]=$tag[$i];
			}
		}

		$lioff = '';
		if(isset($limit->type)){
			if($limit->type == 0)
				$lioff = $limit->limitoffset();
			elseif($limit->type == 1){
				if($where == null)
					$where = 'where '.$limit->fromto();
				else
					$where .= ' and '.$limit->fromto();
			}
		}
		$ptmt = $this->mSqlConnector->ptmt('select '.self::fields." from action $where order by create_date desc $lioff");
		$ptmt->execute($arg);
		return $this->resFormat($ptmt);
	}
	
	/**
	 * ログを新しい順にユーザーとタグ指定で取得する
	 * @param long $uid ユーザーID
	 * @param array $tag [タグ0,タグ1,タグ2,タグ3,タグ4]形式で nullや空文字はチェックしない。['a',null,'b']の場合、tag0が'a'かつtag2が'b'のものがヒットする
	 * @param \Dcs\ActionLogLimit $limit リミットオブジェクト
	 * @return array 条件に該当するログを後述の形式で取得
	 * 		[
	 * 			[
	 * 				'uid' => long ユーザーID,
	 * 				'tag' => [タグ0,タグ1,タグ2,タグ3,タグ4],
	 * 				'log' => string ログ内容,
	 * 				'date' => string 'Y-m-d H:i:s'フォーマットの日付文字列,
	 * 				
	 * 			],
	 * 			...
	 * 		]
	 */
	public function getByUidTag($uid,array $tag=[],$limit=null){
		$arg = [];
		$arg[] = intval($uid);
		if($this->mSqlConnector == null)
			$this->mSqlConnector = $this->mSc->get('doctrine')->getConnection('log');
		
		$where = 'where uid=?';
		if(!is_array($tag))
			$tag = [];
		
		for($i=0;$i<5;++$i){
			if(isset($tag[$i]) && $tag[$i] != null){
				$where .= " and tag$i=?";
				$arg[]=$tag[$i];
			}
		}

		$lioff = '';
		if(isset($limit->type)){
			if($limit->type == 0)
				$lioff = $limit->limitoffset();
			elseif($limit->type == 1)
				$where .= ' and '.$limit->fromto();
		}
		$ptmt = $this->mSqlConnector->ptmt('select '.self::fields." from action $where order by create_date desc $lioff");
		$ptmt->execute($arg);
		return $this->resFormat($ptmt);
	}
	
	
	
	
	
	
	private function resFormat($ptmt){
		$ret = [];
		while($row = $ptmt->fetch(\PDO::FETCH_NUM)){
			$ret[] = [
			'uid' => $uid,
			'tag' => [$row[1],$row[2],$row[3],$row[4],$row[5]],
			'log' => $row[6],
			'date' => $row[7],
				
			];
		}
		return $ret;
	}
	
	
	public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $services){
		$this->mSc = $services;
	}

	private $mSc;
	private $mSqlConnector=null;
	private $mHandlerSocket=null;
	const fields = 'uid,tag0,tag1,tag2,tag3,tag4,log,create_date';
}


class ActionLogLimit{
	public $type;
	public $arg1;
	public $arg2;
	public function limitoffset(){

		if(!is_numeric($this->arg1) || $this->arg1 < 1)
			$this->arg1 = -1;
		$this->arg1 = intval($this->arg1);
		if(!is_numeric($this->arg2) || $this->arg2 < 0)
			$this->arg2 = 0;
		$this->arg2 = intval($this->arg2);
		
		return 'limit '.$this->arg2.','.$this->arg1;
	}
	public function fromto(){
		if(\DateTime::createFromFormat('Y-m-d H:i:s', $this->arg1) === FALSE){
			throw new \Exception('fromto from param is invalid.');
		}
		if(\DateTime::createFromFormat('Y-m-d H:i:s', $this->arg2) === FALSE){
			throw new \Exception('fromto to param is invalid.');
		}
		return "(create_date >= '".$this->arg1."' and create_date <= '".$this->arg2."')";
	}
}

?>