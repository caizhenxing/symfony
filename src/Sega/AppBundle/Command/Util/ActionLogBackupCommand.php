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
class ActionLogBackupCommand extends DcsBaseCommand {

	function configure() {
		$this
			->setName('sega:util:action_log_backup')
			->setDescription('指定期間より前のアクションログをファイルに保存して、削除をする')
			->addOption("period",null,InputOption::VALUE_OPTIONAL,"指定期間日数",30)
			->addOption("dir",null,InputOption::VALUE_OPTIONAL,"保存ディレクトリ","./data/")
			;
	}
	
	function main(InputInterface $input, OutputInterface $output) {
		$period = $input->getOption("period");
		if(!is_numeric($period)){
			$output->writeln("<error>Error : period が数字ではありません.</error>");
			return;
		}
		$period = intval($period);
		if($period < 0){
			$output->writeln("<error>Error : period に負の数字が設定されています.</error>");
			return;
		}
		$dir = $input->getOption("dir");
		if(!is_dir($dir)){
			$output->writeln("<error>Error : ディレクトリ $dir は存在しません.</error>");
			return;
		}
		
		$dir = realpath($dir)."/";
		$path = $dir."action_log.".date("Ymd.His").".sql";
		
		$date = date("Y-m-d H:i:s");
		$date = strtotime($date." -$period days");
		$date = date("Y-m-d H:i:s" , $date);
		$con = $this->get("doctrine")->getConnection("log");
		$user = $con->getUsername();
		$pass = $con->getPassword();
		$host = $con->getHost();
		$port = $con->getPort();
		$db = $con->getDatabase();
		if($port == null || strlen($port) < 1)
			$port = 3306;
		$output->writeln("$path にログを出力中...");
		exec("mysqldump -t --user=$user --password=$pass --host=$host --port=$port --default-character-set=utf8 $db action --where=\"create_date <= '$date'\" > $path");
		if(!is_file($path)){
			$output->writeln("<error>Error : $path を開けません</error>");
			return;
		}
		$output->writeln("出力部分を削除");
		try{
			$ptmt = $con->prepare("delete from action where create_date <= ?");
			$ptmt->execute([$date]);
		}catch(\Exception $e){
			$output->writeln("<error>MySQL Error : [".$e->getCode()."] : ".$e->getMessage()."</error>");
		}
	}
	
}