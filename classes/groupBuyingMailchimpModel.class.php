<?php

/**
 * GBS Deal Model
 *
 * @package GBS
 * @subpackage Deal
 */
class Group_Buying_Mailchimp_Model extends Group_Buying_Post_Type {
	const POST_TYPE = 'gb_deal';
	const REWRITE_SLUG = 'deals';
	const LOCATION_TAXONOMY = 'gb_location';
	const CAT_TAXONOMY = 'gb_category';
	const TAG_TAXONOMY = 'gb_tag';

	const DEAL_STATUS_OPEN = 'open';
	const DEAL_STATUS_PENDING = 'pending';
	const DEAL_STATUS_CLOSED = 'closed';
	const DEAL_STATUS_UNKNOWN = 'unknown';

	const NO_EXPIRATION_DATE = -1;
	const NO_MAXIMUM = -1;
	const MAX_LOCATIONS = 5;

	private static $instances = array();

	private static $meta_keys = array(
		'amount_saved' => '_amount_saved', // string
		'capture_before_expiration' => '_capture_before_expiration', // bool
		'dynamic_price' => '_dynamic_price', // array
		'expiration_date' => '_expiration_date', // int
		'fine_print' => '_fine_print', // string
		'highlights' => '_highlights', // string
		'max_purchases' => '_max_purchases', // int
		'max_purchases_per_user' => '_max_purchases_per_user', // int
		'merchant_id' => '_merchant_id', // int
		'min_purchases' => '_min_purchases', // int
		'number_of_purchases' => '_number_of_purchases', // int
		'price' => '_base_price', // float
		'preview_key' => '_preview_private_key', // float
		'tax' => '_tax', // float
		'taxable' => '_taxable', // bool
		'taxmode' => '_taxmode', // string
		'shipping' => '_shipping', // float
		'shippable' => '_shippable', // string
		'shipping_dyn_price' => '_shipping_dyn_price', // array
		'shipping_mode' => '_shipping_mode', // string
		'rss_excerpt' => '_rss_excerpt', // string
		'used_voucher_serials' => '_used_voucher_serials', // array
		'value' => '_value', // string
		'voucher_expiration_date' => '_voucher_expiration_date', // string
		'voucher_how_to_use' => '_voucher_how_to_use', // string
		'voucher_id_prefix' => '_voucher_id_prefix', //string
		'voucher_locations' => '_voucher_locations', // array
		'voucher_logo' => '_voucher_logo', // int
		'voucher_map' => '_voucher_map', // string
		'voucher_serial_numbers' => '_voucher_serial_numbers', // array
	); // A list of meta keys this class cares about. Try to keep them in alphabetical order.


	public static function init() {
		// register Deal post type
		$post_type_args = array(
			'has_archive' => TRUE,
			'menu_position' => 4,
			'rewrite' => array(
				'slug' => self::REWRITE_SLUG,
				'with_front' => FALSE,
			),
			'supports' => array( 'title', 'editor', 'thumbnail', 'comments', 'custom-fields', 'revisions' ),
			'menu_icon' => GB_URL . '/resources/img/deals.png',
			'hierarchical' => TRUE,
		);
		self::register_post_type( self::POST_TYPE, 'Deal', 'Deals', $post_type_args );

		// register Locations taxonomy
		$singular = 'Location';
		$plural = 'Locations';
		$taxonomy_args = array(
			'rewrite' => array(
				'slug' => 'locations',
				'with_front' => TRUE,
				'hierarchical' => TRUE,
			),
		);
		self::register_taxonomy( self::LOCATION_TAXONOMY, array( self::POST_TYPE ), $singular, $plural, $taxonomy_args );

		// register Locations taxonomy
		$singular = 'Category';
		$plural = 'Categories';
		$taxonomy_args = array(
			'rewrite' => array(
				'slug' => 'deal-categories',
				'with_front' => TRUE,
				'hierarchical' => TRUE,
			),
		);
		self::register_taxonomy( self::CAT_TAXONOMY, array( self::POST_TYPE ), $singular, $plural, $taxonomy_args );

		// register Locations taxonomy
		$singular = 'Tag';
		$plural = 'Tags';
		$taxonomy_args = array(
			'hierarchical' => FALSE,
			'rewrite' => array(
				'slug' => 'deal-tags',
				'with_front' => TRUE,
				'hierarchical' => FALSE,
			),
		);
		self::register_taxonomy( self::TAG_TAXONOMY, array( self::POST_TYPE ), $singular, $plural, $taxonomy_args );
	}

	protected function __construct( $id ) {
		parent::__construct( $id );
	}

