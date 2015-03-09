<?php

namespace Dcs;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * DcsControllerに相当する抽象クラス
 */
class DcsBaseCommand extends ContainerAwareCommand
{
	private $mOutput;
	private $mLogger;
	private $mStartTime;
	
	// trueの場合は、log_file()を標準出力にも表示させる
	private $logging_both_standardout = true;
	// trueの場合は、処理実行時間を標準出力に表示させる
	private $show_execute_time = true;
	
	//--------------------------------------------------------------------------
	// 実行＆ログ関連
	//--------------------------------------------------------------------------
	
	final protected function execute(InputInterface $input, OutputInterface $output) {
		$this->mOutput = &$output;
		$this->mLogger = $this->get('Dcs.CommandLogger');
		$this->mStartTime = microtime(true);
		
		// 実行開始ログ出力
		$this->log_file("begin.", \Monolog\Logger::INFO);
		
		try{
			return $this->main($input, $output);
		}
		catch(\Exception $e) {
			$this->log_file(sprintf('Uncaught Exception [%s] %s (%s:%d)', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()), \Monolog\Logger::ERROR);
			$this->mOutput->writeln('---- [ Exception trace info ] ----------------------------------------');
			$this->mOutput->writeln($e->getTraceAsString());
			$this->mOutput->writeln('----------------------------------------------------------------------');
			throw $e;
		}
	}
	
	function main(InputInterface $input, OutputInterface $output) {
		// please override.
	}
	
	function __destruct() {
		if($this->mLogger != null) {
			// 実行終了ログ出力
			$str = 'finish';
			if($this->show_execute_time) {
				$str .= sprintf(' (%.3fsec)', (microtime(true) - $this->mStartTime));
			}
			$this->log_file($str, \Monolog\Logger::INFO);
			$this->mOutput->writeln('');
		}
	}
	
	// コマンド実行ログファイル <app/logs/***_command.log> に出力する
	// log_levelはmonolog定義に準拠 （ DEBUG INFO NOTICE WARNING ERROR CRITICAL ALERT EMERGENCY )
	function log_file($message, $log_level = \Monolog\Logger::NOTICE) {
		if($this->logging_both_standardout) {
			$this->mOutput->writeln("<comment>[log_file] ".$message."</comment>");
		}
		$str = sprintf("[%s] %s", $this->getName(), $message);
		return $this->mLogger->addRecord($log_level, $str, array());
	}
	
	
	//--------------------------------------------------------------------------
	// Controller と同名のメソッドを定義
	//--------------------------------------------------------------------------
	
	public function get($service) {
		if (!$this->getContainer()->has($service)) {
		    throw new \LogicException(sprintf('Service [%s] is not registered in your application.', $service));
		}
		return $this->getContainer()->get($service);
	}
	
	public function getDoctrine() {
		return $this->get('doctrine');
	}
	
	
	//--------------------------------------------------------------------------
	// DcsController と同じものを定義
	//--------------------------------------------------------------------------
	
	private $mHandlerSocket=null;
	private $mMemcache=null;
	private $mCache=null;
	
	/**
	 * SQLコネクタを取得
	 * PDOオブジェクトのラッパーが帰ってくるのでほぼ同じ使い方をしてOK
	 * @return object
	 */
	public function getSql(){
		return $this->getDoctrine()->getConnection();
	}
	
	/**
	 * ハンドラ―ソケットを取得する
	 * @see \Gaia\Bundle\HandlerSocketBundle\Service\HandlerSocketService
	 * @return object
	 */
	public function getHs(){
		if($this->mHandlerSocket == null){
			$this->mHandlerSocket = $this->get("gaia.handler_socket.default_connection");
		}
		return $this->mHandlerSocket;
	}
	
	/**
	 * Memcacheコネクタを取得
	 * @return \Memcached
	 */
	public function getMemcached(){
		if($this->mMemcache == null){
			$this->mMemcache = $this->get("memcache.default");
		}
		return $this->mMemcache;
	}
	
	/**
	 * キャッシュサービス取得
	 * @return \Dcs\Cache
	 */
	protected function cache(){
		if($this->mCache == null){
			$this->mCache = $this->get("Dcs.Cache");
		}
		return $this->mCache;
	}
	
}
