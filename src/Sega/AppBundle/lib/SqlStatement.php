<?php
namespace Dcs;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

/**
 * なんかいろいろできそうなSQLステートメントらっぱー
 */
class SqlStatement{
	const MODE_AUTO = 0;
	const MODE_MASTER = 1;
	const MODE_SLAVE = 2;
	
	private $con=null;
	private $sql=null;
	private $ptmt=null;
	private $tblName=null;
	private $transactionRead = false;
	public function __construct($tname, $con,$sql){
		$this->con = $con;
		$this->tblName = $tname;
		$this->sql = $sql;
//		$this->ptmt = $con->prepare($sql);
// 		->getWrappedConnection()
	}
	
	/**
	 * セレクト実行
	 * @param array $args SQL文の?に入る値リスト
	 */
	public function select(array $args=[], $mode = self::MODE_MASTER){
		AnalysisCounter::table($this->tblName);
		AnalysisCounter::select();
		switch($mode){
			case self::MODE_MASTER:{
				$tactive = true;
				break;
			}
			case self::MODE_SLAVE:{
				$tactive = false;
				break;
			}
			case self::MODE_AUTO:
			default:{
				$tactive = $this->con->isTransactionActive();
				break;
			}
		}
		if($this->ptmt == null || $this->transactionRead != $tactive){
			$this->transactionRead = $tactive;
			if($tactive)
				$this->ptmt = $this->con->prepare($this->sql);
			else
				$this->ptmt = $this->con->getWrappedConnection()->prepare($this->sql);
		}
		$this->execute($args);
	}
	/**
	 * Fetch実行
	 * 実行前に必ず selectを実行すること
	 * @param int $pdo_fetch_type \PDO::FETCH_XXX  default: \PDO::FETCH_ASSOC
	 * @param bool $is_all trueの場合 fetchAllを実行 falseの場合、1行Fetch  default: false
	 */
	public function fetch($pdo_fetch_type=\PDO::FETCH_ASSOC,$is_all=false){
		if(!$is_all){
			return $this->ptmt->fetch($pdo_fetch_type);
		}
		return $this->ptmt->fetchAll($pdo_fetch_type);
	}
	/**
	 * 1行Fetchを実行
	 * select($args);
	 * fetch($pdo_fetch_type,false); を実行した結果を取得する
	 * @param array $args SQL文の?に入る値リスト
	 * @param int $pdo_fetch_type \PDO::FETCH_XXX  default: \PDO::FETCH_ASSOC
	 */
	public function selectOne(array $args=[],$pdo_fetch_type=\PDO::FETCH_ASSOC, $mode = self::MODE_MASTER){
		$this->select($args,$mode);
		return $this->fetch($pdo_fetch_type,false);
	}
	/**
	 * 全行Fetchを実行
	 * select($args);
	 * fetch($pdo_fetch_type,true); を実行した結果を取得する
	 * @param array $args SQL文の?に入る値リスト
	 * @param int $pdo_fetch_type \PDO::FETCH_XXX  default: \PDO::FETCH_ASSOC
	 */
	public function selectAll(array $args=[],$pdo_fetch_type=\PDO::FETCH_ASSOC, $mode = self::MODE_MASTER){
		$this->select($args,$mode);
		return $this->fetch($pdo_fetch_type,true);
	}
	
	/**
	 * Updateを実行する
	 * @param array $args SQL文の?に入る値リスト
	 * @return int 変更した行数
	 */
	public function update(array $args=[]){
		AnalysisCounter::table($this->tblName);
		AnalysisCounter::update();
		if($this->ptmt == null)
			$this->ptmt = $this->con->prepare($this->sql);
		$this->execute($args);
		return $this->ptmt->rowCount();
	}
	
	/**
	 * Insertを実行する
	 * @param array $args SQL文の?に入る値リスト
	 * @return string 最後に挿入された行のIDまたはシーケンス値
	 */
	public function insert(array $args=[]){
		AnalysisCounter::table($this->tblName);
		AnalysisCounter::insert();
		if($this->ptmt == null)
			$this->ptmt = $this->con->prepare($this->sql);
		$this->execute($args);
		return $this->con->lastInsertId();
	}
	
	
	/**
	 * Deleteを実行する
	 * @param array $args SQL文の?に入る値リスト
	 * @return int 変更した行数
	 */
	public function delete(array $args=[]){
		AnalysisCounter::table($this->tblName);
		AnalysisCounter::delete();
		if($this->ptmt == null)
			$this->ptmt = $this->con->prepare($this->sql);
		$this->execute($args);
		return $this->ptmt->rowCount();
	}
	
	/**
	 * 直近のSQL文によって作用した行数を返す
	 * @return int 作用した行数
	 */
	public function rowCount(){
		if($this->ptmt == null){
			throw new \Symfony\Component\HttpKernel\Exception\HttpException(500,'Error: SqlStatement.rowCount Null reference error. dont execute sql.');
		}
		return $this->ptmt->rowCount();
	}
	
	private function execute(array $args = []){
		$this->ptmt->closeCursor();
		return $this->ptmt->execute($args);
	}
}
?>