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
class ClearCacheCommand extends DcsBaseCommand {

	function configure() {
		$this
			->setName('sega:util:clear_cache')
			->setDescription('キャッシュをクリアする')
			->addOption("all",null,InputOption::VALUE_NONE,"Memcachedもクリアする")
			;
	}
	
	function main(InputInterface $input, OutputInterface $output) {
		if($input->getOption("all")){
			$output->writeln("Memcachedをクリアします");
			
			if(extension_loaded("memcached")){
				$mc = $this->get("memcache.default");
				$mc->flush();
				$output->writeln("  クリア成功");
			}else{

				$output->writeln("<error>Error : Memcachedが有効になっていないかPHPにインストールされていません</error>");
			}
		}

		$output->writeln("APCuをクリアします");
		$yml = new \Dcs\YmlFileLoader();
		$conf = $yml->load("const/symfony_servers.yml");
		if(!isset($conf["servers"])){
			$output->writeln("<error>Error : clear_cache.ymlが設定されていません</error>");
			return;
		}
		$ch = curl_init();
		if(is_array($conf["servers"])){
			foreach($conf["servers"] as $serv){
				curl_setopt($ch,CURLOPT_URL, $serv."dcs/maintenance/clean_apc");
				curl_exec($ch);
			}
		}else{
			curl_setopt($ch,CURLOPT_URL, $conf["servers"]."dcs/maintenance/clean_apc");
			curl_exec($ch);
		}
		curl_close($ch);
		$output->writeln("  クリア成功");

	}
	
}