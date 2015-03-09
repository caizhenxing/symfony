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
class ClearActionDataCommand extends DcsBaseCommand {
	const DELDAY = 3; // この日数より前の情報を削除する
	
	function configure() {
		$this
			->setName('arpg:clear_action_data')
			->setDescription(self::DELDAY.'日以上古い、アクション情報を削除する')
			;
	}
	
	function main(InputInterface $input, OutputInterface $output) {
		$output->writeln(self::DELDAY."日以上古いアクションデータを削除");
		$time = 3600*24*self::DELDAY;
		$time = date("Y-m-d H:i:s",time()-$time);
		$output->writeln('アクションブースト削除');
		while(true){
			$num = $this->getSql()->executeUpdate(
					"delete from action_boost where create_date < ? limit 100",
					[$time]
			);
			if($num < 1)
				break;
			$output->write('.');
			sleep(1);
		}
		$output->writeln('');
		$output->writeln('アクションガチャ削除');
		while(true){
			$num = $this->getSql()->executeUpdate(
				"delete from action_gacha where create_date < ? limit 100",
				[$time]
			);
			if($num < 1)
				break;
			$output->write('.');
			sleep(1);
		}
		$output->writeln('');
		$output->writeln('部屋削除');
		while(true){
			$num = $this->getSql()->executeUpdate(
				"delete from action_room where `limit` < ? limit 100",
				[$time]
			);
			if($num < 1)
				break;
			$output->write('.');
			sleep(1);
		}
		$output->writeln('');
		$output->writeln('マルチチケット削除');
		while(true){
			$num = $this->getSql()->executeUpdate(
				"delete from action_ticket where create_time < ? limit 100",
				[$time]
			);
			if($num < 1)
				break;
			$output->write('.');
			sleep(1);
		}
		$output->writeln('');
		$output->writeln('ガチャゲッター削除');
		while(true){
			$num = $this->getSql()->executeUpdate(
				"delete from gacha_getter where get_date < ? limit 100",
				[$time]
			);
			if($num < 1)
				break;
			$output->write('.');
			sleep(1);
		}
		$output->writeln('');
		$output->writeln('ゴースト履歴削除');
		while(true){
			$num = $this->getSql()->executeUpdate(
				"delete from box_ghost where open_time < ? limit 100",
				[$time]
			);
			if($num < 1)
				break;
			$output->write('.');
			sleep(1);
		}
		$output->writeln('');
		$output->writeln('ポップアップ削除');
		
		$popup = $this->get('Arpg.Logic.PlayerData.HomePopup');
		$t = new \Dcs\Arpg\Time();
		$t->setMySQLDateTime($time);
		$t = $popup->convertTime($t);

		while(true){
			$num = $this->getSql()->executeUpdate(
					"delete from box_home_popup where time < ?",
					[$t]
			);
			if($num < 1)
				break;
			$output->write('.');
			sleep(1);
		}

		$output->writeln('');
		$output->writeln('削除完了');
	}
	
}