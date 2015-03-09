<?php
namespace Logic\GachaData;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Dcs\Arpg\Time as Time;
use \Dcs\DetailTimeLog as DTL;

class Result extends \Dcs\Arpg\Logic{
	public $message;
	public $getBox=[];
	public $effect;

	/**
	 * データ初期化
	 */
	public function init($uid,$gid) {
		DTL::Lap('Arpg.Logic.GachaData.Result start');

		$this->message = $gid.'番ガチャプレイありがとうございます'; // TODO
		$this->getBox = $this->get('Arpg.Logic.Util.Gacha')->drawByGid($uid,$gid);

		$now = new Time();
		$now = $now->getMySQLDateTime();
		$uname = null;
		$Text = $this->get('Arpg.Logic.Util.Text');
		$Rarity = $this->get('Arpg.Logic.Util.Rarity');
		$values=[];
		$this->effect=0;
		$notice = intval($this->get('Arpg.Logic.Util.DevParam')->param(32));

		DTL::Lap('draw');

		foreach($this->getBox as $box){
			if($box == null) continue;
			$ra = -1;
			if(isset($box->card[0]->rarity))
				$ra = $box->card[0]->rarity;
			else if(isset($box->item->rarity))
				$ra = $box->item->rarity;
			if($ra < 0) continue;

			$eff = $Rarity->effect($ra);
			if($this->effect < $eff)
				$this->effect = $eff;

			if($ra < $notice) continue; // SR以上
			if($uname == null){
				$aid = $this->get('Arpg.Logic.Util.ActorStatus')->getActorId($uid);
				$rs = $this->getHs()->select(
						new Table('box_actor',['name']),
						new Query(['='=>$aid])
				);
				if(empty($rs))break;
				$uname = $rs[0][0];
			}
			$txt = $Text->getText(603,['[player]'=>$uname,'[card]'=>$box->card[0]->name,'[rarity]'=>$Rarity->name($ra)]);
			$values[]=[$box->card[0]->stdId,$txt,$now];
		}

		DTL::Lap('create getter');
		if(!empty($values) && !empty($values[0][0])){
			$between = array(
				array(self::STD_ID_SWORD ,self::STD_ID_SWORD +self::GETTER_BETWEEN),
				array(self::STD_ID_HAMMER,self::STD_ID_HAMMER+self::GETTER_BETWEEN),
				array(self::STD_ID_STICK ,self::STD_ID_STICK +self::GETTER_BETWEEN),
				array(self::STD_ID_HELMET,self::STD_ID_HELMET+self::GETTER_BETWEEN),
				array(self::STD_ID_ARMOR ,self::STD_ID_ARMOR +self::GETTER_BETWEEN)
				);
			// $this->getHs()->insertMulti(new Table('gacha_getter',['std_id','text','get_date']),$values);
			foreach ($between as $bt) {
				if($bt[0] < $values[0] && $bt[1] > $values[0][0]){
					$this->getHs()->insertMulti(new Table('gacha_getter',['std_id','text','get_date']),$values);
					break;
				}
			}
		}
		DTL::Lap('send getter');
	}

// ・300001～300999のSR以上。(両手剣)
// ・310001～310999のSR以上。(ハンマー)
// ・320001～320999のSR以上。(杖)
// ・360001～360999のSR以上。(兜)
// ・370001～370999のSR以上。(鎧)
const GETTER_BETWEEN = 10000;
const STD_ID_SWORD  = 300000;
const STD_ID_HAMMER = 310000;
const STD_ID_STICK  = 320000;
const STD_ID_HELMET = 360000;
const STD_ID_ARMOR  = 370000;


}
?>