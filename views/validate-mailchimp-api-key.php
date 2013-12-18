<?php
	include ('../wp-config.php');
	$api_key = $_POST['api_key'];
	if ($api_key == get_option('gbs_mailchimp_api_key')) {
		echo '2';	// API Key Already Present
		exit;
	}
	include_once ('MCAPI.class.php');
	$api = new MCAPI($api_key);
	$retval = $api->ping($api_key);
	if ($retval != "Everything's Chimpy!") {
		echo '0';		// Invalid API Key
		exit;
	}

	echo '1';		// API Key Saved
	exit;

