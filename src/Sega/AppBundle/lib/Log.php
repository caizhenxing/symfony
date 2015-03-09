<?php
namespace Dcs;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class Log{
	const LV_NONE=0;
	const LV_ERROR=1;
	const LV_WARNING=2;
	const LV_INFO=3;
	const LV_DEBUG=4;
	const LV_VERBOSE=5;
	
	/**
	 * レベルを設定する
	 * @param int $lv レベル値
	 */
	public static function level($lv){
		self::$sLv = $lv;
	}
	/**
	 * ユーザーIDを設定する
	 */
	public static function uid($uid){
		self::$sUid = $uid;
	}
	/**
	 * エラーメッセージを出力する
	 * @param object $mes
	 * @param boolean $cs trueにするとコールスタックも表示する デフォルト値:false
	 */
	public static function e($mes,$cs=false){
		switch(self::$sLv){
			case self::LV_ERROR:
			case self::LV_WARNING:
			case self::LV_INFO:
			case self::LV_DEBUG:
			case self::LV_VERBOSE:
				self::log('Error',$mes,$cs);
				break;
			case self::LV_NONE:
			default:
				break;
		}
	}
	/**
	 * 警告メッセージを出力する
	 * @param object $mes
	 * @param boolean $cs trueにするとコールスタックも表示する デフォルト値:false
	 */
	public static function w($mes,$cs=false){
		switch(self::$sLv){
			case self::LV_VERBOSE:
			case self::LV_DEBUG:
			case self::LV_INFO:
			case self::LV_WARNING:
				self::log('Warning',$mes,$cs);
				break;
			case self::LV_ERROR:
			case self::LV_NONE:
			default:
				break;
		}
	}
	/**
	 * 情報メッセージを出力する
	 * @param object $mes
	 * @param boolean $cs trueにするとコールスタックも表示する デフォルト値:false
	 */
	public static function i($mes,$cs=false){
		switch(self::$sLv){
			case self::LV_VERBOSE:
			case self::LV_DEBUG:
			case self::LV_INFO:
				self::log('Info',$mes,$cs);
				break;
			case self::LV_WARNING:
			case self::LV_ERROR:
			case self::LV_NONE:
			default:
				break;
		}
	}
	/**
	 * デバッグメッセージを出力する
	 * @param object $mes
	 * @param boolean $cs trueにするとコールスタックも表示する デフォルト値:false
	 */
	public static function d($mes,$cs=false){
		switch(self::$sLv){
			case self::LV_VERBOSE:
			case self::LV_DEBUG:
				self::log('Debug',$mes,$cs);
				break;
			case self::LV_INFO:
			case self::LV_WARNING:
			case self::LV_ERROR:
			case self::LV_NONE:
			default:
				break;
		}
	}
	/**
	 * 木端メッセージを出力する
	 * @param object $mes
	 * @param boolean $cs trueにするとコールスタックも表示する デフォルト値:false
	 */
	public static function v($mes,$cs=false){
		switch(self::$sLv){
			case self::LV_VERBOSE:
				self::log('Verbose',$mes,$cs);
				break;
			case self::LV_DEBUG:
			case self::LV_INFO:
			case self::LV_WARNING:
			case self::LV_ERROR:
			case self::LV_NONE:
			default:
				break;
		}
	}
	
