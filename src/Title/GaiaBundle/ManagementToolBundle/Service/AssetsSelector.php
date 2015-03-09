<?php

namespace Title\GaiaBundle\ManagementToolBundle\Service;

use Gaia\Bundle\ManagementToolBundle\Model\AssetMasterModelInterface;

class AssetsSelector{
	public function __construct(AssetMasterModelInterface $model){
		$this->mModel = $model;
	}
	private $mModel = null;
	
	/**
	 * Javascript用アセットセレクタデータを生成
	 * @return string
	 */
	public function create(){
		$types = $this->mModel->selectTypes();
		$ret = [];
		foreach($types as $id => $type){
			$type = [
				"id" => intval($id),
				"name" => $type,
				"list" => []
			];
			$items = $this->mModel->selectAssetsByAssetTypeId(intval($id));
			foreach($items as $iid => $item){
				$type["list"][] = [
					"id" => intval($iid),
					"name" => $item
				];
			}
			$ret[] = $type;
		}
		return json_encode($ret);
	}
	static private $listCache = null;
	
	/**
	 * アセットIDからアセットタイプを算出
	 * @param number $aid
	 * @return number
	 */
	public function assetTypeId($aid){
		$aid = intval($aid);
		if(self::$listCache == null){
			$assets_type = $this->mModel->selectTypes();
			$buf = [];
			foreach($assets_type as $id => $val){
				$buf[] = intval($id);
			}
			usort($buf,function($a,$b){
				if($a == $b) return 0;
				return ($a < $b) ? -1 : 1;
			});
			self::$listCache = $buf;
		}
		$pre = 0;
		for($i=0,$len=count(self::$listCache);$i<$len;++$i){
			if($aid < self::$listCache[$i]){
				return $pre;
			}
			$pre = self::$listCache[$i];
		}
		return 0;
	}
	
	/**
	 * アセット名を取得
	 * @param number $aid
	 * @return string
	 */
	public function assetName($aid){
		return $this->mModel->selectAssetName($this->assetTypeId($aid),intval($aid));
	}
}

?>