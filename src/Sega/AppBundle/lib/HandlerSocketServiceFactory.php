<?php

namespace Dcs;

use Gaia\Bundle\HandlerSocketBundle\HandlerSocket\HandlerSocket;

/**
 * ハンドラソケットサービス ファクトリ
 *
 * @copyright Copyright (c) 2013 SEGA Networks Co., Ltd. All rights reserved.
 * @author Itec Hokkaido
 */
class HandlerSocketServiceFactory
{
    /**
     * ハンドラソケットサービス ファクトリメソッド
     *
     * @param array $param 設定値
     *
     * @return HandlerSocketService ハンドラソケットサービス
     */
    public static function create(array $param, $master = false)
    {
        $readSocket = self::createReadSocketOrReadSockets($param,$master);
        $writeSocket = self::createWriteSocket($param);

        return new HandlerSocketService($readSocket, $writeSocket);
    }

    private static function createReadSocketOrReadSockets($param,$master)
    {
        if (isset($param['slaves']) === false) {
            return self::createReadSocket($param);
        }

        $sockets = array();
        foreach ($param['slaves'] as $slave) {

            if (isset($slave['dbname']) === false) {
                $slave['dbname'] = $param['dbname'];
            }
            if($master){
            	$slave['host'] = $param['host'];
            }

            $sockets[] = self::createReadSocket($slave);
        }

        return count($sockets) > 1 ? $sockets : $sockets[0];
    }

    private static function createReadSocket($param)
    {
        $host = $param['host'];
        $dbname = $param['dbname'];
        $port = $param['port'];
        $password = isset($param['password']) ? $param['password'] : null;
        $timeout = isset($param['timeout']) ? $param['timeout'] : null;

        return new HandlerSocket($host, $port, $dbname, $password, $timeout);
    }

    private static function createWriteSocket($param)
    {
        $host = $param['host'];
        $dbname = $param['dbname'];
        $port = $param['port_wr'];
        $password = isset($param['password_wr']) ? $param['password_wr'] : null;
        $timeout = isset($param['timeout']) ? $param['timeout'] : null;

        return new HandlerSocket($host, $port, $dbname, $password, $timeout);
    }
}