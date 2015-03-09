<?php
namespace Sega\AppBundle\Controller;


use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Gaia\Bundle\HandlerSocketBundle\Util\HandlerSocketUtil;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Arpg\StopWatch as StopWatch;
use \Dcs\Arpg\Time as Time;
use \Dcs\Security as sec;

class DefaultController extends \Dcs\DcsController
{
	public $aid = 0;
	public function indexAction($name)
	{
		return $this->run(sec\Mode::NONE(),function(&$out_pem=null) use($name){
			$sep = explode("_",$name);
			
			// WEB更新
			if(strcmp($sep[0],"webup") == 0){
				exec('svn update bundles/segaapp',$output);
				// WebView更新
				echo implode("<br>\n",$output);
			}
			// アポロテスト
			if(strcmp($sep[0],"apollo") == 0){
				$apl = $this->get('gaia.apollo.service');
				echo json_encode($apl->sendLog('ENTRY_GAME',[
						'user_id' => 12345,
						'platform' => 2,
						'noah_id' => 67890,
						'device_type' => 304,
						'value2' => 12,
						'value8' => 1,
				]));
			}
			// 全装備
			if(strcmp($sep[0],"allequip")==0 && is_numeric($sep[1])){
				// allequip_[id]
				$uid = intval($sep[1]);
				// 装備追加バッチ
				$rs = $this->getHs(false)->select(
						new Table("equip_data",["std_id"]),
						new Query([">"=>0],-1)
				);
				$list=[];
				$Equip = $this->get("Arpg.Logic.Util.Equip");
				foreach($rs as $row){
					$std_id = intval($row[0]);
					if($Equip->std2type($std_id) == 6) continue; // 素材
			
					$list[]=[$std_id];
				}
				$Equip->addMulti($uid,$list);
				echo "add all equip to $uid  equip size is ".count($list);
			}
			if(strcmp($sep[0],"echo")==0){
				echo $sep[1];
			}
			
			// ダンジョンチェック
			if(strcmp($sep[0],"duncheck")==0){
				$did = "";
				if(isset($sep[1]) && is_numeric($sep[1]))
					$did = intval($sep[1]);
				
				if(is_int($did)){
					echo "<input type='button' onclick='document.location = \"duncheck\"' value='戻る' />";
					$wid = intval($did/10000) % 100;
					$aid = intval($did/100) % 100;
					$id = intval($did) % 100;
					
					$stmt = $this->sql('quest_dungeon',"select qd.title,dc.stage from quest_dungeon as qd left join dungeon_config as dc on qd.config = dc.id where qd.world_id = ? and qd.area_id = ? and qd.id = ?");
					$rs = $stmt->selectAll([$wid,$aid,$id],\PDO::FETCH_NUM);

					foreach($rs as $row){
						$title = $row[0];
						echo $title."<br>";
						$stage = $row[1];
						if(strlen($stage) < 1)
							$stage = [];
						else{
							$rs = $this->getHs(false)->select(
									new Table("enemy",["id","detail"]),
									new Query([">=" => 0],-1)
							);
							$edb = [];
							foreach($rs as $row){
								$edb[intval($row[0])] = $row[1];
							}
							$rs = $this->getHs(false)->select(
									new Table("stage_config",["id","name"]),
									new Query([">=" => 0],-1)
							);
							$sdb = [];
							foreach($rs as $row){
								$sdb[intval($row[0])] = $row[1];
							}
							
							$stage = explode(",",$stage);
							$sql = null;
							$arg = [];
							foreach($stage as $sid){
								if($sql == null)
									$sql = "select stage,`order`,enemies,type,concat('<b>',gacha,' : </b>',tbox.name) as g,rate,level,level2,level3 from enemy_place left join tbox on enemy_place.gacha = tbox.id where type in (0,6) and stage in(?";
								else
									$sql .= ",?";
								$arg[] = intval($sid);
							}
							if($sql != null)
								$sql .= ")";
							$stmt = $this->sql('enemy_place',$sql);
							$rs = $stmt->selectAll($arg,\PDO::FETCH_NUM);
							
							$stage = [];
							foreach($rs as $row){
								$sid = intval($row[0]);
								$odr = intval($row[1]);
								$type = intval($row[3]);
								if(!isset($stage[$sid]))
									$stage[$sid] = [0,[]];
								if(!isset($stage[$sid][1][$odr]))
									$stage[$sid][1][$odr] = [0,[]];
								if(!isset($stage[$sid][1][$odr][1][$type]))
									$stage[$sid][1][$odr][1][$type] = [0,[]];
								
								$enemy = [];
								if(strlen($row[2]) > 0){
									$enemy = explode(",",$row[2]);
									foreach($enemy as &$e){
										$e = $edb[intval($e)];
									}
									unset($e);
								}
								$stage[$sid][1][$odr][1][$type][1][]=[implode('<br>',$enemy),$row[4],intval($row[5]),round($row[6]/100,2)."%",round($row[7]/100,2)."%",round($row[8]/100,2)."%"];
								$stage[$sid][0]++;
								$stage[$sid][1][$odr][0]++;
								$stage[$sid][1][$odr][1][$type][0]++;
							}
						}
						echo "<table border='1'>";
						echo "<tr><th>ステージ</th><th>オーダー</th><th>タイプ</th><th>敵</th><th>ガチャ</th><th>確率</th><th>HP</th><th>ATK</th><th>DEF</th></tr>";
						foreach($stage as $sid=>$s){
							$dos=$s[0];
							$s = $s[1];
							foreach($s as $oid=>$o){
								$oos = $o[0];
								$o = $o[1];
								foreach($o as $tid=>$t){
									$tos = $t[0];
									$t = $t[1];
									foreach($t as $elem){
										echo "<tr>";
										if($dos > 0){
											echo "<td rowspan='$dos'>".$sid.":".$sdb[$sid]."</td>";
											$dos = 0;
										}
										if($oos > 0){
											echo "<td rowspan='$oos'>$oid</td>";
											$oos = 0;
										}
										if($tos > 0){
											echo "<td rowspan='$tos'>".($tid==0?"敵":"宝箱")."</td>";
											if($tid!=0){
												$elem[3] = $elem[4] = $elem[5] = "---";
											}
											$tos = 0;
										}
										echo "<td>".$elem[0]."</td><td>".$elem[1]."</td><td>".$elem[2]."</td><td>".$elem[3]."</td><td>".$elem[4]."</td><td>".$elem[5]."</td></tr>";
									}
								}
							}
						}
						echo "</table>";
						break;
					}
				}else{
					$rs = $this->getHs(false)->select(
							new Table("quest_dungeon",["world_id","area_id","id","title"]),
							new Query([">"=>0],-1)
					);
					echo "<style>.btn{float:left; width:250px;height:30px;margin:2px; border:1px solid black; cursor:pointer;background:white; color:black;}.btn:hover{background:black; color:white;}</style>";
					foreach($rs as $row){
						$did = 1000000+10000*intval($row[0])+100*intval($row[1])+intval($row[2]);
						echo "<div class='btn' onclick='document.location = \"duncheck_$did\"'>$did:".$row[3]."</div>";
					}
				}

			}
			if(strcmp($sep[0],"test")==0){
				// test用なので好きにする

				$stmt = $this->sql('other',"show table status");
				echo json_encode($stmt->selectAll([],\PDO::FETCH_ASSOC));
			}
			echo "<br>";
			return "";
		});
		
	}
}
