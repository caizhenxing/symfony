<?php

namespace Title\GaiaBundle\ManagementToolBundle\Controller;

use Gaia\Bundle\ManagementToolBundle\Exception\ErrorMessages;
use Title\GaiaBundle\ManagementToolBundle\Exception\TitleErrorMessages;

class SampleUserInfoController extends \Gaia\Bundle\ManagementToolBundle\Controller\UserInfoController
{

// SAMPLE GAIA の Controller を継承してタイトル側の拡張を行う場合のサンプル
//
//    protected function getData($userId)
//    {
//        $result =
//            $this->get('title.master.dao.user.user_data')->selectByUserId($userId);
//        $data['ユーザ ID'] = $result['user_id'];
//        $data['フレンド ID'] = $result['public_id'];
//        $data['公開名'] = $result['user_name'];
//        $data['主人公名'] = $result['hero_name'];
//        $data['Rank'] = $result['rank'];
//        $data['ゲーム開始日時'] = $result['created_time'];
//        $data['最終プレイ日時'] = $result['last_play_time'];
//        $data['出撃パーティ'] = '保留';
//        $data['引継ぎ ID'] = $result['take_over_id'];
//        $data['NoahID'] = $result['noah_id'];
//        return $data;
//    }

    protected function getData($userId)
    {
        $data = parent::getData($userId);
        $data['< タイトル独自データ >'] = '< 777 >';
        // タイトル側のエラーメッセージ取得方法サンプル
        $data['< タイトル独自エラーメッセージ >'] = TitleErrorMessages::get('COMMON', 'SAMPLE');
        // ガイア側のエラーメッセージ取得方法サンプル
        $data['< 管理ツール共通エラーメッセージ>'] = ErrorMessages::get('SESSION', 'INVALID_USER');

        return $data;
    }
}