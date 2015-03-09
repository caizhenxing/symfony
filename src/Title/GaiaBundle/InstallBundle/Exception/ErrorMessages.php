<?php

namespace Title\GaiaBundle\InstallBundle\Exception;

/**
 * エラーメッセージ定義クラス
 *
 * @copyright Copyright (c) 2013 SEGA Networks Co., Ltd. All rights reserved.
 * @author Itec Hokkaido
 */
final class ErrorMessages
{
    /** @var array エラーメッセージ */
    protected static $messages;

    /**
     * エラーメッセージ設定、ロード時以外使用不可
     *
     * @param array $messages エラーメッセージ
     */
    public static function setMessages(array $messages)
    {
        self::$messages = $messages;
    }

    /**
     * エラーメッセージ取得
     *
     * @param $key1 キー1
     * @param $key2 キー2
     * @param array $args 置換パラメータ
     *
     * @return string エラーメッセージ
     */
    public static function get($key1, $key2, array $args = null)
    {
        $message = self::$messages[$key1][$key2];
        if (!empty($args)) {
            return \MessageFormatter::formatMessage('ja_JP', $message, $args);
        }
        return $message;
    }
}