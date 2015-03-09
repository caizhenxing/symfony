<?php
namespace Logic\GameData;

class RecipeData extends \Dcs\Arpg\Logic{
	public static $DBKEY = array('material','time', 'price');
	
	public $createTime=0;
	public $price=0;
	
	public $matIds=[];
	public $matNums=[];
	/**
	 * データ初期化
	 * @param array $row [材料,終了時間,金額]形式の配列
	 */
	public function initHs($row) {
		$this->createTime = intval($row[1]);
		$this->price = intval($row[2]);
		$this->matIds = array();
		$this->matNums = array();
		$sep = explode(',',$row[0]);
		foreach($sep as $line){
			$line=str_replace("\s",'',$line);
			if(strlen($line) < 1) continue;
			list($std_id,$num) = explode(':',$line);
			$std_id = intval($std_id);
			$num = intval($num);
		
			if($std_id > 0 && $num > 0){
				$this->matIds[] = $std_id;
				$this->matNums[] = $num;
			}else{
				continue;
			}
		}
	}
	
	/**
	 * データ初期化
	 * @param array $row ['material'=>材料,'time'=>終了時間,'price'=>金額]形式を含む連想配列
	 */
	public function initSql($row){
		$this->createTime = intval($row['time']);
		$this->price = intval($row['price']);

		$this->matIds = array();
		$this->matNums = array();
		$sep = explode(',',$row['material']);
		foreach($sep as $line){
			$line=str_replace("\s",'',$line);
			if(strlen($line) < 1) continue;
			list($std_id,$num) = explode(':',$line);
			$std_id = intval($std_id);
			$num = intval($num);
		
			if($std_id > 0 && $num > 0){
				$this->matIds[] = $std_id;
				$this->matNums[] = $num;
			}else{
				continue;
			}
		}
	}
	
	public function __sleep(){
		return [
			'createTime','price','matIds','matNums'
		];
	}
}
?>