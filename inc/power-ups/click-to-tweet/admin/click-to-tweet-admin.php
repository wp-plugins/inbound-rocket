<?php
if(!defined('ABSPATH') || !defined('INBOUNDROCKET_PATH')) die('Security');

//=============================================
// WPInboundRocketAdmin Class
//=============================================
class WPClickToTweetAdmin extends WPInboundRocketAdmin {
    
    var $power_up_settings_section = 'inboundrocket_ctt_options';
    var $options;
    
    private static $_instance;
    
    /**
     * Class constructor
     */
    function __construct()
    {
        //=============================================
        // Hooks & Filters
        //=============================================
        if ( is_admin() )
        {	        
            $this->options = get_option('inboundrocket_ctt_options');
            
            // Cache bust tinymce
			add_filter('tiny_mce_version', array($this, 'refresh_mce'));

			// Add button plugin to TinyMCE
			add_action('admin_head', array($this, 'ir_tinymce_button'));			
			
        }
    }
    
   	public static function init(){
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        return self::$_instance;            
    }

	//=============================================
    // Settings Page
    //=============================================

    /**
     * Creates settings options
     */
    public function inboundrocket_ctt_build_settings_page()
    {
	    $this->plugin_settings_tabs[$this->power_up_settings_section] = __('Click To Tweet','inboundrocket');
		register_setting($this->power_up_settings_section, $this->power_up_settings_section, array($this, 'sanitize'));
		add_settings_section('ir_ctt_section', '', '', $this->power_up_settings_section);
		add_settings_field('ir_ctt_settings', __('Click To Tweet Settings','inboundrocket'), array($this, 'ir_ctt_input_fields'), $this->power_up_settings_section, 'ir_ctt_section');
    }
	
	/**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        
        if( isset( $input['ir_ctt_twitter_username'] ) )
        	$new_input['ir_ctt_twitter_username'] = sanitize_text_field( $input['ir_ctt_twitter_username'] );

        return $new_input;
    }
	
	/**
     * Prints input form for settings page
     */	
	public function ir_ctt_input_fields ()
	{
		$options = get_option('inboundrocket_ctt_options');
		$ir_ctt_twitter_username = isset($options['ir_ctt_twitter_username']) ? esc_attr( $options['ir_ctt_twitter_username'] ) : '';
		?>
		<tr valign="top">
			<th style="width: 200px;"><label><?php _e('Instructions','inboundrocket');?></label></th>
			<td><?php _e('To use, simply include the Click to Tweet code in your post. Place your message within the parentheses. Tweet length will be automatically truncated to 120 characters.','inboundrocket');?>  <pre>[clicktotweet]<?php _e('This is a tweet. It is only a tweet.','inboundrocket');?>[/clicktotweet]</pre></td>
		</tr>
		<tr valign="top">
			<th style="width: 200px;"><label><?php _e('Your Twitter Handle','inboundrocket');?></label></th>
			<td><input type="text" name="inboundrocket_ctt_options[ir_ctt_twitter_username]" id="ir_ctt_twitter_username" value="<?=$ir_ctt_twitter_username;?>" />
				<br /><span class="description"><?php _e('Enter your Twitter handle to add "via @yourhandle" to your tweets. Do not include the @ symbol.','inboundrocket');?></span></td>
		</tr>
	<?php					
	}
	
	public function ir_tinymce_button()
	{
		global $typenow;
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
			return;
		}
		
		if( ! in_array( $typenow, array( 'post', 'page' ) ) )
        	return;
	
		if (get_user_option('rich_editing') == 'true') {
			add_filter('mce_external_plugins', array($this, 'ir_tinymce_register_plugin'));
			add_filter('mce_buttons', array($this, 'ir_tinymce_register_button'));
		}
	}

	public function ir_tinymce_register_button($buttons) {
	   array_push($buttons, "|", "ir_clicktotweet");
	   return $buttons;
	}

	public function ir_tinymce_register_plugin($plugin_array) {
	   	if (INBOUNDROCKET_ENABLE_DEBUG==true) { 
		   	$plugin_array['ir_clicktotweet'] = INBOUNDROCKET_CLICK_TO_TWEET_PATH . '/js/click-to-tweet-admin.js';
		} else { 
			$plugin_array['ir_clicktotweet'] = INBOUNDROCKET_CLICK_TO_TWEET_PATH . '/js/click-to-tweet-admin.min.js';
		}
	   
	   return $plugin_array;
	}
	
	/**
	 * Cache bust tinymce
	 */
	public function refresh_mce($ver) {
		$ver += 3;
		return $ver;
	}
	
}
?>