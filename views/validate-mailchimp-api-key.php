<?php
	$path = split('wp-content', __FILE__);
	$path = $path[0];
	if ( !file_exists( $path . '/wp-load.php' ) ) {
		if ( file_exists( $path . '/wordpress/wp-load.php' ) ) {
			require_once( $path . '/wordpress/wp-load.php' );
		}
		elseif ( file_exists( $path . '/wp/wp-load.php' ) ) {
			require_once( $path . '/wp/wp-load.php' );
		}
	}
	else {
		require_once( $path . '/wp-load.php' );
	}
	$api_key = $_POST['api_key'];
	if ($api_key == get_option('gbs_mailchimp_api_key')) {
		echo '2';	// API Key Already Present
		exit;
	}
	include_once ('../lib/MCAPI.class.php');
	$api = new MCAPI($api_key);
	$retval = $api->ping($api_key);
	if ($retval != "Everything's Chimpy!") {
		echo '0';		// Invalid API Key
		exit;
	}

	echo '1';		// API Key Saved
	exit;

