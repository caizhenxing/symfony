<?php

namespace Sega\AppBundle\Controller;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Logic\GachaData as Gacha;
use \Dcs\Security as sec;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\Arpg\Time as Time;
use \Dcs\DetailTimeLog as DTL;

class GachaDataController extends \Dcs\DcsController{

	/**
	 * ガチャバナー取得
	 * リクエストデータ構造
	 * data SessionKey
	 * RPC構造
	 * data:[ Arpg.Logic.GachaData.Bannerコンテナ ]
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getBannerAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$skey = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);

			$rs = $this->get('Arpg.Logic.GachaData.Banner')->getList();
			
			$dat = array();
			usort($rs, function($a,$b){
				if($a->order() == $b->order())
					return 0;
				return ($a->order() < $b->order()) ? -1 : 1;
			});
			foreach($rs as $d){
				if(!$d->enable()) continue;
				$dat[] = $d;
			}
		
			return $dat;
		});
		
	}
	/**
	 * ガチャ情報取得
	 * リクエストデータ構造
	 * data null
	 * RPC構造
	 * data:[ Arpg.Logic.GachaData.Infoコンテナ ]
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getInfoAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$key = 'Arpg.GachaDataController.getInfo';
			$list = $this->cache()->get(\Dcs\Cache::TYPE_APC,$key);
			if($list == null){
				$list = [];
				$rs = $this->getHs(false)->select(
						new Table('gacha_info', Gacha\Info::$DBKEY),
						new Query(array('>'=>0),-1)
				);
				foreach($rs as $row){
					$d = $this->get('Arpg.Logic.GachaData.Info');
					$d->init($row);
					$list[] = $d;
				}
				$this->cache()->set(\Dcs\Cache::TYPE_APC,$key,$list);
			}
			$ret = [];
			foreach($list as $line){
				if($line->enable())
					$ret[] = $line;
			}
			return $ret;
		});
	}
	/**
	 * ガチャ獲得者取得
	 * リクエストデータ構造
	 * data null
	 * RPC構造
	 * data:[ Arpg.Logic.GachaData.Getterコンテナ ]
	 * err: {
	 * 		code: int 1: ユーザーがいない 2: DBエラー
	 * 		mes: string 何かしらメッセージ
	 * }
	 */
	public function getGetterAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$rs = $this->sql('gacha_getter','select std_id, text from gacha_getter order by get_date desc limit 10')->selectAll([], \PDO::FETCH_NUM, \Dcs\SqlStatement::MODE_SLAVE);
			$dat = [];
			foreach($rs as $row){
				$dat[]=[
					'stdId'=>intval($row[0]),
					'getterText'=>$row[1]
				];
			}
			return $dat;
		});
	}
	
	/**
	 * ガチャやる
	 * @param unknown $data
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function tryGachaAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			DTL::Lap('GachaDataController tryGacha start');
			$data = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey = $data['skey'];
			$gacha_id = intval($data['id']);
			
			$user = $this->createCmnAccount();
			
			$this->checkLogin($user,$skey,\Dcs\RequestLock::LOCK_LV3);

			$rs = $this->get('Arpg.Logic.GachaData.Result');
			$rs->init($user->getUid(),$gacha_id);
			DTL::Lap('try draw');
			return  $rs;
		});
	}
}
?>