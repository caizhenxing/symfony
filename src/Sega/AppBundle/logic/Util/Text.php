<?php
namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Cache as Cache;

class Text extends \Dcs\Arpg\Logic{
	
	/**
	 * 文字列を取得する
	 * @param int $id
	 * @param array $arg [AAA]テスト という文字列を取得する際に。arg=['[AAA]'=>'デバッグ']とすると、デバッグテスト と置換して出力する
	 */
	public function getText($id, array $arg=[]){
		$id = intval($id);
		$c = $this->cache();
		$key = 'Sega.AppBundle.Dao.Util.Text.'.\Dcs\Arpg\Config::Lang;
		$list = $c->get(Cache::TYPE_APC,$key);
		if($list == null){
			$rs = $this->getHs(false)->select(
					new Table('lang_text_'.\Dcs\Arpg\Config::Lang,['id','text']),
					new Query(['>'=>0],-1)
			);
			$list = [];
			foreach($rs as $row){
				$list[intval($row[0])] = $row[1];
			}
			$c->set(Cache::TYPE_APC,$key,$list);
		}
		if(isset($list[$id])){
			$search=[];
			$replace=[];
			foreach($arg as $key=>$val){
				$val .= '';
				if(!is_string($val)){
					\Dcs\Log::w("Text.getText invalid value. id[$id] key[$key]");
					continue;
				}
				$search[]=$key;
				$replace[]=$val;
			}
			return str_replace($search,$replace,$list[$id]);
		}
		return '';
	}
	
	/**
	 * 検索用文字列に変換する
	 * @param string $word
	 * @return string
	 */
	public function convertFindText($word){
		$key = 'Arpg.Logic.Util.Text.convertFindText';
		$cache = $this->cache();
		$dat = $cache->get($cache::TYPE_APC,$key);
		if($dat == null){
			$rs = $this->getHs(false)->select(
					new Table('lang_find_str',['priority','base','find']),
					new Query(['>'=>0],-1)
			);
			usort($rs,function($a,$b){
				$a = intval($a[0]);
				$b = intval($b[0]);
				if ($a == $b) {
					return 0;
				}
				return ($a < $b) ? -1 : 1;
			});
			$dat = ['from'=>[],'to'=>[]];
			for($i=0,$len=count($rs);$i<$len;++$i){
				$dat['from'][] = $rs[$i][1];
				$dat['to'][] = $rs[$i][2];
			}
			$cache->set($cache::TYPE_APC,$key,$dat);
		}
		return str_replace($dat['from'],$dat['to'],$word);
	}

	/**
	 * 禁止文字列チェック
	 * @param unknown $word
	 */
	public function checkNg($word){
		$list = $this->getNgList();
		$word = str_replace([' ','　',"\t"],['','',''],$this->convertFindText($word));
		foreach($list as $ng){
			if(mb_stripos($word,$ng) === false) continue;
			return true;
		}
		return false;
	}
	
	private function getNgList(){
		$key = 'Arpg.Logic.Util.Text.checkNg'.\Dcs\Arpg\Config::Lang;
		$c = $this->cache();
		$ret = $c->get(Cache::TYPE_APC,$key);
		if($ret == null){
			$rs = $this->getHs(false)->select(
					new Table('lang_ng_'.\Dcs\Arpg\Config::Lang,['word']),
					new Query(['>'=>0],-1)
			);
			$ret = [];
			foreach($rs as $row){
				$ret[] = $this->convertFindText($row[0]);
			}
			$c->set(Cache::TYPE_APC,$key,$ret);
		}
		return $ret;
	}
}

?>