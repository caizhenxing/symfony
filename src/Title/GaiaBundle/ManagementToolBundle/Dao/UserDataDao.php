<?php

namespace Title\GaiaBundle\ManagementToolBundle\Dao;


use Gaia\Bundle\DatabaseBundle\DBConnector\DBConnectorSQLBase;
use Gaia\Bundle\ManagementToolBundle\Constant\SearchType;
use Gaia\Bundle\ManagementToolBundle\Constant\Sort;
use Title\GaiaBundle\ManagementToolBundle\Constant\TitleSearchType;

class UserDataDao extends DBConnectorSQLBase implements \Gaia\Bundle\ManagementToolBundle\Dao\User\UserDataDaoInterface
{
    const SORT_COLMN_USER_ID = 1;
    const SORT_COLMN_FRIEND_ID = 2;
    const SORT_COLMN_TAKE_OVER_ID = 3;
    const SORT_COLMN_NOAH_ID = 4;
    const SORT_COLMN_PLAYER_ID = 5;

    /**
     * {@inheritdoc}
     */
    public function selectList($searchType, $searchId, $sortColumn, $sortOrder, $limit, $offset)
    {
        $sql =<<<'EOD'
SELECT
    account.user_id
  , friend.public_id
  , account.take_over_id
  , session.noah_id
  , player.iname
  , group_concat(actor.name separator ',') as names
FROM      GAIA_USER_ACCOUNT account
LEFT JOIN GAIA_USER_SESSION session          USING (user_id)
LEFT JOIN GAIA_USER_DATA_ABOUT_FRIEND friend USING (user_id)
LEFT JOIN box_player player on account.user_id = player.uid
LEFT JOIN box_actor actor on account.user_id = actor.uid
%WHERE%
group by account.user_id ORDER BY %ORDER% LIMIT %LIMIT% OFFSET %OFFSET%
EOD;

        $sql = str_replace('%WHERE%',$this->createWhereString($searchType), $sql);
        $sql = str_replace('%ORDER%', $this->createOrderString($sortColumn, $sortOrder), $sql);
        $sql = str_replace('%LIMIT%', intval($limit), $sql);
        $sql = str_replace('%OFFSET%', intval($offset), $sql);

        return $this->sql->fetchAll($sql, ['search_id' => $this->createWhereSearchID($searchType,$searchId)]);
    }

    public function countAllData($searchType, $searchId)
    {
        $sql =<<<'EOD'
SELECT count(*)
FROM      GAIA_USER_ACCOUNT account
LEFT JOIN GAIA_USER_SESSION session          USING (user_id)
LEFT JOIN GAIA_USER_DATA_ABOUT_FRIEND friend USING (user_id)
LEFT JOIN box_player player on account.user_id = player.uid
LEFT JOIN box_actor actor on account.user_id = actor.uid
%WHERE%
EOD;
        $sql = str_replace('%WHERE%',$this->createWhereString($searchType), $sql);

        return  $this->sql->fetchColumn($sql, ['search_id' => $this->createWhereSearchID($searchType,$searchId)]);
    }

    protected function createOrderString($sortColumn, $sortOrder)
    {
        switch ($sortColumn) {
            case self::SORT_COLMN_USER_ID;
                $orderString = 'account.user_id';
                break;
            case self::SORT_COLMN_FRIEND_ID;
                $orderString = 'friend.public_id';
                break;
            case self::SORT_COLMN_TAKE_OVER_ID;
                $orderString = 'account.take_over_id';
                break;
            case self::SORT_COLMN_NOAH_ID;
                $orderString = 'session.noah_id';
                break;
            case self::SORT_COLMN_PLAYER_ID:
                $orderString = 'player.iname';
            	break;
            default:
                return '';
        }

        if ($sortOrder == Sort::ORDER_ASC) {
            $orderString.= ' ASC';
        } else if ($sortOrder == Sort::ORDER_DESC) {
            $orderString.= ' DESC';
        }

        return $orderString;
    }


