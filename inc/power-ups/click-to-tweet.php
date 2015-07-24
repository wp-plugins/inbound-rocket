<?php
/**
	* Power-up Name: Click To Tweet
	* Power-up Class: WPClickToTweet
	* Power-up Menu Text:
	* Power-up Menu Link: settings
	* Power-up Slug: click_to_tweet
	* Power-up URI: http://inboundrocket.co/features/click-to-tweet/
	* Power-up Description: This power-up allows you to easily create tweetable content for your readers.
	* Power-up Icon: powerup-icon-click-to-tweet
	* Power-up Icon Small: powerup-icon-click-to-tweet
	* First Introduced: 1.0
	* Power-up Tags: Sharing
	* Auto Activate: No
	* Permanently Enabled: No
	* Hidden: No
	* cURL Required: No
	* Options Name: inboundrocket_ctt_options	
*/
if(!defined('ABSPATH') || !defined('INBOUNDROCKET_PATH')) die('Security');

//=============================================
// Define Constants
//=============================================

if ( !defined('INBOUNDROCKET_CLICK_TO_TWEET_PATH') )
    define('INBOUNDROCKET_CLICK_TO_TWEET_PATH', INBOUNDROCKET_PATH . '/inc/power-ups/click-to-tweet');

if ( !defined('INBOUNDROCKET_CLICK_TO_TWEET_PLUGIN_DIR') )
	define('INBOUNDROCKET_CLICK_TO_TWEET_PLUGIN_DIR', INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/click-to-tweet');

if ( !defined('INBOUNDROCKET_CLICK_TO_TWEET_PLUGIN_SLUG') )
	define('INBOUNDROCKET_CLICK_TO_TWEET_PLUGIN_SLUG', basename(dirname(__FILE__)));

//=============================================
// Include Needed Files
//=============================================

require_once(INBOUNDROCKET_CLICK_TO_TWEET_PLUGIN_DIR . '/admin/click-to-tweet-admin.php');

//=============================================
// WPInboundRocketClickToTweet Class
//=============================================
class WPClickToTweet extends WPInboundRocket {
	
	var $admin;
	var $options;
	var $share_id;

	/**
	 * Class constructor
	 */
	function __construct ( $activated )
	{
		//=============================================
		// Hooks & Filters
		//=============================================

		if ( ! $activated )
			return false;

		global $inboundrocket_clicktotweet;
		$inboundrocket_clicktotweet = $this;

		add_action( 'wp_enqueue_scripts', array($this,'inboundrocket_ctt_scripts') );
		
		//add_filter( 'the_content', array($this, 'inboundrocket_ctt_replace_tags'), 1);
		
		add_action( 'wp_ajax_click_to_tweet_track', array($this,'inboundrocket_ctt_track_callback') );
		add_action( 'wp_ajax_nopriv_click_to_tweet_track', array($this,'inboundrocket_ctt_track_callback') );
		
		add_shortcode( 'clicktotweet', array($this,'inboundrocket_ctt_shortcode') );
	}

	public function admin_init ( )
	{
		$this->admin = WPClickToTweetAdmin::init();
	}

	function power_up_setup_callback ( )
	{
		$this->admin->power_up_setup_callback();
	}
    
    function inboundrocket_ctt_scripts ()
    {
	    $this->inboundrocket_ctt_styles();
	 	
	 	if (INBOUNDROCKET_ENABLE_DEBUG==true) { 
		 	wp_enqueue_script( "inboundrocket_ctt_script", INBOUNDROCKET_CLICK_TO_TWEET_PATH . '/js/click-to-tweet.js', array( 'jquery' ) );
		} else { 
			wp_enqueue_script( "inboundrocket_ctt_script", INBOUNDROCKET_CLICK_TO_TWEET_PATH . '/js/click-to-tweet.min.js', array( 'jquery' ) ); 
		}
		wp_localize_script( 'inboundrocket_ctt_script', 'inboundrocket_ctt_js', array(
        	'ajaxurl'       => admin_url( 'admin-ajax.php' ),
			'nextNonce'     => wp_create_nonce( 'inboundrocket-ctt-nonce' ),
		));		    
    }
    
    function inboundrocket_ctt_styles()
    {
		if (INBOUNDROCKET_ENABLE_DEBUG==true) { wp_register_style('inboundrocket_ctt_style', INBOUNDROCKET_CLICK_TO_TWEET_PATH . '/css/click-to-tweet.css', false, '0.1'); }
		else { wp_register_style('inboundrocket_ctt_style', INBOUNDROCKET_CLICK_TO_TWEET_PATH . '/css/click-to-tweet.min.css', false, '0.1'); }
		wp_enqueue_style('inboundrocket_ctt_style');
	}
	
	/**
	* Shorten text lenth to 100 characters.
	*/
	public function shorten($input, $length, $ellipses = true, $strip_html = true) {
		    if ($strip_html) {
		        $input = strip_tags($input);
		    }
		    if (strlen($input) <= $length) {
		        return $input;
		    }
		    $last_space = strrpos(substr($input, 0, $length), ' ');
		    $trimmed_text = substr($input, 0, $last_space);
		    if ($ellipses) {
		        $trimmed_text .= '...';
		    }
		    return $trimmed_text;
		}

	/**
	 * Replacement of Tweet tags with the correct HTML
	 */
	public function tweet($text) {
		$this->ctt_options = get_option('inboundrocket_ctt_options');
		
	    $handle = $this->ctt_options['ir_ctt_twitter_username'];
	    
	    if (!empty($handle)) {
	        $handle_code = "&via=".$handle."&related=".$handle;
	    } else {
		    $handle_code = '';
	    }
	    $short = $this->shorten(esc_attr($text), 100);
	    $str = "<div class=\"ir-tweet-clear\"></div><div class=\"ir-click-to-tweet\"><div class=\"ir-ctt-text\"><a href=\"https://twitter.com/share?text=".urlencode($short).$handle_code."&url=".get_permalink()."\" data-text=\"".$short."\" class=\"ctt-action\" target=\"_blank\">".$short."</a></div>";
       
	    if ( ! inboundrocket_check_premium_user() )   
            $str .= "<a href=\"http://inboundrocket.co/?utm_source=".$_SERVER['HTTP_HOST']."&utm_medium=click-to-tweet&utm_campaign=logo\" title=\"".  __('Powered by Inbound Rocket - You write. We will turn them into leads','inboundrocket')."\" target=\"_blank\" class=\"inboundrocket-ctt-branding\">&nbsp;</a>";

        $str .= "<a href=\"https://twitter.com/share?text=".urlencode($short).$handle_code."&url=".get_permalink()."\" data-text=\"".$short."\" class=\"ir-ctt-btn ctt-action\" target=\"_blank\">".__('Click To Tweet','inboundrocket')."</a><div class=\"ir-ctt-tip\"></div></div>";
        return $str;
	}
	
	/**
	 * Add WordPress Shortcode for Click To Tweet
	 */
	public function inboundrocket_ctt_shortcode( $atts, $content = "" ) {
		$atts = shortcode_atts( array(
			'text' => 'this is a sample tweet',
			'type' => 'tweet'
		), $atts, 'clicktotweet' );

		$text = isset($content) ? $content : $atts['text']; 

		switch($atts['type']){
			case "tweet":
				return $this->tweet($text);
			break;
			case "feed":
				return $this->tweet_feed($text);
			break;
		}

		return false;
	}

	/**
	 * Replacement of Tweet tags with the correct HTML for a rss feed
	 */
	public function tweet_feed($text) {
	    $handle = get_option('twitter-handle');
	    if (!empty($handle)) {
	        $handle_code = "&via=".$handle."&related=".$handle;
	    }
	    $short = $this->shorten(esc_attr($text), 100);
	    return "<hr /><p><em>".$short."</em><br /><a href=\"https://twitter.com/share?text=".urlencode($short).$handle_code."&url=".get_permalink()."\" target=\"_blank\">".__('Click To Tweet','inboundrocket')."</a></p><hr />";
	}

	/**
	 * Regular expression to locate tweet tags
	 * retired function for shortcode callback
	public function inboundrocket_ctt_replace_tags($content) {
		if (!is_feed()) {
			$content = preg_replace_callback("/\[tweet text=\"(.*?)\"]/i", array($this, 'tweet'), $content);
		} else {
			$content = preg_replace_callback("/\[tweet text=\"(.*?)\"]/i", array($this, 'tweet_feed'), $content);
		}
		return $content;
	}
	*/
	
	function inboundrocket_ctt_track_callback()
	{	
		if (!defined('DOING_AJAX') || !DOING_AJAX) die('Busted!');
		if (!wp_verify_nonce($_POST['nonce'],'inboundrocket-ctt-nonce')) die('Busted!');
		
		$post_id = intval(sanitize_text_field($_POST['postid']));
		$text = sanitize_text_field($_POST['text']);
		$type = sanitize_text_field($_POST['type']);
		
		if(!empty($text) && !empty($type)){
			$success = $this->inboundrocket_ctt_track($post_id, $text, $type);
			$status = $success ? 200 : 500;
		} else {
			$status = 500;
		}
		
		$return = array(
			'status' => $status,
		);

		echo json_encode($return);
		wp_die();
	}

	private function inboundrocket_ctt_create_hashshare($options)
	{
    	global $wpdb;
    	
    	$post_id = intval($options['post_id']);
    	$blog_id = intval($options['blog_id']);
		$type = sanitize_text_field($options['type']);
		$text = sanitize_text_field($options['text']);
		
		$wpdb->insert($wpdb->ir_shares, array(
        	'lead_hashkey' => isset($_COOKIE['ir_hash']) ? sanitize_text_field($_COOKIE['ir_hash']) : null,
       		'share_type' => $type,
       		'share' => $text,
       		'post_id' => $post_id,
       		'blog_id' => $blog_id,
		));

    	$this->share_id = $wpdb->insert_id;
    	
    	if ($this->share_id > 0) {
       		return $this->share_id;
    	}

    	return false;
	}
	
	private function inboundrocket_ctt_get_share_id($post_id, $text){
		
		global $wpdb;
		
		$post_id = intval($post_id);
		$text = sanitize_text_field($text);
		
		// Pull out our text and put into an array
		$id = $wpdb->get_var( 
			$wpdb->prepare(
				"SELECT share_id FROM {$wpdb->ir_shares} WHERE post_id = %d AND share = %s",
				$post_id,
				$text
			)
		);
		return $id;
		
	}

	private function inboundrocket_ctt_track($post_id, $text, $type)
	{
    	global $wpdb;
		
		$blog_id = get_current_blog_id();
		$post_id = intval($post_id);
		$type = sanitize_text_field($type);
		$text = sanitize_text_field($text);

		$this->share_id = $this->inboundrocket_ctt_get_share_id($post_id, $text);
		if (null === $this->share_id) {
			$options = array(
				'post_id' => $post_id,
				'blog_id' => $blog_id,
				'type' => $type,
				'text' => $text,
			);
			$this->share_id = $this->inboundrocket_ctt_create_hashshare($options);
    	}
    	
		$num_created = $wpdb->insert($wpdb->ir_sharer_stats, array(
       		'share_id' => $this->share_id,
			'type' => $type,
		));

		return $num_created > 0;
	}	
}

//=============================================
// InboundRocket Init
//=============================================

global $inboundrocket_clicktotweet;
?>