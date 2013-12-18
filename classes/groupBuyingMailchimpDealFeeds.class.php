<?php

/**
 * RSS Feed controller
 *
 * @package GBS
 * @subpackage Base
 * @todo  move to the deals controller
 */
class Group_Buying_Mailchimp_Deal_Feeds extends Group_Buying_Controller {
	// TODO This needs to be completely rewritten and use the WP feed API.
	const DEAL_FEED_PATH_OPTION = 'gbs_deal_feed_path';
	const DEAL_FEED_QUERY_VAR = 'gb_show_deal_feed';
	const ADD_TO_DEAL_FEED_QUERY_VAR = 'add_to_deal_feed';
	const AFFILIATE_XML_QUERY_VAR = 'affiliate_xml';
	private static $deal_feed_path = 'gb_deal_feed';
	private static $instance;
	private $deal_feed = NULL;

	public static function init() {
		self::$deal_feed_path = get_option( self::DEAL_FEED_PATH_OPTION, self::$deal_feed_path );
		self::register_query_var( self::ADD_TO_DEAL_FEED_QUERY_VAR, array( get_class(), 'add_to_deal_feed' ) );
		add_action( 'admin_init', array( get_class(), 'register_settings_fields' ), 50, 1 );
		self::register_path_callback( self::$deal_feed_path, array( get_class(), 'on_deal_feed_page' ), self::ADD_TO_DEAL_FEED_QUERY_VAR );

		// TODO
		// add_feed('Some Title', array( get_class(), 'added_feed_example' ) );

		// Add the deal to the RSS feed
		add_filter( 'the_excerpt_deal_rss', array( get_class(), 'deal_feed_custom_rss' ) );
		add_filter( 'the_content_deal_feed', array( get_class(), 'deal_feed_custom_rss' ) );
	}

	public static function register_settings_fields() {
		$page = Group_Buying_UI::get_settings_page();
		$section = 'gb_feed_paths';
		add_settings_section( $section, null, array( get_class(), 'display_feed_paths_section' ), $page );

		// Settings
		register_setting( $page, self::DEAL_FEED_PATH_OPTION );
		add_settings_field( self::DEAL_FEED_PATH_OPTION, self::__( 'Feed Path' ), array( get_class(), 'display_feed_path' ), $page, $section );
	}

	public static function display_feed_paths_section() {
		echo self::__( '<h4>Customize the Feed paths</h4>' );
	}

	public static function display_feed_path() {
		echo trailingslashit( get_home_url() ) . ' <input type="text" name="' . self::DEAL_FEED_PATH_OPTION . '" id="' . self::DEAL_FEED_PATH_OPTION . '" value="' . esc_attr( self::$deal_feed_path ) . '" size="40"/><br />';
	}


