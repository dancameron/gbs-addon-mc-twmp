<?php
if ( class_exists( 'Group_Buying_Controller' ) ) {

	include_once(GB_TWMP_MC_PATH . '/lib/MCAPI.class.php');

	class Group_Buying_Mailchimp_Settings extends Group_Buying_Controller {
		const MAILCHIMP_API_KEY_OPTION = 'gbs_mailchimp_api_key';
		const MAILCHIMP_LIST_ID_OPTION = 'gbs_mailchimp_list_id';
		const MAILCHIMP_FROM_NAME_OPTION = 'gbs_mailchimp_from_name';
		const MAILCHIMP_FROM_EMAIL_OPTION = 'gbs_mailchimp_from_email';
		const MAILCHIMP_TOP_PURCHASE_DEAL_ID_OPTION = 'gbs_top_purchase_deal_id';
		const MAILCHIMP_TOP_PURCHASE_DEAL_TIMESTAMP_OPTION = 'gbs_top_purchase_deal_timestamp';
		const MAILCHIMP_TOP_PURCHASE_GROUP_ADD_ID_OPTION = 'gbs_mailchimp_top_purchase_group_add_id';
		const MAILCHIMP_TOP_PURCHASE_GROUP_ADD_BIT_OPTION = 'gbs_mailchimp_top_purchase_group_add_bit';
		const MAILCHIMP_TOP_PURCHASE_GROUP_REMOVE_ID_OPTION = 'gbs_mailchimp_top_purchase_group_remove_id';
		const MAILCHIMP_TOP_PURCHASE_GROUP_REMOVE_BIT_OPTION = 'gbs_mailchimp_top_purchase_group_remove_bit';
		const MAILCHIMP_SCHEDULED_TEMPLATE_OPTION = 'gbs_mailchimp_scheduled_template';
		const MAILCHIMP_SCHEDULED_DELAY_OPTION = 'gbs_mailchimp_scheduled_delay';
		const MAILCHIMP_SCHEDULED_TEMPLATE_SUBJECT_OPTION = 'gbs_mailchimp_scheduled_template_subject';
		const MAILCHIMP_NON_RENEWED_CUTOFF_OPTION = 'gbs_non_renewed_cutoff';
		const MAILCHIMP_NON_RENEWED_GROUP_ID_OPTION = 'gbs_mailchimp_non_renewed_group_id';
		const MAILCHIMP_NON_RENEWED_GROUP_BIT_OPTION = 'gbs_mailchimp_non_renewed_group_bit';
		private static $mailchimp_api_key = array();
		protected static $settings_page;
		private static $instance;

		public static function init() {
			self::$settings_page = self::register_settings_page( 'mailchimp-settings', self::__( 'Group Buying MailChimp Become Top Settings' ), self::__( 'MailChimp Settings' ), 11000, '' );
			add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 10, 0 );
			self::$mailchimp_api_key = get_option( self::MAILCHIMP_API_KEY_OPTION );
		}

		public static function register_settings_fields() {
			register_setting( self::$settings_page, self::MAILCHIMP_API_KEY_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_LIST_ID_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_FROM_NAME_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_FROM_EMAIL_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_TOP_PURCHASE_DEAL_ID_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_TOP_PURCHASE_DEAL_TIMESTAMP_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_TOP_PURCHASE_GROUP_ADD_ID_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_TOP_PURCHASE_GROUP_ADD_BIT_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_TOP_PURCHASE_GROUP_REMOVE_ID_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_TOP_PURCHASE_GROUP_REMOVE_BIT_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_SCHEDULED_TEMPLATE_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_SCHEDULED_DELAY_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_SCHEDULED_TEMPLATE_SUBJECT_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_NON_RENEWED_CUTOFF_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_NON_RENEWED_GROUP_ID_OPTION );
			register_setting( self::$settings_page, self::MAILCHIMP_NON_RENEWED_GROUP_BIT_OPTION );
			add_settings_section( self::MAILCHIMP_API_KEY_OPTION, '', array( get_class(), 'display_mailchimp_section' ), self::$settings_page );
		}

		public static function display_mailchimp_section() {
		?>
			<link rel="stylesheet" href="../wp-includes/css/gbs-addon.css" media="screen" type="text/css" />
			<script type="text/javascript" src="../wp-includes/js/jquery.min.js"></script>
			<script type="text/javascript" src="../wp-includes/js/gbs-addon.js"></script>
			<div class="main-box">
		<?php
			// Fetch MailChimp API Key
			$gbs_mailchimp_api_key = get_option(self::MAILCHIMP_API_KEY_OPTION);
			$api_key_error = '';
			if ($gbs_mailchimp_api_key) {
				// MailChimp Connection
				$api = new MCAPI($gbs_mailchimp_api_key);
				// Check MailChimp Connection
				$retval = $api->ping($gbs_mailchimp_api_key);
				if ($retval != "Everything's Chimpy!") {
					$api_key_error = 'Invalid API Key';
				}
			}
		?>
				<div class="form-tab">
					<span class="label">MailChimp API Key</span>
					<input type="text" value="<?php echo $gbs_mailchimp_api_key; ?>" id="gbs_mailchimp_api_key_new" name="gbs_mailchimp_api_key_new" <?php if ($gbs_mailchimp_api_key && !$api_key_error) echo 'disabled'; ?>>
					<input type="hidden" name="mailchimp-api-key-exist" id="mailchimp-api-key-exist" value="<?php echo $gbs_mailchimp_api_key ? 'true' : 'false'; ?>">
					<a href="javascript:editAPIKey();" id="edit-api-key" class="button button-primary" <?php if (!$gbs_mailchimp_api_key || $api_key_error) echo 'style="display:none;"'; ?>>Edit</a>
					<a href="javascript:validateAPIKey();" id="validate-api-key" class="button button-primary" <?php if ($gbs_mailchimp_api_key && !$api_key_error) echo 'style="display:none;"'; ?>>Validate</a>
					<a href="javascript:saveAPIKey();" id="save-api-key" class="button button-primary" style="display:none;">Save</a>
					<a href="javascript:resetAPIKey();" id="reset-api-key" class="button button-primary" style="display:none;">Reset</a>
					&nbsp;&nbsp;&nbsp;<span id="api-key-error" class="display-error-message"><?php if ($api_key_error) echo $api_key_error; ?></span>
					<input type="hidden" value="<?php echo $gbs_mailchimp_api_key; ?>" name="gbs_mailchimp_api_key">
				</div>
				<div id="mailchimp_settings_fields">
		<?php
			if ($gbs_mailchimp_api_key && !$api_key_error) {
				// Fetch Options
				$gbs_mailchimp_list_id = get_option(self::MAILCHIMP_LIST_ID_OPTION);
				$gbs_mailchimp_from_name = get_option(self::MAILCHIMP_FROM_NAME_OPTION);
				$gbs_mailchimp_from_email = get_option(self::MAILCHIMP_FROM_EMAIL_OPTION);
				$gbs_top_purchase_deal_id = get_option(self::MAILCHIMP_TOP_PURCHASE_DEAL_ID_OPTION);
				$gbs_top_purchase_deal_timestamp = get_option(self::MAILCHIMP_TOP_PURCHASE_DEAL_TIMESTAMP_OPTION);
				$gbs_mailchimp_top_purchase_group_add_id = get_option(self::MAILCHIMP_TOP_PURCHASE_GROUP_ADD_ID_OPTION);
				$gbs_mailchimp_top_purchase_group_add_bit = get_option(self::MAILCHIMP_TOP_PURCHASE_GROUP_ADD_BIT_OPTION);
				$gbs_mailchimp_top_purchase_group_remove_id = get_option(self::MAILCHIMP_TOP_PURCHASE_GROUP_REMOVE_ID_OPTION);
				$gbs_mailchimp_top_purchase_group_remove_bit = get_option(self::MAILCHIMP_TOP_PURCHASE_GROUP_REMOVE_BIT_OPTION);
				$gbs_mailchimp_scheduled_template = get_option(self::MAILCHIMP_SCHEDULED_TEMPLATE_OPTION);
				$gbs_mailchimp_scheduled_delay = get_option(self::MAILCHIMP_SCHEDULED_DELAY_OPTION);
				$gbs_mailchimp_scheduled_template_subject = get_option(self::MAILCHIMP_SCHEDULED_TEMPLATE_SUBJECT_OPTION);
				$gbs_non_renewed_cutoff = get_option(self::MAILCHIMP_NON_RENEWED_CUTOFF_OPTION);
				$gbs_mailchimp_non_renewed_group_id = get_option(self::MAILCHIMP_NON_RENEWED_GROUP_ID_OPTION);
				$gbs_mailchimp_non_renewed_group_bit = get_option(self::MAILCHIMP_NON_RENEWED_GROUP_BIT_OPTION);

				// MailChimp Connection
				$api = new MCAPI($gbs_mailchimp_api_key);
				// Fetch MailChimp Lists
				$mailchimp_lists = $api->lists();
				if (!$mailchimp_lists['total'])
					$mailchimp_list_error = 'List Not Found';
		?>
					<div class="form-tab">
						<span class="label">List</span>
						<?php if (isset($mailchimp_list_error)) : ?>
							<?php echo '<span class="display-error-message">'.$mailchimp_list_error.'</span>'; ?>
						<?php else : ?>
							<select id="gbs_mailchimp_list_id" name="gbs_mailchimp_list_id" onchange="getMailChimpGroups(this.value);">
								<?php if (!$gbs_mailchimp_list_id) : ?>
									<option value="">-- SELECT --</option>
								<?php endif; ?>
								<?php foreach($mailchimp_lists['data'] as $key=>$value) : ?>
									<option value="<?php echo $value['id']; ?>" <?php if ($gbs_mailchimp_list_id == $value['id']) echo 'selected'; ?>><?php echo substr($value['name'], 0, 50); ?></option>
								<?php endforeach; ?>
							</select>
							<input type="hidden" name="group_id" id="group_id">
						<?php endif; ?>
					</div>
		<?php
				// Fetch MailChimp Templates
				$mailchimp_templates = $api->templates($gbs_mailchimp_api_key, array('user'=>false));

				// Deals
				$deals = Group_Buying_MailChimp_Model::get_active_publish_deals(time());
				foreach ($deals as $key=>$value) {
					$purchase_count = Group_Buying_MailChimp_Model::get_deal_meta($value->ID, '_max_purchases');
					if ($purchase_count > -1) {
						if ($purchase_count == Group_Buying_MailChimp_Model::get_count_deal_purchase($value->ID)) {
							unset ($deals[$key]);
						}
					}
				}

				// MailChimp Groups
				$mailchimp_groups = array();
				$groups = $api->listInterestGroupings($gbs_mailchimp_list_id);
				if (isset($groups)) {
					foreach ($groups as $k=>$v) {
						$mailchimp_groups[] = $v;
					}
				}
				$top_purchase_group_add_found = FALSE;
				$top_purchase_group_remove_found = FALSE;
				$non_renewed_group_found = FALSE;
				foreach ($mailchimp_groups as $key=>$value) {
					if ($value['id'] == $gbs_mailchimp_top_purchase_group_add_id)
						$top_purchase_group_add_found = TRUE;
					if ($value['id'] == $gbs_mailchimp_top_purchase_group_remove_id)
						$top_purchase_group_remove_found = TRUE;
					if ($value['id'] == $gbs_mailchimp_non_renewed_group_id)
						$non_renewed_group_found = TRUE;
				}
		?>
					<div class="form-tab">
						<span class="label">From Name</span>
						<input type="text" value="<?php echo $gbs_mailchimp_from_name; ?>" name="gbs_mailchimp_from_name">
					</div>
					<div class="form-tab">
						<span class="label">From Email</span>
						<input type="text" value="<?php echo $gbs_mailchimp_from_email; ?>" name="gbs_mailchimp_from_email">
					</div>
					<div id="top_templates">
					</div>
					<input type="hidden" name="top_purchase_deal_count" id="top_purchase_deal_count" value="<?php echo $gbs_top_purchase_deal_id ? count($gbs_top_purchase_deal_id) : 0; ?>">
					<?php $count = 0; ?>
					<select id="gbs_top_purchase_deals" name="gbs_top_purchase_deals[]" style="display:none;">
						<?php foreach ($deals as $key=>$value) : ?>
							<option value="<?php echo $value->ID; ?>" <?php if (isset($last_top_deal) && $last_top_deal == $value->ID) echo 'selected'; ?>><?php echo substr($value->post_title, 0, 50); ?></option>
						<?php endforeach; ?>
					</select>
					<div id="top_product_purchase_trigger">
						<?php if (!$gbs_top_purchase_deal_id) : ?>
							<div id="strPurchaseProduct<?php echo $count; ?>">
								<div class="form-tab">
									<span class="label">Top Product Purchase Trigger</span>
									<select id="gbs_top_purchase_deal_id_<?php echo $count; ?>" name="gbs_top_purchase_deal_id[]" onChange="addTopProductPurchaseTrigger(<?php echo $count; ?>);">
										<?php foreach($deals as $key=>$value) : ?>
											<option value="<?php echo $value->ID; ?>" <?php if (isset($last_top_deal) && $last_top_deal == $value->ID) echo 'selected'; ?>><?php echo substr($value->post_title, 0, 50); ?></option>
										<?php endforeach; ?>
									</select>
									<input type="hidden" id="gbs_top_purchase_deal_timestamp_<?php echo $count; ?>" name="gbs_top_purchase_deal_timestamp[]" value="<?php echo strtotime(date('Y-m-d H:i:s')); ?>">
									<a href="javascript:addPurchaseProduct();" id="submit" class="button button-primary">Add</a>
								</div>
							</div>
						<?php else : ?>
							<?php foreach ($gbs_top_purchase_deal_id as $key=>$value) : ?>
								<div id="strPurchaseProduct<?php echo $count; ?>">
									<div class="form-tab">
										<span class="label">Top Product Purchase Trigger</span>
										<select id="gbs_top_purchase_deal_id_<?php echo $count; ?>" name="gbs_top_purchase_deal_id[]" onChange="addTopProductPurchaseTrigger(<?php echo $count; ?>);">
											<?php foreach ($deals as $k=>$v) : ?>
												<option value="<?php echo $v->ID; ?>" <?php if ($value == $v->ID) echo 'selected'; ?>><?php echo substr($v->post_title, 0, 50); ?></option>
											<?php endforeach; ?>
										</select>
										<input type="hidden" id="gbs_top_purchase_deal_timestamp_<?php echo $count; ?>" name="gbs_top_purchase_deal_timestamp[]" value="<?php echo $gbs_top_purchase_deal_timestamp[$count]; ?>">
										<?php if ($count) : ?>
											<a href="javascript:removePurchaseProduct('<?php echo $count; ?>');" id="submit" class="button button-primary">Remove</a>
										<?php else : ?>
											<a href="javascript:addPurchaseProduct();" id="submit" class="button button-primary">Add</a>
										<?php endif; ?>
									</div>
								</div>
								<?php $count++; ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div class="form-tab">
						<span class="label">Top Purchase Group Add</span>
						<input type="hidden" name="top_purchase_group_add_found" id="top_purchase_group_add_found" value="<?php echo $top_purchase_group_add_found ? 'TRUE' : 'FALSE'; ?>">
						<input type="hidden" name="gbs_mailchimp_top_purchase_group_add_id" id="gbs_mailchimp_top_purchase_group_add_id" value="<?php echo $top_purchase_group_add_found ? $gbs_mailchimp_top_purchase_group_add_id : NULL; ?>">
						<input type="hidden" name="gbs_mailchimp_top_purchase_group_add_bit" id="gbs_mailchimp_top_purchase_group_add_bit" value="<?php echo $top_purchase_group_add_found ? $gbs_mailchimp_top_purchase_group_add_bit : NULL; ?>">
						<span style="display:inline-block;">
							<div id="top_purchase_group_add_grouping"></div>
							<select id="gbs_mailchimp_top_purchase_group_add_parents" name="gbs_mailchimp_top_purchase_group_add_parents" onChange="showMailChimpTopPurchaseGroupAddChilds(this.value);">
								<?php if (!$top_purchase_group_add_found) : ?>
									<option value="">-- SELECT --</option>
								<?php endif; ?>
								<?php foreach ($mailchimp_groups as $key=>$value) : ?>
									<option value="<?php echo $value['id']; ?>" <?php if ($gbs_mailchimp_top_purchase_group_add_id == $value['id']) echo 'selected'; ?>><?php echo $value['name']; ?></option>
								<?php endforeach; ?>
							</select>
						</span>
						<?php if ($mailchimp_groups) : ?>
							<?php $count = 0; ?>
							<?php foreach ($mailchimp_groups as $key=>$value) : ?>
								<?php $count++; ?>
								<span style=" display:inline-block;">
									<select id="gbs_mailchimp_top_purchase_group_add_bit_<?php echo $value['id']; ?>"  class="mail-chimp-top-group-select" name="gbs_mailchimp_top_purchase_group_add_bit_<?php echo $value['id']; ?>" style="<?php if ($gbs_mailchimp_top_purchase_group_add_id != $value['id']) echo 'display:none'; ?>" onChange='getMailChimpTopPurchaseGroupAddChildBit(this.value);'>
										<?php if (!$top_purchase_group_add_found) : ?>
											<option value="" selected>-- SELECT --</option>
										<?php endif; ?>
										<?php foreach ($value['groups'] as $k=>$v) : ?>
											<option value="<?php echo $v['bit']; ?>" <?php if ($gbs_mailchimp_top_purchase_group_add_bit == $v['bit'] && $top_purchase_group_add_found) echo 'selected'; ?>><?php echo $v['name']; ?></option>
										<?php endforeach; ?>
									</select>
								</span>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div class="form-tab">
						<span class="label">Top Purchase Group Remove</span>
						<input type="hidden" name="top_purchase_group_remove_found" id="top_purchase_group_remove_found" value="<?php echo $top_purchase_group_remove_found ? 'TRUE' : 'FALSE'; ?>">
						<input type="hidden" name="gbs_mailchimp_top_purchase_group_remove_id" id="gbs_mailchimp_top_purchase_group_remove_id" value="<?php echo $top_purchase_group_remove_found ? $gbs_mailchimp_top_purchase_group_remove_id : NULL; ?>">
						<input type="hidden" name="gbs_mailchimp_top_purchase_group_remove_bit" id="gbs_mailchimp_top_purchase_group_remove_bit" value="<?php echo $top_purchase_group_remove_found ? $gbs_mailchimp_top_purchase_group_remove_bit : NULL; ?>">
						<span style="display:inline-block;">
							<div id="top_purchase_group_remove_grouping"></div>
							<select id="gbs_mailchimp_top_purchase_group_remove_parents" name="gbs_mailchimp_top_purchase_group_remove_parents" onChange="showMailChimpTopPurchaseGroupRemoveChilds(this.value);">
								<?php if (!$top_purchase_group_remove_found) : ?>
									<option value="">-- SELECT --</option>
								<?php endif; ?>
								<?php foreach ($mailchimp_groups as $key=>$value) : ?>
									<option value="<?php echo $value['id']; ?>" <?php if ($gbs_mailchimp_top_purchase_group_remove_id == $value['id']) echo 'selected'; ?>><?php echo $value['name']; ?></option>
								<?php endforeach; ?>
							</select>
						</span>
						<?php if ($mailchimp_groups) : ?>
							<?php $count = 0; ?>
							<?php foreach ($mailchimp_groups as $key=>$value) : ?>
								<?php $count++; ?>
								<span style=" display:inline-block;">
									<select id="gbs_mailchimp_top_purchase_group_remove_bit_<?php echo $value['id']; ?>"  class="mail-chimp-top-group-select" name="gbs_mailchimp_top_purchase_group_remove_bit_<?php echo $value['id']; ?>" style="<?php if ($gbs_mailchimp_top_purchase_group_remove_id != $value['id']) echo 'display:none'; ?>" onChange='getMailChimpTopPurchaseGroupRemoveChildBit(this.value);'>
										<?php if (!$top_purchase_group_remove_found) : ?>
											<option value="" selected>-- SELECT --</option>
										<?php endif; ?>
										<?php foreach ($value['groups'] as $k=>$v) : ?>
											<option value="<?php echo $v['bit']; ?>" <?php if ($gbs_mailchimp_top_purchase_group_remove_bit == $v['bit'] && $top_purchase_group_remove_found) echo 'selected'; ?>><?php echo $v['name']; ?></option>
										<?php endforeach; ?>
									</select>
								</span>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<input type="hidden" name="scheduled_template_count" id="scheduled_template_count" value="<?php echo $gbs_mailchimp_scheduled_template ? count($gbs_mailchimp_scheduled_template) : 0; ?>">
					<?php $count = 0; ?>
					<select id="gbs_mailchimp_scheduled_templates" name="gbs_mailchimp_scheduled_templates[]" style="display:none;">
						<?php foreach ($mailchimp_templates['user'] as $k=>$v) : ?>
							<option value="<?php echo $v['id']; ?>" <?php if ($value == $v['id']) echo 'selected'; ?>><?php echo $v['name']; ?></option>
						<?php endforeach; ?>
					</select>
					<div id="scheduled_templates">
						<?php if (!$gbs_mailchimp_scheduled_template) : ?>						
							<div id="strTemplate<?php echo $count; ?>">
								<div class="form-tab">
									<span class="label">Scheduled Template</span>
									<select id="gbs_mailchimp_scheduled_template" name="gbs_mailchimp_scheduled_template[]">
										<?php foreach ($mailchimp_templates['user'] as $k=>$v) : ?>
											<option value="<?php echo $v['id']; ?>" <?php if ($value == $v['id']) echo 'selected'; ?>><?php echo $v['name']; ?></option>
										<?php endforeach; ?>
									</select>
									<div class="form-tab-2">
										<span class="label-inner">Delay</span>
										<input type="text" value="10" name="gbs_mailchimp_scheduled_delay[]" class="numeric-field" maxlength="5">
										<span class="label-inner">Subject</span>
										<input type="text" value="" name="gbs_mailchimp_scheduled_template_subject[]">
										<a href="javascript:addElement();" id="submit" class="button button-primary">Add</a>
									</div>
								</div>
							</div>
						<?php else : ?>
							<?php foreach ($gbs_mailchimp_scheduled_template as $key=>$value) : ?>
								<div id="strTemplate<?php echo $count; ?>">
									<div class="form-tab">
										<span class="label">Scheduled Template</span>
										<select id="gbs_mailchimp_scheduled_template" name="gbs_mailchimp_scheduled_template[]">
											<?php foreach ($mailchimp_templates['user'] as $k=>$v) : ?>
												<option value="<?php echo $v['id']; ?>" <?php if ($value == $v['id']) echo 'selected'; ?>><?php echo $v['name']; ?></option>
											<?php endforeach; ?>
										</select>
										<div class="form-tab-2">
											<span class="label-inner">Delay</span>
											<input type="text" value="<?php echo isset($gbs_mailchimp_scheduled_delay[$key]) ? $gbs_mailchimp_scheduled_delay[$key] : 10; ?>" name="gbs_mailchimp_scheduled_delay[]" class="numeric-field" maxlength="5">
											<span class="label-inner">Subject</span>
											<input type="text" value="<?php echo isset($gbs_mailchimp_scheduled_template_subject[$key]) ? $gbs_mailchimp_scheduled_template_subject[$key] : '&nbsp;'; ?>" name="gbs_mailchimp_scheduled_template_subject[]">
											<?php if ($count) : ?>
												<a href="javascript:removeTemplateElement('<?php echo $count; ?>');" id="submit" class="button button-primary">Remove</a>
											<?php else : ?>
												<a href="javascript:addElement();" id="submit" class="button button-primary">Add</a>
											<?php endif; ?>
										</div>
									</div>
								</div>
								<?php $count++; ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div class="form-tab">
						<span class="label">Non-renewed Cutoff</span>
						<input type="text" value="<?php echo isset($gbs_non_renewed_cutoff) ? $gbs_non_renewed_cutoff : 3; ?>" name="gbs_non_renewed_cutoff" class="numeric-field" maxlength="5">
					</div>
					<div class="form-tab">
						<span class="label">Non-renewed Group</span>
						<input type="hidden" name="non_renewed_group_found" id="non_renewed_group_found" value="<?php echo $non_renewed_group_found ? 'TRUE' : 'FALSE'; ?>">
						<input type="hidden" name="gbs_mailchimp_non_renewed_group_id" id="gbs_mailchimp_non_renewed_group_id" value="<?php echo $non_renewed_group_found ? $gbs_mailchimp_non_renewed_group_id : NULL; ?>">
						<input type="hidden" name="gbs_mailchimp_non_renewed_group_bit" id="gbs_mailchimp_non_renewed_group_bit" value="<?php echo $non_renewed_group_found ? $gbs_mailchimp_non_renewed_group_bit : NULL; ?>">
						<span style="display:inline-block;">
							<div id="non_renewed_group_grouping"></div>
							<select id="gbs_mailchimp_non_renewed_group_parents" name="gbs_mailchimp_non_renewed_group_parents" onChange="showMailChimpNonRenewedGroupChilds(this.value);">
								<?php if (!$non_renewed_group_found) : ?>
									<option value="">-- SELECT --</option>
								<?php endif; ?>
								<?php foreach ($mailchimp_groups as $key=>$value) : ?>
									<option value="<?php echo $value['id']; ?>" <?php if ($gbs_mailchimp_non_renewed_group_id == $value['id']) echo 'selected'; ?>><?php echo $value['name']; ?></option>
								<?php endforeach; ?>
							</select>
						</span>
						<?php if ($mailchimp_groups) : ?>
							<?php $count = 0; ?>
							<?php foreach ($mailchimp_groups as $key=>$value) : ?>
								<?php $count++; ?>
								<span style=" display:inline-block;">
									<select id="gbs_mailchimp_non_renewed_group_bit_<?php echo $value['id']; ?>"  class="mail-chimp-non-renewed-group-select" name="gbs_mailchimp_non_renewed_group_bit_<?php echo $value['id']; ?>" style="<?php if ($gbs_mailchimp_non_renewed_group_id != $value['id']) echo 'display:none'; ?>" onChange='getMailChimpNonRenewedGroupChildBit(this.value);'>
										<?php if (!$non_renewed_group_found) : ?>
											<option value="" selected>-- SELECT --</option>
										<?php endif; ?>
										<?php foreach ($value['groups'] as $k=>$v) : ?>
											<option value="<?php echo $v['bit']; ?>" <?php if ($gbs_mailchimp_non_renewed_group_bit == $v['bit'] && $non_renewed_group_found) echo 'selected'; ?>><?php echo $v['name']; ?></option>
										<?php endforeach; ?>
									</select>
								</span>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
		<?php
			}
		?>
			</div>
		<?php
		}
	}
}