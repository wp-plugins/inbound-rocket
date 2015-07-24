<?php
if(!defined('ABSPATH') || !defined('INBOUNDROCKET_PATH')) die('Security'); 

//=============================================
// WPInboundRocketAdmin Class
//=============================================
class WPWelcomeBarAdmin extends WPInboundRocketAdmin {
    
    var $power_up_icon;
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
            $this->options = get_option('inboundrocket_wb_options');
        }
    }
    
    public static function init(){
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        return self::$_instance;            
    }
    
    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    function sanitize( $input )
    {
	    $new_input = array();
		
		if( isset( $input['ir_wb_cta_desktop'] ) )
            $new_input['ir_wb_cta_desktop'] = sanitize_text_field( $input['ir_wb_cta_desktop'] );

        if( isset( $input['ir_wb_cta_mobile'] ) )
            $new_input['ir_wb_cta_mobile'] = sanitize_text_field( $input['ir_wb_cta_mobile'] );

        if( isset( $input['ir_wb_email_placeholder'] ) )
            $new_input['ir_wb_email_placeholder'] = sanitize_text_field( $input['ir_wb_email_placeholder'] );

        if( isset( $input['ir_wb_button_text'] ) )
            $new_input['ir_wb_button_text'] = sanitize_text_field( $input['ir_wb_button_text'] );

        if( isset( $input['ir_wb_success_text'] ) )
            $new_input['ir_wb_success_text'] = sanitize_text_field( $input['ir_wb_success_text'] );

        if( isset( $input['ir_wb_interval'] ) )
            $new_input['ir_wb_interval'] = sanitize_text_field( $input['ir_wb_interval'] );
                        
        if( isset( $input['ir_wb_show_every'] ) )
            $new_input['ir_wb_show_every'] = sanitize_text_field( $input['ir_wb_show_every'] );
            
        if( isset( $input['ir_wb_color'] ) )
            $new_input['ir_wb_color'] = sanitize_text_field( $input['ir_wb_color'] );
            
        return $new_input;
    }
    
	/**
     * Prints input form for settings page
     */	
	function ir_wb_input_fields () {
		
		$options = get_option('inboundrocket_wb_options');
						
		$ir_wb_cta_desktop = isset($this->options['ir_wb_cta_desktop']) ? esc_attr( $this->options['ir_wb_cta_desktop'] ) : '';
		$ir_wb_cta_mobile = isset($this->options['ir_wb_cta_mobile']) ? esc_attr( $this->options['ir_wb_cta_mobile'] ) : '';
		$ir_wb_email_placeholder = isset($this->options['ir_wb_email_placeholder']) ? esc_attr( $this->options['ir_wb_email_placeholder'] ) : '';
		$ir_wb_button_text = isset($this->options['ir_wb_button_text']) ? esc_attr( $this->options['ir_wb_button_text'] ) : '';
		$ir_wb_success_text = isset($this->options['ir_wb_success_text']) ? esc_attr( $this->options['ir_wb_success_text'] ) : '';
		
		$ir_wb_interval = isset($this->options['ir_wb_interval']) ? esc_attr( $this->options['ir_wb_interval'] ) : '';
		$ir_wb_show_every = isset($this->options['ir_wb_show_every']) ? esc_attr( $this->options['ir_wb_show_every'] ) : '';		 
		$ir_wb_color = isset($this->options['ir_wb_color']) ? esc_attr( $this->options['ir_wb_color'] ) : '';		
		?>     
				<tr>
					<th><label for="ir_ss_twitter_username"><?php _e('Call to Action (Desktop)','inboundrocket');?>:</label></th>
					<td><input type="text" name="inboundrocket_wb_options[ir_wb_cta_desktop]" id="ir_wb_cta_desktop" value="<?=$ir_wb_cta_desktop;?>">
					<br /><span class="description"><?php _e('Customise the message and call to action for visitors on a desktop browser.','inboundrocket');?></span></td>
				</tr>
				<tr>
					<th><label for="ir_ss_twitter_username"><?php _e('Call to Action (Mobile)','inboundrocket');?>:</label></th>
					<td><input type="text" name="inboundrocket_wb_options[ir_wb_cta_mobile]" id="ir_wb_cta_mobile" value="<?=$ir_wb_cta_mobile;?>">
					<br /><span class="description"><?php _e('Customise the message and call to action for visitors on a mobile browser.','inboundrocket');?></span></td>
				</tr>
				<tr>
					<th><label for="ir_ss_twitter_username"><?php _e('Email Address Placeholder Text','inboundrocket');?>:</label></th>
					<td><input type="text" name="inboundrocket_wb_options[ir_wb_email_placeholder]" id="ir_wb_email_placeholder" value="<?=$ir_wb_email_placeholder;?>">
					<br /><span class="description"><?php _e('Customise the email input field placeholder text.','inboundrocket');?></span></td>
				</tr>
				<tr>
					<th><label for="ir_ss_twitter_username"><?php _e('Button text','inboundrocket');?>:</label></th>
					<td><input type="text" name="inboundrocket_wb_options[ir_wb_button_text]" id="ir_wb_button_text" value="<?=$ir_wb_button_text;?>">
					<br /><span class="description"><?php _e('Customise the message and call to action for visitors on a desktop browser.','inboundrocket');?></span></td>
				</tr>
				<tr>
					<th><label for="ir_ss_twitter_username"><?php _e('Success text','inboundrocket');?>:</label></th>
					<td><input type="text" name="inboundrocket_wb_options[ir_wb_success_text]" id="ir_wb_success_text" value="<?=$ir_wb_success_text;?>">
					<br /><span class="description"><?php _e('Customise the thank you message.','inboundrocket');?></span></td>
				</tr>
				<tr>
					<th><h3><?php _e('Behavior','inboundrocket');?></h3></th>
				</tr>
				<!-- @TODO disabled for now
				<tr>
					<th><label for="ir_wb_show_every">Show every:</label></th>
					<td><input type="text" name="inboundrocket_wb_options[ir_wb_interval]" id="interval" value="<?=$ir_wb_interval;?>"><select>
							<option name="inboundrocket_wb_options[ir_wb_show_every]" value="always" <?php checked('always', $ir_wb_show_every); ?>>always</option>
							<option name="inboundrocket_wb_options[ir_wb_show_every]" value="minute" <?php checked('minute', $ir_wb_show_every); ?>>minutes</option>
							<option name="inboundrocket_wb_options[ir_wb_show_every]" value="hour" <?php checked('hour', $ir_wb_show_every); ?>>hours</option>
							<option name="inboundrocket_wb_options[ir_wb_show_every]" value="day" <?php checked('day', $ir_wb_show_every); ?>>days</option>
							<option name="inboundrocket_wb_options[ir_wb_show_every]" value="month" <?php checked('month', $ir_wb_show_every); ?>>months</option>
							<option name="inboundrocket_wb_options[ir_wb_show_every]" value="year" <?php checked('year', $ir_wb_show_every); ?>>years</option>
						</select>
					<br /><span class="description">Do NOT show Welcome Bar to the same visitor again until this much time has passed.</span></td>
				</tr>
				-->
				<tr>
					<th><label for="ir_wb_color"><?php _e('Pick a color style','inboundrocket');?>:</label></th>
					<td>
						<table>
							<tr>
								<td><img src="<?php echo INBOUNDROCKET_WELCOME_BAR_PATH; ?>/img/red.png" /></td>
								<td><img src="<?php echo INBOUNDROCKET_WELCOME_BAR_PATH; ?>/img/green.png" /></td>
								<td><img src="<?php echo INBOUNDROCKET_WELCOME_BAR_PATH; ?>/img/salmon.png" /></td>
								<td><img src="<?php echo INBOUNDROCKET_WELCOME_BAR_PATH; ?>/img/lightblue.png" /></td>
								<td><img src="<?php echo INBOUNDROCKET_WELCOME_BAR_PATH; ?>/img/lightgrey.png" /></td>
							</tr>
							<tr>
								<td><input type="radio" name="inboundrocket_wb_options[ir_wb_color]" id="ir_wb_color" value="red" <?php checked('red', $ir_wb_color); ?> /></td>
								<td><input type="radio" name="inboundrocket_wb_options[ir_wb_color]" id="ir_wb_color" value="green" <?php checked('green', $ir_wb_color); ?> /></td>
								<td><input type="radio" name="inboundrocket_wb_options[ir_wb_color]" id="ir_wb_color" value="salmon" <?php checked('salmon', $ir_wb_color); ?> /></td>
								<td><input type="radio" name="inboundrocket_wb_options[ir_wb_color]" id="ir_wb_color" value="lightblue" <?php checked('lightblue', $ir_wb_color); ?> /></td>
								<td><input type="radio" name="inboundrocket_wb_options[ir_wb_color]" id="ir_wb_color" value="lightgrey" <?php checked('lightgrey', $ir_wb_color); ?> /></td>
							</tr>
						</table>
					</td>
				</tr>
<?php
	}

}
?>