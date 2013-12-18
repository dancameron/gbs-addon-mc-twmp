<?php

/**
 * Deal controller
 *
 * @package GBS
 * @subpackage Deal
 */
include_once(GB_TWMP_MC_PATH . '/lib/MCAPI.class.php');

class Group_Buying_Mailchimps extends Group_Buying_Controller {
	const CRON_HOOK_1 = 'gb_top_purchase_scheduled_emails_cron';
	const CRON_HOOK_2 = 'gb_non_renewed_trigger_cron';
	private static $api_key = '';
	private static $api = '';
	private static $file_name = '';

	public static function init() {
		self::$api_key = get_option('gbs_mailchimp_api_key');
		add_action( 'init', array( get_class(), 'schedule_cron' ), 10, 0 );
		add_action( 'purchase_completed', array( get_class(), 'gbs_top_purchase_hook' ), 10, 1 );
		add_action( 'voucher_activated', array( get_class(), 'gbs_top_purchase_voucher_hook' ), 10, 1 );
		add_action( self::CRON_HOOK_1, array( get_class(), 'gbs_top_purchase_scheduled_emails' ), 10, 0 );
		add_action( self::CRON_HOOK_2, array( get_class(), 'gbs_non_renewed_trigger' ), 10, 0 );
	}

	public static function schedule_cron() {
		if ( !wp_next_scheduled( self::CRON_HOOK_1 ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK_1 );
		}
		if ( !wp_next_scheduled( self::CRON_HOOK_2 ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK_2 );
		}
	}

	public static function clear_schedule() {
		wp_clear_scheduled_hook( self::CRON_HOOK_1 );
		wp_clear_scheduled_hook( self::CRON_HOOK_2 );
	}

	public function gbs_top_purchase_voucher_hook($voucher) {
		$voucher_id = $voucher->get_id();
		$purchase = $voucher->get_purchase();
		self::gbs_top_purchase_hook($purchase, TRUE);
	}

	public function gbs_write_to_file($string, $append = FILE_APPEND) {
		file_put_contents(self::$file_name, $string, $append);
		file_put_contents(self::$file_name, "\n", FILE_APPEND);
	}

	public function gbs_read_file() {
		$string = file_get_contents(self::$file_name);
		print_r (str_replace("\n", '<br>', $string));
	}

