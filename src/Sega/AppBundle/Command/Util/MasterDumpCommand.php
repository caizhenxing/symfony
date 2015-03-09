<?php

namespace Sega\AppBundle\Command\Util;

use Dcs\DcsBaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Query;
use Gaia\Bundle\HandlerSocketBundle\Parameter\Table;


/**
 * Symfonyコマンド実行のサンプル
 * [参考] http://docs.symfony.gr.jp/symfony2/cookbook/console.html
 * 
 * 実行方法
 * [vagrant@localhost symfony]$ pwd
 * /home/vagrant/public/symfony
 * [vagrant@localhost symfony]$ php app/console sega:sample:test --user_id=1
 * 
 */
class MasterDumpCommand extends DcsBaseCommand {

	function configure() {
		$this
			->setName('arpg:master_dump')
			->setDescription('マスターデータをダンプする')
			->addOption("dir",null,InputOption::VALUE_OPTIONAL,"保存ディレクトリ","./data/")
			;
	}
	
	function main(InputInterface $input, OutputInterface $output) {
		$dir = $input->getOption("dir");
		if(!is_dir($dir)){
			$output->writeln("<error>Error : ディレクトリ $dir は存在しません.</error>");
			return;
		}
		
		$dir = realpath($dir)."/";
		$path = $dir."master_dump.sql";
		
		$con = $this->get("doctrine")->getConnection();
		$user = $con->getUsername();
		$pass = $con->getPassword();
		$host = $con->getHost();
		$port = $con->getPort();
		$db = $con->getDatabase();
		if($port == null || strlen($port) < 1)
			$port = 3306;
		$tbls = [
			'DCS_IP_WHITE_LIST',
			'DCS_MST_CODE_INPUT_LIST',
			'DCS_MST_INVITE_CAMPAIGN',
			'DCS_MST_SERVER_CONNECTOR',
			'GAIA_IP_ADDRESS_WHITE_LIST',
			'GAIA_MAINTENANCE_INFORMATION',
			'GAIA_MNT_ALL_USER_ITEM_FILL_BATCH',
			'GAIA_MNT_ALL_USER_VIRTUAL_MONEY_COUNT_BATCH',
			'GAIA_MNT_MST_ADMIN_PRIVILEGE',
			'GAIA_MNT_MST_ADMIN_ROLE',
			'GAIA_MNT_MST_ADMIN_ROLE_PRIVILEGE',
			'GAIA_MNT_USER_ADMIN',
			'GAIA_MNT_USER_ADMIN_LOGIN_STATUS',
			'GAIA_MNT_USER_ADMIN_ROLE',
			'GAIA_MST_ASSET_TYPE',
			'GAIA_MST_ATOM_CAMPAIGN',
			'GAIA_MST_ATOM_CAMPAIGN_PRESENT',
			'GAIA_MST_ATOM_CAMPAIGN_TYPE',
			'GAIA_MST_BBS_BLACK_LIST_ACTION_TYPE',
			'GAIA_MST_FRIEND_ACTION_TYPE',
			'GAIA_MST_FRIEND_OFFER_ACTION_TYPE',
			'GAIA_MST_GACHA',
			'GAIA_MST_GACHA_CARD',
			'GAIA_MST_GACHA_DISPLAY_RATE',
			'GAIA_MST_GACHA_GROUP',
			'GAIA_MST_GACHA_STOPPER_GROUP',
			'GAIA_MST_INVITE_CAMPAIGN',
			'GAIA_MST_INVITE_CAMPAIGN_PRESENT',
			'GAIA_MST_MAINTENANCE_PERIODIC_SCHEDULE',
			'GAIA_MST_NOAH_OFFER_PRESENT',
			'GAIA_MST_OS_TYPE',
			'GAIA_MST_PURCHASE_AMOUNT_LIMIT_BY_AGE',
			'GAIA_MST_PURCHASE_AMOUNT_RESET_TIMING',
			'GAIA_MST_PURCHASE_DISCOUNT',
			'GAIA_MST_PURCHASE_ITEM_DATA',
			'GAIA_USER_WHITE_LIST',
			'actor_create_model',
			'actor_init',
			'actor_model',
			'actor_status',
			'assetbundle',
			'chat_auto_word',
			'chat_preset_message',
			'dev_param',
			'dungeon_config',
			'effect_data',
			'effect_type',
			'enemy',
			'enemy_attack',
			'enemy_data',
			'enemy_place',
			'equip_addon',
			'equip_addon_switch',
			'equip_data',
			'equip_grow_func',
			'equip_grow_type',
			'equip_material',
			'equip_model',
			'factory_product_list',
			'factory_train',
			'factory_upgrade',
			'gacha_data',
			'gacha_info',
			'game_info',
			'game_param',
			'game_param_type',
			'home_banner',
			'home_public',
			'item_boost',
			'item_data',
			'item_drop',
			'item_drop_event',
			'item_drop_rate',
			'item_trade',
			'item_trade_type',
			'lang_act_tutorial_jp',
			'lang_adv_jp',
			'lang_adv_view_jp',
			'lang_cw_jp',
			'lang_err_jp',
			'lang_find_str',
			'lang_ng_jp',
			'lang_seq_tutorial_jp',
			'lang_text_jp',
			'loading_tips',
			'log_management_tool',
			'login_bonus',
			'magic',
			'mail_all',
			'master_table',
			'mission',
			'mission_icon',
			'navi_chara',
			'navi_chara_message',
			'player_anim_common',
			'player_attack',
			'player_combo',
			'player_init',
			'player_lv',
			'player_status',
			'quest_area',
			'quest_bonus',
			'quest_clear_rank',
			'quest_cross_link',
			'quest_dungeon',
			'quest_tower',
			'quest_world',
			'rarity',
			'rarity_tbox',
			'skill_attack',
			'sound_se_action',
			'stage_config',
			'stage_model',
			'tbox',
			'testtbl',
		];
		$output->writeln("$path にログを出力中...");
		$output->writeln("mysqldump --add-drop-table --user=$user --password=$pass --host=$host --port=$port --default-character-set=utf8 --database $db --tables ".implode(' ',$tbls)." > $path");
		exec("mysqldump --add-drop-table --user=$user --password=$pass --host=$host --port=$port --default-character-set=utf8 --database $db --tables ".implode(' ',$tbls)." > $path");
		if(!is_file($path)){
			$output->writeln("<error>Error : $path を開けません</error>");
			return;
		}
	}
	
}