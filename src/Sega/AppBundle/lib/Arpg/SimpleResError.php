<?php
namespace Dcs\Arpg;
class SimpleResError extends \Exception{
	/**
	 * コンストラクタ
	 * @param string|\Exception $message PHPログに出すエラー
	 * @param int $code ステータスコード
	 * @param array $format コードのメッセージが %T%回プレイ の場合 ["T"=>12] を入力すると 12回プレイ　に変換される
	 */
	public function __construct($message, $code, $action){
		$e = null;
		if($message instanceof \Exception){
			$e = $message;
			$message = $message->getMessage();
		}
		parent::__construct($message,$code,$e);
		$this->mAction = $action;
	}
	
	public function getAction(){
		return $this->mAction;
	}
	
	private $mAction;
}
?>