	/**
	 *
	 *
	 * @static
	 * @param int     $id
	 * @return Group_Buying_Mailchimp_Model
	 */
	public static function get_instance( $id = 0 ) {
		if ( !$id ) {
			return NULL;
		}
		if ( !isset( self::$instances[$id] ) || !self::$instances[$id] instanceof self ) {
			self::$instances[$id] = new self( $id );
		}
		if ( self::$instances[$id]->post->post_type != self::POST_TYPE ) {
			return NULL;
		}
		return self::$instances[$id];
	}

	/**
	 * Get a list of every successful Purchase of this deal
	 *
	 * @return Group_Buying_Purchase[] An array of all Purchases
	 */
	public function validate_mailchimp_api_key($api_key) {
		$api = new MCAPI($api_key);
		$retval = $api->ping($api_key);
		return $retval == "Everything's Chimpy!" ? $api : NULL;
	}

	/**
	 * Get a list of every successful Purchase of this deal
	 *
	 * @return Group_Buying_Purchase[] An array of all Purchases
	 */
	public function get_purchases($id) {
		$purchase_ids = Group_Buying_Purchase::get_purchases( array( 'deal' => $id ) );

		$purchases = array();
		foreach ( $purchase_ids as $purchase_id ) {
			$purchases[] = Group_Buying_Purchase::get_instance( $purchase_id );
		}

		return $purchases;
	}

	/**
	 * Get a list of successful Purchases by a given account
	 *
	 * @param int     $user_id
	 * @return Group_Buying_Purchase[] An array of Purchases
	 */
	public function get_purchases_by_account( $account_id ) {
		$purchase_ids = Group_Buying_Purchase::get_purchases( array( 'deal' => $this->ID, 'account' => $account_id ) );
		return $purchase_ids;
	}

	/**
	 *
	 *
	 * @return int The number of successful purchases of this deal
	 */
	public function get_number_of_purchases( $recalculate = false, $count_pending = true ) {
		$number_of_purchases = $this->get_post_meta( self::$meta_keys['number_of_purchases'] );
		if ( $recalculate || empty( $number_of_purchases ) ) {
			$purchases = $this->get_purchases();
			$number_of_purchases = 0;
			foreach ( $purchases as $purchase ) {
				if ( $purchase->is_complete() || ( $count_pending && $purchase->is_pending() ) ) {
					$purchase_quantity = $purchase->get_product_quantity( $this->ID );
					$number_of_purchases += $purchase_quantity;
				}
			}

			$number_of_purchases = apply_filters( 'gb_deal_number_of_purchases', $number_of_purchases, $this );
			$this->save_post_meta( array(
					self::$meta_keys['number_of_purchases'] => $number_of_purchases
				) );
		}
		return (int)$number_of_purchases;
	}

	/**
	 * Get the unix timestamp indicating when the deal expires
	 *
	 * @return int The current timestamp
	 */
	public function get_expiration_date() {
		$date = (int)$this->get_post_meta( self::$meta_keys['expiration_date'] );
		if ( $date == 0 ) { // a new, unsaved post
			$date = current_time( 'timestamp' ) + 24*60*60;
		}
		return $date;
	}

	/**
	 *
	 *
	 * @return bool Whether this deal has expired
	 */
	public function is_expired() {
		if ( $this->never_expires() ) {
			return FALSE;
		}
		if ( current_time( 'timestamp' ) > $this->get_expiration_date() ) {
			return TRUE;
		}
		return FALSE;
	}

	public function never_expires() {
		return $this->get_expiration_date() == self::NO_EXPIRATION_DATE;
	}

	/**
	 * Get the list of deals that expired since $timestamp
	 *
	 * @static
	 * @param int     $timestamp
	 * @return array The IDs of the recently expired deals
	 */
	public static function get_expired_deals( $timestamp = 0 ) {
		/** @var wpdb $wpdb */
		global $wpdb;
		$sql = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value>%d AND meta_value<%d";
		$query = $wpdb->prepare( $sql, self::$meta_keys['expiration_date'], $timestamp, current_time( 'timestamp' ) );
		return (array)$wpdb->get_col( $query );
	}

	/**
	 * Determine the status of the deal
	 *
	 * @return string The current deal status
	 */
	public function get_status() {
		if ( $this->is_closed() ) {
			return self::DEAL_STATUS_CLOSED;
		} elseif ( $this->is_pending() ) {
			return self::DEAL_STATUS_PENDING;
		} elseif ( $this->is_open() ) {
			return self::DEAL_STATUS_OPEN;
		} else {
			return self::DEAL_STATUS_UNKNOWN;
		}
	}

