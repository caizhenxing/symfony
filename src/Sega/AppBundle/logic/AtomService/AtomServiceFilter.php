<?php

namespace Logic\AtomService;

use Dcs\AtomServiceInterface;
use Dcs\DcsController;
use Dcs\AtomInviteCampaignService;
use Logic\Util\Mail as Mail;

class AtomServiceFilter implements AtomServiceInterface {
	
	/**
	 * Atom用として有効なURLスキーム名かどうか確認する
	 * 
	 * @param string $input
	 *        	URLスキーム
	 * @return bool
	 */
	public function IsValidUrlScheme($input) {
		$valid_scheme = 'fortisia.atom';
		if ($input == $valid_scheme)
			return true;
		else
			return false;
	}

	/**
	 * @inheritdoc
	 */
	public function IsAuthCampaign(DcsController $ctrl, $user_id, $campaign_code , $serial) {
		$con = $ctrl->get('doctrine')->getConnection();
	
		//事前登録で受け取った人は記録して、iPhone用とAndroid用で２重化しないようにする
	
		//アプリ固有の処理(すべて事前登録用のキャンペーンコード)
		$campaign_codes = array('fortisia_pre' , 'fortisia_pre_a');
		if( in_array( $campaign_code , $campaign_codes) ) {
			$campaign_mst = $ctrl->get('gaia.dao.campaign.mst_atom_campaign');
			$serv = $ctrl->get('gaia.dao.campaign.atom_campaign_used_history');
			foreach($campaign_codes as $campaign_code_el) {
				error_log($campaign_code_el);
				$mst_data = $campaign_mst->selectAssetFromCode($campaign_code_el);
				if($serv->campaignAlreadyUsed( $user_id , $mst_data['atom_campaign_id']) ) {
					error_log($campaign_code.' is alreadyPresent');
					return $ctrl::RESULT_ALREADY_PRESENT_RECIVED;
				}
			}
		}
		return 0;
	}
	
	/**
	 * @inheritdoc
	 */
	public function SendReward(DcsController $ctrl, $user_id, $auth_result) {
		$con = $ctrl->get('doctrine')->getConnection();
		$succeed = false;
		
		// present送付処理
		$con->beginTransaction ();
		$Mail = $ctrl->get ( 'Arpg.Logic.Util.Mail' );
		$Text = $ctrl->get ( 'Arpg.Logic.Util.Text' );
		try {
			$sender = [];
			foreach ( $auth_result ['asset'] as $present ) {
				$sender[] = [ 
						'type' => Mail::TYPE_REWARD,
						'from' => $Text->getText ( 10400 ) ,
						'to' => [$user_id],
						'subject' => $auth_result['campaign_name'],
						'message' => $auth_result['acceptance_message'],
						'reward' => $present['asset_id'],
						'reward_num' => $present['asset_count'],
				];
			}
			$Mail->send($sender);
			$con->commit ();
			$succeed = true;
		} catch ( \Exception $e ) {
			// ATOM認証は成功したがプレゼント送付で失敗
			$con->rollBack ();
			error_log ( $e->getMessage () );
		}
		
		return array (
				'succeed' => $succeed,
				'data' => null 
		);
	}
	
	/**
	 * @inheritdoc
	 */
	public function CheckAndSendRewardForInviteUser(DcsController $ctrl, $uid, $isJustInputed = false) {
		$invite_campaign_service = $ctrl->get ( 'Dcs.AtomInviteCampaignService' );
		$invite_result = $invite_campaign_service->checkRewardForInviteUser ( $uid );
		if ($invite_result != null && isset ( $invite_result ['presents'] )) {
			// 報酬付与処理
			$con = $ctrl->get('doctrine')->getConnection();
			$Mail = $ctrl->get ( 'Arpg.Logic.Util.Mail' );
			$Text = $ctrl->get ( 'Arpg.Logic.Util.Text' );

			$Astatus = $ctrl->get('Arpg.Logic.Util.ActorStatus');
			$aid = $Astatus->getActorId($uid);
			$name = $Astatus->getName($aid);
			$step = $ctrl->get('Arpg.Logic.Util.DevParam')->param(90);
			$astep = $ctrl->get('Arpg.Logic.Util.PlayerStatus')->getStatus($uid,self::std_tutorial_step);
			if($astep < $step){
				// 受取状態まで行ってない
				return;
			}
			
			$con->beginTransaction ();
			try {
				$sender = [];
				foreach ( $invite_result ['presents'] as $present ) {
					$sender[] = [ 
							'type' => Mail::TYPE_INVITE,
							'from' => $Text->getText ( 10400 ) ,
							'to' => [$invite_result ['invited_from_user_id']],
							'subject' => $Text->getText(10521),
							'message' => $Text->getText(10520,['[name]'=>$name]),
							'reward' => $present['asset_id'],
							'reward_num' => $present['asset_count'],
					];
				}
				$Mail->send($sender);
				$con->commit ();
				$succeed = true;
			} catch ( \Exception $e ) {
				// ATOM認証は成功したがプレゼント送付で失敗
				$con->rollBack ();
				error_log ( $e->getMessage () );
			}
		}
	}
	const std_tutorial_step = 200;
}
?>