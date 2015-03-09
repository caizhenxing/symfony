<?php

namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Cache as Cache;

class Effect extends \Dcs\Arpg\Logic{
	public function getData($id){
		$id = intval($id);
		$key = "Arpg.Logic.Util.Effect.getData.$id";
		$cache = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($cache == null){
			$rs = $this->getHs(false)->select(
					new Table('effect_data',['id','file','time','script','se_id','se_delay']),
					new Query(['='=>$id])
			);
			foreach($rs as $row){
				$cache = [
						'id'=>intval($row[0]),
						'file'=>$row[1],
						'time'=>($row[2]+0),
						'script'=>$row[3],
						'se_file'=>$this->getSeFile($row[4]),
						'se_delay'=>($row[5]+0)
				];
			}
			if($cache != null)
				$this->cache()->set(\Dcs\Cache::TYPE_APC,$key, $cache);
		}
		return $cache;
	}
	private function getSeFile($id){
		$id = intval($id);
		$key = "Arpg.Logic.Util.Effect.getSeFile.$id";
		$cache = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
		if($cache == null){
			$rs = $this->getHs(false)->select(new Table('sound_se_action',['id']), new Query(['='=>$id]));
			foreach($rs as $row){
				$cache = $row[0];
			}
			$this->cache()->set(\Dcs\Cache::TYPE_APC,$key, $cache);
		}
		return $cache;
	}
}

?>