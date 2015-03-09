<?php

namespace Sega\AppBundle\Command\Sample;

use Dcs\DcsBaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


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
class TestCommand extends DcsBaseCommand {

  function configure() {
    $this
      ->setName('sega:sample:test')
      ->setDescription('php app/console をしたときにこの説明が表示されます')
      // コマンドライン引数の定義
      ->addOption('user_id', null, InputOption::VALUE_REQUIRED, 'hogehoge message', 1)
      ;
  }
  
  function main(InputInterface $input, OutputInterface $output) {
    $uid = $input->getOption('user_id');
    $con = $this->getSql();
    
    $output->writeln('user_id='.$uid." が持ってるカード一覧\n");
    
    // ユーザーが持っているカードぜんぶ
    $card_logic  = new \Logic\CardLogic;
    $ret = $card_logic->getCardDataAllByUID($con, $uid);
    
    for($i = 0; $i < count($ret); $i++) {
      $row = $ret[$i];
      
      // 通常の標準出力はこんな書き方。（デバッグ用想定）
      $output->writeln(sprintf('%d: card_mst_id=%d name=[%s] lv=%d', $i+1, $row['card_mst_id'], $row['name'], $row['lv']));
    }
    
    // ログファイル出力
    $this->log_file('正常終了！');
    
  }
  
}