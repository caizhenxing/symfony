<?php

namespace Sega\AppBundle\Command\Util;

use Dcs\DcsBaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;
use Gaia\Bundle\DatabaseBundle\Present\PresentBoxDao;

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
class ClearOldCardCommand extends DcsBaseCommand {
	const DEL_DAY = 60;
	function configure() {
		$this
			->setName('arpg:clear_old_card')
			->setDescription(self::DEL_DAY.'日以上古いプレゼント・倉庫データを削除する')
			;
	}
	
	function main(InputInterface $input, OutputInterface $output) {

		$time = 3600*24*self::DEL_DAY;
		$time = date("Y-m-d H:i:s",time()-$time);

		$output->writeln(self::DEL_DAY."日以上古いデータを削除します");
		$output->writeln("プレゼントデータ削除");
		while(true){
			$con = $this->getSql();
			try{
				$rs = $con->executeQuery("select present_box_id,status_code,asset_id from GAIA_PRESENT_BOX where status_code <> ? and created_time < ? limit 100",[PresentBoxDao::STATUS_DEFAULT,$time])->fetchAll(\PDO::FETCH_NUM);
				if(empty($rs))
					break;
				$output->write('.');
				$sql = null;
				$args = [];
				$sql2 = null;
				$args2 = [];
				foreach($rs as $row){
					if($sql == null)
						$sql = 'delete from GAIA_PRESENT_BOX where present_box_id in (?';
					else
						$sql .= ',?';
					$args[] = $row[0];
					$asset_id = intval($row[2]);
						
					if(intval($row[1]) == PresentBoxDao::STATUS_POP && $asset_id > 0){
						if($sql2 == null)
							$sql2 = 'delete from box_equip where id in (?';
						else
							$sql2 .= ',?';
						$args2[] = $asset_id;
					}
				}
				$con->beginTransaction();
				if($sql != null){
					$sql .= ')';
					$con->executeUpdate($sql,$args);
				}
				if($sql2 != null){
					$sql2 .= ')';
					$con->executeUpdate($sql2,$args2);
				}
		
				if($con != null && $con->isTransactionActive())
					$con->commit();
			}catch(\Exception $e){
				if($con != null && $con->isTransactionActive()){
					$con->rollBack();
				}
				throw $e;
			}
			$con = null;
			sleep(1);
		}
		$output->writeln('');
		$output->writeln("削除フラグカード削除");
		while(true){
			$con = $this->getSql();
			$rs = $con->executeQuery("select id from box_equip where state = ? and update_date < ? limit 100",[\Logic\Util\Equip::STATE_DEL,$time])->fetchAll(\PDO::FETCH_NUM);
			if(empty($rs))
				break;
			$output->write('.');
			$sql = null;
			$args = [];
			foreach($rs as $row){
				if($sql == null)
					$sql = 'delete from box_equip where id in (?';
				else
					$sql .= ',?';
				$args[] = $row[0];
			}
			if($sql != null){
				$sql .= ')';
				$con->executeUpdate($sql,$args);
			}
			$con = null;
			sleep(1);
		}
		
		$output->writeln('');
		$output->writeln('削除完了');

	}
	
}