    protected function createWhereSearchID($searchType,$searchId){

    	switch ($searchType) {
    		case TitleSearchType::USER_CHARA_ID:
        	case TitleSearchType::USER_ACTOR_NAME:
    			return "%$searchId%";
    	}
    	return $searchId;
    }
    /**
     * WHERE句を作成する
     */
    protected function createWhereString($searchType)
    {
        switch ($searchType) {
        	case TitleSearchType::USER_CHARA_ID:
        		return "where player.iname like :search_id";
        	case TitleSearchType::USER_ACTOR_NAME:
        		return "where actor.name like :search_id";
            case SearchType::USER_USER_ID:
                return 'WHERE account.user_id = :search_id';
            case SearchType::USER_PUBLIC_ID;
                return 'WHERE friend.public_id = :search_id';
            case SearchType::USER_TAKE_OVER_ID;
                return 'WHERE account.take_over_id = :search_id';
            case SearchType::USER_NOAH_ID:
                return 'WHERE session.noah_id = :search_id';
            case SearchType::RECEIPT_NUMBER:
                return '
LEFT JOIN GAIA_PURCHASE_ANDROID_HISTORY android USING (user_id)
LEFT JOIN GAIA_PURCHASE_IOS_HISTORY     ios     USING (user_id)
WHERE android.order_id = :search_id
OR    ios.transaction_id = :search_id
';
            default:
                return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function selectByUserId($userId)
    {
        $sql =<<<'EOD'
SELECT
    account.user_id
  , friend.public_id
  , account.take_over_id
  , session.noah_id
FROM      GAIA_USER_ACCOUNT account
LEFT JOIN GAIA_USER_SESSION session          USING (user_id)
LEFT JOIN GAIA_USER_DATA_ABOUT_FRIEND friend USING (user_id)
WHERE user_id = ?
EOD;
        return $this->sql->fetchAssoc($sql, [$userId]);
    }

    /**
     * {@inheritdoc}
     */
    public function selectFriendByUserId($limit,$offset,$uid,$sortColmn,$sortOrder)
    {
    $sql =<<<'EOD'
SELECT
    gaia_friend.friend_user_id
FROM
    GAIA_USER_ACCOUNT gaia_account
INNER JOIN
    GAIA_USER_FRIEND gaia_friend
ON
    gaia_account.user_id = gaia_friend.user_id
WHERE
    gaia_account.user_id = ?
ORDER BY
    %ORDER%
LIMIT
    %LIMIT%
OFFSET
    %OFFSET%
EOD;
        $orderString = $this->createOrderStringForFriend($sortOrder);
        $sql = str_replace('%OFFSET%', intval($offset), $sql);
        $sql = str_replace('%LIMIT%', intval($limit), $sql);
        $sql = str_replace('%ORDER%', $orderString, $sql);

        return $this->sql->fetchAll($sql, [$uid]);
    }
    
    /**
     * フレンド一覧表示時のORDER句を生成する。
     */
    protected function createOrderStringForFriend($sortOrder)
    {
        $orderString = "gaia_friend.friend_user_id";

        if ($sortOrder == Sort::ORDER_ASC) {
            $orderString.= " ASC";
        } else if ($sortOrder == Sort::ORDER_DESC) {
            $orderString.= " DESC";
        }

        return $orderString;
    }
    
    /**
     * {@inheritdoc}
     */
    public function countFriendByUserIdAllData($uid)
    {
    $sql =<<<'EOD'
SELECT
    count(gaia_friend.user_id) as count
FROM
    GAIA_USER_ACCOUNT gaia_account
INNER JOIN
    GAIA_USER_FRIEND gaia_friend
ON
    gaia_account.user_id = gaia_friend.user_id
WHERE
    gaia_account.user_id = ?
EOD;
        $tmp = $this->sql->fetchAll($sql, [$uid]);
        return $tmp[0]['count'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->sql->fetchColumn('SELECT COUNT(*) FROM GAIA_USER_ACCOUNT');
    }
}