<?php

namespace Title\GaiaBundle\InstallBundle\Model;

use Exception;
use Gaia\Bundle\DatabaseBundle\User\UserAccountDaoInterface;
use Title\GaiaBundle\InstallBundle\Exception\ErrorMessages;

/**
 * データベースチェックモデルクラス
 *
 * @copyright Copyright (c) 2013 SEGA Networks Co., Ltd. All rights reserved.
 * @author Itec Hokkaido
 */
class DatabaseCheckModel implements DatabaseCheckModelInterface
{
    private $userAccountDao;

    /**
     * コンストラクタ
     * @param UserAccountDaoInterface $userAccountDao
     */
    public function __construct(
        UserAccountDaoInterface $userAccountDao
    )
    {
        $this->userAccountDao               = $userAccountDao;
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckedInfo()
    {
        $errors = null;

        // GAIA_USER_ACCOUNT
        try {
            $this->userAccountDao->selectByUserId(1);
        } catch (Exception $e) {
            $errors[] = ErrorMessages::get('DBACCESS', 'NOTFOUND', ['GAIA_USER_ACCOUNT']);
        }

        return $errors;
    }
}