	/**
	 *
	 *
	 * @return bool Whether the deal is currently open
	 */
	public function is_open() {
		if ( ( get_post_status($this->ID) == 'publish' || get_post_status($this->ID) == 'private' ) && !$this->is_closed() ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 *
	 *
	 * @return bool Whether the deal is pending
	 */
	public function is_pending() {
		if ( get_post_status($this->ID) == 'pending' ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 *
	 *
	 * @return bool Whether the deal is closed
	 */
	public function is_closed() {
		if ( $this->is_expired() ) {
			return TRUE;
		} elseif ( $this->is_sold_out() ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function set_shipping( $shipping ) {
		$this->save_post_meta( array(
				self::$meta_keys['shipping'] => $shipping,
			) );
		return $shipping;
	}

	public function get_shipping_meta() {
		$shipping = $this->get_post_meta( self::$meta_keys['shipping'] );
		return $shipping;
	}

	public function get_shipping( $qty = NULL, $local = NULL ) {
		$shipping = $this->get_post_meta( self::$meta_keys['shipping'] );
		// Filtered for the shipping class to do the heavy lifting.
		return apply_filters( 'gb_deal_get_shipping', $shipping, $this, $qty, $local );
	}

	public function set_shippable( $shippable ) {
		$this->save_post_meta( array(
				self::$meta_keys['shippable'] => $shippable,
			) );
		return $shippable;
	}

	public function get_shippable() {
		$shippable = $this->get_post_meta( self::$meta_keys['shippable'] );
		return $shippable;
	}

	public function set_shipping_dyn_price( $shipping_dyn_price ) {
		$this->save_post_meta( array(
				self::$meta_keys['shipping_dyn_price'] => $shipping_dyn_price,
			) );
		return $shipping_dyn_price;
	}

	public function get_shipping_dyn_price() {
		$shipping_dyn_price = $this->get_post_meta( self::$meta_keys['shipping_dyn_price'] );
		return $shipping_dyn_price;
	}

	public function set_shipping_mode( $shipping_mode ) {
		$this->save_post_meta( array(
				self::$meta_keys['shipping_mode'] => $shipping_mode,
			) );
		return $shipping_mode;
	}

	public function get_shipping_mode( $local = NULL ) {
		$shipping_mode = $this->get_post_meta( self::$meta_keys['shipping_mode'] );
		return $shipping_mode;
	}

	public function set_tax( $tax ) {
		/*
		 * 3.3.4 - stores mode and not int
		 */
		if ( is_numeric( $tax ) && $tax >= 1 ) {
			$tax = $tax/100;
		}
		$this->save_post_meta( array(
				self::$meta_keys['tax'] => $tax,
			) );
		return $tax;
	}

	/**
	 * To be deprecated in favor of get_tax_rate (or get_tax_mode for the meta)
	 */
	public function get_tax( $local = NULL ) {
		$mode = $this->get_tax_mode();
		if ( is_int( $mode ) ) {
			return $mode; // returned before the is_taxable check < 3.3.4 tax.
		}
		return Group_Buying_Core_Tax::get_rate( $mode, $local );
	}

	public function get_tax_mode( $local = NULL ) {
		$tax = $this->get_post_meta( self::$meta_keys['tax'] );
		if ( is_numeric( $tax ) ) {
			return (int)$tax; // < 3.3.4 tax.
		}
		return $tax;
	}

	public function set_taxable( $bool ) {
		$this->save_post_meta( array(
				self::$meta_keys['taxable'] => $bool,
			) );
		return $bool;
	}

	public function get_taxable( $local = NULL ) {
		$tax = $this->get_post_meta( self::$meta_keys['taxable'] );
		return $tax;
	}


	public function get_calc_tax( $qty = NULL, $item_data = NULL, $local = NULL ) {
		$tax = Group_Buying_Core_Tax::get_calc_tax( $this, $qty, $item_data, $local );
		return $tax;
	}

	/**
	 * Set the unix timestamp indicating when the deal expires
	 *
	 * @param int @timestamp The new timestamp
	 * @return int The new timestamp
	 */
	public function set_expiration_date( $timestamp ) {
		$this->save_post_meta( array(
				self::$meta_keys['expiration_date'] => $timestamp,
			) );
		return $timestamp;
	}

	/**
	 *
	 *
	 * @return int The maximum number of purchases allowed for this deal
	 */
	public function get_max_purchases() {
		return $this->get_post_meta( self::$meta_keys['max_purchases'] );
	}

	/**
	 * Set a new value for the maximum number of purchases
	 *
	 * @param int     $qty The new value
	 * @return int The maximum number of purchases allowed for this deal
	 */
	public function set_max_purchases( $qty ) {
		$this->save_post_meta( array(
				self::$meta_keys['max_purchases'] => $qty,
			) );
		return $qty;
	}

	/**
	 *
	 *
	 * @return int The number of allowed purchase remaining
	 */
	public function get_remaining_allowed_purchases() {
		$max = $this->get_max_purchases();
		if ( $max == self::NO_MAXIMUM ) {
			return self::NO_MAXIMUM;
		}
		return $max - $this->get_number_of_purchases();
	}

	/**
	 *
	 *
	 * @return bool Whether this deal is sold out
	 */
	public function is_sold_out() {
		if ( $this->get_max_purchases() == self::NO_MAXIMUM ) {
			return FALSE;
		}
		return $this->get_remaining_allowed_purchases() < 1;
	}

	/**
	 *
	 *
	 * @return int The minimum number of purchases for this to be a successful deal
	 */
	public function get_min_purchases() {
		return $this->get_post_meta( self::$meta_keys['min_purchases'] );
	}

	/**
	 * Set a new value for the minimum number of purchases
	 *
	 * @param int     $qty The new value
	 * @return int The minimum number of purchases for this to be a successful deal
	 */
	public function set_min_purchases( $qty ) {
		$this->save_post_meta( array(
				self::$meta_keys['min_purchases'] => $qty,
			) );
		return $qty;
	}

	/**
	 *
	 *
	 * @return int The number of purchases still needed for a successful deal
	 */
	public function get_remaining_required_purchases() {
		$remaining = max( 0, $this->get_min_purchases() - $this->get_number_of_purchases() );
		return apply_filters( 'get_remaining_required_purchases', $remaining );	
	}

	public function is_successful() {
		if ( !$this->capture_before_expiration() && !$this->is_expired() ) {
			return apply_filters( 'gb_deal_is_successful', FALSE, $this ); // can't verify success until it's expired
		}
		$remaining = $this->get_remaining_required_purchases();
		if ( $remaining < 1 ) {
			return apply_filters( 'gb_deal_is_successful', TRUE, $this );
		}
		return apply_filters( 'gb_deal_is_successful', FALSE, $this );
	}

	/**
	 *
	 *
	 * @return int The maximum quantity of this deal that a single user can Purchase
	 */
	public function get_max_purchases_per_user() {
		return $this->get_post_meta( self::$meta_keys['max_purchases_per_user'] );
	}
	/**
	 * Set a new value for the maximum number of purchases per user
	 *
	 * @param int     $qty The new value
	 * @return int The maximum quantity of this deal that a single user can Purchase
	 */
	public function set_max_purchases_per_user( $qty ) {
		$this->save_post_meta( array(
				self::$meta_keys['max_purchases_per_user'] => $qty
			) );
		return $qty;
	}

	/**
	 *
	 *
	 * @return int The ID of the merchant associated with this deal
	 */
	public function get_merchant_id() {
		return $this->get_post_meta( self::$meta_keys['merchant_id'] );
	}

	/**
	 * Set a new value for the merchant ID
	 *
	 * @param int     $id
	 * @return int The ID of the merchant associated with this deal
	 */
	public function set_merchant_id( $id ) {
		$this->save_post_meta( array(
				self::$meta_keys['merchant_id'] => $id
			) );
		return $id;
	}

	/**
	 *
	 *
	 * @return Group_Buying_Merchant The merchant associated with this deal
	 */
	public function get_merchant() {
		$id = $this->get_merchant_id();
		return Group_Buying_Merchant::get_instance( $id );
	}

	public function get_title( $data = array() ) {
		return apply_filters( 'gb_deal_title', get_the_title( $this->ID ), $data );
	}

	public function get_slug() {
		$post = get_post( $this->ID );
		return $post->post_name;
	}

	public function get_value() {
		$value = $this->get_post_meta( self::$meta_keys['value'] );
		return $value;
	}

	public function set_value( $value ) {
		$this->save_post_meta( array(
				self::$meta_keys['value'] => $value
			) );
		return $value;
	}

	public function get_amount_saved() {
		$amount_saved = $this->get_post_meta( self::$meta_keys['amount_saved'] );
		return $amount_saved;
	}

	public function set_amount_saved( $amount_saved ) {
		$this->save_post_meta( array(
				self::$meta_keys['amount_saved'] => $amount_saved
			) );
		return $amount_saved;
	}

	public function get_highlights() {
		$highlights = $this->get_post_meta( self::$meta_keys['highlights'] );
		return $highlights;
	}

	public function set_highlights( $highlights ) {
		$this->save_post_meta( array(
				self::$meta_keys['highlights'] => $highlights
			) );
		return $highlights;
	}

	public function get_fine_print() {
		$fine_print = $this->get_post_meta( self::$meta_keys['fine_print'] );
		return $fine_print;
	}

	public function set_fine_print( $fine_print ) {
		$this->save_post_meta( array(
				self::$meta_keys['fine_print'] => $fine_print
			) );
		return $fine_print;
	}

	public function get_rss_excerpt() {
		$rss_excerpt = $this->get_post_meta( self::$meta_keys['rss_excerpt'] );
		return $rss_excerpt;
	}

	public function set_rss_excerpt( $rss_excerpt ) {
		$this->save_post_meta( array(
				self::$meta_keys['rss_excerpt'] => $rss_excerpt
			) );
		return $rss_excerpt;
	}

	public function get_preview_key() {
		$preview_key = $this->get_post_meta( self::$meta_keys['preview_key'] );
		return $preview_key;
	}

	public function set_preview_key( $preview_key ) {
		$this->save_post_meta( array(
				self::$meta_keys['preview_key'] => $preview_key
			) );
		return $preview_key;
	}

	/**
	 *
	 *
	 * @return string Expiration date for this deal's voucher
	 */
	public function get_voucher_expiration_date() {
		$voucher_expiration_date = $this->get_post_meta( self::$meta_keys['voucher_expiration_date'] );
		return $voucher_expiration_date;
	}

	/**
	 *
	 *
	 * @param string  $date The expiration date for this deal's voucher
	 * @return string The new expiration date for this deal's voucher
	 */
	public function set_voucher_expiration_date( $date ) {
		$this->save_post_meta( array(
				self::$meta_keys['voucher_expiration_date'] => strtotime( $date )
			) );
		return $date;
	}

	/**
	 *
	 *
	 * @return string Instructions on how to use this deal's voucher
	 */
	public function get_voucher_how_to_use() {
		$voucher_how_to_use = $this->get_post_meta( self::$meta_keys['voucher_how_to_use'] );
		return $voucher_how_to_use;
	}

	/**
	 *
	 *
	 * @param string  $instructions The instructions for how to use this deal's voucher
	 * @return string The instructions for how to use this deal's voucher
	 */
	public function set_voucher_how_to_use( $instructions ) {
		$this->save_post_meta( array(
				self::$meta_keys['voucher_how_to_use'] => $instructions
			) );
		return $instructions;
	}

	/**
	 *
	 *
	 * @return string Prefix for this deal's voucher ID
	 */
	public function get_voucher_id_prefix( $fallback = FALSE ) {
		$voucher_id_prefix = $this->get_post_meta( self::$meta_keys['voucher_id_prefix'] );
		if ( $fallback && !$voucher_id_prefix ) {
			$voucher_id_prefix = Group_Buying_Vouchers::get_voucher_prefix();
		}
		return $voucher_id_prefix;
	}

	/**
	 *
	 *
	 * @param string  $prefix The string with which to prefix this deal's voucher IDs
	 * @return string The string with which to prefix this deal's voucher IDs
	 */
	public function set_voucher_id_prefix( $prefix ) {
		$this->save_post_meta( array(
				self::$meta_keys['voucher_id_prefix'] => $prefix
			) );
		return $prefix;
	}

	/**
	 *
	 *
	 * @return array Locations where this deal's voucher may be used
	 */
	public function get_voucher_locations() {
		$voucher_locations = $this->get_post_meta( self::$meta_keys['voucher_locations'] );
		if ( is_null( $voucher_locations ) ) {
			// Initialize with empty locations
			$voucher_locations = array();
			while ( count( $voucher_locations ) < self::MAX_LOCATIONS ) {
				$voucher_locations[] = '';
			}
		}
		return (array)$voucher_locations;
	}

	/**
	 *
	 *
	 * @param array   $locations Locations where this deal's voucher may be used
	 * @return array Locations where this deal's voucher may be used
	 */
	public function set_voucher_locations( $locations ) {
		$this->save_post_meta( array(
				self::$meta_keys['voucher_locations'] => $locations
			) );
		return $locations;
	}

	/**
	 *
	 *
	 * @return string Location of the logo for this deal's voucher
	 */
	public function get_voucher_logo() {
		$voucher_logo = $this->get_post_meta( self::$meta_keys['voucher_logo'] );
		return $voucher_logo;
	}

	/**
	 *
	 *
	 * @param string  $path Location of the logo for this deal's voucher
	 * @return string Location of the logo for this deal's voucher
	 */
	public function set_voucher_logo( $path ) {
		$this->save_post_meta( array(
				self::$meta_keys['voucher_logo'] => $path
			) );
		return $path;
	}

	/**
	 *
	 *
	 * @return string Google maps iframe code for this deal's voucher
	 */
	public function get_voucher_map() {
		$voucher_map = $this->get_post_meta( self::$meta_keys['voucher_map'] );
		return $voucher_map;
	}

	/**
	 *
	 *
	 * @param string  $map Google maps iframe code for this deal's voucher
	 * @return string Google maps iframe code for this deal's voucher
	 */
	public function set_voucher_map( $map ) {
		$this->save_post_meta( array(
				self::$meta_keys['voucher_map'] => $map
			) );
		return $map;
	}

	/**
	 *
	 *
	 * @return string Comma separated list serial numbers for this deal's voucher
	 */
	public function get_voucher_serial_numbers() {
		$voucher_serial_numbers = (array)$this->get_post_meta( self::$meta_keys['voucher_serial_numbers'] );
		return $voucher_serial_numbers;
	}

	/**
	 *
	 *
	 * @param array   $numbers List serial numbers for this deal's voucher
	 * @return array List of serial numbers for this deal's voucher
	 */
	public function set_voucher_serial_numbers( array $numbers ) {
		$this->save_post_meta( array(
				self::$meta_keys['voucher_serial_numbers'] => $numbers
			) );
		return $numbers;
	}

	public function get_next_serial() {
		$serials = $this->get_voucher_serial_numbers();
		if ( !$serials ) {
			return FALSE;
		}
		$used = $this->get_post_meta( self::$meta_keys['used_voucher_serials'] );
		$number_used = ( empty( $used ) ) ? '0' : count( $used );
		if ( $number_used > count( $serials ) || !isset( $serials[$number_used] ) || !( trim( $serials[$number_used] ) ) ) {
			return FALSE;
		}
		return $serials[$number_used];
	}

	public function mark_serial_used( $new ) {
		$serials = $this->get_post_meta( self::$meta_keys['used_voucher_serials'] );
		if ( !is_array( $serials ) ) {
			$serials = array();
		}
		$serials[] = $new;
		$this->save_post_meta( array(
				self::$meta_keys['used_voucher_serials'] => $serials
			) );
	}

	/**
	 * Add a file as a post attachment.
	 */
	public function set_attachement( $files ) {
		if ( !function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin' . '/includes/image.php';
			require_once ABSPATH . 'wp-admin' . '/includes/file.php';
			require_once ABSPATH . 'wp-admin' . '/includes/media.php';
		}

		foreach ( $files as $file => $array ) {
			if ( $files[$file]['error'] !== UPLOAD_ERR_OK ) {
				// Group_Buying_Controller::set_message('upload error : ' . $files[$file]['error']);
			}
			$attach_id = media_handle_upload( $file, $this->ID );
		}
		// Make it a thumbnail while we're at it.
		if ( !is_wp_error($attach_id) && $attach_id > 0 ) {
			update_post_meta( $this->ID, '_thumbnail_id', $attach_id );
		}
		return $attach_id;
	}

	/**
	 *
	 *
	 * @param int     $merchant_id The merchant to look for
	 * @return array List of IDs for deals created by this merchant
	 */
	public static function get_deals_by_merchant( $merchant_id ) {
		$deal_ids = self::find_by_meta( self::POST_TYPE, array( self::$meta_keys['merchant_id'] => $merchant_id ) );
		return $deal_ids;
	}

	/**
	 *
	 *
	 * @static
	 * @return bool TRUE if payments will be captured before deal expiration
	 */
	public function capture_before_expiration() {
		return (bool)$this->get_post_meta( self::$meta_keys['capture_before_expiration'] );
	}

	public function set_capture_before_expiration( $status ) {
		$this->save_post_meta( array(
				self::$meta_keys['capture_before_expiration'] => (bool)$status,
			) );
	}

	public function get_user( $id ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->users} WHERE ID=%d";
		$query = $wpdb->prepare( $sql, $id );
		return (array)$wpdb->get_row( $query );
	}

	public function get_user_by_email( $email ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->users} WHERE user_email=%d";
		$query = $wpdb->prepare( $sql, $email );
		return (array)$wpdb->get_row( $query );
	}

	public function check_non_renewed_user ( $email ) {
		global $wpdb;
		$sql = "SELECT * FROM wp_gbs_non_renewed_users WHERE email=%s";
		$query = $wpdb->prepare( $sql, $email );
		return (array)$wpdb->get_row( $query );
	}

	public function update_non_renewed_user ( $email, $purchase_date ) {
		global $wpdb;
		$data = array('purchase_date' => $purchase_date);
		$where = array('email' => $email);
		$wpdb->update( 'wp_gbs_non_renewed_users', $data, $where );
		return TRUE;
	}

	public function add_non_renewed_user ( $email, $purchase_date ) {
		global $wpdb;
		$data = array('email' => $email, 'purchase_date' => $purchase_date);
		$wpdb->insert( 'wp_gbs_non_renewed_users', $data );
		return TRUE;
	}

	public function get_non_renewed_users_before_purchase_date( $last_purchase_date ) {
		global $wpdb;
		$sql = "SELECT * FROM wp_gbs_non_renewed_users WHERE purchase_date<%s";
		$query = $wpdb->prepare( $sql, $last_purchase_date, $last_purchase_date );
		return (array)$wpdb->get_results( $query );
	}

	public function get_mailchimp_groups( $api, $list_id ) {
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

	public function get_deal_purchases( $deal_id ) {
		global $wpdb;
		$sql = "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_number_of_purchases'";
		$query = $wpdb->prepare( $sql, $deal_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['meta_value'] : NULL;
	}

	public function get_deal_purchase_records( $deal_id, $post_date ) {
		global $wpdb;
		$sql = "SELECT * FROM wp_postmeta WHERE meta_value=%d AND meta_key='_deal_id' AND post_id IN (SELECT ID from wp_posts WHERE post_date > %s)";
		$query = $wpdb->prepare( $sql, $deal_id, $post_date );
		return (array)$wpdb->get_results( $query );
	}

	public function check_top_product_purchase( $deal_id ) {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_deal_id'";
		$query = $wpdb->prepare( $sql, $deal_id );
		return (array)$wpdb->get_row( $query );
	}

	public function get_top_product_purchase_user( $deal_id ) {
		global $wpdb;
		$sql = "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_user_id'";
		$query = $wpdb->prepare( $sql, $deal_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['meta_value'] : NULL;
	}

	public function get_deal_purchase_users( $deal_id ) {
		global $wpdb;
		$users = array();
		$sql = "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_number_of_purchases'";
		$query = $wpdb->prepare( $sql, $deal_id );
		$purchases = (array)$wpdb->get_row( $query );
		if (!isset($purchases) || !count($purchases) || !$purchases['meta_value']) {
			return $users;
		}
		$purchase_records = Group_Buying_Mailchimp_Model::get_deal_purchase_records( $deal_id );
		if ($purchase_records) {
			$users = array();
			foreach ($purchase_records as $key=>$value) {
				if (Group_Buying_Mailchimp_Model::check_top_product_purchase( $value->post_id )) {
					$user_id = Group_Buying_Mailchimp_Model::get_top_product_purchase_user( $value->post_id );
					if (isset($user_id) && $user_id['meta_value']) {
						$users []= Group_Buying_Mailchimp_Model::get_user($user_id['meta_value']);
					}
				}
			}
		}
		return $users;
	}

	public function get_last_cron_time( $cron_type ) {
		global $wpdb;
		$sql = "SELECT cron_time FROM wp_gbs_cron_last_timings WHERE cron_type=%s";
		$query = $wpdb->prepare( $sql, $cron_type );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['cron_time'] : NULL;
	}

	public function update_last_cron_time( $cron_type, $cron_time ) {
		global $wpdb;
		$data = array('cron_time' => $cron_time);
		$where = array('cron_type' => $cron_type);
		$wpdb->update( 'wp_gbs_cron_last_timings', $data, $where );
		return TRUE;
	}

	public function get_purchases_for_top_deal_from_time( $deal_ids, $purchase_date ) {
		global $wpdb;
		$sql = "SELECT post_id, wp_posts.post_date FROM wp_postmeta, wp_posts WHERE wp_postmeta.post_id=wp_posts.ID AND meta_key='_deal_id' AND wp_posts.post_date > %s AND wp_posts.post_type = 'gb_purchase' AND meta_value IN ( " . implode(',', $deal_ids) . ")";
		$query = $wpdb->prepare( $sql, $purchase_date );
		print_r ($query);
		return (array)$wpdb->get_results( $query );
	}

	public function get_purchases_for_top_deal_between_time( $deal_id, $purchase_start_date, $purchase_end_date ) {
		global $wpdb;
		$sql = "SELECT post_id, wp_posts.post_date FROM wp_postmeta, wp_posts WHERE wp_postmeta.post_id=wp_posts.ID AND meta_key='_deal_id' AND wp_posts.post_date BETWEEN %s AND %s AND wp_posts.post_type = 'gb_purchase' AND meta_value = %d";
		$query = $wpdb->prepare( $sql, $purchase_start_date, $purchase_end_date , $deal_id );
		return (array)$wpdb->get_results( $query );
	}

	public function get_purchases_for_top_deal_before_time( $deal_id, $purchase_start_date ) {
		global $wpdb;
		$sql = "SELECT post_id, wp_posts.post_date FROM wp_postmeta, wp_posts WHERE wp_postmeta.post_id=wp_posts.ID AND meta_key='_deal_id' AND wp_posts.post_date <= %s AND wp_posts.post_type = 'gb_purchase' AND meta_value = %d";
		$query = $wpdb->prepare( $sql, $purchase_start_date, $deal_id );
		return (array)$wpdb->get_results( $query );
	}

	public function get_purchases_for_top_deal_on_date( $deal_ids, $purchase_date ) {
		global $wpdb;
		$sql = "SELECT post_id, wp_posts.post_date FROM wp_postmeta, wp_posts WHERE wp_postmeta.post_id=wp_posts.ID AND meta_key='_deal_id' AND Date(wp_posts.post_date) = %s AND wp_posts.post_type = 'gb_purchase' AND meta_value IN ( " . implode(',', $deal_ids) . ")";
		$query = $wpdb->prepare( $sql, $purchase_date );
		return (array)$wpdb->get_results( $query );
	}

	public function get_deal_purchase_record( $post_id ) {
		global $wpdb;
		$sql = "SELECT meta_value FROM wp_postmeta WHERE post_id=%d AND meta_key='_user_id'";
		$query = $wpdb->prepare( $sql, $post_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['meta_value'] : NULL;
	}

	public function get_deal_purchase_id( $deal_id ) {
		global $wpdb;
		$sql = "SELECT post_id FROM wp_postmeta WHERE meta_value=%d AND meta_key='_deal_id'";
		$query = $wpdb->prepare( $sql, $deal_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['post_id'] : NULL;
	}

	public function get_deal_order_record( $purchase_id ) {
		global $wpdb;
		$sql = "SELECT post_id FROM wp_postmeta WHERE meta_value=%d AND meta_key='_purchase_id' AND post_id IN (SELECT ID from wp_posts WHERE post_type = 'gb_payment')";
		$query = $wpdb->prepare( $sql, $purchase_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['post_id'] : NULL;
	}

	public function get_deal_order_status( $order_id ) {
		global $wpdb;
		$sql = "SELECT post_status FROM wp_posts WHERE ID = %d";
		$query = $wpdb->prepare( $sql, $order_id );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['post_status'] : NULL;
	}

	public function get_deals(  ) {
		global $wpdb;
		$sql = "SELECT ID FROM wp_posts WHERE post_type='gb_deal' ORDER BY ID desc";
		$query = $wpdb->prepare( $sql );
		return (array)$wpdb->get_results( $query );
	}

	public function get_deal_details( $deal_id ) {
		global $wpdb;
		$sql = "SELECT * FROM wp_posts WHERE ID=%d";
		$query = $wpdb->prepare( $sql, $deal_id );
		return (array)$wpdb->get_row( $query );
	}

	public function get_deal_meta( $deal_id, $key ) {
		global $wpdb;
		$sql = "SELECT * FROM wp_postmeta WHERE post_id=%d AND meta_key=%s";
		$query = $wpdb->prepare( $sql, $deal_id, $key );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['meta_value'] : NULL;
	}

	public function get_deal_categories( $deal_id ) {
		global $wpdb;
		$sql = "SELECT wp_terms.name, wp_terms.slug FROM wp_terms, wp_term_relationships WHERE object_id=%d AND wp_terms.term_id=wp_term_relationships.term_taxonomy_id";
		$query = $wpdb->prepare( $sql, $deal_id );
		return (array)$wpdb->get_row( $query );
	}

	public function get_active_publish_deals( $timestamp ) {
		global $wpdb;
		$sql = "SELECT * FROM wp_posts, wp_postmeta WHERE post_type='gb_deal' AND post_status='publish' AND wp_posts.ID=wp_postmeta.post_id AND meta_key='_expiration_date' AND (meta_value>='%s' OR meta_value='-1') ORDER by post_date DESC";
		$query = $wpdb->prepare( $sql, $timestamp );
		return (array)$wpdb->get_results( $query );
	}

	public function get_count_deal_purchase( $deal_id ) {
		global $wpdb;
		$sql = "SELECT count(*) as purchase_count FROM wp_postmeta WHERE meta_key='_deal_id' AND meta_value=%d";
		$query = $wpdb->prepare( $sql, $deal_id, $key );
		$result = (array)$wpdb->get_row( $query );
		return $result ? $result['purchase_count'] : NULL;
	}
}
