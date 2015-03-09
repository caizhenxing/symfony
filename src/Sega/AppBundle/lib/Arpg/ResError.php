<?php
namespace Dcs\Arpg;
class ResError extends \Exception{
	/**
	 * コンストラクタ
	 * @param string|\Exception $message PHPログに出すエラー
	 * @param int $code ステータスコード
	 * @param array $format コードのメッセージが %T%回プレイ の場合 ["T"=>12] を入力すると 12回プレイ　に変換される
	 */
	public function __construct($message, $code, $format = []){
		$e = null;
		if($message instanceof \Exception){
			$e = $message;
			$message = $message->getMessage();
		}
		parent::__construct($message,$code,$e);
		$this->mFormat = $format;
	}
	public function getFormat(){
		return $this->mFormat;
	}
	
	private $mFormat = [];
}
?>