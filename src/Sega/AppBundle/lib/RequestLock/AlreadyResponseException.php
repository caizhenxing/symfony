<?php
namespace Dcs\RequestLock;
class AlreadyResponseException extends \Exception{
	/**
	 * コンストラクタ
	 * @param string $response_data
	 * @param string $message
	 * @param \Exception $previous
	 * @param number $code
	 */
	public function __construct($response_data,$message = null, \Exception $previous = null, $code = 0)
	{
		$this->response = $response_data;
		parent::__construct($message, $code, $previous);
	}
	public $response = null;
}
?>