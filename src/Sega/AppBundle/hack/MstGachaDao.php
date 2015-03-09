<?php

namespace Sega\AppBundle\hack;

use Doctrine\DBAL\Connection;
use Gaia\Bundle\DatabaseBundle\DBConnector\DBConnectorSQLBase;
use Gaia\Bundle\DatabaseBundle\Gacha\MstGachaDaoInterface;

/**
 * ガチャマスタ データアクセスクラス
 *
 * @copyright Copyright (c) 2013 SEGA Networks Co., Ltd. All rights reserved.
 * @author Itec Hokkaido
 */
class MstGachaDao extends DBConnectorSQLBase implements MstGachaDaoInterface
{
    const SELECT_SQL =<<<'EOD'
SELECT
    g.rate,
    CONCAT_WS('_', c.asset_id, c.asset_type_id, c.asset_count, g.rarity) AS cards
FROM
    GAIA_MST_GACHA_GROUP g
INNER JOIN
    GAIA_MST_GACHA_CARD c
USING(gacha_group_id)
WHERE
    g.gacha_id = ?
ORDER BY
    null
EOD;

    const SELECT_BY_RARITY_SQL =<<<'EOD'
SELECT
    g.rate,
    CONCAT_WS('_', c.asset_id, c.asset_type_id, c.asset_count, g.rarity) AS cards
FROM
    GAIA_MST_GACHA_GROUP g
INNER JOIN
    GAIA_MST_GACHA_CARD c
USING(gacha_group_id)
WHERE
    g.gacha_id = ?
AND
    g.rarity IN (?)
ORDER BY
    null
EOD;

    const SELECT_PERIOD_SQL =<<<'EOD'
SELECT effective_from, effective_to FROM GAIA_MST_GACHA WHERE gacha_id = ?
EOD;

    /**
     * @inheritdoc
     */
    function selectCards($gachaId, $rarity = null)
    {
    	$key = 'Sega.AppBundle.hack.MstGachaDao.'.$gachaId.'.'.json_encode($rarity);
    	$cache = $this->cache;
    	$result = $cache->get($cache::TYPE_APC,$key);
    	if($result == null){
	        $cardList = array();
	        if ($rarity === null) {
	            $cardList = $this->sql->fetchAll(
	                self::SELECT_SQL,
	                array($gachaId));
	        } else {
	            $rarityArray = is_array($rarity) ? $rarity : array($rarity);
	            $stmt = $this->sql->executeQuery(
	                self::SELECT_BY_RARITY_SQL,
	                array($gachaId, $rarityArray),
	                array(null, Connection::PARAM_INT_ARRAY)
	            );
	            $cardList = $stmt->fetchAll();
	        }
	
	        $rate = -1;
	        $cardKeyValue = '';
	        $result = array();
	
	        foreach ($cardList as $card) {
	            if ($rate === -1) {
	                $rate = $card['rate'];
	                $cardKeyValue = $card['cards'];
	                continue;
	            }
	
	            if ($rate === $card['rate']) {
	                $cardKeyValue.= ','.$card['cards'];
	                continue;
	            } else {
	                $result[] = array('rate' => $rate, 'cards' => $cardKeyValue);
	                $rate = $card['rate'];
	                $cardKeyValue = $card['cards'];
	                continue;
	            }
	        }
	
	        if ($rate !== -1) {
	            $result[] = array('rate' => $rate, 'cards' => $cardKeyValue);
	        }
    		
	        $cache->set($cache::TYPE_APC,$key,$result);
    	}
        return $result;
    }

    /**
     * @inheritdoc
     */
    function getEffectivePeriod($gachaId)
    {
        return $this->sql->fetchAssoc(self::SELECT_PERIOD_SQL, array($gachaId));
    }
    
    /**
     * コンストラクタ
     *
     * @param Connection $sql SQLコネクション
     */
    public function __construct(Connection $sql, \Dcs\Cache $cache)
    {
    	parent::__construct($sql);
        $this->cache = $cache;
    }
    protected $cache;
}