<?php

namespace Sega\AppBundle\Command\Util;

use Dcs\DcsBaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;


/**
 * Symfonyコマンド実行のサンプル
 * [参考] http://docs.symfony.gr.jp/symfony2/cookbook/console.html
 * 
 * 実行方法
 * [vagrant@localhost symfony]$ pwd
 * /home/vagrant/public/symfony
 * [vagrant@localhost symfony]$ php app/console sega:sample:test --user_id=1
 * 
 */
class ActionLogSendCommand extends DcsBaseCommand {
	
	/**
	 * コマンド実行
	 */
	static public function RunBackGround(){
		if(self::Used())
			return;
		exec("nohup php ".__DIR__."/../../../../../app/console ".self::CMD." --force > /dev/null &");
	}
	const CMD = 'sega:util:action_log_send';
	const PFILE = 'actionLogSendProcess';
	
	function configure() {
		$this
			->setName(self::CMD)
			->setDescription('アクションログをファイルからDBに送信する')
			->addOption("force",null,InputOption::VALUE_NONE,"通常時は使用しないでください")
			;
	}
	static private function Pfile(){
		return  \Dcs\ActionLog::TempLogPath().self::PFILE;
	}
	static private function Used(){
		if(file_exists(self::Pfile()))
			return true;
		touch(self::Pfile());
		return false;
	}
	function main(InputInterface $input, OutputInterface $output) {

		if(self::Used() && !$input->getOption("force")){
			$output->writeln("すでに起動しています");
			return;
		}
		$path = \Dcs\ActionLog::TempLogPath();
		while(true){
			$sql = null;
			try{
				$files = scandir($path);
				
				$rmf = [];
				$log = [];
				$now = intval(date("YmdHi"));
				$counter = 0;
				$i = 0;
				foreach($files as $f){
					if(strstr($f,"temp_action_") === false) continue;
					++$counter;
					list($v,$e) = explode(".",$f);
					list($t,$a,$date) = explode("_",$v);
					$date = intval($date);
					if($now-1 > $date){
						$fp = fopen($path.$f,"r");
						if($fp){
							while(!feof($fp)){
								$obj = json_decode(base64_decode(fgets($fp)));
								if(is_array($obj) && isset($obj[self::COL_LEN-1])){
									$log[] = $obj;
									++$i;
									if($i > 100){
										$this->insert($log);
										$log=[];
										$i=0;
									}
								}
							}
						}
						fclose($fp);
						$rmf[] = $f;
					}
				}
				if($i > 0){
					$this->insert($log,$output);
					$log=[];
					$i=0;
				}
				if($counter < 1){
					$output->writeln("全てのログを出力しました");
					break;
				}
				if(self::$CON != null){
					self::$CON->commit();
					self::$CON->close();
					self::$CON=null;
				}
				foreach($rmf as $f){
					unlink($path.$f);
					$output->writeln("send log [$f]");
				}

				$output->writeln("wait...");
			}catch(\Exception $ex){
				$output->writeln("<error>MySQL Error : [".$ex->getCode()."] : ".$ex->getMessage()."</error>");
				if(self::$CON != null && self::$CON->isTransactionActive()){
					self::$CON->rollBack();
				}
				break;
			}
			sleep(15);
		}
		unlink(self::Pfile());
	}
	const COL_LEN = 9;
	static $CON = null;
	static $PTMT = null;
	static $PTMT_LEN = -1;
	private function insert($log){
		if(self::$CON == null){
			self::$CON = $this->get("doctrine")->getConnection("log");
			self::$CON->beginTransaction();
		}
		$len = count($log);
		$args=[];
		for($i=0;$i<$len;++$i){
			$line = $log[$i];
			for($k=0;$k<self::COL_LEN;++$k){
				$args[] = $line[$k];
			}
		}
		if(self::$PTMT == null || self::$PTMT_LEN != $len){
			$sql = "insert into action (system,uid,tag0,tag1,tag2,tag3,tag4,log,create_date) values";
			for($k=0;$k<$len;++$k){
				if($k != 0)
					$sql .= ",";
				$sql .= "(?,?,?,?,?,?,?,?,?)";
			}
			self::$PTMT = self::$CON->prepare($sql);
			self::$PTMT_LEN  = $len;
		}
		self::$PTMT->execute($args);
		self::$PTMT->closeCursor();
	}
}