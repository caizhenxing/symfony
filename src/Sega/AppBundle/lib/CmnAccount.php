<?php
namespace Dcs;

use Gaia\Bundle\UserBundle\Service\UserService;
use Doctrine\DBAL\Connection;

class CmnAccount{
	const SUCCESS=0;	///< 成功
	const FAILED=1;	///< 失敗
	const DBERROR=2;	///< データベースエラー

	const PLATFORM_ANDROID 	= 0;
	const PLATFORM_IPHONE 	= 1;
	const PLATFORM_WEB		= 2;
	const PLATFORM_PC		= 3;
	
	const UUID_LENGTH = 46;
	
	/**
	 * PHPセッションの制限時間(秒)
	 * 短いとサーバーアクセスが増える
	 * 長いと引継ぎ時にその時間だけログインを保持しちゃう
	 * @var int
	 */
	const SESSION_LIMIT = 300;
	
	/**
	 * コンストラクタ
	 * @see Gaia\Bundle\UserBundle\Service\UserService
	 * @param UserService $serv  gaia.user.user_serviceで取得できるサービス
	 */
	public function __construct(UserService $serv, MaintenanceSchedule $mnt){
		$this->serv = $serv;
		$this->mnt = $mnt;
	}
	/**
	 * ユーザID取得
	 *
	 * @param string $uuid UUID
	 *
	 * @return int ユーザID
	 */
	public function getUserId($uuid)
	{
		return $this->serv->getUserId($uuid);
	}

	/**
	 * public_id をキーとして ユーザIDを取得します
	 *
	 * @param int $publicId フレンド検索に利用する公開ID
	 *
	 * @return int 該当するユーザのユーザID
	 */
	public function getUserIdByPublicId($publicId)
	{
		
		return $this->serv->getUserIdByPublicId($publicId);
	}
	/**
	 * public_id をキーとして ユーザIDを取得します
	 *
	 * @param array $publicId フレンド検索に利用する公開IDリスト
	 *
	 * @return array [公開ID1 => ユーザーID1, ... ]
	 */
	public function getUserIdByPublicIds($publicIds)
	{
		return $this->serv->getUserIds($publicIds);
	}

	/**
	 * ユーザIDをキーとして公開IDを取得します
	 *
	 * @param int $uid ユーザID
	 *
	 * @return int 該当のユーザの public_id（フレンド検索時にユーザが利用するID）
	 */
	public function getPublicId($uid)
	{
		return $this->serv->getPublicId($uid);
	}
	
	/**
	 * ユーザーIDをキーとして公開IDを取得します
	 * @param array $uids ユーザーIDリスト
	 * @return array ['ユーザーID1' => 公開ID1, ... ]
	 */
	public function getPublicIds(array $uids){
		return $this->serv->getPublicIds($uids);
	}

	/**
	 * ユーザIDをキーにユーザアカウント情報を取得します
	 *
	 * @param int $uid ユーザID
	 *
	 * @return array 次の形式のユーザアカウント情報、該当するユーザが存在しない場合 null
	 *     {
	 *         'user_id'     : '173',
	 *         'uuid'        : '4ABEA9BC-3578-4E18-BC39-4D080433979C',
	 *         'take_over_id': 'EEArA7793407784',
	 *         'os_type_id'  : 1,
	 *         'os_version'  : OS:deviceModel:CPU,
	 *         'updated_time': '2014-04-01 12:34:56',
	 *         'created_time': '2014-08-01 12:34:56'
	 *     }
	 */
	public function getAccountData($uid)
	{
		return $this->serv->getAccountData($uid);
	}

	/**
	 * UUID をキーにユーザアカウント情報を取得します
	 *
	 * @param int $uuid UUID
	 *
	 * @return array 次の形式のユーザアカウント情報、該当するユーザが存在しない場合 null
	 *     {
	 *         'user_id'     : '173',
	 *         'uuid'        : '4ABEA9BC-3578-4E18-BC39-4D080433979C',
	 *         'take_over_id': 'EEArA7793407784',
	 *         'os_type_id'  : 1,
	 *         'os_version'  : OS:deviceModel:CPU,
	 *         'updated_time': '2014-04-01 12:34:56',
	 *         'created_time': '2014-08-01 12:34:56'
	 *     }
	 */
	public function getAccountDataByUUID($uuid)
	{
		return $this->serv->getAccountDataByUUID($uuid);
	}

	/**
	 * 公開IDをキーにユーザアカウント情報を取得します
	 *
	 * @param int $publicId フレンド検索に利用する公開ID
	 *
	 * @return array 次の形式のユーザアカウント情報、該当するユーザが存在しない場合 null
	 *     {
	 *         'user_id'     : '173',
	 *         'uuid'        : '4ABEA9BC-3578-4E18-BC39-4D080433979C',
	 *         'take_over_id': 'EEArA7793407784',
	 *         'os_type_id'  : 1,
	 *         'os_version'  : OS:deviceModel:CPU,
	 *         'updated_time': '2014-04-01 12:34:56',
	 *         'created_time': '2014-08-01 12:34:56'
	 *     }
	 */
	public function getAccountDataByPublicId($publicId)
	{
		return $this->serv->getAccountDataByPublicId($publicId);
	}
	
	
	
	
	