	public function gbs_top_purchase_hook($purchase, $is_voucher = FALSE) {
		self::$file_name = ABSPATH . WPINC . '/top_purchase_hook_'.date('dmY').'.txt';	//.'_'.date('His')
		// Set Current Time
		$current_time = date('Y-m-d H:i:s');
		self::gbs_write_to_file("Current Time: " . $current_time, 0);

		// Connect to MailChimp API
		$api = Group_Buying_MailChimp_Model::validate_mailchimp_api_key(self::$api_key);
		if (!$api) {
			self::gbs_write_to_file("API Key Error");
			return false;
		}

		// Fetch MailChimp List
		$list_id = get_option('gbs_mailchimp_list_id');
		if (!$list_id) {
			self::gbs_write_to_file("List ID not Found");
			return false;
		}

		// Fetch MailChimp Groups
		$groups = Group_Buying_MailChimp_Model::get_mailchimp_groups( $api, $list_id );
		if (!isset($groups['top_purchase_group_add'])) {
			self::gbs_write_to_file("Add Group not Found");
			return false;
		}

		// Fetch Top Product Deals
		$deal_ids = get_option('gbs_top_purchase_deal_id');
		if (!$deal_ids) {
			self::gbs_write_to_file("Deal IDs not Found");
			return false;
		}
		self::gbs_write_to_file("Deal IDs: " . implode(',' , $deal_ids));

		// Fetch Purchase IDs for Top Deals after Last Cron
		$purchase_id = $purchase->get_id();
		self::gbs_write_to_file("Purchase ID: " . $purchase_id);

		$products = $purchase->get_products();

		$found = FALSE;
		foreach ( $products as $product ) {
			if (in_array((int) $product['deal_id'], $deal_ids)) {
				$found = TRUE;
				break;
			}
		}


		if (!$found) {
			self::gbs_write_to_file("Top Product Purchase not Found");
			return false;
		}

		$order_id = Group_Buying_MailChimp_Model::get_deal_order_record( $purchase_id );
		self::gbs_write_to_file("Order ID: " . $order_id);

		$order_status = Group_Buying_MailChimp_Model::get_deal_order_status( $order_id );
		self::gbs_write_to_file("Order Status: " . $order_status);

		if (!$is_voucher && $order_status == 'pending')
			return false;

		$user_id = $purchase->get_user();
		self::gbs_write_to_file("User ID: " . $user_id);

		// Fetch User
		$user = Group_Buying_MailChimp_Model::get_user($user_id);
		self::gbs_write_to_file("User Email: " . $user['user_email']);

		// Check User
		if (isset($user)) {
			$merge_vars = array('FNAME' => $user['display_name'], 'LNAME' => '',
				'GROUPINGS' => array(
					array('id' => $groups['top_purchase_group_add']['id'], 'groups' => $groups['top_purchase_group_add']['name']),
				)
			);

			// Subscribe to List& Groups
			$result = $api->listSubscribe($list_id, $user['user_email'], $merge_vars, 'html', 'false', 'true');
			if (!$api->errorCode) {
				if (Group_Buying_MailChimp_Model::check_non_renewed_user( $user['user_email']))
					Group_Buying_MailChimp_Model::update_non_renewed_user( $user['user_email'], date('Y-m-d H:i:s') );
				else
					Group_Buying_MailChimp_Model::add_non_renewed_user( $user['user_email'], date('Y-m-d H:i:s') );
			} 
			self::gbs_write_to_file("Result: " . $result);
		}
		return true;
	}

