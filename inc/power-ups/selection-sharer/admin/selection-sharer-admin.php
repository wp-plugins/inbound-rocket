<?php
if(!defined('ABSPATH') || !defined('INBOUNDROCKET_PATH')) die('Security');

//=============================================
// WPInboundRocketAdmin Class
//=============================================
class WPSelectionSharerAdmin extends WPInboundRocketAdmin {
    
    var $power_up_settings_section = 'inboundrocket_ss_options';
    var $options;
    
    private static $_instance = null;
    
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
            $this->options = get_option('inboundrocket_ss_options');
            
            add_action('admin_enqueue_scripts', array($this,'inboundrocket_ss_enqueue_script'));
        }
    }
    
    public static function init(){
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        return self::$_instance;            
    }
    
    /**
     * Enqueue Power-Up Scripts
     */
    function inboundrocket_ss_enqueue_script()
	{
		 if (INBOUNDROCKET_ENABLE_DEBUG==true) { wp_register_script('inboundrocket-ir-admin-js', INBOUNDROCKET_SELECTION_SHARER_PATH . '/admin/js/selection-sharer-admin.js', array ( 'jquery' ), FALSE, FALSE);}
		 else { wp_register_script('inboundrocket-ir-admin-js', INBOUNDROCKET_SELECTION_SHARER_PATH . '/admin/js/selection-sharer-admin.min.js', array ( 'jquery' ), FALSE, FALSE);}
         wp_enqueue_script('inboundrocket-ir-admin-js');
	}

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    function sanitize ( $input )
    {
        $new_input = array();

		if( isset( $input['ir_ss_twitter_username'] ) )
            $new_input['ir_ss_twitter_username'] = sanitize_text_field( str_replace('@','',$input['ir_ss_twitter_username']) );

        if( isset( $input['ir_ss_tweet_suffix'] ) )
            $new_input['ir_ss_tweet_suffix'] = sanitize_text_field( $input['ir_ss_tweet_suffix'] );

        if( isset( $input['ir_ss_drop_suffix'] ) )
            $new_input['ir_ss_drop_suffix'] = intval( $input['ir_ss_drop_suffix'] );
            
        if( isset( $input['ir_ss_short_url_service'] ) )
            $new_input['ir_ss_short_url_service'] = sanitize_text_field( $input['ir_ss_short_url_service'] );

        if( isset( $input['ir_ss_campaign_variables'] ) )
            $new_input['ir_ss_campaign_variables'] = intval( $input['ir_ss_campaign_variables'] );

        if( isset( $input['ir_ss_awesm_api_key'] ) )
            $new_input['ir_ss_awesm_api_key'] = sanitize_text_field( $input['ir_ss_awesm_api_key'] );
            
        if( isset( $input['ir_ss_bitly_login'] ) )
            $new_input['ir_ss_bitly_login'] = sanitize_text_field( $input['ir_ss_bitly_login'] );  

        if( isset( $input['ir_ss_bitly_api_key'] ) )
            $new_input['ir_ss_bitly_api_key'] = sanitize_text_field( $input['ir_ss_bitly_api_key'] );
            
        return $new_input;
    }

	/**
     * Prints input form for settings page
     */	
	function ir_ss_input_fields () {
		
		$options = get_option('inboundrocket_ss_options');
		
		$ir_ss_twitter_username = isset($options['ir_ss_twitter_username']) ? esc_attr( $options['ir_ss_twitter_username'] ) : '';
		$ir_ss_tweet_suffix = isset($options['ir_ss_tweet_suffix']) ? esc_attr( $options['ir_ss_tweet_suffix'] ) : '';
		$ir_ss_awesm_api_key = isset($options['ir_ss_awesm_api_key']) ? esc_attr( $options['ir_ss_awesm_api_key'] ) : '';
		$ir_ss_awesm_tool = isset($options['ir_ss_awesm_tool']) ? esc_attr( $options['ir_ss_awesm_tool'] ) : '';
		$ir_ss_awesm_channel = isset($options['ir_ss_awesm_channel']) ? esc_attr( $options['ir_ss_awesm_channel'] ) : '';
		$ir_ss_bitly_login = isset($options['ir_ss_bitly_login']) ? esc_attr( $options['ir_ss_bitly_login'] ) : '';
		$ir_ss_bitly_api_key = isset($options['ir_ss_bitly_api_key']) ? esc_attr( $options['ir_ss_bitly_api_key'] ) : '';
		
		$ir_ss_campaign_variables = isset($options['ir_ss_campaign_variables']) ? '1' : '0';
		$ir_ss_drop_suffix = isset($options['ir_ss_drop_suffix']) ? '1' : '0';
		$ir_ss_short_url_service = isset($options['ir_ss_short_url_service']) ? esc_attr( $options['ir_ss_short_url_service'] ) : 'googl';
		?>     
	<tr>
		<th><label for="ir_ss_twitter_username"><?php _e('Twitter username','inboundrocket');?>:</label></th>
		<td><input type="text" name="inboundrocket_ss_options[ir_ss_twitter_username]" id="ir_ss_twitter_username" value="<?=$ir_ss_twitter_username;?>"><br /><span class="description"><?php _e('Enter your Twitter handle to add "via @yourhandle" to your tweets. Do not include the @ symbol.','inboundrocket');?></span></td>
	</tr>
	<tr>
		<th><label for="ir_ss_tweet_suffix"><?php _e('Tweet suffix','inboundrocket');?>:</label></th>
		<td><input type="text" name="inboundrocket_ss_options[ir_ss_tweet_suffix]" id="ir_ss_tweet_suffix" value="<?=$ir_ss_tweet_suffix;?>">
		<br /><span class="description"><?php _e('You can suffix your automated tweets with extra hashtags, like','inboundrocket');?> <code><?php _e('#hashtag','inboundrocket');?></code></span></td>
	</tr>
	<tr>
		<th><label for="ir_ss_drop_suffix"><?php _e('Drop suffix','inboundrocket');?>:</label></th>
		<td><input type="checkbox" name="inboundrocket_ss_options[ir_ss_drop_suffix]" id="ir_ss_drop_suffix" value="1" <?php checked('1', $ir_ss_drop_suffix, true); ?> /> <span class="description"><?php _e('Drop the suffix if the tweet exceeds 140 characters.','inboundrocket');?></span></td>
	</tr>
	<tr>
		<th><label for="ir_ss_campaign_variables"><?php _e('Campaign variables','inboundrocket');?>:</label></th>
		<td><input type="checkbox" name="inboundrocket_ss_options[ir_ss_campaign_variables]" id="ir_ss_campaign_variables" value="1" <?php checked($ir_ss_campaign_variables,'1', true); ?> /> <span class="description"><?php _e('Add Google Analytics campaign variables to the permalink for tracking.','inboundrocket');?></span></td>
	</tr>
	<tr>
		<th><label for="ir_ss_api_service_awesm"><?php _e('Short URL service','inboundrocket');?>:</label></th>
		<td><input type="radio" name="inboundrocket_ss_options[ir_ss_short_url_service]" id="ir_ss_api_service_awesm" value="awesm" <?php checked('awesm', $ir_ss_short_url_service); ?> /> <label for="ir_ss_api_service_awesm">Awe.sm</label> &nbsp;&nbsp;&nbsp;
			<input type="radio" name="inboundrocket_ss_options[ir_ss_short_url_service]" id="ir_ss_service_bitly" value="bitly" <?php checked('bitly', $ir_ss_short_url_service); ?> /> <label for="ir_ss_service_bitly">Bit.ly</label> &nbsp;&nbsp;&nbsp;
			<input type="radio" name="inboundrocket_ss_options[ir_ss_short_url_service]" id="ir_ss_service_googl" value="googl" <?php checked('googl', $ir_ss_short_url_service); ?> /> <label for="ir_ss_service_googl">Goo.gl</label></td>
	</tr>
	<tr class="awesm">
		<th><h4>Awe.sm <?php _e('credentials','inboundrocket');?></h4></th>
	</tr>
	<tr class="awesm">
		<th><label for="ir_ss_awesm_api_key">Awe.sm <?php _e('API key','inboundrocket');?>:</label></th>
		<td><input type="text" name="inboundrocket_ss_options[ir_ss_awesm_api_key]" id="ir_ss_awesm_api_key" value="<?=$ir_ss_awesm_api_key;?>" class="regular-text" /></td>
	</tr>
	<tr class="awesm">
		<th><label for="ir_ss_awesm_tool">Awe.sm <?php _e('Tool key','inboundrocket');?>:</label></th>
		<td><input type="text" name="inboundrocket_ss_options[ir_ss_awesm_tool]" id="ir_ss_awesm_tool" value="<?=$ir_ss_awesm_tool;?>" class="regular-text" /></td>
	</tr>
	<tr class="awesm">
		<th><label for="ir_ss_awesm_channel">Awe.sm <?php _e('Channel','inboundrocket');?></label></th>
		<td><input type="text" name="inboundrocket_ss_options[ir_ss_awesm_channel]" id="ir_ss_awesm_channel" value="<?=$ir_ss_awesm_channel;?>" class="regular-text" /></td>
	</tr>
	<tr class="bitly">
		<th><h4>Bit.ly <?php _e('credentials','inboundrocket');?></h4></th>
	</tr>
	<tr class="bitly">
		<th><label for="ir_ss_bitly_login">Bit.ly <?php _e('Login name','inboundrocket');?>:</label></th>
		<td><input type="text" name="inboundrocket_ss_options[ir_ss_bitly_login]" id="ir_ss_bitly_login" value="<?=$ir_ss_bitly_login;?>" class="regular-text" /></td>
	</tr>			
	<tr class="bitly">
		<th><label for="ir_ss_bitly_login">Bit.ly <?php _e('API key:','inboundrocket');?></label></th>
		<td><input type="text" name="inboundrocket_ss_options[ir_ss_bitly_api_key]" id="ir_ss_bitly_api_key" value="<?=$ir_ss_bitly_api_key;?>" class="regular-text"></td>
	</tr>
	<?php switch($ir_ss_short_url_service) {
		case "bitly":
			echo "<script>jQuery('.awesm').hide();</script>";
		break;
		case "awesm":
			echo "<script>jQuery('.bitly').hide();</script>";
		break;
		default:
			echo "<script>jQuery('.bitly,.awesm').hide();</script>";
		}
	}	

}
?>