<?php

namespace Title\GaiaBundle\ManagementToolBundle\Service;

use Gaia\Bundle\ManagementToolBundle\Util\SessionUtil;
use Gaia\Bundle\ManagementToolBundle\Constant\SessionKey;

class Log{
	use \Dcs\Base;
	
	/**
	 * ログ出力
	 * @param string $tag
	 * @param sgring $mes
	 */
	public function out($tag, $mes){
		$auid = SessionUtil::get(SessionKey::USER_ID, $this->get("request"));
		$ptmt = $this->getSql()->prepare("INSERT INTO log_management_tool (`admin_user_id`, `tag`, `mes`, `date`) VALUES (?,?,?,now())");
		$ptmt->execute([$auid,$tag,$this->convertA2S($mes,"")]);
	}
	
	
	
	
	
	protected function convertA2S($ary,$inc){
		if(is_array($ary)){
			$mes = "";
			for($i=0,$len=count($ary);$i<$len;++$i){
				$mes .= $this->convertA2S($ary[$i],$inc."  ");
			}
			return $mes;
		}else{
			return $inc.$ary."\n";
		}
	}
	
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