	public static function gbs_top_purchase_scheduled_emails() {
		self::$file_name = ABSPATH . WPINC . '/top_purchase_scheduled_emails_'.date('dmY').'.txt';	//.'_'.date('His')
		// Set Current Time
		$current_time = date('Y-m-d H:i:s');
		self::gbs_write_to_file("Current Time: " . $current_time, 0);

		// Connect to MailChimp API
		$api = Group_Buying_MailChimp_Model::validate_mailchimp_api_key(self::$api_key);
		if (!$api) {
			self::gbs_write_to_file("API Key Error");
			return false;
		}

		// Fetch MailChimp List
		$list_id = get_option('gbs_mailchimp_list_id');
		if (!$list_id) {
			self::gbs_write_to_file("List ID not Found");
			return false;
		}

		// Fetch MailChimp Groups
		$groups = Group_Buying_MailChimp_Model::get_mailchimp_groups( $api, $list_id );
		if (!isset($groups['non_renewed_group'])) {
			self::gbs_write_to_file("Non-Renewed Group not Found");
			return false;
		}

		// Fetch Top Product Deals
		$deal_ids = get_option('gbs_top_purchase_deal_id');
		if (!$deal_ids) {
			self::gbs_write_to_file("Deal IDs not Found");
			return false;
		}
		self::gbs_write_to_file("Deal IDs: " . implode(',' , $deal_ids));

		// Fetch Last Cron Time
		$last_cron_time = Group_Buying_MailChimp_Model::get_last_cron_time( 'top_purchase_scheduled_emails' );
		self::gbs_write_to_file("Last Cron Time: " . $last_cron_time);

		// Get Scheduled Templates and Delays
		$scheduled_templates = get_option('gbs_mailchimp_scheduled_template');
		$scheduled_delays = get_option('gbs_mailchimp_scheduled_delay');
		$scheduled_template_subjects = get_option('gbs_mailchimp_scheduled_template_subject');

		// Check If Templates and Delays exist
		if (isset($scheduled_templates)) {
			foreach ($scheduled_templates as $key=>$value) {
				$users = array();
				$final_users = array();
				$segments = array();

				$delay = isset($scheduled_delays[$key]) ? $scheduled_delays[$key] : 0;
				self::gbs_write_to_file("Delay: " . $delay);
				// Calculate Purchase Date Start if there are more templates
				$product_purchase_date = date('Y-m-d', time() - ($delay * 24 * 60 * 60));
				self::gbs_write_to_file("Product Purchase Date: " . $product_purchase_date);

				// Fetch Purchase IDs for Top Deals after Last Cron
				$purchase_ids = Group_Buying_MailChimp_Model::get_purchases_for_top_deal_on_date( $deal_ids, $product_purchase_date );
				self::gbs_write_to_file("Purchases: " . count($purchase_ids));

				if (count($purchase_ids)) {
					// Fetch Users
					foreach ($purchase_ids as $k=>$v) {
						$user_id = Group_Buying_MailChimp_Model::get_deal_purchase_record( $v->post_id );
						if (!in_array($user_id, $users)) {
							$users [] = $user_id;
						}
					}
					self::gbs_write_to_file("Users: " . implode(',' , $users));

					foreach ($users as $k=>$v) {
						// Fetch User
						$user = Group_Buying_MailChimp_Model::get_user($v);
						// Check User
						if (isset($user)) {
							// Check Member exist in List Group
							$member = $api->listMemberInfo($list_id, array($user['user_email']) );

							if ($member['success']) {
								if (isset($member['data'][0]['merges']['GROUPINGS'])) {
									foreach ($member['data'][0]['merges']['GROUPINGS'] as $i=>$j) {
										if ($j['id'] == $groups['top_purchase_group_add']['id'] && is_numeric(strpos($j['groups'], $groups['top_purchase_group_add']['name']))) {
											$final_users []= $v;
										}
									}
								}
							}
						}
					}

					self::gbs_write_to_file("Final Users: " . implode(',' , $final_users));

					if (count($final_users)) {
						$user_emails = array();
						foreach ($final_users as $k=>$v) {
							$user = Group_Buying_MailChimp_Model::get_user($v);
							$user_emails[] = $user['user_email'];
						}
						self::gbs_write_to_file("User Emails: " . implode(',' , $user_emails));

						// Delete Yesterday's Static Segment
						foreach ($api->listStaticSegments($list_id) as $k=>$v) {
//							$yesterday_date = date('dmY', mktime(0, 0, 0, date('m'), date('d')-1, date('y')));
							if (is_numeric(strpos($v['name'], 'st'.($key+1))))	//$yesterday_date
								$api->listStaticSegmentDel($list_id, $v['id']);
						}

						// Create Static Segment
						$segment_id = $api->listStaticSegmentAdd($list_id, 'st'.($key+1).'_'.date('dmY'));
						self::gbs_write_to_file("Segment ID: " . $segment_id);

						if ($segment_id) {
							// Add User to Static Segment
							$api->listStaticSegmentMembersAdd($list_id, $segment_id, $user_emails);
							$conditions = array();
							$segments = array();
							$conditions[] = array('field' => 'static_segment', 'op' => 'eq', 'value' => $segment_id);
							$segments = array('match' => 'any', 'conditions' => $conditions);
							$segment_test = $api->campaignSegmentTest($list_id, $segments);
							self::gbs_write_to_file("Segment Test: " . $segment_test);

							if ($segment_test) {
								// create campaign
								$type = 'regular';
								$opts = array();
								$opts['list_id'] = $list_id;
								$opts['from_email'] = get_option('gbs_mailchimp_from_email');
								$opts['from_name'] = get_option('gbs_mailchimp_from_name');
								$opts['subject'] = $scheduled_template_subjects[$key] ? $scheduled_template_subjects[$key] : 'Hi';
								$opts['template_id'] = $value;
								$content = array();
								$content['html'] = '&nbsp';
								$campaign_id = $api->campaignCreate($type, $opts, $content, $segments);
								self::gbs_write_to_file("Campaign ID: " . $campaign_id);
								$return = $api->campaignSendNow($campaign_id);
								self::gbs_write_to_file("Return: " . $return);
							}
						}
					}
				}
			}
		}
		Group_Buying_MailChimp_Model::update_last_cron_time( 'top_purchase_scheduled_emails' , $current_time );
		return true;
	}

