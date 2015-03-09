<?php

namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Cache as Cache;

class DevParam extends \Dcs\Arpg\Logic{
	public function param($id){
		$id = intval($id);
		$dat = $this->all();
		if(isset($dat[$id]))
			return $dat[$id];
		return 0;
	}
	/**
	 * @return array(int パラメータID => float パラメータ, ...)
	 */
	public function all(){
		$key = 'Sega.AppBundle.Dao.Util.DevParam';
		$ret = $this->cache()->get(Cache::TYPE_APC,$key);
		if($ret == null){

			$rs = $this->getHs(false)->select(
					new Table('dev_param', array('id','val')),
					new Query(array('>=' => 0),-1)
			);
			
			$ret = [];
			foreach($rs as $row){
				$ret[intval($row[0])] = $row[1]+0;
			}
			$this->cache()->set(Cache::TYPE_APC,$key,$ret);
		}
		return $ret;
	}
}

?>