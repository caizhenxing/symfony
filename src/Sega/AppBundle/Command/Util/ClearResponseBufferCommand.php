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
class ClearResponseBufferCommand extends DcsBaseCommand {

	function configure() {
		$this
			->setName('sega:util:clear_response_buff')
			->setDescription('保存しているレスポンスバッファを削除する')
			;
	}
	
	function main(InputInterface $input, OutputInterface $output) {
		$time = $this->get("Dcs.RequestLock")->getResTime();
		$time = date("Y-m-d H:i:s",time()-$time);
		$this->getSql()->executeUpdate(
				"delete from DCS_RESPONSE where create_date < ?",
				[$time]
		);

		$output->writeln("[$time]より古いレスポンスを削除");
	}
	
}