	private static function log($tag,$mes,$cs){
		if($mes instanceof \Exception){
			$trace = $mes->getTrace();
			$str = [];
			$str[] = '  '.get_class($mes).' : ['.$mes->getCode().'] : "'.$mes->getMessage().'"';
			if($cs){
				for($i=0;$i<count($trace);++$i){
					$line = $trace[$i];
					$class = sprintf('    %4d',$i+1).'. ';
					if(array_key_exists('class',$line))
						$class .= $line['class'].$line['type'];
					$class .= $line['function'];
					$str[] = $class;
					if(array_key_exists('file', $line)){
						$str[] = '          '.$line['file'].'  line '.$line['line'];
					}
				}
			}
			self::output($tag,$str);
		}else{
			if(is_null($mes)){
				$mes = 'null';
			}elseif(is_numeric($mes) || is_string($mes)){
				$mes = ''.$mes;
			}elseif(is_bool($mes)){
				$mes = $mes?'true':'false';
			}elseif(is_array($mes)){
				$mes = json_encode($mes);
			}elseif(is_resource($mes)){
				$mes = 'Resource:'.get_resource_type($mes);
			}elseif(is_object($mes)){
				if(method_exists($mes,'__toString'))
					$mes = get_class($mes).':'.$mes;
				else
					$mes = get_class($mes).':'.json_encode($mes);
			}else{
				$mes = json_encode($mes);
			}
			$str = [];
			
			if($cs){
				$str[]=$mes;
				$trace = (new \Exception())->getTrace();
				for($i=1;$i<count($trace);++$i){
					$line = $trace[$i];
					$class = sprintf('    %4d',$i-1).'. ';
					if(array_key_exists('class',$line))
						$class .= $line['class'].$line['type'];
					$class .= $line['function'];
					$str[]=$class;
					if(array_key_exists('file',$line)){
						$str[] = '          '.$line['file'].'  line '.$line['line'];
					}
				}
				self::output($tag,$str);
			}else{
				self::output($tag,$mes);
			}
		}
	}
	private static function output($tag,$logs){
		$micro = microtime(true);
		$mes = null;
		if(is_array($logs)){
			if(empty($logs))
				$logs=[''];
			$mes = strtr(self::$sFormat,[
					'<date>' => date('Y-m-d H:i:s.').sprintf('%03d',intval($micro*1000)%1000),
					'<lv>' => $tag,
					'<uid>' => self::$sUid==null?'-':self::$sUid.'',
					'<cni>' => \Dcs\ActionLog::system(),
					'<route>' => self::$sRoute,
					'<micro>' => intval($micro*1000000),
					'<log>' => $logs[0],
			]);
			array_shift($logs);
			if(!empty($logs)){
				$mes .= PHP_EOL.implode(PHP_EOL,$logs);
			}
		}else{
			$mes = strtr(self::$sFormat,[
					'<date>' => date('Y-m-d H:i:s.').sprintf('%03d',intval($micro*1000)%1000),
					'<lv>' => $tag,
					'<uid>' => self::$sUid==null?'-':self::$sUid.'',
					'<cni>' => \Dcs\ActionLog::system(),
					'<route>' => self::$sRoute,
					'<micro>' => intval($micro*1000000),
					'<log>' => $logs,
			]);
		}
		if(self::$sPath == null){
			error_log($mes);
		}else{
			self::write_log($mes.PHP_EOL);
		}
	}
	private static function write_log($mes){
		if(self::$sFp == null){
			self::$sFp = fopen(self::$sPath,'a');
			register_shutdown_function(function(){
				if(self::$sFp){
					fclose(self::$sFp);
				}
			});
		}
		if(self::$sFp && flock(self::$sFp,LOCK_EX)){
			fwrite(self::$sFp,$mes);
			fflush(self::$sFp);
			flock(self::$sFp,LOCK_UN);
		}else{
			error_log($mes,3,self::$sPath);
		}
	}
	private static $sFp = null;
	private static $sUid = null;
	private static $sLv = self::LV_NONE;
	private static $sRoute = null;
	private static $sPath = null;
	private static $sFormat = '';
	public function onKernelRequest(GetResponseEvent $event){
		self::$sRoute = $event->getRequest()->get('_route');
	}
	public function __construct($path,$format,$lv){
		self::$sPath = strtr($path,['%date%'=>date('Ymd')]);
		self::$sFormat = $format;
		switch($lv){
			case 'error':
				self::$sLv = self::LV_ERROR;
				break;
			case 'warning':
				self::$sLv = self::LV_WARNING;
				break;
			case 'info':
				self::$sLv = self::LV_INFO;
				break;
			case 'debug':
				self::$sLv = self::LV_DEBUG;
				break;
			case 'verbose':
				self::$sLv = self::LV_VERBOSE;
				break;
			case 'none':
			default:
				break;
		}
	}
}
?>