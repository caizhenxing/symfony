<?php
namespace Dcs;

class config{
	/**
	 * ユーザー作成時に必要なパラメータ
	 * @var integer
	 */
	const NewUserInput=config::NEW_USER_INPUT_NONE;
	const NEW_USER_INPUT_ALL=0;		///< UIDとパスワードを入力
	const NEW_USER_INPUT_UID=1;		///< UIDのみ入力
	const NEW_USER_INPUT_AUTO=2;	///< UIDとPASS自動生成
	const NEW_USER_INPUT_NONE=3;	///< UIDを自動生成　PASS不使用
	
	/**
	 * 一時使用IDの長さ
	 * @var integer
	 */
	const OnceIdLength=16;
	/**
	 *  オートUIDの長さ
	 */
	const AutoUidLength=16;
	/**
	 *  オートパスワードの長さ
	 */
	const AutoPassLength=16;
	
	/**
	 * 送信エラー用キャッシュ生存時間(sec)
	 */
	const ResponsCacheLimit=600;
	
	/**
	 * スローリクエストログ出力時間
	 */
	const SlowRequestTime=0;
	
	/**
	 * スローリクエスト詳細出力フラグ
	 */
	const SlowRequestDetail=true;
	
	/**
	 * リクエスト集計フラグ
	 */
	const RequestAggregate=false;
	
	/**
	 * Jmeterモード
	 */
	const JmeterMode=true;
}
?>