<?php
namespace Dcs\Arpg;

/**
 * Arpg時間を計算するためのクラス
 * @author takeday
 */
class Time{
	/**
	 * 現在時刻で初期化
	 */
	public function __construct(){
		$sub_time = 0;
		if(\Dcs\Arpg\Config::Debug)
			$sub_time = \Dcs\Arpg\Config::SubTime;
		if(self::$now_time === null)
			self::$now_time = time()-self::UNIX_BASE+$sub_time;
		
		$this->time = self::$now_time;
	}
	
	/**
	 * 秒を追加する
	 * @param int $sec 追加する秒
	 */
	public function add($sec){
		$this->time += intval($sec);
		return $this;
	}
	
	/**
	 * ARPG時間を設定する
	 */
	public function set($atime){
		$this->time = $atime;
		return $this;
	}
	/**
	 * ARPG時間を取得する
	 * @return number
	 */
	public function get(){
		return $this->time > 0x7fffffff?0x7fffffff:$this->time;
	}
	
	/**
	 * Unix時間で設定する
	 * @param int $utime
	 */
	public function setUnixTime($utime){
		$this->time = $utime - self::UNIX_BASE;
		return $this;
	}
	/**
	 * Unix時間を取得する
	 * @return int
	 */
	public function getUnixTime(){
		return $this->time + self::UNIX_BASE;
	}
	
	
	/**
	 * DateTime型で設定する
	 * @param \DateTime $dt
	 */
	public function setDateTime(\DateTime $dt){
		$this->time = $dt->getTimestamp() - self::UNIX_BASE;
		return $this;
	}
	/**
	 * DateTime型で取得する
	 * @return \DateTime
	 */
	public function getDateTime(){
		return (new \DateTime())->setTimestamp($this->time+self::UNIX_BASE);
	}
	
	/**
	 * MySQL Datetime文字列型で設定する
	 * @param string $mysql_dt YYYY-MM-DD hh:mm:ss形式の文字列
	 */
	public function setMySQLDateTime($mysql_dt){
		if($mysql_dt == null){
			$this->time = 0;
			return $this;
		}
		$dt = \DateTime::createFromFormat('Y-m-d H:i:s', $mysql_dt);
		$this->setDateTime($dt);
		return $this;
	}
	
	/**
	 * MySQL Datetime文字列型で取得する
	 * @return string
	 */
	public function getMySQLDateTime(){
		return date('Y-m-d H:i:s', $this->getUnixTime());
	}
	
	/**
	 * Unix時間をArpg時間に変換する
	 * @param int $utime Unix時間
	 * @return int Arpg時間
	 */
	static public function UnixTime2Arpg($utime){
		return $utime - self::UNIX_BASE;
	}
	/**
	 * DateTimeをArpg時間に変換する
	 * @param \DateTime $dt DateTime型
	 * @return int Arpg時間
	 */
	static public function DateTime2Arpg(\DateTime $dt){
		return $dt->getTimestamp() - self::UNIX_BASE;
	}
	/**
	 * MySQL時間文字列をArpg時間に変換する
	 * @param string $mysql_dt MySQL時間文字列
	 * @return int Arpg時間
	 */
	static public function MySQL2Arpg($mysql_dt){
		$ret = 0;
		if($mysql_dt != null){
			$dt = \DateTime::createFromFormat('Y-m-d H:i:s', $mysql_dt);
			$ret = self::DateTime2Arpg($dt);
		}
		return $ret;
	}
	/**
	 * Arpg時間をUnix時間に変換する
	 * @param int $atime Arpg時間
	 * @return int Unix時間
	 */
	static public function Arpg2UnixTime($atime){
		return $atime + self::UNIX_BASE;
	}
	/**
	 * Arpg時間をDateTimeオブジェクトに変換する
	 * @param int $atime Arpg時間
	 * @return \DateTime
	 */
	static public function Arpg2DateTime($atime){
		$ret = new \DateTime();
		$ret->setTimestamp($atime+self::UNIX_BASE);
		return $ret;
	}
	/**
	 * Arpg時間をMySQL時間文字列に変換する
	 * @param int $atime Arpg時間
	 * @return string MySQL時間文字列
	 */
	static public function Arpg2MySQL($atime){
		return date('Y-m-d H:i:s', self::Arpg2UnixTime($atime));
	}
	
	/**
	 * ARPG時間 2010/1/1 00:00:00からの秒
	 * @var int
	 */
	private $time=0;
	private static $now_time = null;
	
	/**
	 * 2010/1/1 00:00:00のUnix時間
	 */
	const UNIX_BASE = 1262271600;
}
?>