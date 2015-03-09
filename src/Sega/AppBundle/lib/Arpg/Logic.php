<?php
namespace Dcs\Arpg;

use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

/**
 * データアクセスオブジェクトベースクラス
 */
class Logic{
	use \Dcs\Base;
	public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $services){
		$this->mSc = $services;
	}
	
	/**
	 * サービスを取得する
	 * @param string $id サービスID
	 * @return サービスコンテナ
	 */
	protected function get($id){
		return $this->mSc->get($id);
	}

	private $mSc;
}
?>