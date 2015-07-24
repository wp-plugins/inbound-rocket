<?php
/**
	* Power-up Name: Welcome Bar
	* Power-up Class: WPWelcomeBar
	* Power-up Menu Text:
	* Power-up Menu Link: settings
	* Power-up Slug: welcome_bar
	* Power-up URI: http://inboundrocket.co/features/welcome-bar/
	* Power-up Description: Welcome Bar sits beautifully at the top of your website, is an easy and non-intrusive way to ask people to join your email list.
	* Power-up Icon: powerup-icon-welcome-bar
	* Power-up Icon Small: powerup-icon-welcome-bar
	* First Introduced: 1.0
	* Power-up Tags: Lead Converting
	* Auto Activate: No
	* Permanently Enabled: No
	* Hidden: No
	* cURL Required: No
	* Options Name: inboundrocket_wb_options
*/
if(!defined('ABSPATH') || !defined('INBOUNDROCKET_PATH')) die('Security');

//=============================================
// Define Constants
//=============================================

if ( !defined('INBOUNDROCKET_WELCOME_BAR_PATH') )
    define('INBOUNDROCKET_WELCOME_BAR_PATH', INBOUNDROCKET_PATH . '/inc/power-ups/welcome-bar');

if ( !defined('INBOUNDROCKET_WELCOME_BAR_PLUGIN_DIR') )
	define('INBOUNDROCKET_WELCOME_BAR_PLUGIN_DIR', INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/welcome-bar');

if ( !defined('INBOUNDROCKET_WELCOME_BAR_PLUGIN_SLUG') )
	define('INBOUNDROCKET_WELCOME_BAR_PLUGIN_SLUG', basename(dirname(__FILE__)));

//=============================================
// Include Needed Files
//=============================================

require_once(INBOUNDROCKET_WELCOME_BAR_PLUGIN_DIR . '/admin/welcome-bar-admin.php');

//=============================================
// WPInboundRocketSelectionSharer Class
//=============================================
class WPWelcomeBar extends WPInboundRocket {
	
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

		global $inboundrocket_welcome_bar;
		$inboundrocket_welcome_bar = $this;
		
		add_filter( 'wp_footer', array($this,'inboundrocket_wb_scripts'));
		
		// Setup db connection actions : welcome_bar-save_db
		add_action( 'wp_ajax_welcome_bar-save_db', array($this,'inboundrocket_wb_save_data_callback') );
		add_action( 'wp_ajax_nopriv_welcome_bar-save_db', array($this,'inboundrocket_wb_save_data_callback') );
		
	}

	public function admin_init ( )
	{
		$admin_class = get_class($this) . 'Admin';
		$this->admin = $admin_class::init();
	}

	function power_up_setup_callback ( )
	{
		$this->admin->power_up_setup_callback();
	}
	
	function inboundrocket_wb_scripts()
	{
		$this->inboundrocket_wb_styles();
	 	if (INBOUNDROCKET_ENABLE_DEBUG==true) { wp_enqueue_script( "inboundrocket_wb_script", INBOUNDROCKET_WELCOME_BAR_PATH . '/js/welcome-bar.js', array( 'jquery' ), false );}
	 	else { wp_enqueue_script( "inboundrocket_wb_script", INBOUNDROCKET_WELCOME_BAR_PATH . '/js/welcome-bar.min.js', array( 'jquery' ), false );}
		wp_localize_script( 'inboundrocket_wb_script', 'inboundrocket_wb_js', array(
        	'ajaxurl'       => admin_url( 'admin-ajax.php' ),
			'nextNonce'     => wp_create_nonce( 'wb_form_lead_submitted' )
		));

		$this->wb_options = get_option('inboundrocket_wb_options');
		
		$ir_wb_cta_desktop = isset($this->wb_options['ir_wb_cta_desktop']) ? esc_attr( $this->wb_options['ir_wb_cta_desktop'] ) : '';
		$ir_wb_cta_mobile = isset($this->wb_options['ir_wb_cta_mobile']) ? esc_attr( $this->wb_options['ir_wb_cta_mobile'] ) : '';
		$ir_wb_email_placeholder = isset($this->wb_options['ir_wb_email_placeholder']) ? esc_attr( $this->wb_options['ir_wb_email_placeholder'] ) : '';
		$ir_wb_button_text = isset($this->wb_options['ir_wb_button_text']) ? esc_attr( $this->wb_options['ir_wb_button_text'] ) : '';
		$ir_wb_success_text = isset($this->wb_options['ir_wb_success_text']) ? esc_attr( $this->wb_options['ir_wb_success_text'] ) : '';
		
		$ir_wb_interval = isset($this->wb_options['ir_wb_interval']) ? esc_attr( $this->wb_options['ir_wb_interval'] ) : '';
		$ir_wb_show_every = isset($this->wb_options['ir_wb_show_every']) ? esc_attr( $this->wb_options['ir_wb_show_every'] ) : '';		 
		$ir_wb_color = isset($this->wb_options['ir_wb_color']) ? esc_attr( $this->wb_options['ir_wb_color'] ) : '';
		
		if ($ir_wb_color=='red'): $text_style = '#ffffff'; $button_style = '#000000'; $bar_style = '#eb593c';
			elseif ($ir_wb_color=='green'): $text_style = '#ffffff'; $button_style = '#000000'; $bar_style = '#569625';
			elseif ($ir_wb_color=='lightblue'): $text_style = '#000000'; $button_style = '#ffffff'; $bar_style = '#def1ff';
			elseif ($ir_wb_color=='salmon'): $text_style = '#000000'; $button_style = '#ffffff'; $bar_style = '#f0e1d1';
			elseif ($ir_wb_color=='lightgrey'): $text_style = '#000000'; $button_style = '#ffffff'; $bar_style = '#ededed';
		endif
	?>	
	
	<!-- START NORMAL welcome_bar -->
	<div class="noMobile showDesktop">
		<div class="welcome_bar" style="display: none; background-color: <?=!empty($bar_style) ? $bar_style : '#eee';?>; color: <?=!empty($text_style) ? $text_style : '#000000';?>;">
			<?php  if ( ! inboundrocket_check_premium_user() ) {
               echo '<a href="http://inboundrocket.co/?utm_source='. $_SERVER['HTTP_HOST'] .'&utm_medium=welcome-bar&utm_campaign=logo" title="'. __('Powered by Inbound Rocket - You write. We will turn them into leads','inboundrocket') .'" target="_blank" class="inboundrocket-wb-branding" style="background-color:';
               echo !empty($bar_style) ? $bar_style : '#eee';
               echo '" ><img src="' .INBOUNDROCKET_WELCOME_BAR_PATH .'/img/welcome-bar-logo.png" alt="" /></a>';
             }
           ?>
			<span id="welcome-span" data-success="<?=!empty($ir_wb_success_text) ? $ir_wb_success_text : __('Thank you for signing up!','inboundrocket'); ?>" style=""><?=!empty($ir_wb_cta_desktop) ? $ir_wb_cta_desktop : __('Join Our Mailing List','inboundrocket');?>
					<form id="welcome_bar-form" method="post" action=""><?php wp_nonce_field('welcome_bar_form_lead_submitted', 'welcome_bar_form_nonce'); ?>
					<?php wp_nonce_field('wb_form_lead_submitted', 'wb_form_nonce'); ?>
					<input type="hidden" name="origin" id="origin" value="desktop">
					<input type="hidden" name="current_page" id="current_page" value="<?=$this->inboundrocket_wb_getURL();?>">
					<?php wp_referer_field(true); ?>
					<!--<input type="text" name="name" id="name" placeholder="Firstname and Lastname"> -->
	                <input type="email" name="emailaddress" id="emailaddress" placeholder="<?=!empty($ir_wb_email_placeholder) ? $ir_wb_email_placeholder : __('Email Address', 'inboundrocket');?>">
	                <button type="submit" class="welcome_bar-link"><?=!empty($ir_wb_button_text) ? $ir_wb_button_text : __('Submit', 'inboundrocket');?></button>
            	</form>
            </span>
			<a class="close-notify" style="background-color: <?=!empty($bar_style) ? $bar_style : '#eee';?>;" ><img src="<?php echo INBOUNDROCKET_WELCOME_BAR_PATH; ?>/img/welcome-bar-up-arrow.png" /></a>
			
		</div>
		<div class="welcome_bar-stub" style="display: none;"><a class="show-notify" onclick="welcome_bar_show();" style="background-color: <?=!empty($bar_style) ? $bar_style : '#eee';?>;"><img src="<?php echo INBOUNDROCKET_WELCOME_BAR_PATH; ?>/img/welcome-bar-down-arrow.png" /></a></div>
	</div>
	<!-- END NORMAL welcome_bar -->

	<!-- START MOBILE welcome_bar if only email heigh 6.8em name and email 9em-->
	<div class="showMobile noDesktop">
		<div class="mwelcome_bar" data-success="<?=!empty($ir_wb_success_text) ? $ir_wb_success_text : __('Thank you for signing up!','inboundrocket'); ?>" style="display: none; background-color: <?=!empty($bar_style) ? $bar_style : '#eee';?>; color: <?=!empty($text_style) ? $text_style : '#000000';?>;">	
			<span id="welcome-span" style="height: 6.8em;">
			<?=!empty($ir_wb_cta_desktop) ? $ir_wb_cta_desktop : __('Join Our Mailing List','inboundrocket');?><a class="mclose-notify" onclick="mwelcome_bar_hide();" style="background-color: <?=!empty($bar_style) ? $bar_style : '#eee';?>;">X</a>
			<?php  if ( ! inboundrocket_check_premium_user() ) {
               echo '<a href="http://inboundrocket.co/?utm_source='. $_SERVER['HTTP_HOST'] .'&utm_medium=welcome-bar-mobile&utm_campaign=logo" title="'.  __('Powered by Inbound Rocket - You write. We\'ll turn them into leads','inboundrocket') .'" target="_blank" class="inboundrocket-wb-mbranding" style="background-color:';
               echo !empty($bar_style) ? $bar_style : '#eee';
               echo '" ><img src="' .INBOUNDROCKET_WELCOME_BAR_PATH .'/img/welcome-bar-logo.png" alt="" /></a>';
             }
           ?> 
			<form id="mwelcome_bar-form" method="post" action=""><?php wp_nonce_field('welcome_bar_form_lead_submitted', 'welcome_bar_form_nonce'); ?>
				<input type="hidden" name="origin" id="origin" value="mobile">
				<input type="hidden" name="current_page" id="current_page" value="<?=$this->inboundrocket_wb_getURL();?>">
				<?php wp_referer_field(true); ?>
				<!-- <div class="minput"><input type="text" name="name" id="name" placeholder="Firstname and Lastname"></div> -->
                <div class="minput"><input type="email" name="emailaddress" id="emailaddress" placeholder="<?=!empty($ir_wb_email_placeholder) ? $ir_wb_email_placeholder : __('Email Address','inboundrocket');?>"></div>
                <div class="minput"><button type="submit" class="mwelcome_bar-link"><?=!empty($ir_wb_button_text) ? $ir_wb_button_text : __('Submit','inboundrocket');?></button></div>
            </form></span>
		</div>
		<div class="mwelcome_bar-stub" style="display: none;"><a class="mshow-notify" onclick="mwelcome_bar_show();" style="background-color: <?=!empty($bar_style) ? $bar_style : '#eee';?>;"><img src="<?php echo INBOUNDROCKET_WELCOME_BAR_PATH; ?>/img/welcome-bar-down-arrow.png" /></a></div>
	</div>
	<!-- END MOBILE welcome_bar -->
		<?php
    }
    
    function inboundrocket_wb_styles()
    {
		if (INBOUNDROCKET_ENABLE_DEBUG==true) { wp_register_style('inboundrocket_wb_style', INBOUNDROCKET_WELCOME_BAR_PATH . '/css/welcome-bar.css', false, '0.1');}
		else { wp_register_style('inboundrocket_wb_style', INBOUNDROCKET_WELCOME_BAR_PATH . '/css/welcome-bar.min.css', false, '0.1');}
		wp_enqueue_style('inboundrocket_wb_style');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-effects-bounce');
	}

	function inboundrocket_wb_save_data_callback()
	{
		$nonce = $_POST['wb_form_nonce'];
		if (!wp_verify_nonce($nonce,'wb_form_lead_submitted') || !isset($_POST['email'])) die('Busted!');

		$email = isset($_POST['email'] ) ? sanitize_text_field($_POST['email']) : die('No email');
		$hashkey = isset($_POST['hashkey']) ? sanitize_text_field($_POST['hashkey']) : '';
		$current_url = isset($_POST['page_url']) ? esc_url($_POST['page_url']) : '';
		$form_hash = isset($_POST['submission_hash']) ? sanitize_text_field($_POST['submission_hash']) : '';
		$form_selector_id = isset($_POST['form_selector_id']) ? sanitize_text_field($_POST['form_selector_id']) : '';
		$form_selector_classes = isset($_POST['form_selector_classes']) ? sanitize_text_field($_POST['form_selector_classes']) : '';
		
		$fields = array(
			'email' => $email
		);
		
		$insert_id = $this->inboundrocket_wb_insert_form_submission($hashkey,$fields,$current_url,$form_hash,$form_selector_id,$form_selector_classes);
		$fields['insert_id'] = $insert_id;
		/*
		if(!empty($insert_id)) {
			return $insert_id;
		} else {
			die("FAILED");
		}*/
		echo json_encode($fields);
		exit;
	}
	
	function inboundrocket_wb_insert_form_submission($hashkey, $fields, $current_url, $form_hashkey, $form_selector_id, $form_selector_classes)
	{
    	global $wpdb,$current_site;
    	
    	if(!isset($hashkey) || empty($hashkey)) return false;
    	    	
    	$page_title = wp_title('',false);
    	$blog_id = isset($current_site->id) ? $current_site->id : '';    	

    	$wpdb->insert($wpdb->ir_submissions, array(
        	'lead_hashkey' => $hashkey,
        	'form_hashkey' => $form_hashkey,
       		'form_fields' => json_encode($fields),
       		'form_page_url' => $current_url,
       		'form_page_title' => $page_title,
       		'form_selector_id' => $form_selector_id,
       		'form_selector_classes' => $form_selector_classes,
       		'blog_id' => $blog_id
		));

    	$insert_id = $wpdb->insert_id;
    	if ($insert_id > 0) {
       		return $insert_id;
    	}

    	return false;
	}
	
	function inboundrocket_wb_getURL()
	{
		$protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === FALSE ? 'http' : 'https';
		$host     = $_SERVER['HTTP_HOST'];
		$script   = $_SERVER['SCRIPT_NAME'];
		$params   = $_SERVER['QUERY_STRING'];
		$currentUrl = $protocol . '://' . $host . $script;
		if(isset($params) && !empty($params)) $currentUrl .= '?' . $params;
		return $currentUrl;
	}

}

//=============================================
// InboundRocket Init
//=============================================

global $inboundrocket_welcome_bar;
?>