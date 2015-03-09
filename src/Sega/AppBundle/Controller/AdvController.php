<?php
/**
 * メモ
 * 地道に高速化１を実装済み
 */
namespace Sega\AppBundle\Controller;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Logic\AdvData\ReceiveData as ReceiveData;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;

class AdvController extends \Dcs\DcsController{

	/**
	 * プリセットメッセージを取得
	 * data: セッションキー
	 * RPC構造
	 * data:{
	 * 		skey: セッションキー
	 * 		did: ダンジョンID
	 * }
	 */
	public function getAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$did = intval($data['did']);
			$type = intval($data['type']);


			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			$key = 'Arpg.Logic.AdvData.ReceiveData.init:'.$did.':'.$type;
			$cache = $this->cache();
			$ret = $cache->get($cache::TYPE_APC,$key);
			if($ret == null){
				// アドベンチャー会話データの取得
				$rs = $this->getHs(false)->select(
						new Table(ReceiveData::TBL,ReceiveData::$FLD,ReceiveData::INDEX_DUNGEON_ID),
						new Query(['='=>[$did,$type]],-1)
				);
				$ret = [];
				foreach($rs as $row){
					$dat = $this->get('Arpg.Logic.AdvData.ReceiveData');
					$dat->init($row);
					$ret[] = $dat;
				}
				$cache->set($cache::TYPE_APC,$key,$ret);
			}
			return $ret;
		});
	}

	/**
	 * ストーリービューリスト
	 * リクエストデータ構造
	 * 		セッションキー
	 * レスポンスデータ構造
	 * 		List<PlayerData.StoryView>
	 */
	public function storyViewAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);

			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$key = 'Arpg.Controller.Adv.storyView';
			$base = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
			if($base == null){
				// アドベンチャー会話
				$rs = $this->getHs(false)->select(
						new Table('lang_adv_view_jp',['id','name','union_id','child']),
						new Query(['>'=>0],-1)
				);
				$sr = [];
				foreach($rs as $row){
					$union_id = intval($row[2]);
					$sep = explode(',',$row[3]);
					$views = [];
					if($union_id == 0){
						foreach($sep as $s){
							$s = intval($s);
							if($s > 0)
								$views[]=$s;
						}
					}
					$sr[intval($row[0])]=[
						'name'=>$row[1],
						'type'=>intval($union_id/10000000),
						'stdId'=>intval($union_id%10000000),
						'views'=>$views
					];
				}
				$base = $this->makeTree($sr,1);
				$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$base);
			}
			// ユーザーのクエスト状況のロード
			//$stmt = $this->sql('box_quest','select world_id,area_id,dungeon_id,nb_try,nb_clear from box_quest where uid = ?');
			//$rs = $stmt->selectAll(array($uid),\PDO::FETCH_NUM);
			$rs = $this->getHs()->select(
					new Table('box_quest',['world_id','area_id','dungeon_id','nb_try','nb_clear']),
					new Query(['='=>$uid],-1)
			);

			$sr = [];
			foreach($rs as &$row){
				$sr[1000000+intval($row[0])*10000+intval($row[1])*100+intval($row[2])]=[
						1 => intval($row[3]) > 0,
						2 => intval($row[4]) > 0
				];
			}
			$base = $this->cutTree($sr,$base);

			return $base['views'];
		});
	}
	private function cutTree($rs,$base){
		$t = $base['type'];
		if($t == 3)
			return $base;
		if($t == 1 || $t == 2){
			if(isset($rs[$base['stdId']][$t]) && $rs[$base['stdId']][$t])
				return $base;

		}
		if(is_array($base['views'])){
			$swap = [];
			foreach($base['views'] as $v){
				$v = $this->cutTree($rs,$v);
				if($v != null)
					$swap[] = $v;
			}
			if(empty($swap)) return null;
			$base['views'] = $swap;
			return $base;
		}else{
			return null;
		}

	}
	private function makeTree($rs,$id,$dipth=7){
		if($dipth == 0) return null;
		if(!isset($rs[$id])) return null;
		$my = $rs[$id];
		$views=$my['views'];
		$my['views'] = [];
		foreach($views as $v){
			$c = $this->makeTree($rs,$v,$dipth-1);
			if($c == null) continue;
			$my['views'][] = $c;
		}
		if($my['type'] == 0 && empty($my['views'])) return null;
		return $my;
	}
}
?>