	/**
	 * ユーザー生成
	 * getUuidとgetSid以外無効なので気を付ける
	 * @return integer ステート
	 */
	public function create($os,$info){
		return $this->createInner(intval($os),$info,10);
	}
	private function createInner($os,$info,$count){
		if($count < 1) return CmnAccount::DBERROR;
		$uuid = '';
		try{
			$uuid = $this->createRandString(CmnAccount::UUID_LENGTH);
			$udata = $this->serv->create($uuid,$os,$info);
			$this->uuid = $uuid;
			$this->uid = $udata['user_id'];
			$this->sid = $this->serv->updateSession($this->uid);
		}catch(\Exception $e){
			\Dcs\Log::e($e,true);
			$this->uuid = null;
			return $this->createInner($os,$info,$count-1);
		}
		return CmnAccount::SUCCESS;
	}
	/**
	 * ログイン
	 * セッションIDを書き換えてログイン
	 * getUuidとgetSid以外無効なので気を付ける
	 * @param string $uuid
	 * @return integer ステート
	 */
	public function login($uuid,$os){
		if($uuid == null)
			return CmnAccount::FAILED;
		$udata = null;
		try{
			$this->uid = $this->serv->getUserId($uuid);
			if(empty($this->uid))
				return CmnAccount::FAILED;
			$udata = $this->serv->getAccountData($this->uid);
			$this->sid = $this->serv->updateSession($this->uid);
		}catch(\Exception $e){
			\Dcs\Log::e($e,true);
			return CmnAccount::DBERROR;
		}
		if($udata == null){
			return CmnAccount::FAILED;
		}
		$otid = intval($udata['os_type_id']);
		if($otid >= 0 && $otid != intval($os)){
			return CmnAccount::FAILED;
		}
		$this->uuid = $uuid;
		$this->checkMaintenance();
		\Dcs\Log::uid($this->uid);
		
		$this->is_login = true;
		return CmnAccount::SUCCESS;
	}
	/**
	 * ログインしてるチェック
	 * セッションIDを書き換えずにデータをロード
	 * @param string $sid	セッションID
	 * @return integer ログインステート
	 */
	public function loginCheck($sid){
		$sdata = null;
		try{
			$sdata = $this->serv->getSessionData($sid);
		}catch(\Exception $e){
			\Dcs\Log::e($e,true);
			return CmnAccount::DBERROR;
		}
		if($sdata == null){
			return CmnAccount::FAILED;
		}
		$this->uid = $sdata['user_id'];
		$this->checkMaintenance();
		$this->sid = $sid;
		$this->noah = $sdata['noah_id'];
		if(isset($sdata['created_time']) && $sdata['created_time'] != null){
			$this->ban = [
				'summary' => $sdata['summary'],
				'message' => $sdata['message'],
				'created_time' => $sdata['created_time'],
			];
		}
		\Dcs\Log::uid($this->uid);
		$this->is_login = true;
		return CmnAccount::SUCCESS;
	}
	private function checkMaintenance(){
		if($this->mnt->isMaintenanceByUser($this->uid))
			throw new \Symfony\Component\HttpKernel\Exception\HttpException(503);
	}
	
	/**
	 * Uuid
	 * 生成したときしか存在しない
	 */
	public function getUuid(){
		return $this->uuid;
	}
	/**
	 * セッションID
	 * @return string 
	 */
	public function getSid(){
		return $this->sid;
	}
	/**
	 * ユーザーID
	 * 各種データの主キーとして使う
	 */
	public function getUid(){
		return $this->uid;
	}
	/**
	 * ノアID
	 */
	public function getNoah(){
		return $this->noah;
	}
	/**
	 * ノアIDを設定する
	 * @param string $val
	 */
	public function setNoah($val){
		if(!$this->is_login) throw new \Exception('ログインしていません');
		$this->serv->updateNoahId($this->uid, $val);
		$this->noah = $val;
	}
	/**
	 * アカウントBANデータを取得する
	 * loginかloginCheckを事前に実行していること
	 * @return {array|null} ['summary' => アカバンタイトル,'message'=>アカバンメッセージ,'created_time'=>アカバン日] nullの場合アカバンされてない
	 */
	public function getBanData(){
		return $this->ban;
	}

	private function hashPass($pass){
		return hash('sha512',$pass);
	}
	private function createRandString($max){
		$list = explode(' ','a b c d e f g h i j k l m n o p q r s t u v w x y z 0 1 2 3 4 5 6 7 8 9');
		$ret = '';
		for($i=0;$i<$max;++$i){
			$ret .= $list[array_rand($list)];
		}
		return $ret;
	}
	const LOGIN_KEY = 'DCS_CMN_ACCOUNT_LOGIN';
	private $serv;	///< サービス
	private $mnt;	///< メンテナンス用サービス
	
	private $uid;	///< int	ユーザーID
	private $uuid;	///< string	ログイン用ID
	private $sid;	///< string	一時ログイン用のセッションID
	
	private $ban=null;
	private $noah;			///< string
	private $is_login = false;
}
?>