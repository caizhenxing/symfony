<?php

namespace Logic\Jmeter\Scene;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Dcs\Jmeter;
use \Dcs\Security as sec;
use Logic\Util\Equip;

/**
 * Logic\Jmeter\RequestLogicの内容を分割
 * @author takeda_yoshihiro
 *
 */
trait NewUser{
	private function dcs_create_account(Jmeter $jm,$mode,$scene){
		$rpc = $jm->getRpc();
		if($rpc->err != null)
			throw new \Exception('Jmeter dcs_create_account failed -> HttpStatus 500');
		$data = $rpc->data;
		if(strlen($data['mL']['mUuid']) < 1 || strlen($data['mS']['mSid']) < 1){
			throw new \Exception('Jmeter dcs_login failed -> HttpStatus 500'.json_encode($data));
		}
		$jm->newUuid($data['mL']['mUuid']);
		$jm->newSid($data['mS']['mSid']);
		
		$ajm = $jm->getAjmData();
		$ptmt = $this->sqlCon()->prepare('select user_id from GAIA_USER_ACCOUNT where uuid = ? limit 1');
		$ptmt->execute([
			$data['mL']['mUuid']
		]);
		$rs = $ptmt->fetch(\PDO::FETCH_NUM);
		$ajm['uid'] = $rs[0];
		switch($mode){
			case self::MODE_NUONLY:
				{
					$jm->setNext('stdconnect/read/player_basic',15,'xor',self::skey($jm),$ajm);
					break;
				}
			case self::MODE_NEW:
				{
					$jm->setNext('stdconnect/read/init_data',15,'xor',null,$ajm);
					break;
				}
			default:
				{
					break;
				}
		}
	}
	private function arpg_readonly_get_init_actor_parts(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
				{
					$ajm = $jm->getAjmData();
					$data = $jm->getRpc()->data;
					$is_male = mt_rand(0,99)<50;
					$mf = $is_male?'male':'female';
					
					$jm->setNext('stdconnect/write/entry_new_actor',15,'xor',[
						'faceType' => $data[$mf.'Face'][mt_rand(0,4)],
						'hairColor' => mt_rand(0,7),
						'bodyColor' => mt_rand(0,5),
						'gender' => $is_male,
						'eyeColor' => mt_rand(0,7),
						'name' => self::NameBase.mt_rand(0,99),
						'hairStyle' => $data[$mf.'Hair'][mt_rand(0,5)],
						'skey' => self::skey($jm)
					],$ajm);
					break;
				}
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					$data = $jm->getRpc()->data;
					$is_male = mt_rand(0,99)<50;
					$mf = $is_male?'male':'female';
					$sheet = [
						'is_male' => $is_male,
						'skin' => mt_rand(0,5),
						'face' => $data[$mf.'Face'][mt_rand(0,4)],
						'eye' => mt_rand(0,7),
						'hair' => $data[$mf.'Hair'][mt_rand(0,5)],
						'hair_color' => mt_rand(0,7),
						'costume' => 370118,
						'weapon' => 300001,
						'head_gear' => 360000,
						'no_cache' => false
					];
					$ajm['asheet'] = $sheet;
					
					$jm->setNext('stdconnect/read/player_dungeon_stdid',15,'xor',[
						'sid' => '1010101',
						'skey' => self::skey($jm)
					],$ajm);
					break;
				}
			default:
				{
					break;
				}
		}
	}
	private function arpg_readwrite_entry_new_actor(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					$ajm['aid'] = $jm->getRpc()->data['actor']->id;
					$jm->setNext('stdconnect/write/create_dungeon_and_room',15,'xor',[
							'state' => 0,
							'did' => 1010101,
							'open' => 1,
							'pid' => $ajm['pid'],
							'limit' => 60,
							'skey' => self::skey($jm)
							],$ajm);
					break;
				}
			default:
				{
					break;
				}
		}
	}
	private function arpg_readwrite_write_tutorial_step(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NUONLY:
				{
					$ajm = $jm->getAjmData();
					$step = intval($ajm['tstep']);
					switch($step){
						case 2:
							{
								$ajm = $jm->getAjmData();
								$ajm['tstep'] = 3;
								$jm->setNext('stdconnect/write/write_tutorial_step',15,'xor',[
									'tag' => 1,
									'step' => 3,
									'skey' => self::skey($jm)
								],$ajm);
								break;
							}
						case 3:{
							$ajm = $jm->getAjmData();
							$ajm['tstep'] = 4;
							$jm->setNext('stdconnect/write/write_tutorial_step',15,'xor',[
									'tag' => 1,
									'step' => 4,
									'skey' => self::skey($jm)
									],$ajm);
							break;
						}
						case 4:
							{
								$jm->setNext('stdconnect/write/end_dungeon',15,'xor',[
									'time' => 59,
									'tbox' => [],
									'item' => [],
									'cross' => [],
									'elixir' => 0,
									'clear' => true,
									'kill' => 13,
									'did' => 1010101,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case 21010101:
							{
								$ajm = $jm->getAjmData();
								$ajm['tstep'] = 5;
								$jm->setNext('stdconnect/write/write_tutorial_step',15,'xor',[
									'tag' => 1,
									'step' => 5,
									'skey' => self::skey($jm)
								],$ajm);
								break;
							}
						case 5:
						default:
							{
								// new user end
								break;
							}
					}
					break;
				}
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					$step = intval($ajm['tstep']);
					switch($step){
						case 2:
							{
								$jm->setNext('stdconnect/read/gacha_getter',15,'xor',self::skey($jm),$ajm);
								break;
							}
						case 3:
							{
								$ajm = $jm->getAjmData();
								$ajm['adv'] = 1;
								$req = [
									'skey' => self::skey($jm)
								];
								$req['did'] = 1010101;
								$req['type'] = 1;
								$jm->setNext('stdconnect/read/adv_data',15,'xor',$req,$ajm);
								break;
							}
						case 4:
							{
								$jm->setNext('stdconnect/write/end_dungeon',15,'xor',[
									'time' => 59,
									'tbox' => [],
									'item' => [],
									'cross' => [],
									'elixir' => 0,
									'clear' => true,
									'kill' => 13,
									'did' => 1010101,
									'skey' => self::skey($jm)
								],$jm->getAjmData());
								break;
							}
						case 21010101:
							{
								$ajm = $jm->getAjmData();
								$ajm['tstep'] = 5;
								$jm->setNext('stdconnect/write/write_tutorial_step',15,'xor',[
									'tag' => 1,
									'step' => 5,
									'skey' => self::skey($jm)
								],$ajm);
								break;
							}
						case 5:
							{
								$ajm = $jm->getAjmData();
								$ajm['adv'] = 2;
								$req = [
									'skey' => self::skey($jm)
								];
								$req['did'] = 1010101;
								$req['type'] = 2;
								$jm->setNext('stdconnect/read/adv_data',15,'xor',$req,$ajm);
								break;
							}
					}
					break;
				}
			default:
				{
					break;
				}
		}
	}
	private function arpg_readonly_act_tutorial(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					$ajm['tstep'] = 3;
					$jm->setNext('stdconnect/write/write_tutorial_step',15,'xor',[
						'tag' => 1,
						'step' => 3,
						'skey' => self::skey($jm)
					],$ajm);
					break;
				}
			default:
				{
					break;
				}
		}
	}
	private function arpg_readonly_player_dungeon_stdid(Jmeter $jm,$mode,$scene){
		switch($mode){
			case self::MODE_NEW:
				{
					$ajm = $jm->getAjmData();
					
					$jm->setNext('arpg/Fitting/model',15,'xor',$ajm['asheet'],$ajm);
					break;
				}
			default:
				{
					break;
				}
		}
	}
}
?>