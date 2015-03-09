<?php

namespace Sega\AppBundle\Command\Util;

use Dcs\DcsBaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use \Dcs\Security as sec;


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
class SecurityCommand extends DcsBaseCommand {

	function configure() {
		$this
			->setName('sega:util:xor')
			->setDescription('暗号化を使う')
			->addArgument('data', InputArgument::REQUIRED, 'XORをかけるデータ')
			->addOption('act',null, InputOption::VALUE_REQUIRED, "[enc|dec] エンコードかデコードを指定する")
			;
	}
	
	function main(InputInterface $input, OutputInterface $output) {
		$act = $input->getOption("act");
		$data = $input->getArgument('data');
		if(strcmp($act,"enc") == 0){
			$output->writeln(sec::encrypt(sec\Mode::X_OR(), $data));
		}elseif(strcmp($act,"dec") == 0){
			$output->writeln(sec::decrypt(sec\Mode::X_OR(), $data));
		}else{
			$output->writeln("<error>actオプションに encとdec以外が指定されています</error>");
		}
	}
	
}