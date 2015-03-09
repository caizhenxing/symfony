<?php
/**
 * メモ
 * 仮実装
 */
namespace Logic\PlayerData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Logic\GameData\RecipeData as RecipeData;
use \Dcs\Arpg\Time as Time;
use \Logic\Util\GameParam as GameParam;

class HomePub extends \Dcs\Arpg\Logic{
	public $infoList;
	public $banner;

	public $reloadTime;
	
	public $cureStpCp;
	public $addonStoneCp;
	public $extCardStockCp;
	public $elixirCp;
	public $spiritCp;
	public $charaCreationCp;
	public $friendSlotCp;
	public $boxKeyCp;
	
	public $wareHouseMaxSize; // warehouseサイズ最大
	public $wareHouseExtSize; // 一回の拡張で追加されるサイズ
	
	public $friendMaxSize;
	public $frinedExtSize;

	public function __sleep(){
		return [
			'infoList',
			'banner',
			
			'reloadTime',
			
			'cureStpCp',
			'addonStoneCp',
			'extCardStockCp',
			'elixirCp',
			'spiritCp',
			'charaCreationCp',
			'friendSlotCp',
			'boxKeyCp',
			
			'wareHouseMaxSize', 
			'wareHouseExtSize',
			
			'friendMaxSize',
			'frinedExtSize',
		];
	}
	const CACHE_TIME = 3600;
	
	const TYPE_INFO = 0;
	const TYPE_EQUEST = 2;
	
	/**
	 * データ初期化
	 */
	public function init(){
		$key = 'Sega.Appbundle.Dao.PlayerData.HomePub.Cache';
		$c = $this->cache();

		$Gparam = $this->get('Arpg.Logic.Util.GameParam');
		$Dparam = $this->get('Arpg.Logic.Util.DevParam');
		$dat = $c->get(\Dcs\Cache::TYPE_APC,$key);
		if($dat == null){
			$now = new Time();
			
			$rs = $this->getHs(false)->select(
					new Table('home_public',['data','effective_from','effective_to','action','action_value'],'effective_to'),
					new Query(['>'=>$now->getMySQLDateTime()],-1),
					self::CACHE_TIME
			);
			$now = $now->get();

			$this->infoList = [];
			$this->banner = [];
			$this->reloadTime = $now+self::CACHE_TIME;
			
			foreach($rs as $row){
				$from = new Time();
				$to = new Time();
				$from = $from->setMySQLDateTime($row[1])->get();
				$to = $to->setMySQLDateTime($row[2])->get();
				
				if($now + self::CACHE_TIME+3600 < $from) continue;
				$dat = [
						'from' => $from,
						'to' => $to,
						'name' => $row[0],
						'action' => intval($row[3]),
						'actionValue' => $row[4],
				];
				$this->infoList[] = $dat;
			}
			
			// バナー
			$list = [];
			$rs = $this->getHs(false)->select(new Table('home_banner',['file','time','effective_from','effective_to','action','action_value']),new Query(['>'=>0],-1));
			foreach($rs as $row){
				$list[] = $this->createHomeBannerRow($row);
			}
			foreach($list as $line){
				if( $line['to'] < $now || ($now+self::CACHE_TIME*2) < $line['from'])
					continue;
				$this->banner[] = $line;
			}
			
			$c->set(\Dcs\Cache::TYPE_APC,$key,$this,self::CACHE_TIME);
		}else{
			$this->infoList = $dat->infoList;
			$this->banner = $dat->banner;
			$this->reloadTime = $dat->reloadTime;
		}
		$this->cureStpCp=$Gparam->getParam(GameParam::CURE_STP_CP);
		$this->addonStoneCp=$Gparam->getParam(GameParam::ADDON_STONE_CP);
		$this->extCardStockCp=$Gparam->getParam(GameParam::EXT_CARD_CP);
		$this->elixirCp=$Dparam->param(80);
		$this->spiritCp=$Gparam->getParam(GameParam::SPIRIT_CP);
		$this->friendSlotCp=$Gparam->getParam(GameParam::FRIEND_EXT_CP);
		$this->boxKeyCp=$Gparam->getParam(GameParam::KBOX_KEY_CP);
		$this->wareHouseMaxSize=$Dparam->param(77);
		$this->wareHouseExtSize=$Dparam->param(79);
		$this->friendExtSize=$Dparam->param(78);
		$this->friendMaxSize=$Dparam->param(76);
		$this->charaCreationCp=$Gparam->getParam(GameParam::CHARA_CREATE_CP);
	}
	private function createHomeBannerRow($row){
		$ret = [];
		$ret['file'] = $row[0];
		$ret['time'] = intval($row[1]);
		$time = new Time();
		$time->setMySQLDateTime($row[2]);
		$ret['from'] = $time->get();
		$time = new Time();
		$time->setMySQLDateTime($row[3]);
		$ret['to'] = $time->get();
		$ret['action'] = intval($row[4]);
		$ret['actionValue'] = $row[5];
		return $ret;
	}
}

?>