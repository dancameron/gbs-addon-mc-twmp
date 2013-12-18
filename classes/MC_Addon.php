<?php

/**
 * Load via GBS Add-On API
 */
class MC_Addon extends Group_Buying_Controller {
	
	public static function init() {

		require_once('groupBuyingMailchimpModel.class.php');
		require_once('groupBuyingMailchimps.class.php');
		require_once('groupBuyingMailchimpDealFeeds.class.php');
		require_once('mailchimp-settings.php');

		Group_Buying_Mailchimp_Model::init();
		Group_Buying_Mailchimps::init();
		Group_Buying_Mailchimp_Deal_Feeds::init();

		add_action( 'init', array( 'Group_Buying_Mailchimp_Settings', 'init' ) );
	}

	public static function gb_addon( $addons ) {
		$addons['sms_notes_notifier'] = array(
			'label' => self::__( 'TWMP MailChimp Modifications' ),
			'description' => self::__( 'Feed and more.' ),
			'files' => array(),
			'callbacks' => array(
				array( __CLASS__, 'init' ),
			),
			'active' => TRUE,
		);
		return $addons;
	}

}