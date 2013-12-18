<?php
/*
Plugin Name: Group Buying Addon - TWMP MailChimp Modification
Version: 1
Plugin URI: http://groupbuyingsite.com/marketplace
Description: Unknown
Author: Sprout Venture
Author URI: http://sproutventure.com/wordpress
Plugin Author: Dan Cameron
Text Domain: group-buying
*/


define( 'GB_TWMP_MC_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) . '/' );
define ('GB_TWMP_MC_URL', plugins_url( '', __FILE__) );

// Load after all other plugins since we need to be compatible with groupbuyingsite
add_action( 'plugins_loaded', 'gb_twmp_mc_mods' );
function gb_twmp_mc_mods() {
	require_once 'classes/MC_Addon.php';
	// Hook this plugin into the GBS add-ons controller
	add_filter( 'gb_addons', array( 'MC_Addon', 'gb_addon' ), 10, 1 );
}