<?php

/**
 * Load via GBS Add-On API
 */
class MC_Addon extends Group_Buying_Controller {
	
	public static function init() {

		require_once('GB_MailChimp_Utility.php');
		require_once('groupBuyingMailchimps.class.php');
		require_once('GB_MailChimp_Deal_Feeds.php');
		require_once('GB_MailChimp_Settings.php');

		Group_Buying_Mailchimps::init();
		GB_MailChimp_Deal_Feeds::init();
		GB_MailChimp_Settings::init();
		add_action( 'admin_head', array( __CLASS__, 'url_path_for_js' ) );
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

	public function url_path_for_js() {
		?>
		<script type="text/javascript">
			var $tw_mc_mods_url = '<?php echo GB_TWMP_MC_URL ?>/';
			var $tw_mc_mods_views_url = '<?php echo GB_TWMP_MC_URL ?>/views/';
		</script>
		<?php
	}

}