	/**
	 *
	 *
	 * @static
	 * @return string The URL to the feed page
	 */
	public static function get_url() {
		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$deal_feed_path );
		} else {
			$router = GB_Router::get_instance();
			return $router->get_url( 'gb_show_deal_feed' );
		}
	}

	/**
	 * We're on the feed page, so handle any form submissions from that page,
	 * and make sure we display the correct information (i.e., the feed)
	 *
	 * @static
	 * @return void
	 */
	public static function on_deal_feed_page() {
		// by instantiating, we process any submitted values
		$deal_feed = self::get_instance();
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	private function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}
	private function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}
	/**
	 *
	 *
	 * @static
	 * @return Group_Buying_Mailchimp_Deal_Feeds
	 */
	public static function get_instance() {
		if ( !( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// $this->feed = Group_Buying_Feed::get_instance(); // TODO DAN optimize
		if ( isset( $_GET[self::AFFILIATE_XML_QUERY_VAR] ) && !empty( $_GET[self::AFFILIATE_XML_QUERY_VAR] ) ) {
			$this->affiliate_xml();
		} else {
			$this->top_deal_feed();
		}
	}


	public static function added_feed_example() {
		// TODO a bunch of these need to be created, each with their own WP_Query args
		self::deal_feed( $query_args );
	}

	/**
	 * Display Deal RSS Feed
	 * @param  array  $query_args default query args
	 * @return string             fully formatted RSS feed
	 */
	public function top_deal_feed( ) {

		$items = array();

		$deals = Group_Buying_Mailchimp_Model::get_active_publish_deals(time());

		foreach ($deals as $key=>$value) {
			$deal_id = $value->ID;
			$post_thumbnail = ( has_post_thumbnail($deal_id) ) ? get_the_post_thumbnail($deal_id, 'deal-post-thumbnail-rss') : false;
			$deal = Group_Buying_Mailchimp_Model::get_deal_details($deal_id);
			$categories = Group_Buying_Mailchimp_Model::get_deal_categories($deal_id);
			$price = Group_Buying_Mailchimp_Model::get_deal_meta($deal_id, '_base_price');
			$discount = Group_Buying_Mailchimp_Model::get_deal_meta($deal_id, '_amount_saved');
			$image_id = Group_Buying_Mailchimp_Model::get_deal_meta($deal_id, '_thumbnail_id');
			$image = Group_Buying_Mailchimp_Model::get_deal_meta($image_id, '_wp_attached_file');

			$html = '
			
				<style type="text/css">
						/**
							* @tab Header
							* @section top text
							* @tip Set the styling for your top tag text.
							* @style Top Tag Text
							*/
						.top-tag-text{
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-weight:normal;
							/*@editable*/font-size:11px;
							/*@editable*/color:#6f7680;
							/*@editable*/padding:0px;
							/*@editable*/margin:0px;
						}
						/**
							* @tab Body
							* @section Body Background
							* @tip Change Your Main Body Background Color.
							* @style Body Background
							*/
						body {
							/*@editable*/margin:0px;
							/*@editable*/padding:0px;
							/*@editable*/background:#e7e7e7;
						}
						/**
							* @tab Body
							* @section Main Content Background
							* @tip Change Your Main Content Background Color.
							* @style Main Content Background
							*/
						.main-content {
							/*@editable*/background:#fafafa;
						}
						/**
							* @tab Body
							* @section Body Text Link
							* @tip Set the styling for your top tag text.
							* @style Body Text Link
							*/
						.content-text a{
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-weight:normal;
							/*@editable*/font-size:12px;
							/*@editable*/color:#cb002b;
							/*@editable*/padding:0px;
							/*@editable*/margin:0px;
							/*@editable*/text-decoration:none;
						}
						/**
							* @tab Body
							* @section Body Text
							* @tip Set the styling for your top tag text.
							* @style Body Text
							*/
						.content-text{
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-weight:normal;
							/*@editable*/font-size:13px;
							/*@editable*/color:#271f1b;
							/*@editable*/padding:0px;
							/*@editable*/margin:0px;
						}
						.content-text ul{
							margin:0px 0 0 15px;
							padding:0px;
						}
						/**
							* @tab Body
							* @section Body Red Text
							* @tip Set the styling for your top tag text.
							* @style Body Red Text
							*/
							
						img {
							/*@editable*/border:1px solid #ccc;
						}
							
						.body-red-text{
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-weight:normal;
							/*@editable*/font-size:12px;
							/*@editable*/color:#cb002b;
							/*@editable*/padding:0px;
							/*@editable*/margin:0px;
							/*@editable*/text-decoration:none;
						}
						/**
							* @tab Body
							* @section Body Red Text Link
							* @tip Set the styling for your top tag text.
							* @style Body Red Text Link
							*/
						.body-red-text a{
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-weight:normal;
							/*@editable*/font-size:12px;
							/*@editable*/color:#cb002b;
							/*@editable*/padding:0px;
							/*@editable*/margin:0px;
							/*@editable*/text-decoration:none;
						}
						/**
							* @tab Body
							* @section Main Heading
							* @tip Change Your Main Heading Style.
							* @style Main Heading
							*/
						.main-heading {
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-size:15px;
							/*@editable*/color:#c00014;
							/*@editable*/font-weight: bold;
						}
						/**
							* @tab Body
							* @section Main White Heading
							* @tip Change Your Main Heading Style.
							* @style Main White Heading
							*/
						.miss-heading {
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-size:15px;
							/*@editable*/color:#ffffff;
							/*@editable*/font-weight: bold;
						}
						/**
							* @tab Body
							* @section Body White Text
							* @tip Set the styling for your Body White Text.
							* @style Body White Text
							*/
						.miss-text{
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-weight:normal;
							/*@editable*/font-size:12px;
							/*@editable*/color:#ffffff;
							/*@editable*/padding:0px;
							/*@editable*/margin:0px;
						}
						/**
							* @tab Body
							* @section Body White Text
							* @tip Set the styling for your Body White Text.
							* @style Body White Text
							*/
						.body-text-bold{
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-weight:bold;
							/*@editable*/font-size:16px;
							/*@editable*/color:#000000;
							/*@editable*/padding:0px;
							/*@editable*/margin:0px;
						}
						/**
							* @tab Body
							* @section Body White Text Link
							* @tip Set the styling for your Body White Text Link.
							* @style Body White Text Link
							*/
						.miss-text a{
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-weight:normal;
							/*@editable*/font-size:12px;
							/*@editable*/color:#ffffff;
							/*@editable*/padding:0px;
							/*@editable*/margin:0px;
							/*@editable*/text-decoration:underline;
						}
						/**
							* @tab Body
							* @section Main Heading
							* @tip Change Your Main Heading Style.
							* @style Main Heading
							*/
						.deals-heading {
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-size:15px;
							/*@editable*/color:#000000;
							/*@editable*/font-weight: bold;
						}
						/**
							* @tab Body
							* @section Red Box
							* @tip Change Your Box Style.
							* @style Red Box
							*/
						.red-box {
							/*@editable*/background:#e9e9e9;
							/*@editable*/border:1px solid #ccc;
						}
						/**
							* @tab Body
							* @section White Box
							* @tip Change Your Box Style.
							* @style White Box
							*/
						.white-box {
							/*@editable*/background:#FFFFFF;
							/*@editable*/border: 1px solid #ccc;
						}
						.repeatable-box {
							display:inline-block;
						}
						/**
							* @tab Body
							* @section View Detail Link
							* @tip Change Your View Detail Link Style.
							* @style View Detail Link
							*/
						.view-detail {
							/*@editable*/background-color: #a30000;
							/*@editable*/border:1px solid #660b0e;
							/*@editable*/font-family: Arial, Helvetica, sans-serif;
							/*@editable*/text-align: center;
							/*@editable*/font-size: 12px;
							/*@editable*/width: 120px;
							/*@editable*/height: 35px;
							/*@editable*/color: #FFFFFF;
							/*@editable*/cursor: pointer;
							/*@editable*/text-decoration: none !important;
							/*@editable*/display: block;
							/*@editable*/line-height: 35px;
							/*@editable*/padding:0 5px;
						}
						/**
							* @tab Footer
							* @section Footer Design
							* @tip Change Your Footer Theme Style.
							* @style Viwe Detail Link
							*/
						.footer{
							/*@editable*/background:#a30000;
							/*@editable*/font-family:Arial, Helvetica, sans-serif; 
							/*@editable*/font-size:13px; 
							/*@editable*/color:#fff; 
							/*@editable*/height:30px;
						}
						/**
							* @tab Footer
							* @section Footer Links
							* @tip Change Your Footer Link Style.
							* @style Footer Links
							*/
						.footer a{
							/*@editable*/font-family:Arial, Helvetica, sans-serif; 
							/*@editable*/font-size:13px; 
							/*@editable*/color:#fff; 
							/*@editable*/height:30px;
							/*@editable*/text-decoration:none;
						}
						/**
							* @tab Footer 
							* @section Footer Text
							* @tip Change Your Footer Text.
							* @style Footer Text
							*/
						.footer-text{
							/*@editable*/font-family:Arial, Helvetica, sans-serif; 
							/*@editable*/font-size:10px; 
							/*@editable*/color:#808080; 

						}
						/**
							* @tab Footer
							* @section Footer Text Link
							* @tip Change Your Footer Link.
							* @style Footer Text Link
							*/
						.footer-text a{
							/*@editable*/font-family:Arial, Helvetica, sans-serif; 
							/*@editable*/font-size:10px; 
							/*@editable*/color:#808080;
							/*@editable*/text-decoration:underline;
						}
						.footer-text b{
							font-size:11px;
						}
						/**
							* @tab Body
							* @section Red Box Text
							* @tip Set the styling for your Box Text.
							* @style Red Box Text
							*/
						.red-box-text{
							/*@editable*/font-family:Arial, Helvetica, sans-serif; 
							/*@editable*/font-size:18px; 
							/*@editable*/font-weight:bold;
							/*@editable*/color:#000000;
						}
						.content-text1 {
							/*@editable*/font-family:Arial, Helvetica, sans-serif;
							/*@editable*/font-weight:normal;
							/*@editable*/font-size:13px;
							/*@editable*/color:#271f1b;
							/*@editable*/padding:0px;
							/*@editable*/margin:0px;
						}		
						.rssTitle, .rssSubTitle, .rssBottomLinks {
							display: none !important;
						}
						br {
							display: none !important;
						}
					</style>
			
			
	 <table width="525" height="250" border="0" cellpadding="0" cellspacing="0" bgcolor="#fafafa" class="main-content"  style="border:1px solid #ccc;">
		
		
		
		<tr>
		
		<td width="50"><!-- --></td>
		
		<td width="284" align="left" valign="middle"><img src="'.site_url().'/wp-content/uploads/'.$image.'" width="274" height="196" mc:edit="Box_image_2" mc:allowdesigner alt=""  /></td>
		
		<td width="20"><!-- --></td>
		
		<td width="280" valign="top" >
		<table width="220" border="0" cellpadding="0" cellspacing="0">
		
		<tr>
		<td  height="15"><!-- --></td>
		</tr>
		
		<tr>
		<td align="left" class="body-text-bold" style="font-size:16px;font-weight:bold;" mc:edit="body_bold_text" mc:allowdesigner="mc:allowdesigner" ><strong>'.self::cutGBSText($deal['post_title'],120).'</strong></td>
		</tr>
		<tr>
		<td  height="16"><!-- --></td>
		</tr>
			
		<tr>
		<td><!-- Red Box Start -->
		<div class="red-box" style="background:#e9e9e9;border:1px solid #ccc;">
		<table border="0" align="center"  cellpadding="0" cellspacing="0">
		<tr>
		<td height="5"><!-- --></td>
		</tr>
		<tr>
		<td width="7"><!-- --></td>
		<td width="120" class="white-box" style="background:#ffffff;border:1px solid #ccc;">
		<table cellpadding="0" cellspacing="0" border="0">
		<tr>
		<td  height="5"><!-- --></td>
		</tr>
		<tr>
		<td width="30"></td>
		<td align="left" class="red-box-text" style="font-family:Arial, Helvetica, sans-serif;font-size:18px;font-weight:bold;" mc:edit="top_box_price_1" mc:allowdesigner="mc:allowdesigner">Τιμή</td>
		<td width="30"></td>
		</tr>
		<tr>
		<td height="5"><!-- --></td>
		</tr>
		<tr>
		<td width="25"></td>
		<td align="center" class="red-box-text" style="font-family:Arial, Helvetica, sans-serif;font-size:18px;font-weight:bold;" mc:edit="top_box_price_2" mc:allowdesigner="mc:allowdesigner">'.$price.'&#8364;</td>
		<td width="20"></td>
		</tr>
		<tr>
		<td height="5"></td>
		</tr>
		</table>
		</td>
		<td width="5"><!-- --></td>
		<td width="120" class="white-box" style="background:#ffffff;border:1px solid #ccc;">
		<table  cellpadding="0" cellspacing="0" border="0">
		<tr>
		<td  height="10"><!-- --></td>
		</tr>
		<tr>
		<td width="25"></td>
		<td align="left"  class="red-box-text" style="font-family:Arial, Helvetica, sans-serif;font-size:18px;font-weight:bold;"  mc:edit="top_box_discount_1" mc:allowdesigner="mc:allowdesigner">Έκπτωση</td>
		<td width="30"></td>
		</tr>
		<tr>
		<td height="5"><!-- --></td>
		</tr>
		<tr>
		<td width="35"></td>
		<td align="center" class="red-box-text" style="font-family:Arial, Helvetica, sans-serif;font-size:18px;font-weight:bold;" mc:edit="top_box_discount_2" mc:allowdesigner="mc:allowdesigner">'.$discount.'</td>
		<td width="5"></td>
		</tr>
		<tr>
		<td height="15"></td>
		</tr>
		</table>
		</td>
		<td width="5"><!-- --></td>
		<td></td>
		</tr>
		<tr>
		<td height="5"><!-- --></td>
		</tr>
		</table>
		</div>
		</td>
		</tr>
		<tr>
		<td height="12"><!-- --></td>
		</tr>
		<tr>
		<td align="left" class="body-text-bold" mc:edit="body_bold_text" mc:allowdesigner="mc:allowdesigner" ><!--REMOVED BY DUSTIN '.$deal['post_content'].'--></td>
		</tr>
		<tr>
		<td  height="0"><!-- --></td>
		</tr>
		<tr>
		<td align="center" mc:edit="top_box_image" mc:allowdesigner="mc:allowdesigner" width="120px" style="background-color:#ac0003; color:#ffffff; border:1px solid #660b0e;cursor: pointer; display: block; font-family:Arial, Helvetica, sans-serif; font-size:12px; padding-top:5px; padding-bottom:5px; text-decoration:none; "><a style="color:#ffffff; font-weight:bold;text-decoration:none;" href="'.$deal['guid'].'" class="">Δείξε μου το Deal</a> </td>
		</tr>
		</table>
		</td>
		
		
		<td width="0"><!-- --></td>
		</tr>
		
		</table>';

			$items[] = array(
				'title' => $deal['post_title'],
//				'link' => urlencode($deal['guid']),
//				'categories' => $categories ? implode(',', $categories) : '',
				'category' => $categories ? $categories['slug'] : '',
//				'dc:creator' => $deal['post_author'],
				'description' => $html, //$deal['post_content'],
				'content:encoded' => $html, //$deal['post_content_filtered'],
				'guid' => urlencode($deal['guid']),
//				'pubDate' => $deal['post_date']
			);
		}

		print self::get_deal_feed( apply_filters( 'gb_deal_feed_items', $items ) );
		exit;
	}

	/**
	 * Display Deal XML Feed
	 * @param  array  $query_args default query args
	 * @return string             fully formatted XML feed
	 */
	public function affiliate_xml( $query_args = array() ) {
		// Get deals
		$query_args = self::query_args( $query_args );
		$deals = new WP_Query( $query_args );

		$items = array();
		while ( $deals->have_posts() ) : $deals->the_post();
			// Set ID
			$deal_id = get_the_ID();

			// Locations
			$markets = array();
			$market_names = array();
			$market_array = array();
			$market_name_array = array();
			$locations = gb_get_deal_locations( $deal_id );
			foreach ( $locations as $location ) {
				$market_array[] = $location->slug;
				$market_name_array[] = $location->name;
			}
			$markets = implode( ',', $market_array );
			$market_names = implode( ',', $market_name_array );

			// Categories
			$categories = array();
			$category_names = array();
			$category_array = array();
			$category_name_array = array();
			$cats = gb_get_deal_categories( $deal_id );
			foreach ( $cats as $cat ) {
				$category_array[] = $cat->slug;
				$category_name_array[] = $cat->name;
			}
			$categories = implode( ',', $category_array );
			$category_names = implode( ',', $category_name_array );

			// thumbnails
			if ( has_post_thumbnail() ) {
				$post_thumbnail_id = get_post_thumbnail_id( $deal_id );
				if ( $post_thumbnail_id ) {
					$image_array = wp_get_attachment_image_src( $post_thumbnail_id, 'post-thumbnail', false );
					$image_url = $image_array[0];
				}
			}
			// Content
			$the_content = ( gb_get_rss_excerpt() ) ? gb_get_rss_excerpt() : get_the_content();

			// Build Array
			$items[$deal_id] = array(
				'id' => $deal_id,
				'market' => $markets,
				'url' => get_permalink(),
				'image_url' => $image_url,
				'title' => get_the_title(),
				'highlights' => gb_get_highlights(),
				'restrictions' => gb_get_fine_print(),
				'description' => $the_content,
				'value' => gb_get_formatted_money( gb_get_deal_worth() ),
				'price' => gb_get_formatted_money( gb_get_price() ),
				'required_qty' => gb_get_min_purchases(),
				'purchased_qty' => gb_get_number_of_purchases(),
				'category' => $categories,
				'purchase_link' => gb_get_add_to_cart_url(),
				'savings' => gb_get_amount_saved()
			);
			// If has an expiration
			if ( gb_has_expiration( $deal_id ) ) {
				$items[$deal_id] += array(
					'ending_time' => gb_get_deal_end_date( DATE_ATOM ),
				);
			}
			// If item has an associated merchant
			if ( gb_has_merchant( $deal_id ) ) {
				$items[$deal_id] += array(
					'merchant' => gb_get_merchant_name( gb_get_merchant_id() ),
					'address' => gb_get_merchant_street( gb_get_merchant_id() ),
					'city' => gb_get_merchant_city( gb_get_merchant_id() ),
					'state' => gb_get_merchant_state( gb_get_merchant_id() ),
					'zip' => gb_get_merchant_zip( gb_get_merchant_id() ),
					'country' => gb_get_merchant_country( gb_get_merchant_id() ),
					'phone' => gb_get_merchant_phone( gb_get_merchant_id() ),
				);
			}
		endwhile;
		// filter items
		$items = apply_filters( 'gb_affiliate_xml_items', $items, $query_args );
		$items = apply_filters( 'gb_affiliate_xml_items-' . $_GET[self::AFFILIATE_XML_QUERY_VAR], $items, $query_args );

		// Print a XML feed
		print self::get_xml_feed( $items );
		
		exit();
	}

	/**
	 * Build query args for WP_Query based on URL query variables
	 * @param  array $query_args default set of query_args
	 * @return array             
	 */
	public static function query_args( $query_args = null ) {  // TODO DAN optimize

		$post_type = ( isset( $_GET['post_type'] ) || $query_args['post_type'] != '' ) ? $_GET['post_type'] : Group_Buying_Deal::POST_TYPE ; // this will break in almost every case.

		$meta = array();
		if ( isset( $_GET['expired'] ) ) {
			if ( $_GET['expired'] != 'any' || $_GET['expired'] != 'all' ) { // unless it's set to everything we need to only show expired
				$meta[] = array(
					'key' => '_expiration_date',
					'value' => current_time( 'timestamp' ),
					'compare' => '<' );
			}
		} else { // default to current deals.
			$meta[] = array(
				'key' => '_expiration_date',
				'value' => array( 0, current_time( 'timestamp' ) ),
				'compare' => 'NOT BETWEEN' );
		}
		$query_args = array(
			'post_type' => $post_type,
			'post_status' => 'publish',
			'meta_query' => $meta,
		);
		if ( isset( $_GET['location'] ) && $_GET['location'] != '' ) {
			$query_args[gb_get_location_tax_slug()] = $_GET['location'];
		}

		// Filter the Query Args
		$query_args = apply_filters( 'gb_feed_query_args', $query_args );
		if ( isset( $_GET[self::AFFILIATE_XML_QUERY_VAR] ) ) {
			$query_args = apply_filters( 'gb_affiliate_feed_query_args-' . $_GET[self::AFFILIATE_XML_QUERY_VAR], $query_args );
		}
		return $query_args;
	}

	/**
	 * Build feed for deals, differs from affiliate_xml
	 * @param  array  $items array of items to build nodes from
	 * @return string        RSS formatted XML feed
	 */
	public static function get_deal_feed( $items = array() ) {

		if ( empty( $items ) ) return; // nothing to do.

		$shift = $items;
		$first_item = array_shift( $shift );
		ob_start();
		header( "Content-Type:text/xml" );
		?>
			<rss version="2.0"
				xmlns:content="http://purl.org/rss/1.0/modules/content/"
				xmlns:dc="http://purl.org/dc/elements/1.1/"
				xmlns:atom="http://www.w3.org/2005/Atom"
				xmlns:sy="http://purl.org/rss/1.0/modules/syndication/">
				<channel>
		<?php 
					if ( !empty( $first_item['pubDate'] ) ) {
						echo '<pubDate>'.$first_item['pubDate'].'</pubDate>';
					}

					foreach ( $items as $item ) {
						echo "<item>\n";
						foreach ( $item as $node => $content ) {
							if ( $node == "content:encoded" || $node == "description" ) {
								echo "<".$node."><![CDATA[".$content."]]></".$node.">\n";
							} else {
								echo "<".$node.">".$content."</".$node.">\n";
							}
						}
						echo "</item>\n\n";
					} 
		?>
				</channel>
			</rss>
		<?php
		$deal_feed = ob_get_clean();
		return apply_filters( 'gb_get_feed', $deal_feed, $items );
	}

	/**
	 * Build feed for deals, differs from get_feed since it's not RSS formatted
	 * @param  array  $items array of items to build nodes from
	 * @return string        an XML feed
	 */
	public static function get_xml_feed( $items = array() ) {

		if ( empty( $items ) ) return; // nothing to do.

		ob_start();
		header( "Content-Type:text/xml" );
			echo "<itemset>\n";
				foreach ( $items as $item ) {
					echo "<item>\n";
					foreach ( $item as $node => $content ) {
						if ( in_array( $node, array( "highlights", "restrictions", "description", "merchant", "address", "city", "state" , "zip" , "country", "phone", "excerpt" ) ) ) {
							echo "<".$node."><![CDATA[".$content."]]></".$node.">\n";
						} else {
							echo "<".$node.">".$content."</".$node.">\n";
						}
					}
					echo "</item>\n\n";
				}
			echo "</itemset>\n";
		$deal_feed = ob_get_clean();
		$filter_feed = apply_filters( 'gb_get_xml_feed', $deal_feed, $items );
		if ( isset( $_GET[self::AFFILIATE_XML_QUERY_VAR] ) ) {
			$filter_feed = apply_filters( 'gb_get_xml_feed-' . $_GET[self::AFFILIATE_XML_QUERY_VAR], $filter_feed, $items );
		}
		return $filter_feed;
	}

	/**
	 * Filter the default WP Feed for all deals and include some meta info
	 * @param  string $content full content of post
	 * @return string          content
	 */
	public function deal_feed_custom_rss( $content ) {
		global $post;
		if ( has_post_thumbnail( $post->ID ) ) {
			$content = '<p>' . get_the_post_thumbnail( $post->ID, 'gbs_voucher_thumb' ) . '</p>';
		}
		if ( get_post_type( $post->ID ) == Group_Buying_Deal::POST_TYPE ) {
			$content = ( has_post_thumbnail( get_the_ID() ) ) ? get_the_post_thumbnail( get_the_ID(), 'gbs_voucher_thumb' ) : '';
			$content .= '<p><strong>'.gb_get_formatted_money( gb_get_deal_worth() ).'</strong></p>';
			$content .= '<p>'.self::__( 'Expires On:' ).' '.gb_get_deal_end_date().'<br/>'.sprintf( self::__( '<span>%s</span> buyers!' ), gb_get_number_of_purchases() ).'<br/>'.self::__( 'Savings:' ).' '.gb_get_amount_saved().'</p>';
			if ( gb_get_rss_excerpt() != '' ) {
				$content .= gb_get_rss_excerpt();
			} else {
				$content .= get_the_content();
			}
			$content = apply_filters( 'deal_feed_custom_rss_content', $content, $post->ID );
		} else {
			$content .= get_the_content();
		}
		return apply_filters( 'gb_deal_feed_custom_rss', $content );
	}
	public static function cutGBSText($value, $length)
	{   
		if(is_array($value)) list($string, $match_to) = $value;
		else { $string = $value; $match_to = $value{0}; }

		$match_start = stristr($string, $match_to);
		$match_compute = strlen($string) - strlen($match_start);

		if (strlen($string) > $length)
		{
			if ($match_compute < ($length - strlen($match_to)))
			{
				$pre_string = substr($string, 0, $length);
				$pos_end = strrpos($pre_string, " ");
				if($pos_end === false) $string = $pre_string."...";
				else $string = substr($pre_string, 0, $pos_end)."...";
			}
			else if ($match_compute > (strlen($string) - ($length - strlen($match_to))))
			{
				$pre_string = substr($string, (strlen($string) - ($length - strlen($match_to))));
				$pos_start = strpos($pre_string, " ");
				$string = "...".substr($pre_string, $pos_start);
				if($pos_start === false) $string = "...".$pre_string;
				else $string = "...".substr($pre_string, $pos_start);
			}
			else
			{       
				$pre_string = substr($string, ($match_compute - round(($length / 3))), $length);
				$pos_start = strpos($pre_string, " "); $pos_end = strrpos($pre_string, " ");
				$string = "...".substr($pre_string, $pos_start, $pos_end)."...";
				if($pos_start === false && $pos_end === false) $string = "...".$pre_string."...";
				else $string = "...".substr($pre_string, $pos_start, $pos_end)."...";
			}

			$match_start = stristr($string, $match_to);
			$match_compute = strlen($string) - strlen($match_start);
		}
	   
		return $string;
	} 
}
