<?php

namespace Logic\Util;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Cache as Cache;
use \Dcs\Arpg\Time as Time;

class TakeOver extends \Dcs\Arpg\Logic{
	/**
	 * 引継ぎ情報を取得する
	 * @param number $uid ユーザーID
	 * @return array ['password'=>引継ぎパス,'limitTime'=>YYYY-MM-DD hh:mm:ss形式文字列の有効期限]
	 */
	public function info($uid){
		$sv = $this->get('gaia.user.user_take_over_service');
		$vt = $sv->getValidTime($uid);
		if($vt == false){
			return [
				'password' => null,
				'limitTime' => null
			];
		}
		
		$time = new Time();
		$now = $time->get();
		$time->setUnixTime(intval($vt/1000));

		if($now > $time->get()){
			return [
				'password' => null,
				'limitTime' => null
			];
		}
		$rs = $this->getHs()->select(
				new Table('simple_takeover',['pass']),
				new Query(['='=>$uid])
		);
		if(empty($rs)){
			return [
				'password' => null,
				'limitTime' => null
			];
		}
		$pass = $rs[0][0];
		
		return [
			'password' => $pass,
			'limitTime' => $time->getMySQLDateTime()
		];
	}
	
	/**
	 * 引継ぎ設定する
	 * @param int $uid ユーザーID
	 * @param string $tid 引継ぎID
	 * @return NULL|array ['password'=>引継ぎパス,'limitTime'=>YYYY-MM-DD hh:mm:ss形式文字列の有効期限] nullの場合失敗
	 */
	public function offer($uid,$tid){

		$this->useTransaction();
		$ptmt = $this->sql('simple_takeover','insert into simple_takeover (uid,pass,tid) values(?,?,?) on duplicate key update pass = values(pass),tid=values(tid)');
		$pass = null;
		for($i=0;$i<10;++$i){
			$buff = $this->createRandString(8);
			try{
				$ptmt->insert([$uid,$buff,$tid]);
				$pass = $buff;
				break;
			}catch(\Exception $e){
			}
		}
		
		if($pass == null){
			return null;
			throw new ResError('dont make pass',3000);
		}
		$sv = $this->get('gaia.user.user_take_over_service');
		$time = new Time();
		$time->setUnixTime(intval($sv->setPassword($uid,$pass)['pass_validtime']/1000));
			
		return [
			'password' => $pass,
			'limitTime' => $time->getMySQLDateTime()
		];
	}
	
	public function accept($uuid,$pass,$ost,$info){
		$rs = $this->getHs()->select(
				new Table('simple_takeover',['tid'],'PASS'),
				new Query(['='=>$pass])
		);
		if(empty($rs)){
			throw new ResError('cant takeover',3001);
		}
		$tid = $rs[0][0];
		$this->useTransaction();
		$sv = $this->get('gaia.user.user_take_over_service');
		try{
			$sv->takeOver($tid,$pass,$uuid,$ost,$info);
		}catch(\Exception $e){
			throw new ResError('cant takeover',3001);
		}
		$this->sql('simple_takeover','delete from simple_takeover where pass = ?')->delete([$pass]);
		return true;
	}
	
	private function createRandString($max){
		$list = [
			'a','b','c','d','e','f','g','h','i','j',
			'k','l','m','n','o','p','q','r','s','t',
			'u','v','w','x','y','z',
			'0','1','2','3','4','5','6','7','8','9'
		];
		$ret = '';
		$len = count($list)-1;
		for($i=0;$i<$max;++$i){
			$ret .= $list[mt_rand(0,$len)];
		}
		return $ret;
	}
}

?>