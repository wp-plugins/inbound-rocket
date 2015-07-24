<?php
/**
	* Power-up Name: Selection Sharer
	* Power-up Class: WPSelectionSharer
	* Power-up Menu Text:
	* Power-up Menu Link: settings
	* Power-up Slug: selection_sharer
	* Power-up URI: http://inboundrocket.co/features/selection-sharer/
	* Power-up Description: Medium like popover menu to share on Twitter or by email any text selected on the page.
	* Power-up Icon: power-up-icon-selection-sharer
	* Power-up Icon Small: power-up-icon-selection-sharer_small
	* First Introduced: 1.0
	* Power-up Tags: Sharing
	* Auto Activate: Yes
	* Permanently Enabled: No
	* Hidden: No
	* cURL Required: No
	* Options Name: inboundrocket_ss_options
*/
if(!defined('ABSPATH') || !defined('INBOUNDROCKET_PATH')) die('Security');

//=============================================
// Define Constants
//=============================================

if ( !defined('INBOUNDROCKET_SELECTION_SHARER_PATH') )
    define('INBOUNDROCKET_SELECTION_SHARER_PATH', INBOUNDROCKET_PATH . '/inc/power-ups/selection-sharer');

if ( !defined('INBOUNDROCKET_SELECTION_SHARER_PLUGIN_DIR') )
	define('INBOUNDROCKET_SELECTION_SHARER_PLUGIN_DIR', INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/selection-sharer');

if ( !defined('INBOUNDROCKET_SELECTION_SHARER_PLUGIN_SLUG') )
	define('INBOUNDROCKET_SELECTION_SHARER_PLUGIN_SLUG', basename(dirname(__FILE__)));

//=============================================
// Include Needed Files
//=============================================

require_once(INBOUNDROCKET_SELECTION_SHARER_PLUGIN_DIR . '/admin/selection-sharer-admin.php');

//=============================================
// WPSelectionSharer Class
//=============================================
class WPSelectionSharer extends WPInboundRocket {
	
	var $admin;
	var $options;

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

		global $inboundrocket_selectionsharer;
		$inboundrocket_selectionsharer = $this;
		
		add_action( 'wp_enqueue_scripts', array($this,'inboundrocket_ss_scripts') );
		
		add_action( 'wp_ajax_selection_sharer_gettext', array($this,'inboundrocket_ss_textlist_callback') );
		add_action( 'wp_ajax_nopriv_selection_sharer_gettext', array($this,'inboundrocket_ss_textlist_callback') );
		
		add_action( 'wp_ajax_selection_sharer_track', array($this,'inboundrocket_ss_track_callback') );
		add_action( 'wp_ajax_nopriv_selection_sharer_track', array($this,'inboundrocket_ss_track_callback') );
		
		add_action( 'wp_ajax_selection_sharer_settings', array($this,'inboundrocket_ss_settings_callback') );
		add_action( 'wp_ajax_nopriv_selection_sharer_settings', array($this,'inboundrocket_ss_settings_callback') );
		
		add_action( 'wp_ajax_selection_sharer_shorturl', array($this,'inboundrocket_ss_shorturl_callback') );
		add_action( 'wp_ajax_nopriv_selection_sharer_shorturl', array($this,'inboundrocket_ss_shorturl_callback') );
		
		if ( ! inboundrocket_check_premium_user() ) add_action( 'wp_head', array($this,'inboundrocket_ss_premium') );
	}

	public function admin_init()
	{
		$this->admin = WPSelectionSharerAdmin::init();
	}
	
	public function inboundrocket_ss_premium()
	{
		echo '<script type="text/javascript">
			jQuery(document).ready(function($){
		    	$(\'<div class="inboundrocket-ss-branding"><a href="http://inboundrocket.co/?utm_source='.$_SERVER['HTTP_HOST'].'&utm_medium=selectionsharer&utm_campaign=logo" title="'.  __("Powered by Inbound Rocket - You write. We will turn them into leads",'inboundrocket') .'" class="popup-link" target="_blank">'.__('powered by','inboundrocket').' Inbound Rocket</a></div>\').insertAfter(\'body\').hide();
		});
		</script>';
	}

	function power_up_setup_callback()
	{
		$this->admin->power_up_setup_callback();
	}
	
	function inboundrocket_ss_textlist_callback()
	{
		global $wpdb;
		
		// Pull out our text and put into an array
		$list = $wpdb->get_results( 
			"SELECT share,share_id,SUM(share_type='ss-facebook-text') as fbcount,SUM(share_type='ss-twitter-text') as twcount,SUM(share_type='ss-linkedin-text') as licount,SUM(share_type='ss-email-text') as emcount
		FROM {$wpdb->ir_shares}
		GROUP BY share
		ORDER BY share_id", ARRAY_A
		);
		
		$new_list = array();
		foreach($list as $item){
			$result = $this->inboundrocket_ss_get_share_type($item['share_id']);
			if($result=="ss-twitter-text") $new_list[] = $item;
		}
		
		wp_die( json_encode($new_list) );
	}
	
	function inboundrocket_ss_settings_callback()
	{	
		if (!defined('DOING_AJAX') || !DOING_AJAX) die('Busted!');
		$nonce = $_POST['nonce'];
		if (!wp_verify_nonce($nonce,'inboundrocket-ss-nonce')) die('Busted!');
		
		$options = isset($this->options) ? $this->options : get_option('inboundrocket_ss_options');
		
		// Remove API keys and logins for security purposes
		if(isset($options['ir_ss_short_url_service']) && $options['ir_ss_short_url_service']=='bitly'){
			unset($options['ir_ss_bitly_login']);
			unset($options['ir_ss_bitly_api_key']);
		} elseif(isset($options['ir_ss_short_url_service']) && $options['ir_ss_short_url_service']=='awesm') {
			unset($options['ir_ss_awesm_api_key']);
		}
		wp_die( json_encode($options) );
	}
	
	function inboundrocket_ss_get_share_type($share_id)
	{
    	global $wpdb;

		$share_id = intval(sanitize_text_field($share_id));

    	$sql = $wpdb->prepare(
        	"SELECT share_type FROM {$wpdb->ir_shares} WHERE share_id = %d LIMIT 1",
        	$share_id
		);
    	$type = $wpdb->get_var($sql);
    	if ($type) {
       		return $type;
    	}   

    	return null;
	}
	
	function inboundrocket_ss_track_callback()
	{	
		if (!defined('DOING_AJAX') || !DOING_AJAX) die('Busted!');
		$nonce = $_POST['nonce'];
		if (!wp_verify_nonce($nonce,'inboundrocket-ss-nonce')) die('Busted!');
		
		$post_id = intval(sanitize_text_field($_REQUEST['postid']));
		$text = sanitize_text_field($_REQUEST['text']);
		$type = sanitize_text_field($_REQUEST['type']);
		
		if($type=='twitter') { $type = 'ss-twitter-text';} elseif($type=='facebook') { $type = 'ss-facebook-text'; } elseif($type=='linkedin') { $type = 'ss-linkedin-text'; } elseif($type=='email') { $type = 'ss-email-text'; }
		
		if(!empty($text) && !empty($type)){
			$success = $this->inboundrocket_ss_track($post_id, $text, $type);
			$status = $success ? 200 : 500;
		} else {
			$status = 500;
		}
		
		$return = array(
			'status' => $status,
		);

		wp_die( json_encode($return) );
	}
	
	function inboundrocket_ss_shorturl_callback()
	{
		if (!defined('DOING_AJAX') || !DOING_AJAX) die('Busted!');
		$nonce = $_POST['nonce'];
		if (!wp_verify_nonce($nonce,'inboundrocket-ss-nonce')) die('Busted!');
		
		$options = isset($this->options) ? $this->options : get_option('inboundrocket_ss_options');
		
		$url = sanitize_text_field($_POST['url']); 
		$type = sanitize_text_field($_POST['type']);
		
		if( isset($options['ir_ss_campaign_variables']) && $options['ir_ss_campaign_variables']===1 && $type == 'twitter'){
			$url .= '?utm_source=inbound-rocket&utm_medium=selection-sharer&utm_campaign=twitter';
		} elseif( isset($options['ir_ss_campaign_variables']) && $options['ir_ss_campaign_variables']===1 && $type == 'facebook') {
			$url .= '?utm_source=inbound-rocket&utm_medium=selection-sharer&utm_campaign=facebook';
		} elseif( isset($options['ir_ss_campaign_variables']) && $options['ir_ss_campaign_variables']===1 && $type == 'linkedin') {
			$url .= '?utm_source=inbound-rocket&utm_medium=selection-sharer&utm_campaign=linkedin';			
		} elseif( isset($options['ir_ss_campaign_variables']) && $options['ir_ss_campaign_variables']===1 && $type == 'email') {
			$url .= '?utm_source=inbound-rocket&utm_medium=selection-sharer&utm_campaign=email';
		}
		
		$shortURL = $this->inboundrocket_ss_shorten($url);
		
		$return = array(
			'shorturl' => $shortURL,
			'longurl' => $url,
		);
		
		wp_die( json_encode($return) );
	}

    /**
	 * Url shorten connector
	 */
	function inboundrocket_ss_shorten($url){
		
		$options = get_option('inboundrocket_ss_options');
		
		$ir_ss_short_url_service = isset($options['ir_ss_short_url_service']) ? esc_attr( $options['ir_ss_short_url_service'] ) : '';
		
		$ir_ss_campaign_variables = isset($options['ir_ss_campaign_variables']) ? esc_attr( $options['ir_ss_campaign_variables'] ) : '0';
		if($ir_ss_campaign_variables==1) $url_add = ''; else $url_add = '';
		
		$ir_ss_awesm_api_key = isset($options['ir_ss_awesm_api_key']) ? esc_attr( $options['ir_ss_awesm_api_key'] ) : '';
		$ir_ss_awesm_tool = isset($options['ir_ss_awesm_tool']) ? esc_attr( $options['ir_ss_awesm_tool'] ) : '';
		$ir_ss_awesm_channel = isset($options['ir_ss_awesm_channel']) ? esc_attr( $options['ir_ss_awesm_channel'] ) : '';
		
		$ir_ss_bitly_login = isset($options['ir_ss_bitly_login']) ? esc_attr( $options['ir_ss_bitly_login'] ) : '';
		$ir_ss_bitly_api_key = isset($options['ir_ss_bitly_api_key']) ? esc_attr( $options['ir_ss_bitly_api_key'] ) : '';
		
		if (!preg_match('/((https?|ftps?):\/\/)((\d{1,3}\.){3}\d{1,3}|[a-z0-9-\.]{1,255}\.[a-zA-Z]{2,6})(:\d{1,5})?(\/\S*)*/',$url))
        {
            return null;
        }
        
        switch($ir_ss_short_url_service){
	        case "bitly":
	        
	        	$query = array(
					"version" => "2.0.1",
					"longUrl" => $url,
					"login" => trim($ir_ss_bitly_login),
					"apiKey" => trim($ir_ss_bitly_api_key)
				);
				$query = http_build_query($query);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "http://api.bitly.com/v3/shorten?".$query);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

				$response = curl_exec($ch);
				curl_close($ch);

				$response = json_decode($response);
				if( $response->status_txt == "OK") return $response->data->url; else return $response;
	        
	        break;
	        case "awesm":
	        
		        $params = array(
				        'v' => 3,
				        'url' => $url,
				        'key' => $ir_ss_awesm_api_key,
				        'tool' => $ir_ss_awesm_tool,
				        'channel' => $ir_ss_awesm_channel    
				);
				
				$new_link_request = curl_init();
				curl_setopt($new_link_request, CURLOPT_URL, 'http://api.awe.sm/url.txt');
				curl_setopt($new_link_request, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($new_link_request, CURLOPT_TIMEOUT, 5);
				curl_setopt($new_link_request, CURLOPT_POST, 1);
				curl_setopt($new_link_request, CURLOPT_POSTFIELDS, $params);
				$new_link = curl_exec($new_link_request);
				$response_code = curl_getinfo($new_link_request, CURLINFO_HTTP_CODE);
				curl_close($new_link_request);
				
				if ($response_code != 200) { // Link created
	                 // Error creating link
				    return "API error: HTTP {$response_code}: {$new_link}";
				}
		        
 	 			return $new_link;
	        
	        break;
	        case "googl":
	        
	        	// default to goo.gl
				$curl = curl_init('https://www.googleapis.com/urlshortener/v1/url');
				curl_setopt_array($curl, array (
  					CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
  					CURLOPT_RETURNTRANSFER => 1,
  					CURLOPT_TIMEOUT        => 5,
  					CURLOPT_CONNECTTIMEOUT => 0,
  					CURLOPT_POST           => 1,
  					CURLOPT_SSL_VERIFYHOST => 0,
  					CURLOPT_SSL_VERIFYPEER => 0,
  					CURLOPT_POSTFIELDS     => '{"longUrl": "' . $url . '"}')
  				);
  				$response = json_decode(curl_exec($curl), true);
  				curl_close($curl);
  				return (!empty($response['id']) ? $response['id'] : 'goo.gl failed');	
			
	        break;
	        default:
	        	return $url;
	        break;
        }
	}
	
	function inboundrocket_ss_getURL()
	{
		$protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === FALSE ? 'http' : 'https';
		$host     = $_SERVER['HTTP_HOST'];
		$script   = $_SERVER['SCRIPT_NAME'];
		$params   = $_SERVER['QUERY_STRING'];
		$currentUrl = $protocol . '://' . $host . $script;
		if(isset($params) && !empty($params)) $currentUrl .= '?' . $params;
		return $currentUrl;
	}

	function inboundrocket_ss_scripts()
	{	
		$this->inboundrocket_ss_styles();
	 	if (INBOUNDROCKET_ENABLE_DEBUG==true) { wp_enqueue_script( "inboundrocket_ss_script", INBOUNDROCKET_SELECTION_SHARER_PATH . '/js/selection-sharer.js', array( 'jquery' ) );}
	 	else {wp_enqueue_script( "inboundrocket_ss_script", INBOUNDROCKET_SELECTION_SHARER_PATH . '/js/selection-sharer.min.js', array( 'jquery' ) );}
		wp_localize_script( 'inboundrocket_ss_script', 'inboundrocket_ss_js', array(
        	'ajaxurl'       => admin_url( 'admin-ajax.php' ),
			'nextNonce'     => wp_create_nonce( 'inboundrocket-ss-nonce' ),
		));
    }
    
    function inboundrocket_ss_styles()
    {
		if (INBOUNDROCKET_ENABLE_DEBUG==true) { wp_register_style('inboundrocket_ss_style', INBOUNDROCKET_SELECTION_SHARER_PATH . '/css/selection-sharer.css', false, '0.1');}
		else { wp_register_style('inboundrocket_ss_style', INBOUNDROCKET_SELECTION_SHARER_PATH . '/css/selection-sharer.min.css', false, '0.1');}
		wp_enqueue_style('inboundrocket_ss_style');
	}
	
	private function inboundrocket_ss_create_hashshare($options)
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

	private function inboundrocket_ss_get_share_id($post_id, $text){
		
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

	private function inboundrocket_ss_track($post_id, $text, $type)
	{
    	global $wpdb;
		
		$blog_id = get_current_blog_id();
		$post_id = intval($post_id);
		$type = sanitize_text_field($type);
		$text = sanitize_text_field($text);

		$this->share_id = $this->inboundrocket_ss_get_share_id($post_id, $text);
		if (null === $this->share_id) {
			$options = array(
				'post_id' => $post_id,
				'blog_id' => $blog_id,
				'type' => $type,
				'text' => $text,
			);
			$this->share_id = $this->inboundrocket_ss_create_hashshare($options);
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

global $inboundrocket_selectionsharer;
?>