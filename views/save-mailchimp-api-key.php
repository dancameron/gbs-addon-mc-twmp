<?php
	include ('../wp-config.php');
	$api_key = $_POST['api_key'];
	if (!$api_key) {
		echo '2';	// API Key Already Present
		exit;
	}

	update_option('gbs_mailchimp_api_key', $api_key);
	update_option('gbs_mailchimp_list_id', '');
	update_option('gbs_mailchimp_top_purchase_group_add_id', '');
	update_option('gbs_mailchimp_top_purchase_group_add_bit', '');
	update_option('gbs_mailchimp_top_purchase_group_remove_id', '');
	update_option('gbs_mailchimp_top_purchase_group_remove_bit', '');
	update_option('gbs_mailchimp_non_renewed_group_id', '');
	update_option('gbs_mailchimp_non_renewed_group_bit', '');
	update_option('gbs_mailchimp_scheduled_template', '');
	update_option('gbs_mailchimp_scheduled_delay', '');
	update_option('gbs_mailchimp_scheduled_template_subject', '');
	echo '1';		// API Key Saved
	exit;
