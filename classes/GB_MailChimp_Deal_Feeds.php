<?php

/**
 * RSS Feed controller
 *
 * @package GBS
 * @subpackage Base
 * @todo  move to the deals controller
 */
class GB_MailChimp_Deal_Feeds extends Group_Buying_Controller {
	// TODO This needs to be completely rewritten and use the WP feed API.
	const DEAL_FEED_PATH_OPTION = 'gbs_deal_feed_path';
	const DEAL_FEED_QUERY_VAR = 'gb_show_deal_feed';
	const AFFILIATE_XML_QUERY_VAR = 'affiliate_xml';
	private static $deal_feed_path = 'gb_deal_feed';
	private static $instance;
	private $deal_feed = NULL;

	public static function init() {
		self::$deal_feed_path = get_option( self::DEAL_FEED_PATH_OPTION, self::$deal_feed_path );
		self::register_settings();

		add_action( 'gb_router_generate_routes', array( get_class(), 'register_registration_callback' ), 10, 1 );
	}

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Settings
		$settings = array(
			'gb_url_path_deal_feeds' => array(
				'weight' => 180,
				'settings' => array(
					self::DEAL_FEED_PATH_OPTION => array(
						'label' => self::__( 'Deal Feed Path' ),
						'option' => array(
							'label' => trailingslashit( get_home_url() ),
							'type' => 'text',
							'default' => self::$deal_feed_path
							)
						)
					)
				)
			);
		do_action( 'gb_settings', $settings, Group_Buying_UI::SETTINGS_PAGE );
	}

	/**
	 * Register the path callback
	 *
	 * @static
	 * @param GB_Router $router
	 * @return void
	 */
	public static function register_registration_callback( GB_Router $router ) {
		$args = array(
			'path' => self::$deal_feed_path,
			'title' => 'MC Deals Feed',
			'page_callback' => array( get_class(), 'on_feed_page' )
		);
		$router->add_route( self::DEAL_FEED_QUERY_VAR, $args );
	}

	/**
	 * We're on the feed page, so handle any form submissions from that page,
	 * and make sure we display the correct information (i.e., the feed)
	 *
	 * @static
	 * @return void
	 */
	public static function on_feed_page() {
		// by instantiating, we process any submitted values
		$feed = self::get_instance();
		$feed->show_gbs_feed();
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
			return $router->get_url( self::DEAL_FEED_QUERY_VAR );
		}
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
	 * @return GB_MailChimp_Deal_Feeds
	 */
	public static function get_instance() {
		if ( !( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function show_gbs_feed() {
		do_action( 'gb_show_gbs_deal_feed' );
		$this->top_deal_feed();
	}

	/**
	 * Display Deal RSS Feed
	 * @param  array  $query_args default query args
	 * @return string             fully formatted RSS feed
	 */
	public function top_deal_feed( ) {

		$items = array();

		$deals = GB_MailChimp_Utility::get_active_publish_deals(time());
		foreach ($deals as $key => $post ) {
			$deal_id = $post->ID;
			$deal = Group_Buying_Deal::get_instance( $deal_id );
			$categories = array_shift( gb_get_deal_categories($deal_id) );

			$price = $deal->get_price();
			$discount = $deal->get_amount_saved();

			// calculate discount
			$original_price = str_replace( '$', '', $deal->get_value() );
			if ( $original_price != '' ) {
				$discount = (1-($price/$original_price))*100;
				$discount = round($discount, 2);
				// $discount = gb_get_formatted_money( $original_price - $price );
			}

			$post_thumbnail = ( has_post_thumbnail( $deal_id ) ) ? wp_get_attachment_url( get_post_thumbnail_id( $deal_id ) ) : false;

			ob_start(); ?>
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
		
		<td width="284" align="left" valign="middle"><img src="'.$post_thumbnail.'" width="274" height="196" mc:edit="Box_image_2" mc:allowdesigner alt=""  /></td>
		
		<td width="20"><!-- --></td>
		
		<td width="280" valign="top" >
		<table width="220" border="0" cellpadding="0" cellspacing="0">
		
		<tr>
		<td  height="15"><!-- --></td>
		</tr>
		
		<tr>
		<td align="left" class="body-text-bold" style="font-size:16px;font-weight:bold;" mc:edit="body_bold_text" mc:allowdesigner="mc:allowdesigner" ><strong><?php echo self::cutGBSText($deal->get_title(),120) ?></strong></td>
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
		<td align="center" class="red-box-text" style="font-family:Arial, Helvetica, sans-serif;font-size:18px;font-weight:bold;" mc:edit="top_box_price_2" mc:allowdesigner="mc:allowdesigner"><?php echo $price ?>&#8364;</td>
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
		<td align="center" class="red-box-text" style="font-family:Arial, Helvetica, sans-serif;font-size:18px;font-weight:bold;" mc:edit="top_box_discount_2" mc:allowdesigner="mc:allowdesigner"><?php echo $discount ?>%</td>
		<td width="5"></td>
		</tr>
		<tr>
		<td height="15"></td>
		</tr>
		</table>
		</td>

		<?php if ( Group_Buying_Cashback_Rewards_Adv::display_reward( $deal_id ) ): ?>
			<td width="120" class="white-box" style="background:#ffffff;border:1px solid #ccc;">
			<table  cellpadding="0" cellspacing="0" border="0">
			<tr>
			<td  height="10"><!-- --></td>
			</tr>
			<tr>
			<td width="25"></td>
			<td align="left"  class="red-box-text" style="font-family:Arial, Helvetica, sans-serif;font-size:18px;font-weight:bold;"  mc:edit="top_box_discount_1" mc:allowdesigner="mc:allowdesigner">ανταμοιβές</td>
			<td width="30"></td>
			</tr>
			<tr>
			<td height="5"><!-- --></td>
			</tr>
			<tr>
			<td width="35"></td>
			<td align="center" class="red-box-text" style="font-family:Arial, Helvetica, sans-serif;font-size:18px;font-weight:bold;" mc:edit="top_box_discount_2" mc:allowdesigner="mc:allowdesigner"><?php echo gb_get_formatted_money( Group_Buying_Cashback_Rewards_Adv::get_reward( $deal ) ) ?></td>
			<td width="5"></td>
			</tr>
			<tr>
			<td height="15"></td>
			</tr>
			</table>
			</td>
		<?php endif ?>

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
		<td align="left" class="body-text-bold" mc:edit="body_bold_text" mc:allowdesigner="mc:allowdesigner" ><!--REMOVED BY DUSTIN '.$post->post_content.'--></td>
		</tr>
		<tr>
		<td  height="0"><!-- --></td>
		</tr>
		<tr>
		<td align="center" mc:edit="top_box_image" mc:allowdesigner="mc:allowdesigner" width="120px" style="background-color:#ac0003; color:#ffffff; border:1px solid #660b0e;cursor: pointer; display: block; font-family:Arial, Helvetica, sans-serif; font-size:12px; padding-top:5px; padding-bottom:5px; text-decoration:none; "><a style="color:#ffffff; font-weight:bold;text-decoration:none;" href="'.$post->guid.'" class="">Δείξε μου το Deal</a> </td>
		</tr>
		</table>
		</td>
		
		
		<td width="0"><!-- --></td>
		</tr>
		
		</table> <?php

		$html = ob_get_clean();

			$items[] = array(
				'title' => $deal->get_title(),
				//'link' => urlencode($deal['guid']),
				//'categories' => $categories ? implode(',', $categories) : '',
				'category' => $categories ? $categories->slug : '',
				//'dc:creator' => $deal['post_author'],
				'description' => $html, //$deal['post_content'],
				'content:encoded' => $html, //$deal['post_content_filtered'],
				'guid' => urlencode($post->guid),
				//'pubDate' => $deal['post_date']
			);
		}

		print Group_Buying_Feeds::get_feed( apply_filters( 'gb_deal_feed_items', $items ) );
		exit;
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