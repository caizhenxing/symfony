<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class Collection extends \Dcs\Arpg\Logic{
	
	/**
	 * プレイヤーステータスかチェック
	 * @param int $std_id 論理ID
	 */
	private function isCollect($std_id){
		$sid = intval($std_id);
		return 100000 <= $sid && $sid < 400000;
	}

	/**
	 * コレクションに作成する すでに存在するばあい実行しない
	 * @param string $uid
	 * @param int $std_id
	 * @param int $num 追加する数
	 */
	public function create($uid,$std_id){
		if(!$this->isCollect($std_id)) return;
		$this->get('Arpg.Logic.Util.PlayerStatus')->create($uid,$std_id,$num,false);
	}
	/**
	 * ステータスを複数行作成する すでに存在するばあい実行しない
	 * 同じパラを複数回追加してもOK
	 * @param int $uid
	 * @param array $list [[std_id,num] or std_id, ...]の形式であること
	 */
	public function createMulti($uid,$list){
		if(empty($list)) return;
		$insert = [];
		foreach($list as $line){
			$std_id = 0;
			$num = 1;
			if(is_array($line)){
				$std_id = intval($line[0]);
				$num = intval($line[1]);
			}else{
				$std_id = intval($line);
			}
			if(!$this->isCollect($std_id)) continue;
			$insert[] =[$uid,$std_id,$num];
		}
		$this->get('Arpg.Logic.Util.PlayerStatus')->createMulti($insert,false);
	}
	
	
	/**
	 * ステータスを追加する
	 * @param string $uid
	 * @param int $std_id
	 * @param int $num 追加する数
	 */
	public function add($uid, $std_id, $num=1){
		if(!$this->isCollect($std_id)) return;
		$this->get('Arpg.Logic.Util.PlayerStatus')->add($uid,$std_id,$num,false);
	}	
	/**
	 * ステータスを複数行追加する
	 * 同じパラを複数回追加してもOK
	 * @param int $uid
	 * @param array $list [[std_id,num] or std_id, ...]の形式であること
	 */
	public function addMulti($uid,$list){
		if(empty($list)) return;
		$insert = [];
		foreach($list as $line){
			$std_id = 0;
			$num = 1;
			if(is_array($line)){
				$std_id = intval($line[0]);
				$num = intval($line[1]);
			}else{
				$std_id = intval($line);
			}
			if(!$this->isCollect($std_id)) continue;
			$insert[] =[$uid,$std_id,$num];
		}
		$this->get('Arpg.Logic.Util.PlayerStatus')->addMulti($insert,false);
	}
	
	/**
	 * ステータスを取得する
	 * @param int $uid
	 * @param int $std_id
	 * @return int 
	 */
	public function num($uid, $std_id){
		if(!$this->isCollect($std_id)) return 0;
		return $this->get('Arpg.Logic.Util.PlayerStatus')->getStatus($uid,$std_id,false);
	}
	private $ptmt_get = null;

	/**
	 * ステータスを取得する
	 * @param int $uid
	 * @param array $std_ids 
	 * @return array[stdid=>num, ... ]
	 */
	public function numMulti($uid,array $std_ids){
		if(empty($std_ids)) return [];
		$list = [];
		foreach($std_ids as $std_id){
			if(!$this->isCollect($std_id)) continue;
			$list[] = $std_id;
		}
		return $this->get('Arpg.Logic.Util.PlayerStatus')->getStatusMulti($uid,$list,false);
	}
	
	
}

?>