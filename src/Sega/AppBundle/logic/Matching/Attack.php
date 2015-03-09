<?php
namespace Logic\Matching;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

/**
 * マッチング後ダンジョン用データ
 */
class Attack extends \Dcs\Arpg\Logic{
	const TABLE = 'enemy_attack';
	const SELECT_INDEX = 'ENM_INDEX';
	public static $COLMNS = [
		'nocoli','animation','pre_anim','pre_time','stiff_anim',			// 0-4
		'stiff_time','interval','distance','dist_near','power',				// 5-9
		'mpower','type','attribute','bullet','bullet_origin',				// 10-14
		'effect','effect_delay','effect_origin','pre_effect','pre_delay',	// 15-19
		'pre_origin','post_effect','post_delay','post_origin','time',		// 20-24
		'collision','status','stat_rate','bullet_exp','id',					// 25-29
		'next_id','hp',	'b_speed','b_homing','b_vy',						// 30-34
		'b_ay','b_num','b_ang','gene_enemy'									// 35-39
	];
	
	public $id;
	public $next_id;
	public $hp;
	public $nocoli;
	public $style;
	public $anime;
	public $pre_anim;
	public $pre_time;
	public $stiff_anim;
	public $stiff_time;
	public $interval;
	public $distance;
	public $dist_near;
	public $power;
	public $mpower;
	public $status;
	public $stat_rate;
	public $type;
	public $attribute;
	public $__effect;
	public $time;
	public $collision;
	public $b_speed;
	public $b_homing;
	public $b_vy;
	public $b_ay;
	public $b_num;
	public $b_ang;
	public $__g;
	
	public function __sleep(){
		return [
			'id',
			'next_id',
			'hp',
			'nocoli',
			'anime',
			'style',
			'pre_anim',
			'pre_time',
			'stiff_anim',
			'stiff_time',
			'interval',
			'distance',
			'dist_near',
			'power',
			'mpower',
			'status',
			'stat_rate',
			'type',
			'attribute',
			'__effect',
			'time',
			'collision',
			'b_speed',
			'b_homing',
			'b_vy',
			'b_ay',
			'b_num',
			'b_ang',
			'__g',
		];
	}
	
	/**
	 * 初期化
	 * @param array $row $COLMNSを実行した結果の行
	 * @return array 使用するエフェクトidのリスト 敵情報が変な場合nullを返す
	 */
	public function init($style,$row){
		$this->id = intval($row[29]);
		$this->next_id = intval($row[30]);
		$this->hp = intval($row[31]);
		$this->style = $style;
		$this->nocoli =intval($row[0]);
		$this->anime = $row[1];
		$this->pre_anim = $row[2];
		$this->pre_time = $row[3]+0;
		$this->stiff_anim = $row[4];
		$this->stiff_time = $row[5]+0;
		$this->interval = $row[6]+0;
		$this->distance = $row[7]+0;
		$this->dist_near = $row[8]+0;
		$this->power = intval($row[9]);
		$this->mpower = intval($row[10]);
		$this->status = intval($row[26]);
		$this->stat_rate = intval($row[27]);
		$this->type = intval($row[11]);
		$this->attribute = intval($row[12]);
		$this->__effect = [
			[
				'__e'=>intval($row[13]),
				'delay'=>0,
				'origin'=>$row[14],
			],
			[
				'__e'=>intval($row[15]),
				'delay'=>($row[16]+0),
				'origin'=>$row[17],
			],
			[
				'__e'=>intval($row[18]),
				'delay'=>($row[19]+0),
				'origin'=>$row[20],
			],
			[
				'__e'=>intval($row[21]),
				'delay'=>($row[22]+0),
				'origin'=>$row[23],
			],
			[
				'__e'=>intval($row[28]),
				'delay'=>0,
				'origin'=>null
			]
		];
		$this->time = json_decode('['.$row[24].']',true);
		$this->collision = json_decode('['.$row[25].']',true);

		$this->b_speed = $row[32]+0;
		$this->b_homing = $row[33]+0;
		$this->b_vy = $row[34]+0;
		$this->b_ay = $row[35]+0;
		$this->b_num = intval($row[36]);
		$this->b_ang = intval($row[37]);
		$this->__g = intval($row[38]);
		
		return [$this->__effect[0]['__e'],$this->__effect[1]['__e'],$this->__effect[2]['__e'],$this->__effect[3]['__e'],$this->__effect[4]['__e']];
	}
}

?>