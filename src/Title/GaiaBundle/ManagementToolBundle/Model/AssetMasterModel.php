<?php

namespace Title\GaiaBundle\ManagementToolBundle\Model;

use Gaia\Bundle\ManagementToolBundle\Model\AssetMasterModelInterface;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;

class AssetMasterModel extends \Dcs\Arpg\Logic implements AssetMasterModelInterface
{
    // TODO ManagementTool DBから取得するなどして、タイトル独自のアセットを実装してください
    private static $types = [
    	0 => 'なし',
        1 => 'プレイヤーステータス',
        50000 => 'アクターステータス',
        101000 => '進化素材',
        200000 => '消耗アイテム',
        201000 => '魔法アイテム',
        202000 => 'ブーストアイテム',
        203000 => '特殊アイテム',
        300000 => '両手剣',
        310000 => 'ハンマー',
        320000 => '杖',
        350000 => '素材',
        360000 => 'ヘッドギア',
        370000 => 'コスチューム',
        380000 => '首飾り',
        390000 => '指輪',
    ];
        
    /**
     * アセットタイプ取得
     *
     * @return array [[asset_type_id => asset_type_name], [asset_type_id => asset_type_name], ...]
     */
    function selectTypes()
    {
        return self::$types;
    }

    /**
     * アセット取得
     *
     * @param int $assetTypeId アセットタイプID
     *
     * @return array [[asset_id => asset_name], [asset_id => asset_name], ...]
     */
    function selectAssetsByAssetTypeId($assetTypeId)
    {
    	$ret = [];
		switch($assetTypeId){
			case 1:{
				$rs = $this->getHs()->select(
						new Table("player_status",["std_id","name"]),
						new Query([">"=>0],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key == 10000 || $key == 10001 || $key == 10003){
						$name = $row[1];
						$ret[$key] = $name;
					}
				}
				break;
			}
			case 50000:{
				$rs = $this->getHs()->select(
						new Table("actor_status",["std_id","name"]),
						new Query([">"=>0],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 101000:{
				$rs = $this->getHs()->select(
						new Table("item_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= 200000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
				
			}
			case 200000:{
				$rs = $this->getHs()->select(
						new Table("item_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= 201000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 201000:{
				$rs = $this->getHs()->select(
						new Table("item_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= 202000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 202000:{
				$rs = $this->getHs()->select(
						new Table("item_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= 203000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 203000:{
				$rs = $this->getHs()->select(
						new Table("item_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= 204000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 300000:{
				$rs = $this->getHs()->select(
						new Table("equip_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= $assetTypeId+2000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 310000:{
				$rs = $this->getHs()->select(
						new Table("equip_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= $assetTypeId+2000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 320000:{
				$rs = $this->getHs()->select(
						new Table("equip_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= $assetTypeId+2000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 350000:{
				$rs = $this->getHs()->select(
						new Table("equip_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= $assetTypeId+10000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 360000:{
				$rs = $this->getHs()->select(
						new Table("equip_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= $assetTypeId+2000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 370000:{
				$rs = $this->getHs()->select(
						new Table("equip_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= $assetTypeId+2000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 380000:{
				$rs = $this->getHs()->select(
						new Table("equip_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= $assetTypeId+5000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			case 390000:{
				$rs = $this->getHs()->select(
						new Table("equip_data",["std_id","name"]),
						new Query([">="=>$assetTypeId],-1)
				);
				foreach($rs as $row){
					$key = intval($row[0]);
					if($key >= $assetTypeId+5000) continue;
					$name = $row[1];
					$ret[$key] = $name;
				}
				break;
			}
			default:
				$ret[0] = "なし";
				break;
		}
		return $ret;
    }

    /**
     * アセット名取得
     *
     * @param int $assetTypeId アセットタイプID
     * @param int $assetId アセットID
     *
     * @return string アセット名
     */
    function selectAssetName($assetTypeId, $assetId)
    {
    	$ret = null;
		switch($assetTypeId){
			case 1:{
				$rs = $this->getHs()->select(
						new Table("player_status",["name"]),
						new Query(["="=>$assetId])
				);
				foreach($rs as $row){
					$ret = $row[0];
				}
				break;
			}
			case 50000:{
				$rs = $this->getHs()->select(
						new Table("actor_status",["name"]),
						new Query(["="=>$assetId])
				);
				foreach($rs as $row){
					$ret = $row[0];
				}
				break;
			}
			case 101000:
			case 200000:
			case 201000:
			case 202000:
			case 203000:{
				$rs = $this->getHs()->select(
						new Table("item_data",["name"]),
						new Query(["="=>$assetId])
				);
				foreach($rs as $row){
					$ret = $row[0];
				}
				break;
			}
			
			case 300000:
			case 310000:
			case 320000:
			case 350000:
			case 360000:
			case 370000:
			case 380000:
			case 390000:{
				$rs = $this->getHs()->select(
						new Table("equip_data",["name"]),
						new Query(["="=>$assetId])
				);
				foreach($rs as $row){
					$ret = $row[0];
				}
				break;
			}
			default:
		}
		if($ret == null)
			$ret = "Undefined";
		return $ret;
    }

    /**
     * アセットタイプID存在チェック
     *
     * @param int $assetTypeId アセットタイプID
     *
     * @return boolean
     */
    function existsTypeId($assetTypeId)
    {
        return isset(self::$types[$assetTypeId]);
    }

    /**
     * アセットID存在チェック
     *
     * @param int $assetTypeId アセットタイプID
     * @param int $assetId アセットID
     *
     * @return boolean
     */
    function existsAssetsId($assetTypeId, $assetId)
    {
        return $this->selectAssetName($assetTypeId, $assetId) != null;
    }

    /**
     * アセットタイプ名取得
     *
     * @param int $assetTypeId アセットタイプID
     *
     * @return string アセットタイプ名
     */
    function selectAssetTypeName($assetTypeId)
    {
        return $this->existsAssetTypeId($assetTypeId)
            ? self::$types[$assetTypeId]
            : null;
    }

    /**
     * アセットタイプID存在チェック
     *
     * @param int $assetTypeId アセットタイプID
     *
     * @return boolean
     */
    function existsAssetTypeId($assetTypeId)
    {
        return isset(self::$types[$assetTypeId]);
    }
}