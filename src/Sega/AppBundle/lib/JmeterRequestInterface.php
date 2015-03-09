<?php
namespace Dcs;

use Dcs\Jmeter;

/**
 * Jmeter用リクエスト生成インタフェース
 */
interface JmeterRequestInterface {
	/**
	 * 次リクエスト生成ロジックを実行する
	 * 同じパラメータで再接続したい場合、内部でExceptionを投げれば、Jmeter側で再接続を行ってくれる
	 * @param Jmeter $jmeter Jmeterインスタンス
	 */
	public function run(Jmeter $jmeter);
}

?>