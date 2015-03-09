<?php

namespace Title\GaiaBundle\InstallBundle\Model;

/**
 * データベースチェックモデルインターフェース
 *
 * @copyright Copyright (c) 2013 SEGA Networks Co., Ltd. All rights reserved.
 * @author Itec Hokkaido
 */
interface DatabaseCheckModelInterface
{

    /**
     * データベースチェック情報を取得
     *
     * @return array チェックが OK の場合は空配列、NGの場合はエラーテーブル名の配列
     */
    function getCheckedInfo();
}
