<?php

class GB_MailChimp_Utility {

	public function validate_mailchimp_api_key( $api_key ) {
		include_once(GB_TWMP_MC_PATH . '/lib/MCAPI.class.php');
		$api = new MCAPI($api_key);
		$retval = $api->ping($api_key);
		if ($retval != "Everything's Chimpy!") {
			return FALSE;
		}
		return $api;
	}

	public function insert_tables() {
		$option_key = 'db_updated_version_2';
		$updated = get_option( $option_key  );
		if ( !$updated ) {
			global $wpdb;

			if ( !empty ( $wpdb->charset ) )
				$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";

			$sql = "CREATE TABLE IF NOT EXISTS `wp_gbs_non_renewed_users` (
					  `email` varchar(255) NOT NULL,
					  `purchase_date` datetime NOT NULL,
					  UNIQUE KEY `email` (`email`)
					) ENGINE = InnoDB {$charset_collate};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			$sql = "CREATE TABLE IF NOT EXISTS `wp_gbs_cron_last_timings` (
					  `cron_type` varchar(255) NOT NULL,
					  `cron_time` datetime NOT NULL
					) ENGINE = MYISAM {$charset_collate};

					INSERT INTO `wp_gbs_cron_last_timings` (`cron_type`, `cron_time`) VALUES
					('top_purchase_scheduled_emails', '2013-05-01 00:00:00'),
					('non_renewed_trigger', '2013-05-01 00:00:00');";
			dbDelta( $sql );
			update_option( $option_key, time() );
		}
	}

	public static function get_user( $id ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->users} WHERE ID=%d";
		$query = $wpdb->prepare( $sql, $id );
		return (array)$wpdb->get_row( $query );
	}

	public static function get_user_by_email( $email ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->users} WHERE user_email=%d";
		$query = $wpdb->prepare( $sql, $email );
		return (array)$wpdb->get_row( $query );
	}

	public static function check_non_renewed_user( $email ) {
		global $wpdb;
		$sql = "SELECT * FROM wp_gbs_non_renewed_users WHERE email=%s";
		$query = $wpdb->prepare( $sql, $email );
		return (array)$wpdb->get_row( $query );
	}

	public static function update_non_renewed_user( $email, $purchase_date ) {
		global $wpdb;
		$data = array('purchase_date' => $purchase_date);
		$where = array('email' => $email);
		$wpdb->update( 'wp_gbs_non_renewed_users', $data, $where );
		return TRUE;
	}

	public static function add_non_renewed_user( $email, $purchase_date ) {
		global $wpdb;
		$data = array('email' => $email, 'purchase_date' => $purchase_date);
		$wpdb->insert( 'wp_gbs_non_renewed_users', $data );
		return TRUE;
	}

	public static function get_non_renewed_users_before_purchase_date( $last_purchase_date ) {
		global $wpdb;
		$sql = "SELECT * FROM wp_gbs_non_renewed_users WHERE purchase_date<%s";
		$query = $wpdb->prepare( $sql, $last_purchase_date, $last_purchase_date );
		return (array)$wpdb->get_results( $query );
	}

	public static function get_mailchimp_groups( $api, $list_id ) {
		// Fetch MailChimp Groups for a List
		$mailchimp_groups = array();
		$groups = $api->listInterestGroupings($list_id);
		if (isset($groups)) {
			foreach ($groups as $k=>$v) {
				$mailchimp_groups[] = $v;
			}
		}

		$groups = array();

		// Fetch Top & Non Renewed Groups ID and bit
		$groups['top_purchase_group_add']['id'] = get_option('gbs_mailchimp_top_purchase_group_add_id');
		$groups['top_purchase_group_add']['bit'] = get_option('gbs_mailchimp_top_purchase_group_add_bit');
		$groups['top_purchase_group_remove']['id'] = get_option('gbs_mailchimp_top_purchase_group_remove_id');
		$groups['top_purchase_group_remove']['bit'] = get_option('gbs_mailchimp_top_purchase_group_remove_bit');
		$groups['non_renewed_group']['id'] = get_option('gbs_mailchimp_non_renewed_group_id');
		$groups['non_renewed_group']['bit'] = get_option('gbs_mailchimp_non_renewed_group_bit');

		foreach ($mailchimp_groups as $key=>$value) {
			if ($value['id'] == $groups['top_purchase_group_add']['id']) {
				foreach ($value['groups'] as $k=>$v) {
					if ($v['bit'] == $groups['top_purchase_group_add']['bit']) {
						$groups['top_purchase_group_add']['name'] = $v['name'];
					}
				}
			}
			if ($value['id'] == $groups['top_purchase_group_remove']['id']) {
				foreach ($value['groups'] as $k=>$v) {
					if ($v['bit'] == $groups['top_purchase_group_remove']['bit']) {
						$groups['top_purchase_group_remove']['name'] = $v['name'];
					}
				}
			}
			if ($value['id'] == $groups['non_renewed_group']['id']) {
				foreach ($value['groups'] as $k=>$v) {
					if ($v['bit'] == $groups['non_renewed_group']['bit']) {
						$groups['non_renewed_group']['name'] = $v['name'];
					}
				}
			}
		}
		if (!isset($groups['top_purchase_group_add']['name']))
			unset($groups['top_purchase_group_add']);
		if (!isset($groups['top_purchase_group_remove']['name']))
			unset($groups['top_purchase_group_remove']);
		if (!isset($groups['non_renewed_group']['name']))
			unset($groups['non_renewed_group']);
		return $groups;
	}

	public static function get_deal_purchases( $deal_id ) {
		global $wpdb;
		$sql = "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_number_of_purchases'";
		$query = $wpdb->prepare( $sql, $deal_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['meta_value'] : NULL;
	}

	public static function get_deal_purchase_records( $deal_id, $post_date ) {
		global $wpdb;
		$sql = "SELECT * FROM wp_postmeta WHERE meta_value=%d AND meta_key='_deal_id' AND post_id IN (SELECT ID from wp_posts WHERE post_date > %s)";
		$query = $wpdb->prepare( $sql, $deal_id, $post_date );
		return (array)$wpdb->get_results( $query );
	}

	public static function check_top_product_purchase( $deal_id ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_deal_id'";
		$query = $wpdb->prepare( $sql, $deal_id );
		return (array)$wpdb->get_row( $query );
	}

	public static function get_top_product_purchase_user( $deal_id ) {
		global $wpdb;
		$sql = "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_user_id'";
		$query = $wpdb->prepare( $sql, $deal_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['meta_value'] : NULL;
	}

	public static function get_deal_purchase_users( $deal_id ) {
		global $wpdb;
		$users = array();
		$sql = "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_number_of_purchases'";
		$query = $wpdb->prepare( $sql, $deal_id );
		$purchases = (array)$wpdb->get_row( $query );
		if (!isset($purchases) || !count($purchases) || !$purchases['meta_value']) {
			return $users;
		}
		$purchase_records = GB_MailChimp_Utility::get_deal_purchase_records( $deal_id );
		if ($purchase_records) {
			$users = array();
			foreach ($purchase_records as $key=>$value) {
				if (GB_MailChimp_Utility::check_top_product_purchase( $value->post_id )) {
					$user_id = GB_MailChimp_Utility::get_top_product_purchase_user( $value->post_id );
					if (isset($user_id) && $user_id['meta_value']) {
						$users []= GB_MailChimp_Utility::get_user($user_id['meta_value']);
					}
				}
			}
		}
		return $users;
	}

	public static function get_last_cron_time( $cron_type ) {
		global $wpdb;
		$sql = "SELECT cron_time FROM wp_gbs_cron_last_timings WHERE cron_type=%s";
		$query = $wpdb->prepare( $sql, $cron_type );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['cron_time'] : NULL;
	}

	public static function update_last_cron_time( $cron_type, $cron_time ) {
		global $wpdb;
		$data = array('cron_time' => $cron_time);
		$where = array('cron_type' => $cron_type);
		$wpdb->update( 'wp_gbs_cron_last_timings', $data, $where );
		return TRUE;
	}

	public static function get_purchases_for_top_deal_from_time( $deal_ids, $purchase_date ) {
		global $wpdb;
		$sql = "SELECT post_id, wp_posts.post_date FROM wp_postmeta, wp_posts WHERE wp_postmeta.post_id=wp_posts.ID AND meta_key='_deal_id' AND wp_posts.post_date > %s AND wp_posts.post_type = 'gb_purchase' AND meta_value IN ( " . implode(',', $deal_ids) . ")";
		$query = $wpdb->prepare( $sql, $purchase_date );
		print_r ($query);
		return (array)$wpdb->get_results( $query );
	}

	public static function get_purchases_for_top_deal_between_time( $deal_id, $purchase_start_date, $purchase_end_date ) {
		global $wpdb;
		$sql = "SELECT post_id, wp_posts.post_date FROM wp_postmeta, wp_posts WHERE wp_postmeta.post_id=wp_posts.ID AND meta_key='_deal_id' AND wp_posts.post_date BETWEEN %s AND %s AND wp_posts.post_type = 'gb_purchase' AND meta_value = %d";
		$query = $wpdb->prepare( $sql, $purchase_start_date, $purchase_end_date , $deal_id );
		return (array)$wpdb->get_results( $query );
	}

	public static function get_purchases_for_top_deal_before_time( $deal_id, $purchase_start_date ) {
		global $wpdb;
		$sql = "SELECT post_id, wp_posts.post_date FROM wp_postmeta, wp_posts WHERE wp_postmeta.post_id=wp_posts.ID AND meta_key='_deal_id' AND wp_posts.post_date <= %s AND wp_posts.post_type = 'gb_purchase' AND meta_value = %d";
		$query = $wpdb->prepare( $sql, $purchase_start_date, $deal_id );
		return (array)$wpdb->get_results( $query );
	}

	public static function get_purchases_for_top_deal_on_date( $deal_ids, $purchase_date ) {
		global $wpdb;
		$sql = "SELECT post_id, wp_posts.post_date FROM wp_postmeta, wp_posts WHERE wp_postmeta.post_id=wp_posts.ID AND meta_key='_deal_id' AND Date(wp_posts.post_date) = %s AND wp_posts.post_type = 'gb_purchase' AND meta_value IN ( " . implode(',', $deal_ids) . ")";
		$query = $wpdb->prepare( $sql, $purchase_date );
		return (array)$wpdb->get_results( $query );
	}

	public static function get_deal_purchase_record( $post_id ) {
		global $wpdb;
		$sql = "SELECT meta_value FROM wp_postmeta WHERE post_id=%d AND meta_key='_user_id'";
		$query = $wpdb->prepare( $sql, $post_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['meta_value'] : NULL;
	}

	public static function get_deal_purchase_id( $deal_id ) {
		global $wpdb;
		$sql = "SELECT post_id FROM wp_postmeta WHERE meta_value=%d AND meta_key='_deal_id'";
		$query = $wpdb->prepare( $sql, $deal_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['post_id'] : NULL;
	}

	public static function get_deal_order_record( $purchase_id ) {
		global $wpdb;
		$sql = "SELECT post_id FROM wp_postmeta WHERE meta_value=%d AND meta_key='_purchase_id' AND post_id IN (SELECT ID from wp_posts WHERE post_type = 'gb_payment')";
		$query = $wpdb->prepare( $sql, $purchase_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['post_id'] : NULL;
	}

	public static function get_deal_order_status( $order_id ) {
		global $wpdb;
		$sql = "SELECT post_status FROM wp_posts WHERE ID = %d";
		$query = $wpdb->prepare( $sql, $order_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['post_status'] : NULL;
	}

	public static function get_deals(  ) {
		global $wpdb;
		$sql = "SELECT ID FROM wp_posts WHERE post_type='gb_deal' ORDER BY ID desc";
		$query = $wpdb->prepare( $sql );
		return (array)$wpdb->get_results( $query );
	}

	public static function get_deal_details( $deal_id ) {
		global $wpdb;
		$sql = "SELECT * FROM wp_posts WHERE ID=%d";
		$query = $wpdb->prepare( $sql, $deal_id );
		return (array)$wpdb->get_row( $query );
	}

	public static function get_deal_meta( $deal_id, $key ) {
		global $wpdb;
		$sql = "SELECT * FROM wp_postmeta WHERE post_id=%d AND meta_key=%s";
		$query = $wpdb->prepare( $sql, $deal_id, $key );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['meta_value'] : NULL;
	}

	public static function get_deal_categories( $deal_id ) {
		global $wpdb;
		$sql = "SELECT wp_terms.name, wp_terms.slug FROM wp_terms, wp_term_relationships WHERE object_id=%d AND wp_terms.term_id=wp_term_relationships.term_taxonomy_id";
		$query = $wpdb->prepare( $sql, $deal_id );
		return (array)$wpdb->get_row( $query );
	}

	public static function get_active_publish_deals( $timestamp ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->posts}, {$wpdb->postmeta} WHERE post_type='gb_deal' AND post_status='publish' AND {$wpdb->posts}.ID={$wpdb->postmeta}.post_id AND meta_key='_expiration_date' AND (meta_value>='%s' OR meta_value='-1') ORDER by post_date DESC";
		$query = $wpdb->prepare( $sql, $timestamp );
		return (array)$wpdb->get_results( $query );
	}

	public static function get_count_deal_purchase( $deal_id ) {
		global $wpdb;
		$sql = "SELECT count(*) as purchase_count FROM wp_postmeta WHERE meta_key='_deal_id' AND meta_value=%d";
		$query = $wpdb->prepare( $sql, $deal_id, $key );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['purchase_count'] : NULL;
	}
}