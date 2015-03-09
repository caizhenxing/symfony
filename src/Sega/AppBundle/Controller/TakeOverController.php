<?php
namespace Sega\AppBundle\Controller;


use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Gaia\Bundle\HandlerSocketBundle\Util\HandlerSocketUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Arpg\StopWatch as StopWatch;
use \Dcs\Arpg\ResError as ResError;
use \Dcs\Arpg\Time as Time;
use \Dcs\Security as sec;

class TakeOverController extends \Dcs\DcsController
{
	private function makeResponsData($ret){
		if($ret['password'] == null){
			$ret['password'] = '';
		}
		if($ret['limitTime'] == null){
			$ret['limitTime'] = 0;
		}else{
			$ret['limitTime'] = Time::MySQL2Arpg($ret['limitTime']);
		}
		return $ret;
	}
	public function infoAction($data){
		return $this->run(sec\Mode::RSA(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey  = $data['skey'];
			$out_pem = $data['aid'];
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			
			$ret = $this->get('Arpg.Logic.Util.TakeOver')->info($uid);
			return $this->makeResponsData($ret);
		});
	}
	public function checkAction($data){
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$pass  = json_decode(sec::decrypt(sec\Mode::RSA(), $data),true);
			
			$rs = $this->getHs()->select(
					new Table('simple_takeover',['uid'],'PASS'),
					new Query(['='=>$pass])
			);
			$uid = null;
			foreach($rs as $row){
				$uid = intval($row[0]);
				break;
			}
			if($uid == null)
				throw new ResError('dont find takeover uid data. pass['.$pass.']',3002);

			$sv = $this->get('gaia.user.user_take_over_service');
			$vt = $sv->getValidTime($uid);
			if($vt == false)
				throw new ResError('dont find takeover validtime data. pass['.$pass.']',3002);

			$time = new Time();
			$time->setUnixTime(intval($vt/1000));
			$now = new Time();
			if($now->get() > $time->get())
				throw new ResError('dont find takeover data in time. pass['.$pass.']',3002);
			
			$lv = $this->get('Arpg.Logic.Util.PlayerStatus')->getStatus($uid,self::std_lv);
			$Astatus = $this->get('Arpg.Logic.Util.ActorStatus');
			$name = $Astatus->getName($Astatus->getActorId($uid));
			
			return [
				'lv' => $lv,
				'name' => $name,
			];
		});
	}
	
	public function offerAction($data){
		return $this->run(sec\Mode::RSA(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::X_OR(), $data),true);
			$skey  = $data['skey'];
			$out_pem = $data['aid'];
			
			$user = $this->createCmnAccount();
			$this->checkLogin($user,$skey);
			$uid = $user->getUid();
			$tid = $user->getAccountData($uid)['take_over_id'];

			$ret = $this->get('Arpg.Logic.Util.TakeOver')->offer($uid,$tid,\Dcs\RequestLock::LOCK_LV3);
			if($ret == null){
				throw new ResError('dont make pass',3000);
			}

			return $this->makeResponsData($ret);
		});
	}
	public function acceptAction($data){		
		return $this->run(sec\Mode::X_OR(),function(&$out_pem=null) use($data){
			$data  = json_decode(sec::decrypt(sec\Mode::RSA(), $data),true);
			// TODO LOCK
			$uuid = $data['uuid'];
			$pass = $data['pass'];
			$ost = intval($data['type']);
			$info = $data['info'];
			return $this->get('Arpg.Logic.Util.TakeOver')->accept($uuid,$pass,$ost,$info);
		});
		
	}
	
	const std_lv = 1;
}
