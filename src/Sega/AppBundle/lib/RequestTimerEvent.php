<?php
namespace Dcs;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Dcs\DetailTimeLog;

/**
 * 各種コネクタを取得するメソッドを追加
 * @author takeday
 */
class RequestTimerEvent{
	private $mLogicStart = null;
	
	public function onKernelRequest(GetResponseEvent $event){
		$this->mLogicStart = microtime(true);
		\Dcs\Log::i('start action. ');
		self::Start();
	}
	public function onKernelResponse(FilterResponseEvent $event){
		if($this->mLogicStart === null){
			return;
		}
		// ロジックタイム出力
		$time = microtime(true)-$this->mLogicStart;
		if(\Dcs\config::SlowRequestTime > 0 && intval($time*1000) > \Dcs\config::SlowRequestTime){
			\Dcs\Log::i('end action. too slow request. '.intval($time*1000).'msec '.\Dcs\AnalysisCounter::toString());
			if(self::Size() > 0){
				self::Lap('END REQUEST');
				self::Log('detail time log');
			}
		}else{
			\Dcs\Log::i('end action. '.\Dcs\AnalysisCounter::toString());
		}
		if(\Dcs\config::RequestAggregate){
			$route = $event->getRequest()->get('_route');
			error_log($route.','.($time*1000)."\n",3,'../app/logs/RequestAggregate.log');
		}
		self::End();
	}



	/**
	 * 前回Lapから現在までの時間を追加
	 * @param string $mes
	 */
	static public function Lap($mes = 'lap'){
		if(!\Dcs\config::SlowRequestDetail) return;
		self::Start();
		self::$sLap[] = [self::$sEntity->retime(),$mes];
	}


	/**
	 * 計測開始
	 */
	static protected function Start(){
		if(!\Dcs\config::SlowRequestDetail) return;
		if(self::$sEntity == null){
			self::$sLap = [];
			self::$sEntity = new StopWatch();
			self::$sEntity->start();
		}
	}
	/**
	 * 現在までのラップタイムを出力
	 * @param string $title
	 */
	static protected function Log($title = 'start'){
		if(!\Dcs\config::SlowRequestDetail) return;
		\Dcs\Log::i($title);
		for($i=0,$len=count(self::$sLap);$i<$len;++$i){
			$line = self::$sLap[$i];
			\Dcs\Log::i(sprintf('   %6d   %s',$line[0],$line[1]));
		}
	}
	
	/**
	 * 計測終了
	 */
	static protected function End(){
		if(!\Dcs\config::SlowRequestDetail) return;
		self::$sLap = [];
		self::$sEntity = null;
	}
	
	/**
	 * Lap回数
	 */
	static protected function Size(){
		return count(self::$sLap);
	}
	
	static private $sLap=[];
	static private $sEntity=null;
}
?>