	public static function gbs_non_renewed_trigger() {
		self::$file_name = ABSPATH . WPINC . '/non_renewed_trigger_'.date('dmY').'.txt';	//.'_'.date('His')
		// Set Current Time
		$current_time = date('Y-m-d H:i:s');
		self::gbs_write_to_file("Current Time: " . $current_time, 0);

		// Connect to MailChimp API
		$api = Group_Buying_MailChimp_Model::validate_mailchimp_api_key(self::$api_key);
		if (!$api) {
			self::gbs_write_to_file("API Key Error");
			return false;
		}

		// Fetch MailChimp List
		$list_id = get_option('gbs_mailchimp_list_id');
		if (!$list_id) {
			self::gbs_write_to_file("List ID not Found");
			return false;
		}

		// Fetch MailChimp Groups
		$groups = Group_Buying_MailChimp_Model::get_mailchimp_groups( $api, $list_id );
		if (!isset($groups['non_renewed_group'])) {
			self::gbs_write_to_file("Non-Renewed Group not Found");
			return false;
		}

		// Fetch Last Cron Time
		$last_cron_time = Group_Buying_MailChimp_Model::get_last_cron_time( 'non_renewed_trigger' );
		self::gbs_write_to_file("Last Cron Time: " . $last_cron_time);

		// Get Cutoff Period
		$cutoff = get_option('gbs_non_renewed_cutoff');
		self::gbs_write_to_file("Cutoff: " . $cutoff);

		// Condition: purchase date < number of days ago
		$cutoff_time = $cutoff * 24 * 60 * 60;
		self::gbs_write_to_file("Cutoff Time: " . $cutoff_time);

		// Calculate Last Purchase Date
		$last_purchase_time = date('Y-m-d H:i:s', time() - $cutoff_time);
		self::gbs_write_to_file("Last Purchase Time: " . $last_purchase_time);

		// Get All Users from wp_gbs_non_renewed_users having last purchase date before cutoff time
		// Additionally, also cutting off those users which are already updated to non-renewed group
		$users = Group_Buying_MailChimp_Model::get_non_renewed_users_before_purchase_date( $last_purchase_time );
		self::gbs_write_to_file("Total Users: " . count($users));

		if ($users) {
			foreach ($users as $key=>$value) {
				$add_to_group = FALSE;
				self::gbs_write_to_file("User Email: " . $value->email);
				// Check Member exist in List Group
				$member = $api->listMemberInfo($list_id, array($value->email) );
				if ($member['success']) {
					self::gbs_write_to_file("Member Found");
					if (isset($member['data'][0]['merges']['GROUPINGS'])) {
						foreach ($member['data'][0]['merges']['GROUPINGS'] as $k=>$v) {
							if ($v['id'] == $groups['top_purchase_group_add']['id'] && is_numeric(strpos($v['groups'], $groups['top_purchase_group_add']['name']))) {
								$add_to_group = TRUE;
								break;
							}
						}
						if ($add_to_group) {
							// Add User to Non Renewed Group
							$merge_vars = array('GROUPINGS' => array(
									array('id' => $groups['non_renewed_group']['id'], 'groups' => $groups['non_renewed_group']['name']),
								)
							);
							$result = $api->listUpdateMember($list_id, $value->email, $merge_vars, 'html', true);
							self::gbs_write_to_file("Result: " . $result);
							// Update or Insert in Table
							if (!$api->errorCode) {
								Group_Buying_MailChimp_Model::update_non_renewed_user( $value->email, date('Y-m-d H:i:s') );
							}
						}
						else
							self::gbs_write_to_file("Member not in Top Purchase Group Add");
					}
				}
				else
					self::gbs_write_to_file("Member not Found");
			}
		}

		// Update Cron Time
		Group_Buying_MailChimp_Model::update_last_cron_time( 'non_renewed_trigger' , $current_time );

		return true;
	}
}
