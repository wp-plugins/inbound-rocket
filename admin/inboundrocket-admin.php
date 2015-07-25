<?php
if ( !defined('ABSPATH') ) exit;

if ( !defined('INBOUNDROCKET_PLUGIN_VERSION') ) 
{
    header('HTTP/1.0 404 Not Found');
    die;
}

if( INBOUNDROCKET_ENABLE_DEBUG === true )
{
	error_reporting(E_ALL);
	ini_set('display_errors',1);
}

//=============================================
// Define Constants
//=============================================

if ( !defined('INBOUNDROCKET_ADMIN_PATH') )
    define('INBOUNDROCKET_ADMIN_PATH', untrailingslashit(__FILE__));
    
//=============================================
// Include Needed Files
//=============================================
require_once INBOUNDROCKET_PLUGIN_DIR . '/inc/inboundrocket-functions.php';

if ( !class_exists('IR_StatsDashboard') )
	require_once INBOUNDROCKET_PLUGIN_DIR . '/admin/inc/class-stats-dashboard.php';
	
if ( !class_exists('IR_List_Table') )
    require_once INBOUNDROCKET_PLUGIN_DIR . '/admin/inc/class-inboundrocket-list-table.php';
    
if ( !class_exists('IR_Contact') )
    require_once INBOUNDROCKET_PLUGIN_DIR . '/admin/inc/class-inboundrocket-contact.php';  
    
if ( !class_exists('IR_Lead_List_Table') )
    require_once INBOUNDROCKET_PLUGIN_DIR . '/admin/inc/class-inboundrocket-lead-list-table.php';

if ( !class_exists('IR_Lead_List_Editor') )
    require_once INBOUNDROCKET_PLUGIN_DIR . '/admin/inc/class-inboundrocket-lead-list-editor.php';


require_once(INBOUNDROCKET_PLUGIN_DIR . '/inc/lib/MailChimp/MailChimp.php');

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

//=============================================
// InboundRocketAdmin Class
//=============================================
class WPInboundRocketAdmin {
	
	var $admin_power_ups;
	var $power_up_icon;
	var $stats_dashboard;
	var $action;
	
	private $kses_html = array(
	    'a' => array(
	        'href' => array(),
	        'title' => array(),
	        'rel' => array(),
	        'class' => array()
	    ),
	    'b' => array(),
	    'blockquote' => array(),
	    'br' => array(),
	    'dd' => array(
		    'class' => array()
	    ),
	    'del' => array(),
	    'div' => array(
		    'class' => array()
	    ),
	    'dl' => array(
		    'class' => array()
	    ),
	    'dt' => array(
		    'class' => array()
	    ),
	    'em' => array(),
	    'font' => array(
		    'size' => array(),
		    'face' => array(),
		    'class' => array()
	    ),
	    'h1' => array(
		    'class' => array()
	    ),
	    'h2' => array(
		    'class' => array()
	    ),
	    'h3' => array(
		    'class' => array()
	    ),
	    'h4' => array(
		    'class' => array()
	    ),
	    'h5' => array(
		    'class' => array()
	    ),
	    'h6' => array(
		    'class' => array()
	    ),
	    'hr' => array(
		    'class' => array()
	    ),
	    'i' => array(),
	    'img' => array(
		    'title' => array(),
		    'src' => array(),
		    'alt' => array(),
		    'width' => array(),
		    'height' => array(),
		    'class' => array(),
	    ),
	    'li' => array(
		    'class' => array()
	    ),
	    'ol' => array(),
	    'p' => array(),
	    'pre' => array(),
	    'small' => array(),
	    'span' => array(),
	    'strong' => array(),
	    'sub' => array(),
	    'sup' => array(),
	    'table' => array(
		    'width' => array(),
		    'height' => array(),
		    'border' => array(),
		    'cellpadding' => array(),
		    'cellspacing' => array(),
		    'class' => array()
	    ),
	    'td' => array(
		    'class' => array()
	    ),
	    'th' => array(
		    'class' => array()
	    ),
	    'thead' => array(),
	    'tr' => array(),
	    'tt' => array(),
	    'u' => array(),
	    'ul' => array(
		    'class' => array()
	    ),
	    'center' => array()
	);
	private $protocals = array('http','https');
	
    protected $plugin_settings_tabs = array();
	
    /**
     * Class constructor
     */
    function __construct( $power_ups )
    {
        //=============================================
        // Hooks & Filters
        //=============================================
        
        $this->action = $this->inboundrocket_current_action();
        $this->admin_power_ups = $power_ups;
        
        if(is_admin()):
        	add_action('admin_menu', array(&$this, 'inboundrocket_add_menu_items'));
        	add_action('admin_init', array(&$this, 'inboundrocket_settings_page'));
        	
        	add_action('admin_print_styles', array(&$this, 'add_inboundrocket_admin_styles'));
			add_action('admin_enqueue_scripts', array(&$this, 'add_inboundrocket_admin_scripts'));
			
			add_filter('plugin_action_links', array(&$this, 'inboundrocket_plugin_settings_link'), 10, 2);
			
			if ( isset($_GET['page']) && $_GET['page'] === 'inboundrocket_stats' ) {
	            add_action('admin_footer', array(&$this, 'build_contacts_chart'));
	        }
        endif;
 	}	
	
	//=============================================
    // Menus
    //=============================================
    /**
     * Adds Inbound Rocket menu to /wp-admin sidebar
     */
    function inboundrocket_add_menu_items()
    {
        $options = get_option('inboundrocket_options');

        global $submenu;
        global  $wp_version;
        
        $capability = 'activate_plugins';
        if ( ! current_user_can('activate_plugins') )
        {
            if ( ! array_key_exists('ir_grant_access_to_' . inboundrocket_get_user_role(), $options ) )
                return FALSE;
            else
            {
                if ( current_user_can('manage_network') ) // super admin
                    $capability = 'manage_network';
                else if ( current_user_can('edit_pages') ) // editor
                    $capability = 'edit_pages';
                else if ( current_user_can('publish_posts') ) // author
                    $capability = 'publish_posts';
                else if ( current_user_can('edit_posts') ) // contributor
                    $capability = 'edit_posts';
                else if ( current_user_can('read') ) // subscriber
                    $capability = 'read';
            }
        }
        
        self::check_admin_action();
        
        if ( ini_get('allow_url_fopen') )
            $inboundrocket_icon = ($wp_version < 3.8 && !is_plugin_active('mp6/mp6.php') ? INBOUNDROCKET_PATH . '/img/inboundrocket-icon-32x32.png' : 'data:image/svg+xml;base64,' . base64_encode(@file_get_contents(INBOUNDROCKET_PATH . '/img/inboundrocket-svg-icon.svg')));
        else
        {

            $inboundrocket_icon = INBOUNDROCKET_PATH . '/img/inboundrocket-icon-32x32.png';
        }
		
		add_menu_page('Inbound Rocket', 'Inbound Rocket', $capability, 'inboundrocket_stats', array($this, 'inboundrocket_build_stats_page'),  $inboundrocket_icon , '25.10167');

		foreach ( $this->admin_power_ups as $power_up )
        {
            if ( $power_up->activated )
            {
                $power_up->admin_init();

                // Creates the menu icon for power-up if it's set. Overrides the main Inbound Rocket menu to hit the contacts power-up
                if ( $power_up->menu_text )
                    add_submenu_page('inboundrocket_stats', $power_up->menu_text, $power_up->menu_text, $capability, 'inboundrocket_' . $power_up->menu_link, array($power_up, 'power_up_setup_callback'));   
            }
        }

		add_submenu_page('inboundrocket_stats', __('Lead Lists', 'inboundrocket'), __('Lead Lists', 'inboundrocket'), $capability, 'inboundrocket_lead_lists', array(&$this, 'inboundrocket_build_lead_list_page'));
        add_submenu_page('inboundrocket_stats', __('Settings', 'inboundrocket'), __('Settings', 'inboundrocket'), 'activate_plugins', 'inboundrocket_settings', array(&$this, 'inboundrocket_plugin_options'));
        add_submenu_page('inboundrocket_stats', __('Power-ups', 'inboundrocket'), __('Power-ups', 'inboundrocket'), 'activate_plugins', 'inboundrocket_power_ups', array(&$this, 'inboundrocket_power_ups_page'));
        
		if ( ! inboundrocket_check_premium_user() )
			add_submenu_page('inboundrocket_stats', __('Upgrade to Premium', 'inboundrocket'), __('Upgrade to Premium', 'inboundrocket'), 'activate_plugins', 'inboundrocket_premium_upgrade', array(&$this, 'inboundrocket_premium_upgrade_page'));
        
        $submenu['inboundrocket_stats'][0][0] = 'Stats';
        
    }    
    
    //=============================================
    // Settings Page
    //=============================================

    /**
     * Adds setting link for Inbound Rocket to plugins management page 
     *
     * @param   array $links
     * @return  array
     */
    function inboundrocket_plugin_settings_link ( $links, $plugin_file )
    {
	    static $plugin;
		
		if (!isset($plugin)) $plugin = INBOUNDROCKET_PLUGIN_SLUG.'/'.INBOUNDROCKET_PLUGIN_SLUG.'.php';
		
		if ($plugin == $plugin_file) {
			$settings_link = '<a href="' . admin_url('admin.php?page=inboundrocket_settings') . '">' . __('Settings', 'inboundrocket') . '</a>';
			array_unshift($links, $settings_link);
		}
        return $links;
    }    
     
     /**
     * Creates the stats page
     */
    function inboundrocket_build_stats_page ()
    {
        global $wp_version;
        
        if ( !current_user_can( 'manage_categories' ) )
            wp_die(__('You do not have sufficient permissions to access this page.','inboundrocket'));
        
        $this->stats_dashboard = new IR_StatsDashboard();
        
        //die(print_r($this->stats_dashboard));

        inboundrocket_track_plugin_activity("Loaded Stats Page");

        echo '<div id="inboundrocket" class="ir-stats wrap '. ( $wp_version < 3.8 && !is_plugin_active('mp6/mp6.php') ? 'pre-mp6' : ''). '">';
        
        	$this->inboundrocket_header('' . __('Inbound Rocket Stats', 'inboundrocket') . ': ' . date('F j Y, g:ia', current_time('timestamp')), 'inboundrocket-stats__header');
        
			echo '<div class="inboundrocket-stats__top-container">';
            	echo $this->inboundrocket_postbox('inboundrocket-stats__chart', inboundrocket_single_plural_label(number_format($this->stats_dashboard->total_contacts_last_30_days), __('new contact', 'inboundrocket'), __('new contacts', 'inboundrocket')) . ' ' . __('last 30 days', 'inboundrocket') . '', $this->inboundrocket_build_contacts_chart_stats());
			echo '</div>';

			echo '<div class="inboundrocket-stats__postbox_container">';
            	echo $this->inboundrocket_postbox('inboundrocket-stats__new-contacts', inboundrocket_single_plural_label(number_format($this->stats_dashboard->total_new_contacts), __('new contact', 'inboundrocket'), __('new contacts', 'inboundrocket')) . ' ' . __('today', 'inboundrocket') . '', $this->inboundrocket_build_new_contacts_postbox());
				echo $this->inboundrocket_postbox('inboundrocket-stats__returning-contacts', inboundrocket_single_plural_label(number_format($this->stats_dashboard->total_returning_contacts), __('returning contact', 'inboundrocket'), __('returning contacts', 'inboundrocket')) . ' ' . __('today', 'inboundrocket') . '', $this->inboundrocket_build_returning_contacts_postbox());
				echo $this->inboundrocket_postbox('inboundrocket-stats__most-popular-pages', __('Most popular pages today', 'inboundrocket'), $this->inboundrocket_build_most_popular_pages_postbox());
			echo '</div>';

			echo '<div class="inboundrocket-stats__postbox_container">';
            	echo $this->inboundrocket_postbox('inboundrocket-stats__new-sources', __('New contact sources last 30 days', 'inboundrocket'), $this->inboundrocket_build_sources_postbox());
				echo $this->inboundrocket_postbox('inboundrocket-stats__new-shares', __('New shares last 30 days', 'inboundrocket'), $this->inboundrocket_build_new_shares_postbox());
				echo $this->inboundrocket_postbox('inboundrocket-stats__most-popular-referral', __('Top 5 referral sources', 'inboundrocket'), $this->inboundrocket_build_most_popular_referrers_postbox());
			echo '</div>';
			echo '<div style="clear:both"></div>';
        $this->inboundrocket_footer();
        
        echo '</div>';
        
    }
    
    /*
	* Valid Email Address filter_var
	*/
	private function get_valid_email( $email ) {
  		return filter_var( $email, FILTER_VALIDATE_EMAIL );
	}
    
    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize ( $input )
    {
        $new_input = array();
        
        // Settings
        if( isset( $input['ir_email'] ) && !empty($input['ir_email']) )
            
            $new_email_arr = array();
            if(isset($input['ir_email']) && strpos($input['ir_email'], ",")===true):
	            $emails_arr = explode( ",", sanitize_text_field($input['ir_email']) );
	            foreach($emails_arr as $email){
		           if($this->get_valid_email( $email )){
		            	$new_email_arr[] = $email;
		           } 
	            }
            endif;
            
            if(isset($new_email_arr[0])){
	            $new_input['ir_email'] = implode(',', $new_email_arr);
            } else {
	            $new_input['ir_email'] = isset($input['ir_email']) ? sanitize_text_field($input['ir_email']) : '';
            }
            

        if( isset( $input['ir_installed'] ) )
            $new_input['ir_installed'] = absint( $input['ir_installed'] );

        if( isset( $input['ir_db_version'] ) )
            $new_input['ir_db_version'] = sanitize_text_field( $input['ir_db_version'] );

        if( isset( $input['onboarding_step'] ) ) {
            $new_input['onboarding_step'] = intval( $input['onboarding_step'] );
            
			if($new_input['onboarding_step']==2){
	        	inboundrocket_register_user();
        	}
        
        }

        if( isset( $input['onboarding_complete'] ) )
            $new_input['onboarding_complete'] = intval( $input['onboarding_complete'] );

		if( isset( $input['converted_to_tags'] ) )
            $new_input['converted_to_tags'] = sanitize_text_field( $input['converted_to_tags'] );

        if( isset( $input['names_added_to_contacts'] ) )
            $new_input['names_added_to_contacts'] = sanitize_text_field( $input['names_added_to_contacts'] );

        if( isset( $input['inboundrocket_version'] ) )
            $new_input['inboundrocket_version'] = sanitize_text_field( $input['inboundrocket_version'] );

        if( isset( $input['ir_updates_subscription'] ) )
            $new_input['ir_updates_subscription'] = sanitize_text_field( $input['ir_updates_subscription'] );
		
        if( isset( $input['beta_tester'] ) )
        {
            $new_input['beta_tester'] = sanitize_text_field( $input['beta_tester'] );
            inboundrocket_set_beta_tester_property(TRUE);
        }
        else
            inboundrocket_set_beta_tester_property(FALSE);

        $user_roles = get_editable_roles();
        if ( count($user_roles) )
        {
            foreach ( $user_roles as $key => $role )
            {
                $role_id_tracking = 'ir_do_not_track_' . $key;
                $role_id_access = 'ir_grant_access_to_' . $key;

                if ( isset( $input[$role_id_tracking] ) )
                    $new_input[$role_id_tracking] = sanitize_text_field( $input[$role_id_tracking] );

                if ( isset( $input[$role_id_access] ) )
                    $new_input[$role_id_access] = sanitize_text_field( $input[$role_id_access] );
            }
        }
      	
      	// Emails
        if( isset( $input['ir_emails_welcome_send'] ) )
            $new_input['ir_emails_welcome_send'] = intval( $input['ir_emails_welcome_send'] );
        
        if( isset( $input['ir_emails_welcome_subject'] ) )
            $new_input['ir_emails_welcome_subject'] = sanitize_text_field( $input['ir_emails_welcome_subject'] );
        
        if( isset( $input['ir_emails_welcome_content'] ) )
            $new_input['ir_emails_welcome_content'] = wp_kses( $input['ir_emails_welcome_content'], $this->kses_html, $this->protocals );                  	
      	    
        return $new_input;
    }   
 
	/**
     * Creates the stats page
    */
    function inboundrocket_build_lead_list_page ()
    {
        global $wp_version;

		if ( isset($_POST['tag_name']) )
        {
            $list_id = ( isset($_POST['list_id']) ? absint($_POST['list_id']) : FALSE );
            $tagger = new IR_Lead_List_Editor($list_id);

            $tag_name           = sanitize_text_field($_POST['tag_name']);
            $tag_form_selectors = '';
            $tag_synced_lists   = array();

            foreach ( $_POST as $name => $value )
            {
                // Create a comma deliniated list of selectors for tag_form_selectors
                if ( strstr($name, 'email_form_tags_') )
                {
                    $tag_selector = '';
                    if ( strstr($name, '_class') )
                        $tag_selector = str_replace('email_form_tags_class_', '.', $name);
                    else if ( strstr($name, '_id') )
                        $tag_selector = str_replace('email_form_tags_id_', '#', $name);

                    if ( $tag_selector )
                    {
                        if ( ! strstr($tag_form_selectors, $tag_selector) )
                            $tag_form_selectors .= $tag_selector . ',';
                    }
                } // Create a comma deliniated list of synced lists for tag_synced_lists
                else if ( strstr($name, 'email_connect_') )
                {
                    // Name comes through as email_connect_espslug_listid, so replace the beginning of each one with corresponding esp slug, which leaves just the list id
                    if ( strstr($name, '_mailchimp') )
                        $synced_list = array('esp' => 'mailchimp', 'list_id' => str_replace('email_connect_mailchimp_', '', $name), 'list_name' => $value);
                    else if ( strstr($name, '_constant_contact') )
                        $synced_list = array('esp' => 'constant_contact', 'list_id' => str_replace('email_connect_constant_contact_', '', $name), 'list_name' => $value);
                    else if ( strstr($name, '_aweber') )
                        $synced_list = array('esp' => 'aweber', 'list_id' => str_replace('email_connect_aweber_', '', $name), 'list_name' => $value);
                    else if ( strstr($name, '_campaign_monitor') )
                        $synced_list = array('esp' => 'campaign_monitor', 'list_id' => str_replace('email_connect_campaign_monitor_', '', $name), 'list_name' => $value);
                    else if ( strstr($name, '_getresponse') )
                        $synced_list = array('esp' => 'getresponse', 'list_id' => str_replace('email_connect_getresponse_', '', $name), 'list_name' => $value);

                    array_push($tag_synced_lists, $synced_list);
                }
            }

            if ( $_POST['email_form_tags_custom'] )
            {
                if ( strstr($_POST['email_form_tags_custom'], ',') )
                {
                    foreach ( explode(',', sanitize_text_field($_POST['email_form_tags_custom'])) as $tag )
                    {
                        if ( ! strstr($tag_form_selectors, $tag) )
                            $tag_form_selectors .= $tag . ',';
                    }
                }
                else
                {
                    if ( ! strstr($tag_form_selectors, sanitize_text_field( $_POST['email_form_tags_custom']) ) )
                        $tag_form_selectors .= sanitize_text_field( $_POST['email_form_tags_custom'] ) . ',';
                }
            }

            // Sanitize the selectors by removing any spaces and any trailing commas
            $tag_form_selectors = rtrim(str_replace(' ', '', $tag_form_selectors), ',');

            if ( $list_id )
            {
                $tagger->save_list(
                    $list_id,
                    $tag_name,
                    $tag_form_selectors,
                    serialize($tag_synced_lists)
                );
            }
            else
            {
                $tagger->list_id = $tagger->add_list(
                    $tag_name,
                    $tag_form_selectors,
                    serialize($tag_synced_lists)
                );
            }
        }

        echo '<div id="inboundrocket" class="wrap '. ( $wp_version < 3.8 && !is_plugin_active('mp6/mp6.php')  ? 'pre-mp6' : ''). '">';

            if ( $this->action == 'edit_list' || $this->action == 'add_list' ) {
                inboundrocket_track_plugin_activity("Loaded Lead List Editor");
                $this->inboundrocket_render_tag_editor();
            }
            else
            {
                inboundrocket_track_plugin_activity("Loaded Lead List List");
                $this->inboundrocket_render_tag_list_page();
            }

            $this->inboundrocket_footer();

        echo '</div>';
    }

    /**
     * Creates list table for Contacts page
     *
     */
    function inboundrocket_render_tag_editor ()
    {
        ?>
        <div class="inboundrocket-contacts">
            <?php

                if ( $this->action == 'edit_list' || $this->action == 'add_list' )
                {
                    $list_id = ( isset($_GET['tag']) ? sanitize_text_field($_GET['tag']) : FALSE);
                    $tagger = new IR_Lead_List_Editor($list_id);
                }

                if ( $tagger->list_id ) $tagger->get_tag_details($tagger->list_id);
                
                $this->inboundrocket_header(( $this->action == 'edit_list' ? 'Edit a Lead List' : __('Add a Lead List', 'inboundrocket') ), 'inboundrocket-contacts__header');
                
                echo '<a href="' . admin_url('admin.php?page=inboundrocket_lead_lists') .'">&larr; '. __('Manage Lead Lists', 'inboundrocket') .'</a>';
            ?>

            <div class="">
                <form id="inboundrocket-tag-settings" action="<?php echo admin_url('admin.php?page=inboundrocket_lead_lists'); ?>" method="POST">

                    <table class="form-table"><tbody>
                        <tr>
                            <th scope="row"><label for="tag_name"><?php _e('Lead List name','inboundrocket'); ?></label></th>
                            <td><input name="tag_name" type="text" id="tag_name" value="<?php echo ( isset($tagger->details->tag_text) ? esc_attr($tagger->details->tag_text) : '' ); ?>" class="regular-text" placeholder="<?php _e('Lead List name','inboundrocket'); ?>"></td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Automatically tag contacts who fill out any of these forms','inboundrocket'); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php _e('Automatically tag contacts who fill out any of these forms','inboundrocket'); ?></span></legend>
                                    <?php 
                                        $tag_form_selectors = ( isset($tagger->details->tag_form_selectors) ? explode(',', str_replace(' ', '', $tagger->details->tag_form_selectors)) : '');
                                        
                                        foreach ( $tagger->selectors as $selector )
                                        {
                                            $html_id = 'email_form_tags_' . str_replace(array('#', '.'), array('id_', 'class_'), $selector); 
                                            $selector_set = FALSE;
                                            
                                            if ( isset($tagger->details->tag_form_selectors) && strstr($tagger->details->tag_form_selectors, $selector) )
                                            {
                                                $selector_set = TRUE;
                                                $key = array_search($selector, $tag_form_selectors);
                                                if ( $key !== FALSE )
                                                    unset($tag_form_selectors[$key]);
                                            }
                                            
                                            echo '<label for="' . $html_id . '">';
                                                echo '<input name="' . $html_id . '" type="checkbox" id="' . $html_id . '" value="" ' . ( $selector_set ? 'checked' : '' ) . '>';
                                                echo $selector;
                                            echo '</label><br>';
                                        }
                                    ?>
                                </fieldset>
                                <br>
                                <input name="email_form_tags_custom" type="text" value="<?php echo ( $tag_form_selectors ? implode(', ', $tag_form_selectors) : ''); ?>" class="regular-text" placeholder="#form-id, .form-class">
                                <p class="description"><?php _e('Include additional form\'s css selectors.','inboundrocket'); ?></p>
                            </td>
                        </tr>

                        
                        <?php
                        /*
                            $esp_power_ups = array(
                                'Selection Sharer'  => 'selection_sharer', 
                                'Click to Tweet'    => 'click_to_tweet', 
                                'Welcome Bar'       => 'welcome_bar', 
                            );

                            foreach ( $esp_power_ups as $power_up_name => $power_up_slug )
                            {
                                if ( WPInboundRocket::is_power_up_active($power_up_slug) )
                                {
	                                
                                    global ${'inboundrocket_' . $power_up_slug . '_wp'}; // e.g inboundrocket_mailchimp_connect_wp
                                    $esp_name = strtolower(str_replace('_connect', '', $power_up_slug)); // e.g. mailchimp
                                    $lists = ${'inboundrocket_' . $power_up_slug . '_wp'}->admin->ir_get_lists();
                                    $synced_lists = ( isset($tagger->details->tag_synced_lists) ? unserialize($tagger->details->tag_synced_lists) : '' );

                                    echo '<tr>';
                                        echo '<th scope="row">Push tagged contacts to these ' . $power_up_name . ' lists</th>';
                                        echo '<td>';
                                            echo '<fieldset>';
                                                echo '<legend class="screen-reader-text"><span>Push tagged contacts to these ' . $power_up_name . ' email lists</span></legend>';
                                                //
                                                $esp_name_readable = ucwords(str_replace('_', ' ', $esp_name));
                                                $esp_url = str_replace('_', '', $esp_name) . '.com';

                                                switch ( $esp_name ) 
                                                {
                                                    case 'mailchimp' :
                                                        $esp_list_url = 'http://admin.mailchimp.com/lists/new-list/';
                                                        $settings_page_anchor_id = '#ir_mls_api_key';
                                                        $invalid_key_message = 'It looks like your ' . $esp_name_readable . ' API key is invalid...<br/><br/>';
                                                        $invalid_key_link = '<a target="_blank" href="http://kb.mailchimp.com/accounts/management/about-api-keys#Find-or-Generate-Your-API-Key">Get your API key</a> from <a href="http://admin.mailchimp.com/account/api/" target="_blank">MailChimp.com</a>';
                                                    break;

                                                    default:
                                                        $esp_list_url = '';
                                                        $settings_page_anchor_id = '';
                                                    break;
                                                }

                                                if ( ! ${'inboundrocket_' . $power_up_slug . '_wp'}->admin->authed )
                                                {
                                                    echo 'It looks like you haven\'t set up your ' . $esp_name_readable . ' integration yet...<br/><br/>';
                                                    echo '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=inboundrocket_settings' . $settings_page_anchor_id . '">Set up your ' . $esp_name_readable . ' integration</a>';
                                                }
                                                else if ( ${'inboundrocket_' . $power_up_slug . '_wp'}->admin->invalid_key )
                                                {
                                                    echo $invalid_key_message;
                                                    echo '<p>' . $invalid_key_link . ' then try copying and pasting it again in <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=inboundrocket_settings' . $settings_page_anchor_id . '">Inbound Rocket → Settings</a></p>';
                                                }
                                                else if ( count($lists) )
                                                {
                                                    foreach ( $lists as $list )
                                                    {
                                                        $list_id = $list->id;

                                                        // Hack for constant contact's list id string (e.g. http://api.constantcontact.com/ws/customers/name%40website.com/lists/1234567890) 
                                                        if ( $power_up_name == 'Constant Contact' )
                                                            $list_id = end(explode('/', $list_id));

                                                        $html_id = 'email_connect_' . $esp_name . '_' . $list_id;
                                                        $synced = FALSE;

                                                        if ( $synced_lists )
                                                        {
                                                            
                                                            // Search the synched lists for this tag for the list_id
                                                            $key = inboundrocket_array_search_deep($list_id, $synced_lists, 'list_id');

                                                            if ( isset($key) )
                                                            {
                                                                // Double check that the list is synced with the actual ESP
                                                                if ( $synced_lists[$key]['esp'] == $esp_name )
                                                                    $synced = TRUE;
                                                            }
                                                        }

                                                        echo '<label for="' . $html_id  . '">';
                                                            echo '<input name="' . $html_id  . '" type="checkbox" id="' . $html_id  . '" value="' . $list->name . '" ' . ( $synced ? 'checked' : '' ) . '>';
                                                            echo $list->name;
                                                        echo '</label><br>';
                                                    }
                                                }
                                                else
                                                {
                                                    echo 'It looks like you don\'t have any ' . $esp_name_readable . ' lists yet...<br/><br/>';
                                                    echo '<a href="' . $esp_list_url . '" target="_blank">Create a list on ' . $esp_url . '</a>';
                                                }
                                            echo '</fieldset>';
                                        echo '</td>';
                                    echo '</tr>';
                                }
                            }
                            */
                        ?>
                        
                    </tbody></table>
                    <input type="hidden" name="list_id" value="<?php echo esc_attr($list_id); ?>" />
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes','inboundrocket'); ?>">
                    </p>
                </form>
            </div>

        </div>

        <?php
    }

    /**
     * Creates list table for Lead Lists page
     *
     */
    function inboundrocket_render_tag_list_page ()
    {
        global $wp_version;

        if ( $this->action == 'delete_list')
        {
            $list_id = ( isset($_GET['tag']) ? $_GET['tag'] : FALSE);
            $tagger = new IR_Lead_List_Editor($list_id);
            $tagger->delete_list($list_id);
        }

        //Create an instance of our package class...
        $inboundrocketTagsTable = new IR_Lead_List_Table();

        // Process any bulk actions before the contacts are grabbed from the database
        $inboundrocketTagsTable->process_bulk_action();
        
        //Fetch, prepare, sort, and filter our data...
        $inboundrocketTagsTable->data = $inboundrocketTagsTable->get_lead_lists();
        $inboundrocketTagsTable->prepare_items();

        ?>
        <div class="inboundrocket-contacts">

            <?php
                $this->inboundrocket_header(''.__('Manage Inbound Rocket Lead Lists', 'inboundrocket') .' <a href="' . wp_nonce_url(admin_url('/admin.php?page=inboundrocket_lead_lists&action=add_list')).'" class="add-new-h2">'.__('Add New', 'inboundrocket') .'</a>', 'inboundrocket-contacts__header');
            ?>
            
            <div class="">

                <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                <form id="" method="GET">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />
                    
                    <div class="inboundrocket-contacts__table">
                        <?php $inboundrocketTagsTable->display();  ?>
                    </div>

                    <input type="hidden" name="contact_type" value="<?php echo ( isset($_GET['contact_type']) ? esc_attr($_GET['contact_type']) : '' ); ?>"/>
                   
                    <?php if ( isset($_GET['filter_content']) ) : ?>
                        <input type="hidden" name="filter_content" value="<?php echo ( isset($_GET['filter_content']) ? esc_attr($_GET['filter_content']) : '' ); ?>"/>
                    <?php endif; ?>

                    <?php if ( isset($_GET['filter_action']) ) : ?>
                        <input type="hidden" name="filter_action" value="<?php echo ( isset($_GET['filter_action']) ? esc_attr($_GET['filter_action']) : '' ); ?>"/>
                    <?php endif; ?>

                </form>
                
            </div>

        </div>

        <?php
    }
    
    /**
     * Creates contacts chart content
     */
    function inboundrocket_build_contacts_chart_stats () 
    {
        $contacts_chart = "";

        $contacts_chart .= "<div class='inboundrocket-stats__chart-container'>";
            $contacts_chart .= '<div id="contacts_chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>';
        $contacts_chart .= "</div>";
        $contacts_chart .= "<div class='inboundrocket__big-numbers-container'>";
            $contacts_chart .= "<div class='inboundrocket-stats__big-number'>";
                $contacts_chart .= "<label class='inboundrocket-stats__big-number-top-label'>".__('Today','inboundrocket')."</label>";
                $contacts_chart .= "<h1  class='inboundrocket-stats__big-number-content'>" . number_format($this->stats_dashboard->total_new_contacts) . "</h1>";
                $contacts_chart .= "<label class='inboundrocket-stats__big-number-bottom-label'>".__('new','inboundrocket')." " . ( $this->stats_dashboard->total_new_contacts != 1 ? __('contacts','inboundrocket') : __('contact','inboundrocket') ) . "</label>";
            $contacts_chart .= "</div>";
            $contacts_chart .= "<div class='inboundrocket-stats__big-number big-number--average'>";
                $contacts_chart .= "<label class='inboundrocket-stats__big-number-top-label'>".__('Avg last 90 days','inboundrocket')."</label>";
                $contacts_chart .= "<h1  class='inboundrocket-stats__big-number-content'>" . number_format($this->stats_dashboard->avg_contacts_last_90_days) . "</h1>";
                $contacts_chart .= "<label class='inboundrocket-stats__big-number-bottom-label'>".__('new','inboundrocket')." " . ( $this->stats_dashboard->avg_contacts_last_90_days != 1 ? __('contacts','inboundrocket') : __('contact','inboundrocket') ) . "</label>";
            $contacts_chart .= "</div>";
            $contacts_chart .= "<div class='inboundrocket-stats__big-number'>";
                $contacts_chart .= "<label class='inboundrocket-stats__big-number-top-label'>".__('Best day ever','inboundrocket')."</label>";
                $contacts_chart .= "<h1  class='inboundrocket-stats__big-number-content'>" . number_format($this->stats_dashboard->best_day_ever) . "</h1>";
                $contacts_chart .= "<label class='inboundrocket-stats__big-number-bottom-label'>".__('new','inboundrocket')." " . ( $this->stats_dashboard->best_day_ever != 1 ? __('contacts','inboundrocket') : __('contact','inboundrocket') ) . "</label>";
            $contacts_chart .= "</div>";
            $contacts_chart .= "<div class='inboundrocket-stats__big-number'>";
                $contacts_chart .= "<label class='inboundrocket-stats__big-number-top-label'>".__('All time','inboundrocket')."</label>";
                $contacts_chart .= "<h1  class='inboundrocket-stats__big-number-content'>" . number_format($this->stats_dashboard->total_contacts) . "</h1>";
                $contacts_chart .= "<label class='inboundrocket-stats__big-number-bottom-label'>".__('total','inboundrocket')." " . ( $this->stats_dashboard->total_contacts != 1 ? __('contacts','inboundrocket') : __('contact','inboundrocket') ) . "</label>";
            $contacts_chart .= "</div>";
        $contacts_chart .= "</div>";

        return $contacts_chart;
    }
    
     /**
     * Creates contacts chart content
     */
    function inboundrocket_build_new_contacts_postbox () 
    {
        $new_contacts_postbox = "";

        if ( count($this->stats_dashboard->new_contacts) )
        {
            $new_contacts_postbox .= '<table class="inboundrocket-postbox__table"><tbody>';
                $new_contacts_postbox .= '<tr>';
                    $new_contacts_postbox .= '<th>'.__('contact','inboundrocket').'</th>';
                    $new_contacts_postbox .= '<th>'.__('pageviews','inboundrocket').'</th>';
                    $new_contacts_postbox .= '<th>'.__('original source','inboundrocket').'</th>';
                $new_contacts_postbox .= '</tr>';

                foreach ( $this->stats_dashboard->new_contacts as $contact )
                {
                   	$gravatar_hash = md5( strtolower( trim( $contact->lead_email ) ) ); 
                    $new_contacts_postbox .= '<tr>';
                        $new_contacts_postbox .= '<td class="">';
                            $new_contacts_postbox .= '<a href="'.admin_url('admin.php?page=inboundrocket_contacts&action=view&lead=' . $contact->lead_id . '&stats_dashboard=1').'"><img class="lazy pull-left inboundrocket-contact-avatar inboundrocket-dynamic-avatar_' . substr($contact->lead_id, -1) .'" src="http://www.gravatar.com/avatar/' . $gravatar_hash . '" width="35px" height="35px" style="border-radius: 50%"><b>' . $contact->lead_email . '</b></a>';
                        $new_contacts_postbox .= '</td>';
                        $new_contacts_postbox .= '<td class="">' . $contact->pageviews . '</td>';
                        $new_contacts_postbox .= '<td class="">' . $this->stats_dashboard->print_readable_source($this->stats_dashboard->check_lead_source($contact->lead_source, $contact->lead_origin_url)) . '</td>';
                    $new_contacts_postbox .= '</tr>';
                }

            $new_contacts_postbox .= '</tbody></table>';
        }
        else
            $new_contacts_postbox .= '<i>'.__('No new contacts today...','inboundrocket').'</i>';

        return $new_contacts_postbox;
    }
    
    /**
     * Creates returning contacts chart content
     */
    function inboundrocket_build_returning_contacts_postbox () 
    {
        $returning_contacts_postbox = "";

        if ( count($this->stats_dashboard->returning_contacts) )
        {
            $returning_contacts_postbox .= '<table class="inboundrocket-postbox__table"><tbody>';
                $returning_contacts_postbox .= '<tr>';
                    $returning_contacts_postbox .= '<th>'.__('contact','inboundrocket').'</th>';
                    $returning_contacts_postbox .= '<th>'.__('pageviews','inboundrocket').'</th>';
                    $returning_contacts_postbox .= '<th>'.__('original source','inboundrocket').'</th>';
                $returning_contacts_postbox .= '</tr>';

                foreach ( $this->stats_dashboard->returning_contacts as $contact )
                {
                    $gravatar_hash = md5( strtolower( trim( $contact->lead_email ) ) );
                    $returning_contacts_postbox .= '<tr>';
                        $returning_contacts_postbox .= '<td class="">';
                            $returning_contacts_postbox .= '<a href="'.admin_url('admin.php?page=inboundrocket_contacts&action=view&lead=' . $contact->lead_id . '&stats_dashboard=1').'"><img class="lazy pull-left inboundrocket-contact-avatar inboundrocket-dynamic-avatar_' . substr($contact->lead_id, -1) .'" src="http://www.gravatar.com/avatar/' . $gravatar_hash . '" width="35px" height="35px" style="border-radius: 50%"><b>' . $contact->lead_email . '</b></a>';
                        $returning_contacts_postbox .= '</td>';
                        $returning_contacts_postbox .= '<td class="">' . $contact->pageviews . '</td>';
                        $returning_contacts_postbox .= '<td class="">' . $this->stats_dashboard->print_readable_source($this->stats_dashboard->check_lead_source($contact->lead_source, $contact->lead_origin_url)) . '</td>';
                    $returning_contacts_postbox .= '</tr>';
                }

            $returning_contacts_postbox .= '</tbody></table>';
        }
        else
            $returning_contacts_postbox .= '<i>'.__('No new contacts today...','inboundrocket').'</i>';

        return $returning_contacts_postbox;
    }
    
    /**
     * Creates most popuplar pages chart content
     */
    function inboundrocket_build_most_popular_pages_postbox () 
    {
        $most_popular_pages_postbox = "";


        if ( count($this->stats_dashboard->most_popular_pages) )
        {
			$most_popular_pages_postbox .= '<table class="inboundrocket-postbox__table"><tbody>';
            $most_popular_pages_postbox .= '<tr>';
                $most_popular_pages_postbox .= '<th style="width:60%">'.__('Page','inboundrocket').'</th>';
                $most_popular_pages_postbox .= '<th style="width:10%;text-align:center;">'.__('Count','inboundrocket').'</th>';
                $most_popular_pages_postbox .= '<th style="width:30%;"></th>';
            $most_popular_pages_postbox .= '</tr>';
            
            foreach ( $this->stats_dashboard->most_popular_pages as $pages )
            {
	            
	        if(!empty($pages['total']))
	        {
	        	$percentage = ($pages['total'] * 100)/$pages['max_total'];
	        	if($percentage>100) $percentage = 100;
	        	
	        } else
	        	$percentage = 0;
	        	   
            $most_popular_pages_postbox .= '<tr>';
                $most_popular_pages_postbox .= '<td style="width:40%" class="">' . esc_attr($pages['page_title']) . '</td>';
                $most_popular_pages_postbox .= '<td style="width:10%;text-align:center;" class="">' . esc_attr($pages['total']) . '</td>';
                $most_popular_pages_postbox .= '<td style="width:50%;overflow:hidden;">';
                    $most_popular_pages_postbox .= '<div style="background: #f0f0f0; padding: 0px; height: 20px !important;"><div style="background: #f16b18; height: 100%; width: ' . $percentage . '%;">&nbsp;</div></div>';
                $most_popular_pages_postbox .= '</td>';
            $most_popular_pages_postbox .= '</tr>';
            
            }
            
            $most_popular_pages_postbox .= '</tbody></table>';
        }
        else
        	$most_popular_pages_postbox .= '<i>'.__('No most visited pages at this time...','inboundrocket').'</i>';

        return $most_popular_pages_postbox;
	}

    /**
     * Creates new shares chart content
     */
    function inboundrocket_build_new_shares_postbox () 
    {
        $new_shares_postbox = "";

        if ( count($this->stats_dashboard->new_shares) )
        {
			$new_shares_postbox .= '<table class="inboundrocket-postbox__table"><tbody>';
            $new_shares_postbox .= '<tr>';
            $new_shares_postbox .= '<th style="width:15%;">'.__('Shared to','inboundrocket').'</th>';
            $new_shares_postbox .= '<th style="text-align:center;width:10%;">'.__('Count','inboundrocket').'</th>';
            $new_shares_postbox .= '<th style="width:75%;">'.__('Content','inboundrocket').'</th>';
            $new_shares_postbox .= '</tr>';
            
            foreach ( $this->stats_dashboard->new_shares as $shares )
            {
	            $new_shares_postbox .= '<tr>';
                $new_shares_postbox .= '<td class="">' . $shares['shared_to'] . '</td>';
                $new_shares_postbox .= '<td style="text-align:center;" class="">' . $shares['shared_count'] . '</td>';
                $new_shares_postbox .= '<td class="">' . $shares['shared_content'] . '</td>';
				$new_shares_postbox .= '</tr>';	
            }
            
            $new_shares_postbox .= '</tbody></table>';
            
        } else
        	$new_shares_postbox .= '<i>'.__('No new shares at this time...','inboundrocket').'</i>';
        return $new_shares_postbox;
	}

    /**
     * Creates popular referer chart content
     */
    function inboundrocket_build_most_popular_referrers_postbox () 
    {
        $most_popular_referrer_postbox = "";

        if ( count($this->stats_dashboard->most_popular_referrer) )
        {
	        $most_popular_referrer_postbox .= '<table class="inboundrocket-postbox__table"><tbody>';
            $most_popular_referrer_postbox .= '<tr>';
                $most_popular_referrer_postbox .= '<th width="60%">'.__('Referral','inboundrocket').'</th>';
                $most_popular_referrer_postbox .= '<th width="10%">'.__('Count','inboundrocket').'</th>';
                $most_popular_referrer_postbox .= '<th width="30%"></th>';
            $most_popular_referrer_postbox .= '</tr>';
            
            foreach ( $this->stats_dashboard->most_popular_referrer as $referral )
            {
	            
        	$percentage = round(($referral['referral_count']/$referral['referral_total']) * 100);
        	if($percentage>100) $percentage = 100;
	            
            $most_popular_referrer_postbox .= '<tr>';
                $most_popular_referrer_postbox .= '<td class="">' . $referral['referral_source'] . '</td>';
                $most_popular_referrer_postbox .= '<td class="">' . $referral['referral_count'] . '</td>';
                $most_popular_referrer_postbox .= '<td style="width:100%">';
                    $most_popular_referrer_postbox .= '<div style="background: #f0f0f0; padding: 0px; height: 20px !important;width:100%;"><div style="background: #f16b18; height: 100%; width: ' . $percentage . '%;">&nbsp;</div></div>';
                $most_popular_referrer_postbox .= '</td>';
            $most_popular_referrer_postbox .= '</tr>';
            
            }
            
            $most_popular_referrer_postbox .= '</tbody></table>';
        }
        else
        	$most_popular_referrer_postbox .= '<i>'.__('No referrers today...','inboundrocket').'</i>';

        return $most_popular_referrer_postbox;
	}	 
    
    /**
     * Creates sources chart content
     */
    function inboundrocket_build_sources_postbox () 
    {
        $sources_postbox = "";

        $sources_postbox .= '<table class="inboundrocket-postbox__table"><tbody>';
            $sources_postbox .= '<tr>';
                $sources_postbox .= '<th width="150">'.__('source','inboundrocket').'</th>';
                $sources_postbox .= '<th width="20">'.__('Contacts','inboundrocket').'</th>';
                $sources_postbox .= '<th></th>';
            $sources_postbox .= '</tr>';
            $sources_postbox .= '<tr>';
                $sources_postbox .= '<td class="">'.__('Direct Traffic','inboundrocket').'</td>';
                $sources_postbox .= '<td class="">' . $this->stats_dashboard->direct_count . '</td>';
                $sources_postbox .= '<td>';
                    $sources_postbox .= '<div style="background: #f0f0f0; padding: 0px; height: 20px !important;"><div style="background: #f16b18; height: 100%; width: ' . ( $this->stats_dashboard->max_source ? (($this->stats_dashboard->direct_count/$this->stats_dashboard->max_source)*100) : '0' ) . '%;">&nbsp;</div></div>';
                $sources_postbox .= '</td>';
            $sources_postbox .= '</tr>';
            $sources_postbox .= '<tr>';
                $sources_postbox .= '<td class="">'.__('Organic Search','inboundrocket').'</td>';
                $sources_postbox .= '<td class="">' . $this->stats_dashboard->organic_count . '</td>';
                $sources_postbox .= '<td>';
                    $sources_postbox .= '<div style="background: #f0f0f0; padding: 0px; height: 20px !important;"><div style="background: #f16b18; height: 100%; width: ' . ( $this->stats_dashboard->max_source ? (($this->stats_dashboard->organic_count/$this->stats_dashboard->max_source)*100) : '0' ) . '%;">&nbsp;</div></div>';
                $sources_postbox .= '</td>';
            $sources_postbox .= '</tr>';
            $sources_postbox .= '<tr>';
                $sources_postbox .= '<td class="">'.__('Referrals','inboundrocket').'</td>';
                $sources_postbox .= '<td class="">' . $this->stats_dashboard->referral_count . '</td>';
                $sources_postbox .= '<td>';
                    $sources_postbox .= '<div style="background: #f0f0f0; padding: 0px; height: 20px !important;"><div style="background: #f16b18; height: 100%; width: ' . ( $this->stats_dashboard->max_source ? (($this->stats_dashboard->referral_count/$this->stats_dashboard->max_source)*100) : '0' ) . '%;">&nbsp;</div></div>';
                $sources_postbox .= '</td>';
            $sources_postbox .= '</tr>';
            $sources_postbox .= '<tr>';
                $sources_postbox .= '<td class="">'.__('Social Media','inboundrocket').'</td>';
                $sources_postbox .= '<td class="">' . $this->stats_dashboard->social_count . '</td>';
                $sources_postbox .= '<td>';
                    $sources_postbox .= '<div style="background: #f0f0f0; padding: 0px; height: 20px !important;"><div style="background: #f16b18; height: 100%; width: ' . ( $this->stats_dashboard->max_source ? (($this->stats_dashboard->social_count/$this->stats_dashboard->max_source)*100) : '0' ). '%;">&nbsp;</div></div>';
                $sources_postbox .= '</td>';
            $sources_postbox .= '</tr>';
            $sources_postbox .= '<tr>';
                $sources_postbox .= '<td class="">'.__('Email Marketing','inboundrocket').'</td>';
                $sources_postbox .= '<td class="">' . $this->stats_dashboard->email_count . '</td>';
                $sources_postbox .= '<td>';
                    $sources_postbox .= '<div style="background: #f0f0f0; padding: 0px; height: 20px !important;"><div style="background: #f16b18; height: 100%; width: ' . ( $this->stats_dashboard->max_source ? (($this->stats_dashboard->email_count/$this->stats_dashboard->max_source)*100) : '0' ) . '%;">&nbsp;</div></div>';
                $sources_postbox .= '</td>';
            $sources_postbox .= '</tr>';
            $sources_postbox .= '<tr>';
                $sources_postbox .= '<td class="">'.__('Paid Search','inboundrocket').'</td>';
                $sources_postbox .= '<td class="">' . $this->stats_dashboard->paid_count . '</td>';
                $sources_postbox .= '<td>';
                    $sources_postbox .= '<div style="background: #f0f0f0; padding: 0px; height: 20px !important;"><div style="background: #f16b18; height: 100%; width: ' . ( $this->stats_dashboard->max_source ? (($this->stats_dashboard->paid_count/$this->stats_dashboard->max_source)*100) : '0' ) . '%;">&nbsp;</div></div>';
                $sources_postbox .= '</td>';
            $sources_postbox .= '</tr>';
        $sources_postbox .= '</tbody></table>';

        return $sources_postbox;
    }
    
    /**
     * Prints checkbox for Inbound Rocket user subscription
     */
    function ir_subscription_callback()
    {
	    $options = get_option('inboundrocket_options');
	    ?>
	    <label for="ir_updates_subscription"><input type="checkbox" id="ir_updates_subscription" value="1" name="inboundrocket_options[ir_updates_subscription]" <?php echo checked( 1, ( isset($options['ir_updates_subscription']) ? $options['ir_updates_subscription'] : '0' ), FALSE ) ?>/><?php _e('Keep me up to date with security and feature updates','inboundrocket');?></label>
	    <?php
    }
    
    /**
     * Prints checkboxes for toggling Inbound Rocket access to specific user roles
     */
    function ir_grant_access_callback ()
    {
        $options = get_option('inboundrocket_options');
     
        $user_roles = get_editable_roles();
        
        // Show a disabled checkbox for administrative roles that always need to be enabled so users don't get locked out of the Inbound Rocket settings
        echo '<p><input id="ir_grant_access_to_administrator" type="checkbox" value="1" checked disabled/>';
        echo '<label for="ir_grant_access_to_administrator">'.__('Administrators','inboundrocket').'</label></p>';

        if ( count($user_roles) )
        {
            foreach ( $user_roles as $key => $role )
            {
                $admin_role = FALSE;
                if ( isset($role['capabilities']['activate_plugins']) && $role['capabilities']['activate_plugins'] )
                    $admin_role = TRUE;

                $role_id = 'ir_grant_access_to_' . $key;

                if ( ! $admin_role )
                {
                    printf(
                        '<p><input id="' . $role_id . '" type="checkbox" name="inboundrocket_options[' . $role_id . ']" value="1"' . checked( 1, ( isset($options[$role_id]) ? $options[$role_id] : '0' ), FALSE ) . '/>' . 
                        '<label for="' . $role_id . '">' . $role['name'] . 's' . '</label></p>'
                    );
                }
            }
        }
    }
    
    function ir_visitor_tracking_settings()
    {
	    $this->plugin_settings_tabs['inboundrocket_options'] = __('Visitor Tracking','inboundrocket');
        add_settings_section('ir_settings_section', '', array($this, 'inboundrocket_options_section_heading'), 'inboundrocket_options');
        
        add_settings_field(
            'ir_email',
            __('Notification email','inboundrocket'),
            array($this, 'ir_email_callback'),
            'inboundrocket_options',
            'ir_settings_section'
        );
        
        if ( function_exists('curl_init') && function_exists('curl_setopt') ) : 
        
        add_settings_field(
        	'ir_updates_subscription', 
        	__('Subscribe to updates','inboundrocket'), 
        	array($this, 'ir_subscription_callback'), 
        	'inboundrocket_options', 
        	'ir_settings_section'
        );
        
        endif;
                
        add_settings_field(
            'ir_do_not_track',
            __('Do not track logged in','inboundrocket'),
            array($this, 'ir_do_not_track_callback'),
            'inboundrocket_options',
            'ir_settings_section'
        );
        
        add_settings_field(
            'ir_grant_access',
            __('Grant Inbound Rocket access to','inboundrocket'),
            array($this, 'ir_grant_access_callback'),
            'inboundrocket_options',
            'ir_settings_section'
        );
    }
    
    function ir_emails_settings()
    {
	   $this->plugin_settings_tabs['inboundrocket_email_options'] = __('Emails','inboundrocket');
	   add_settings_section('ir_emails_section', '', '', 'inboundrocket_email_options');
	    
	   add_settings_field(
	   		'ir_emails_welcome_send', 
	   		__('Send welcome email','inboundrocket'),
	   		array($this, 'ir_emails_welcome_send_callback'),
	   		'inboundrocket_email_options',
	   		'ir_emails_section'
	   );
	   
	   add_settings_field(
	   		'ir_emails_welcome_subject', 
	   		__('Welcome email subject','inboundrocket'),
	   		array($this, 'ir_emails_welcome_subject_callback'),
	   		'inboundrocket_email_options',
	   		'ir_emails_section'
	   ); 
	   
	   add_settings_field(
	   		'ir_emails_welcome_content', 
	   		__('Welcome email content','inboundrocket'),
	   		array($this, 'ir_emails_welcome_content_callback'),
	   		'inboundrocket_email_options',
	   		'ir_emails_section'
	   );     
    }
    
    function ir_emails_welcome_subject_callback()
    {
	     $options = get_option('inboundrocket_email_options');
	    $ir_emails_welcome_subject = isset($options['ir_emails_welcome_subject']) && $options['ir_emails_welcome_subject'] ? sanitize_text_field($options['ir_emails_welcome_subject']) : '';
     
        printf(
            '<input id="ir_emails_welcome_subject" type="text" id="title" name="inboundrocket_email_options[ir_emails_welcome_subject]" value="%s" style="width:70%%;" /><br/><span class="description">'.__('This is the subject of the welcome email, which will be send after someone signs-up on your website.','inboundrocket').'</span>',
            $ir_emails_welcome_subject
        ); 
	}
	
	function ir_emails_welcome_content_callback()
    {
	    $options = get_option('inboundrocket_email_options');
	    $ir_emails_welcome_content = isset($options['ir_emails_welcome_content']) && $options['ir_emails_welcome_content'] ? wp_kses($options['ir_emails_welcome_content'], $this->kses_html, $this->protocals) : '';
     
        printf(
            '<textarea name="inboundrocket_email_options[ir_emails_welcome_content]" id="ir_emails_welcome_content" style="width:70%%;height:350px;">%s</textarea><br/><span class="description">'.__('This is the email which will be send after someone sign-ups on your website. It can either be plain text or you can copy paste your HTML code in here..','inboundrocket').'</span>',
            $ir_emails_welcome_content
        ); 
	}
    
    function ir_emails_welcome_send_callback()
    {
	    $options = get_option('inboundrocket_email_options');
	    ?>
	    <label for="ir_emails_welcome_send"><input type="checkbox" id="ir_emails_welcome_send" value="1" name="inboundrocket_email_options[ir_emails_welcome_send]" <?php echo checked( 1, ( isset($options['ir_emails_welcome_send']) ? intval($options['ir_emails_welcome_send']) : '0' ), FALSE ) ?>/><?php _e('Send a welcome email after someone signs up?','inboundrocket');?></label>
	    <?php
    }
    
    /**
     * Creates settings options
     */
    function inboundrocket_settings_page()
    {
		global $inboundrocket_contacts;
		
		register_setting('inboundrocket_options', 'inboundrocket_options', array($this, 'sanitize'));
		register_setting('inboundrocket_email_options', 'inboundrocket_email_options', array($this, 'sanitize'));
		
		$this->ir_visitor_tracking_settings();
		$this->ir_emails_settings();
		 
		if( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['inboundrocket_email_options']) ){
	    	inboundrocket_update_option('inboundrocket_options','inboundrocket_email_options',$_POST['inboundrocket_email_options']);  
        }				
		if( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['inboundrocket_ss_options']) ){
	    	inboundrocket_update_option('inboundrocket_options','inboundrocket_ss_options',$_POST['inboundrocket_ss_options']);  
        }
		if( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['inboundrocket_ctt_options']) ){
	    	inboundrocket_update_option('inboundrocket_options','inboundrocket_ctt_options',$_POST['inboundrocket_ctt_options']);  
        }
        if( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['inboundrocket_is_options']) ){
        	inboundrocket_update_option('inboundrocket_options','inboundrocket_is_options',$_POST['inboundrocket_is_options']);
        }
        if( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['inboundrocket_wb_options']) ){
        	inboundrocket_update_option('inboundrocket_options','inboundrocket_wb_options',$_POST['inboundrocket_wb_options']);
        }
        
        $inboundrocket_active_power_ups = unserialize(get_option('inboundrocket_active_power_ups'));
        
        if (count($inboundrocket_active_power_ups) > 1 )
		{
	        foreach($this->admin_power_ups as $power_up){
		        if($power_up->activated == 1 && $power_up->permanent != '1'){
			        $options_name = $power_up->options_name;
			        
			        switch($options_name){
				        case "inboundrocket_ctt_options":
				        
				        if(class_exists('WPClickToTweetAdmin')){
					        $c2t = WPClickToTweetAdmin::init();
					        $this->plugin_settings_tabs[$options_name] = __('Click To Tweet','inboundrocket');
							register_setting($options_name, $options_name, array($c2t, 'sanitize'));
							add_settings_section('ir_ctt_section', '', '', $options_name);
							add_settings_field('ir_ctt_settings', __('Click To Tweet Settings','inboundrocket'), array($c2t, 'ir_ctt_input_fields'), $options_name, 'ir_ctt_section');
						}
				        
				        break;
				        case "inboundrocket_ss_options":
				        
				        if(class_exists('WPSelectionSharerAdmin')){
					        $ss = WPSelectionSharerAdmin::init();
				        	$this->plugin_settings_tabs[$options_name] = __('Selection Sharer','inboundrocket');
							register_setting($options_name, $options_name, array($ss, 'sanitize'));
							add_settings_section('ir_ss_section', '', '', $options_name);
							add_settings_field('ir_ss_settings', __('Selection Sharer Settings','inboundrocket'), array($ss, 'ir_ss_input_fields'), $options_name, 'ir_ss_section');
						}
				        
				        break;
				        case "inboundrocket_wb_options":
				        
				        if(class_exists('WPWelcomeBarAdmin')){
					        $wb = WPWelcomeBarAdmin::init();
				       		$this->plugin_settings_tabs[$options_name] = __('Welcome Bar','inboundrocket');
							register_setting($options_name, $options_name, array($wb, 'sanitize'));
							add_settings_section('ir_wb_section', '', '', $options_name);
							add_settings_field('ir_wb_settings', __('Welcome Bar Settings','inboundrocket'), array($wb, 'ir_wb_input_fields'), $options_name, 'ir_wb_section');
						}
				        
				        break;
			        }
		        }
	        }
	        
		} else {
			
	        add_settings_section('ir_settings_section', ''.__('You have not activated any power-ups. Visit the','inboundrocket').' <a href="'.admin_url('admin.php?page=inboundrocket_power_ups').'">'.__('power-ups page','inboundrocket').'</a>, '. __('activate some today and start increasing conversions.','inboundrocket').'', '', 'inboundrocket_options');
	        
	    }
	    
	    // Update onboarding steps
	    $options = get_option('inboundrocket_options');
	    if(!isset($options['onboarding_step'])) 
	    	inboundrocket_update_option('inboundrocket_options', 'onboarding_step', 1);
	        	
	    if(isset($_POST['onboarding_step'])){
	    	inboundrocket_update_option('inboundrocket_options','onboarding_step',intval($_POST['onboarding_step']));
	    }
       
		if(isset($_POST['onboarding_complete'])){
			inboundrocket_update_option('inboundrocket_options','onboarding_complete',intval($_POST['onboarding_complete']));
		}
		
		if(isset($_POST['ir_updates_subscription'])){
			inboundrocket_update_option('inboundrocket_options','ir_updates_subscription',absint($_POST['ir_updates_subscription']));
		}
       
	}
	
    function inboundrocket_options_section_heading ( )
    {
       $this->tracking_code_installed_message();   
    }

    function print_hidden_settings_fields()
    {
         // Hacky solution to solve the Settings API overwriting the default values
        $options = get_option('inboundrocket_options');

        $ir_installed               = ( isset($options['ir_installed']) ? esc_attr($options['ir_installed']) : 1 );
        $inboundrocket_version      = ( isset($options['inboundrocket_version']) ? esc_attr($options['inboundrocket_version']) : INBOUNDROCKET_PLUGIN_VERSION );
        $ir_db_version              = ( isset($options['ir_db_version']) ? esc_attr($options['ir_db_version']) : INBOUNDROCKET_DB_VERSION );
        $onboarding_complete        = ( isset($options['onboarding_complete']) ? esc_attr($options['onboarding_complete']) : 0 );
        $onboarding_step            = ( isset($options['onboarding_step']) ? esc_attr($options['onboarding_step'])+1 : 1 );
        $converted_to_tags          = ( isset($options['converted_to_tags']) ? esc_attr($options['converted_to_tags']) : 0 );
        $names_added_to_contacts    = ( isset($options['names_added_to_contacts']) ? esc_attr($options['names_added_to_contacts']) : 0 );
        $premium					= ( isset($options['premium']) ? esc_attr($options['premium']) : 0 );
        
        printf(
            '<input id="ir_installed" type="hidden" name="inboundrocket_options[ir_installed]" value="%d" />',
            esc_attr( $ir_installed )
        );
        
        printf(
            '<input id="inboundrocket_version" type="hidden" name="inboundrocket_options[inboundrocket_version]" value="%s" />',
            esc_attr( $inboundrocket_version )
        );

        printf(
            '<input id="ir_db_version" type="hidden" name="inboundrocket_options[ir_db_version]" value="%s" />',
            esc_attr( $ir_db_version )
        );

        printf(
            '<input id="onboarding_complete" type="hidden" name="inboundrocket_options[onboarding_complete]" value="%d" />',
            esc_attr( $onboarding_complete )
        );
        
        printf(
            '<input id="onboarding_step" type="hidden" name="inboundrocket_options[onboarding_step]" value="%d" />',
            esc_attr( $onboarding_step )
        );
        
        printf(
            '<input id="converted_to_tags" type="hidden" name="inboundrocket_options[converted_to_tags]" value="%d" />',
            esc_attr( $converted_to_tags )
        );

        printf(
            '<input id="names_added_to_contacts" type="hidden" name="inboundrocket_options[names_added_to_contacts]" value="%d" />',
            esc_attr( $names_added_to_contacts )
        );
        
        printf(
            '<input id="premium" type="hidden" name="inboundrocket_options[premium]" value="%d" />',
            esc_attr( $premium )
        );

    }
    
    function has_leads ( )
    {
        global $wpdb;

        $q = "SELECT COUNT(hashkey) FROM {$wpdb->ir_leads} WHERE lead_deleted = 0 AND hashkey != '' AND lead_email != ''";
        $num_contacts = $wpdb->get_var($q);

        if ( $num_contacts > 0 )
        {
           return true;
        }
        else
        {
           return false;
        }
    }

    function tracking_code_installed_message ( )
    {
        global $wpdb;

        $num_contacts = $wpdb->get_var( "SELECT COUNT(hashkey) FROM {$wpdb->ir_leads} WHERE lead_deleted = 0 AND hashkey != '' AND lead_email != ''" );

        if ( $num_contacts > 0 )
        {
            echo '<div class="inboundrocket-section">';
                echo '<p style="color: #090; font-weight: bold;">'. __('Visitor tracking is installed and tracking visitors.','inboundrocket').'</p>';
                echo '<p>'.__('The next time a visitor fills out a form on your WordPress site with an email address, Inbound Rocket will show you the contact\'s referral source, page view history and actions taken on the site.','inboundrocket').'</p>';
            echo '</div>';
        }
        else
        {
            echo '<div class="inboundrocket-section">';
                echo '<p style="color: #ff7c00; font-weight: bold;">'.__('Inbound Rocket is setup and waiting for a form submission...','inboundrocket').'</p>';
                echo '<p>'.__('Can\'t wait to see Inbound Rocket in action? Go fill out a form on your site to see your first contact.','inboundrocket').'</p>';
            echo '</div>';
        }
    }

   /**
     * Prints do not track checkboxes for settings page
     */
    function ir_do_not_track_callback()
    {
        $options = get_option('inboundrocket_options');
        $user_roles = get_editable_roles();

        if ( count($user_roles) )
        {
            foreach ( $user_roles as $key => $role )
            {
                $role_id = 'ir_do_not_track_' . $key;
                printf(
                    '<p><input id="' . $role_id . '" type="checkbox" name="inboundrocket_options[' . $role_id . ']" value="1"' . checked( 1, ( isset($options[$role_id]) ? $options[$role_id] : '0' ), FALSE ) . '/>' . 
                    '<label for="' . $role_id . '">' . $role['name'] . 's' . '</label></p>'
                );
            }
        }
    }
    
   /**
     * Creates settings page
     */
    function inboundrocket_plugin_options()
    {
        if ( !current_user_can( 'manage_categories' ) ) {
            wp_die(__('You do not have sufficient permissions to access this page.','inboundrocket'));
        }

        $ir_options = get_option('inboundrocket_options');
        
        // Load onboarding for new plugin install
        if ( !isset($ir_options['onboarding_complete']) || $ir_options['onboarding_complete'] == 0 ) {
	        inboundrocket_update_option('inboundrocket_options', 'onboarding_complete', 0);
            $this->inboundrocket_plugin_onboarding();
        } else {
	        inboundrocket_track_plugin_activity("Loaded Settings Page");
            $this->inboundrocket_plugin_settings();
        } 
    }

    /**
     * Creates premium page
     */
    function inboundrocket_premium_upgrade_page ()
    {
       global  $wp_version;
       
       inboundrocket_track_plugin_activity("Loaded Premium Page");

        echo '<div id="inboundrocket" class="ir-premium wrap '. ( $wp_version < 3.8 && !is_plugin_active('mp6/mp6.php') ? 'pre-mp6' : ''). '">';
        
        $this->inboundrocket_header('Inbound Rocket Premium (soon)');
        
        ?>
        <p><?php _e('Inbound Rocket Premium will give you all the goodies of Inbound Rocket and more','inboundrocket');?>... </p>
        <p><?php _e('Currently your Inbound Rocket installation offers you already some great goodies','inboundrocket');?>:</p>
            <div class="featurelist">   
                <div class="content">
                    <ul class="features">
                        <li><?php _e('Contacts Tracking','inboundrocket');?></li>
                        <p><?php _e('Learn more about your visitors.','inboundrocket');?></p>
                        <li><?php _e('Contacts Analytics','inboundrocket');?></li>
                        <p><?php _e('Find out what content and traffic sources convert the best.','inboundrocket');?></p>
                        <li><?php _e('Helpful power-ups','inboundrocket');?></li>
                        <p><?php _e('Things like our Click To Tweet, Selection Sharer, and Welcome Bar functionality will help you get more traffic and convert your visitors more easily.','inboundrocket');?></p>
                    </ul>
                </div>
            </div>
            <p><?php _e('However we got more in store for you, keep an eye out on','inboundrocket');?> <a href='http://inboundrocket.co/?utm_source=<?php echo $_SERVER['HTTP_HOST']; ?>&utm_medium=plugin-admin&utm_campaign=premium-upgrade' title='<?php _e('Powered by Inbound Rocket - You write. We\'ll turn them into leads','inboundrocket');?>' target='_blank'><?php _e('our blog','inboundrocket');?></a> <?php _e('or','inboundrocket');?> <a href='https://twitter.com/inboundrocket' target='_blank' title='Inbound Rocket on Twitter'><?php _e('our Twitter','inboundrocket');?></a> <?php _e('or','inboundrocket');?> <a href='https://www.facebook.com/inboundrocket' target='_blank' title='Inbound Rocket on Facebook'>Facebook</a> <?php _e('to be amongst the first to find out!','inboundrocket');?></p>
			<div style="clear:both"></div>
            
        <?php

        $this->inboundrocket_footer();

        //end wrap
        echo '</div>';

    }  

    /**
     * Creates onboarding settings page
     */
    function inboundrocket_plugin_onboarding ()
    {
        global  $wp_version;

        inboundrocket_track_plugin_activity("Loaded On-boarding Page");
        $ir_options = get_option('inboundrocket_options');
        
        echo '<div id="inboundrocket" class="ir-onboarding wrap '. ( $wp_version < 3.8 && !is_plugin_active('mp6/mp6.php') ? 'pre-mp6' : ''). '">';
    
        $this->inboundrocket_header('Inbound Rocket Plugin Setup');
        ?>
        <div class="onboarding-steps">
		<?php if ( ! isset($_GET['activate_powerup']) ) : ?>
            
            <?php if ( $ir_options['onboarding_step'] == 1 ) : ?>

                <?php inboundrocket_track_plugin_activity('Onboarding Step 2 - Get Contact Reports'); ?>

                <ol class="onboarding-steps-names">
                    <li class="onboarding-step-name completed"><?php _e('Activate Inbound Rocket','inboundrocket');?></li>
                    <li class="onboarding-step-name active"><?php _e('Get Contact Reports','inboundrocket');?></li>
                    <li class="onboarding-step-name"><?php _e('Activate Power-Ups','inboundrocket');?></li>
                </ol>
                <div class="onboarding-step">
                    <h2 class="onboarding-step-title"><?php _e('Where should we send your contact reports?','inboundrocket');?></h2>
                    <div class="onboarding-step-content">
                        <p class="onboarding-step-description"><strong>Inbound Rocket</strong> <?php _e('will help you get to know your website visitors by sending you a report including traffic source and pageview history each time a visitor fills out a form.','inboundrocket');?></p>
                        <form id="ir-onboarding-form" method="post" action="<?=admin_url('options.php');?>">
	                        <?php
		                        $this->print_hidden_settings_fields();
		                    ?>
							<?php settings_fields('inboundrocket_options'); ?>
							<div>
                            <?php $this->ir_email_callback(); ?>
                            <?php if ( function_exists('curl_init') && function_exists('curl_setopt') ) : ?>
                                    <br />
                            <?php $this->ir_subscription_callback(); ?>
                            <?php endif; ?>
							</div>
                            <input type="submit" name="submit" id="submit" class="button button-primary button-big" value="<?php esc_attr_e( __('Save Email','inboundrocket')); ?>">
                        </form>
                    </div>
                </div>

            <?php elseif ( $ir_options['onboarding_step'] == 2 ) : ?>
								
                <?php 
	                
	                inboundrocket_track_plugin_activity('Onboarding Step 3 - Activate Power-Ups'); 
	                inboundrocket_update_option('inboundrocket_options','onboarding_step',3); 
	                
                ?>

				<ol class="onboarding-steps-names">
                    <li class="onboarding-step-name completed"><?php _e('Activate Inbound Rocket','inboundrocket');?></li>
                    <li class="onboarding-step-name completed"><?php _e('Get Contact Reports','inboundrocket');?></li>
                    <li class="onboarding-step-name active"><?php _e('Activate Power-Ups','inboundrocket');?></li>
                </ol>
                <div class="onboarding-step">
                    <h2 class="onboarding-step-title"><?php _e('Grow your visitors and your contacts on','inboundrocket');?> <?php echo get_bloginfo('wpurl') ?><br><small><?php _e('by activating power-ups now','inboundrocket');?></small></h2>
                    <form id="ir-onboarding-form" method="post" action="<?=admin_url('options.php');?>">
	                    
                        <?php $this->print_hidden_settings_fields();  ?>
                        <div class="popup-options">
                            <label class="popup-option">
                                <input type="checkbox" name="powerups" value="selection_sharer" checked="checked"><?php _e('Selection Sharer','inboundrocket');?>
                                <img src="<?php echo INBOUNDROCKET_PATH ?>/img/power-ups/power-up-icon-selection-sharer@2x.png">
                            </label>
                            <label class="popup-option">
                                <input type="checkbox" name="powerups" value="click_to_tweet"><?php _e('Click To Tweet','inboundrocket');?>
                                <img src="<?php echo INBOUNDROCKET_PATH ?>/img/power-ups/powerup-icon-click-to-tweet@2x.png">
                            </label>
                            <label class="popup-option">
                                <input type="checkbox" name="powerups" value="welcome_bar"><?php _e('Welcome Bar','inboundrocket');?>
                                <img src="<?php echo INBOUNDROCKET_PATH ?>/img/power-ups/powerup-icon-welcome-bar@2x.png">
                            </label>
                        </div>
                        <a id="btn-activate-subscribe" href="<?php echo admin_url('admin.php?page=inboundrocket_settings&inboundrocket_action=activate&redirect_to=' . urlencode(admin_url('admin.php?page=inboundrocket_settings&activate_powerup=true'))); ?>&power_up=selection_sharer" class="button button-primary button-big"><?php esc_attr_e(__('Activate these power-ups','inboundrocket'));?></a>
                        <p><a href="<?php echo admin_url('admin.php?page=inboundrocket_settings&activate_powerup=false'); ?>"><?php _e('Don\'t activate power-ups right now','inboundrocket');?></a></p>
                    </form>
                </div>
                
                <script type="text/javascript">
	                jQuery(document).ready(function($){
		                $('input[name=powerups]').change(function() {
							var url = $('#btn-activate-subscribe').attr('href');
							if( $(this).is(":checked") ) {
								url = url + ','+$(this).val();
								console.log(url);
								$('#btn-activate-subscribe').prop('href',url);
							} else {
								var url = $('#btn-activate-subscribe').attr('href');
								url = url.replace(','+$(this).val(),'');
								url = url.replace($(this).val(),'');
								$('#btn-activate-subscribe').prop('href',url);
							}
						});
					});
	            </script>

            <?php endif; ?>

        <?php else : ?>
			
            <?php
	            inboundrocket_update_option('inboundrocket_options','onboarding_complete',1); 
	            inboundrocket_track_plugin_activity('Onboarding Complete');
            ?>

            <ol class="onboarding-steps-names">
                <li class="onboarding-step-name completed"><?php _e('Activate Inbound Rocket','inboundrocket');?></li>
                <li class="onboarding-step-name completed"><?php _e('Get Contact Reports','inboundrocket');?></li>
                <li class="onboarding-step-name completed"><?php _e('Activate Power-Ups','inboundrocket');?></li>
            </ol>
            <div class="onboarding-step">
                <h2 class="onboarding-step-title"><?php _e('Setup Complete!','inboundrocket');?><br><small><?php _e('Inbound Rocket is waiting for your first form submission.','inboundrocket');?></small></h2>
                <div class="onboarding-step-content">
                    <p class="onboarding-step-description"><?php _e('Inbound Rocket is setup and waiting for a form submission.','inboundrocket');?><br /> <?php _e('Once Inbound Rocket detects a form submission, a new contact will be added to your contacts list. We recommend filling out a form on your site to test that Inbound Rocket is working correctly.','inboundrocket');?></p><p class="onboarding-step-description"><?php if (isset($_GET['activate_powerup']) && $_GET['activate_powerup']=='true') { ?><?php _e('Next to our basic form and visitor tracking the following power-ups are also activated','inboundrocket');?>:<br /><?php _e('Press "Complete Setup" and configure your power-ups.','inboundrocket');?><?php } ?></p>
                        <a href="<?php echo admin_url('admin.php?page=inboundrocket_settings'); ?>" class="button button-primary button-big"><?php esc_attr_e(__('Complete Setup','inboundrocket')); ?></a>
                </div>
            </div>

        <?php endif; ?>
        
        </div>

        
        <div class="onboarding-steps-help">
            <h4><?php _e('Any questions?','inboundrocket');?></h4>
            <?php if ( isset($ir_options['premium']) && $ir_options['premium'] ) : ?>
                <p><?php _e('Send us a message and we’re happy to help you get set up.','inboundrocket');?></p>
                <a class="button" href="mailto:help@inboundrocket.co?Subject=Hello" title="Contact Inbound Rocket Support."><?php _e('Mail with us','inboundrocket');?></a>
            <?php else : ?>
                <p><?php _e('Leave us a message in the WordPress support forums. We\'re always happy to help you get set up and can answer any questions there.','inboundrocket');?></p>
                <a class="button" href="https://wordpress.org/support/plugin/inbound-rocket" target="_blank"><?php _e('Go to Forums','inboundrocket');?></a>
            <?php endif; ?>
        </div>
        

        <?php
        
        $this->inboundrocket_footer();

        //end wrap
        echo '</div>';
    }
	
	function plugin_options_tabs() {
	    $current_tab = isset( $_GET['tab'] ) ? esc_attr($_GET['tab']) : 'inboundrocket_options';
		
	    screen_icon();
	    echo '<h2 class="nav-tab-wrapper">';
	    foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
	        $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
	        echo '<a class="nav-tab ' . $active . '" href="'.admin_url('admin.php?page=inboundrocket_settings&tab=' . $tab_key).'">' . $tab_caption . '</a>';
	    }
	    echo '</h2>';
	}
	
    /**
     * Creates default settings page
     */
    function inboundrocket_plugin_settings()
    {
        global  $wp_version;
                
        echo '<div id="inboundrocket" class="ir-settings wrap '. ( $wp_version < 3.8 && !is_plugin_active('mp6/mp6.php') ? 'pre-mp6' : ''). '">';
        
        $this->inboundrocket_header(__('Inbound Rocket Settings','inboundrocket'));
        
        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'inboundrocket_options';

        $this->plugin_options_tabs();
        //@TODO if no module activated display different message
        
        ?>
            <form method="post" action="options.php">
	        <?php 
		        $this->print_hidden_settings_fields();
		        wp_nonce_field( 'ir-verify-nonce' );
                settings_fields($tab);
                do_settings_sections($tab);
                submit_button('Save Settings');
            ?>
            </form>
        <?php
        $this->inboundrocket_footer();

        //end wrap
        echo '</div>';
    }	

    /**
     * Prints email input for settings page
     */
    function ir_email_callback ()
    {
        $options = get_option('inboundrocket_options');
        $ir_email = ( isset($options['ir_email']) && $options['ir_email'] ? $options['ir_email'] : '' ); // Get email from plugin settings, if none set, use admin email
     
        printf(
            '<input id="ir_email" type="text" id="title" name="inboundrocket_options[ir_email]" value="%s" size="50"/><br/><span class="description">'. __('Separate multiple emails with commas. Leave blank to disable email notifications.','inboundrocket').'</span>',
            $ir_email
        );    
    }	

    /**
     * Creates power-up page
     */
    function inboundrocket_power_ups_page ()
    {
        global  $wp_version;
        
        inboundrocket_track_plugin_activity("Viewed Power-ups Page");

        if ( !current_user_can( 'manage_categories' ) )
        {
            wp_die(__('You do not have sufficient permissions to access this page.','inboundrocket'));
        }

        echo '<div id="inboundrocket" class="ir-settings wrap '. ( $wp_version < 3.8 && !is_plugin_active('mp6/mp6.php') ? 'pre-mp6' : ''). '">';
        
        $this->inboundrocket_header(__('Inbound Rocket Power-ups','inboundrocket'));
        
        ?>
            <p><?php _e('Get the most out of your Inbound Rocket installation by installing one of the below power-ups.','inboundrocket');?></p>
            
            <h2 class="powerup-title"><?php _e('Core Power Ups', 'inboundrocket'); ?></h2>
            <ul class="powerup-list">
	            <?php $power_up_count = 0; ?>
                                
                <?php foreach ( $this->admin_power_ups as $power_up ) : ?>
                    <?php 
                        // Skip displaying the power-up on the power-ups page if it's hidden
                        if ( $power_up->hidden )
                            continue;
                            
                        if ( $power_up_count == 1 ) :
                    ?>
					<!-- static content stats power-up - not a real power-up and this is a hack to put it second in the order -->
                        <li class="powerup activated">
                            <div class="img-container">
                                <img src="<?php echo INBOUNDROCKET_PATH; ?>/img/power-ups/powerup-icon-analytics@2x.png" height="120px" width="200px">
                            </div>
                            <h2><?php _e('Statistics','inboundrocket');?></h2>
                            <p><?php _e('Get some nice graphs to show you how you are doing.','inboundrocket');?></p>
                            <a href="<?php echo wp_nonce_url(admin_url('/wp-admin/admin.php?page=inboundrocket_stats')); ?>" class="button button-large"><?php _e('View Stats','inboundrocket');?></a>
                        </li>
                        
                        <li class="powerup activated">
		                    <div class="img-container">
		                        <img src="<?php echo INBOUNDROCKET_PATH; ?>/img/power-ups/powerup-icon-ideas@2x.png" height="120px" width="200px">
		                    </div>
		                    <h2><?php _e('Suggestions?','inboundrocket');?></h2>
		                    <p><?php _e('Have an idea for a power-up? We\'d love to hear it!','inboundrocket');?></p>
		                    <a href="mailto:ideas@inboundrocket.co" target="_blank" class="button button-primary button-large"><?php _e('Give us a shout','inboundrocket');?></a>
		                </li>
                    <?php
                        endif;
                    ?>
                    
                    <?php if( $power_up->slug == 'welcome_bar') : ?>
            			</ul>
            			<h2 class="powerup-title"><?php _e('Lead Converting Power Ups','inboundrocket'); ?></h2>
            			<ul class="powerup-list">
                    <?php elseif( $power_up->slug == 'selection_sharer') : ?>
            			</ul>
                    	<h2 class="powerup-title"><?php _e('Sharing Power Ups','inboundrocket'); ?></h2>
                    	<ul class="powerup-list">
                    <?php endif; ?>
                             
					<li class="powerup <?php echo ( $power_up->activated ? 'activated' : ''); ?>">
	                    <div class="img-container">
	                        <?php if ( strstr($power_up->icon, 'dashicon') ) : ?>
	                            <span class="<?php echo $power_up->icon; ?>"></span>
	                        <?php else : ?>
	                            <img src="<?php echo INBOUNDROCKET_PATH . '/img/power-ups/' . $power_up->icon . '@2x.png'; ?>" height="120px" width="200px"/>
	                        <?php endif; ?>
	                    </div>
	                    <h2><?php echo $power_up->power_up_name; ?></h2>
	                    <p><?php echo $power_up->description; ?></p>
	                    <p><a href="<?php echo $power_up->link_uri; ?>" target="_blank"><?php _e('Learn more','inboundrocket');?></a></p>  
	
	                    <?php if ( $power_up->activated ) : ?>
	                        <?php if ( ! $power_up->permanent ) : ?>
	                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=inboundrocket_power_ups&inboundrocket_action=deactivate&power_up=' . $power_up->slug)); ?>" class="button button-secondary button-large"><?php _e('Deactivate','inboundrocket');?></a>
	                        <?php endif; ?>
	                    <?php else : ?>
	                        <?php if ( ( $power_up->curl_required && function_exists('curl_init') && function_exists('curl_setopt') ) || ! $power_up->curl_required ) : ?>
	                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=inboundrocket_power_ups&inboundrocket_action=activate&power_up=' . $power_up->slug)); ?>" class="button button-primary button-large"><?php _e('Activate','inboundrocket');?></a>
	                        <?php else : ?>
	                            <p><a href="http://stackoverflow.com/questions/2939820/how-to-enable-curl-installed-ubuntu-lamp-stack" target="_blank"><?php _e('Install cURL','inboundrocket');?></a> <?php _e('to use this power-up.','inboundrocket');?></p>
	                        <?php endif; ?>
	                    <?php endif; ?>
	
	                    <?php if ( $power_up->activated || $power_up->permanent ) : ?>
	                        <?php if ( $power_up->menu_link == 'contacts' ) : ?>
	                            <a href="<?php echo admin_url('admin.php?page=inboundrocket_' . $power_up->menu_link); ?>" class="button button-secondary button-large"><?php _e('View Contacts','inboundrocket');?></a>
	                            <a href="<?php echo admin_url('admin.php?page=inboundrocket_settings'); ?>" class="button button-secondary button-large"><?php _e('Configure','inboundrocket');?></a>
	                        <?php else : ?>
	                            <a href="<?php echo admin_url('admin.php?page=inboundrocket_' . $power_up->menu_link.'&tab='.$power_up->options_name); ?>" class="button button-secondary button-large"><?php _e('Configure','inboundrocket');?></a>
	                        <?php endif; ?>
	                    <?php endif; ?>
					</li>

                 <?php $power_up_count++; ?>
                <?php endforeach; ?>
               
            </ul>
			<div style="clear:both"></div>
        <?php
        $this->inboundrocket_footer();
        
        //end wrap
       echo '</div>';
    }

    function check_admin_action ( )
    {
        if ( isset( $_GET['inboundrocket_action'] ) ) 
        {	        
            switch ( $_GET['inboundrocket_action'] ) 
            {
                case 'activate' :

                    $power_up = esc_attr( $_GET['power_up'] );
                    
                    if( strpos($power_up, ',') !== false ){
	                
	                    $power_ups = explode(',',$power_up);
	                    foreach($power_ups as $power_up){
	                    	WPInboundRocket::activate_power_up( $power_up, FALSE );
	                    	inboundrocket_track_plugin_activity($power_up . " power-up activated");
	                    }
                    
                    } else {
                    
                    	WPInboundRocket::activate_power_up( $power_up, FALSE );
                    	inboundrocket_track_plugin_activity($power_up . " power-up activated");
                    
                    }
                    
                    if ( isset($_GET['redirect_to']) )
                        wp_redirect($_GET['redirect_to']);
                    else
                        wp_redirect(admin_url('admin.php?page=inboundrocket_power_ups'));
                    exit;

                    break;

                case 'deactivate' :

                    $power_up = esc_attr( $_GET['power_up'] );
                    
                    if( strpos($power_up, ',') !== false ){
                    
                    	$power_ups = explode(',',$power_up);
	                    foreach($power_ups as $power_up){
	                    	WPInboundRocket::deactivate_power_up( $power_up, FALSE );
	                    	inboundrocket_track_plugin_activity($power_up . " power-up deactivated");
	                    }
                    	
					} else {
						
						WPInboundRocket::deactivate_power_up( $power_up, FALSE );
						inboundrocket_track_plugin_activity($power_up . " power-up deactivated");
						
					}
 
                    wp_redirect(admin_url('admin.php?page=inboundrocket_power_ups'));
                    exit;

                    break;
            }
        }
    }
	
	//=============================================
    // Admin Styles & Scripts
    //=============================================

    /**
     * Adds admin style sheets
     */
    function add_inboundrocket_admin_styles ()
    {
        $screen = get_current_screen();
        
        //die(print_r($screen));
        if ( in_array( $screen->id, array( 'inbound-rocket_page_inboundrocket_power_ups', 'toplevel_page_inboundrocket_stats', 'inbound-rocket_page_inboundrocket_settings', 'inbound-rocket_page_inboundrocket_contacts', 'inbound-rocket_page_inboundrocket_lead_lists', 'inbound-rocket_page_inboundrocket_premium_upgrade' ) ) ) {
	        if (INBOUNDROCKET_ENABLE_DEBUG==true) { wp_register_style('inboundrocket-admin-css', INBOUNDROCKET_PATH . '/admin/inc/css/inboundrocket-admin.css');    
	        } else { wp_register_style('inboundrocket-admin-css', INBOUNDROCKET_PATH . '/admin/inc/css/inboundrocket-admin.min.css');}
			wp_enqueue_style('inboundrocket-admin-css');
			
			if (INBOUNDROCKET_ENABLE_DEBUG==true) { wp_register_style('inboundrocket-select2', INBOUNDROCKET_PATH . '/inc/assets/css/select2.css');
			} else { wp_register_style('inboundrocket-select2', INBOUNDROCKET_PATH . '/inc/assets/css/select2.min.css'); }
			wp_enqueue_style('inboundrocket-select2');
        }
    }
    
    /**
     * Adds admin javascript
    */
    
    function add_inboundrocket_admin_scripts ()
    {
        global $pagenow;

        if ( ($pagenow == 'admin.php' && isset($_GET['page']) && strstr($_GET['page'], 'inboundrocket')) ) 
        {
	        wp_register_script('inboundrocket-highcharts-js', INBOUNDROCKET_PATH . '/admin/inc/js/highcharts.min.js', array ( 'jquery' ), FALSE, FALSE);
	        wp_enqueue_script('inboundrocket-highcharts-js');
	        
	        wp_register_script('inboundrocket-select2-js', INBOUNDROCKET_PATH . '/admin/inc/js/select2.min.js', array ( 'jquery' ), FALSE, FALSE);
	        wp_enqueue_script('inboundrocket-select2-js');

	        if (INBOUNDROCKET_ENABLE_DEBUG==true) { wp_register_script('inboundrocket-admin-js', INBOUNDROCKET_PATH . '/admin/inc/js/inboundrocket-admin.js', array ( 'jquery' ), FALSE, TRUE);
            } else { wp_register_script('inboundrocket-admin-js', INBOUNDROCKET_PATH . '/admin/inc/js/inboundrocket-admin.min.js', array ( 'jquery' ), FALSE, TRUE); }
            wp_enqueue_script('inboundrocket-admin-js');
            
            wp_localize_script('inboundrocket-admin-js', 'ir_admin_ajax', array('ajax_url' => get_admin_url(NULL,'') . '/admin-ajax.php'));

        }
    }

    //=============================================
    // Internal Class Functions
    //=============================================  
    /**
     * Creates postbox for admin
     *
     * @param string
     * @param string
     * @param string
     * @param bool
     * @return string   HTML for postbox
     */
    function inboundrocket_postbox ( $css_class, $title, $content, $handle = TRUE )
    {
        $postbox_wrap = "";
        
        $postbox_wrap .= '<div class="' . $css_class . ' inboundrocket-postbox">';
            $postbox_wrap .= '<h3 class="inboundrocket-postbox__header">' . $title . '</h3>';
            $postbox_wrap .= '<div class="inboundrocket-postbox__content">' . $content . '</div>';
        $postbox_wrap .= '</div>';

        return $postbox_wrap;
    }    
    /**
     * Prints the admin page title, icon and help notification
     *
     * @param string
     */
    function inboundrocket_header ( $page_title = '', $css_class = '' )
    {
        ?>
        <?php screen_icon('inboundrocket'); ?>
        <div class="ir-content">
	        <div class="ir-frame">
		        <div class="header">
					<nav role="navigation" class="header-nav drawer-nav nav-horizontal">
						<ul class="main-nav">
							<li class="inboundrocket-logo"><a href="<?php echo admin_url('admin.php?page=inboundrocket_stats'); ?>" title="Inbound Rocket Stats"><span>Inbound Rocket</span></a></li>
						</ul>
					</nav>
				</div><!-- header -->
				<div class="clouds-sm"></div>
				<div class="wrapper">
		        <h2 class="<?php echo $css_class ?>"><?php echo $page_title; ?></h2>
		
		        <?php $options = get_option('inboundrocket_options'); ?>
		
		        <?php if ( isset($_GET['settings-updated']) && $options['onboarding_complete'] ) : ?>
		            <div id="message" class="updated">
		                <p><strong><?php _e('Settings saved.', 'inboundrocket'); ?></strong></p>
		            </div>
		        <?php endif;
    } 
    
    
    /*
	 * Prints the Footer
	 */
    function inboundrocket_footer ()
    {
        $ir_options = get_option('inboundrocket_options');
        ?>
			        </div><!-- .wrapper -->
			
			        <div class="footer">
				        <div class="fly"></div>
				        <nav class="primary nav-horizontal">
							<ul class="primary-footer">
								<li class="current"><a href="http://inboundrocket.co/" target="_blank" title="Inbound Rocket">Inbound Rocket</a> <?php echo INBOUNDROCKET_PLUGIN_VERSION?></li>
							</ul>
						</nav><!-- .primary -->

						<nav class="secondary nav-horizontal">
							<ul class="secondary-footer">
								<?php if ( isset($ir_options['premium']) && $ir_options['premium'] ) : ?>
								<li>Need help? <a href="mailto:help@inboundrocket.co?Subject=Oops.." title="<?php _e('Contact Inbound Rocket Support.','inboundrocket'); ?>"><?php _e('Contact Us','inboundrocket'); ?></a></li>
								<?php else : ?>
								<li><a href="https://wordpress.org/support/plugin/inbound-rocket" target="_blank"><?php _e('Support forums', 'inboundrocket'); ?></a></li>
								<?php endif; ?>
								<li><a href="http://inboundrocket.co/blog/" title="<?php _e('Get product &amp; security updates','inboundrocket'); ?>" target="_blank"><?php _e('Get product &amp; security updates','inboundrocket'); ?></a></li>
								<li><a href="https://wordpress.org/support/view/plugin-reviews/inbound-rocket?rate=5#postform" title="<?php _e('Leave us a review','inboundrocket'); ?>"><?php _e('Leave us a review','inboundrocket'); ?></a></li>
							</ul>
						</nav><!-- .secondary -->
						
						<p class="sharing"><a href="https://twitter.com/inboundrocket" class="twitter-follow-button" data-show-count="false"><?php _e('Follow @inboundrocket','inboundrocket'); ?></a>
			
			            <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script></p>
			        </div>
								
			</div><!-- .ir-frame -->
		</div><!-- .ir-content -->
        <?php
    }
    
    function build_contacts_chart ( )
    {
        ?>
        <script type="text/javascript">
            function create_weekends ( $ )
            {
                var $ = jQuery;

                series = chart.get('contacts');
                var in_between = Math.floor(series.data[1].barX - (Math.floor(series.data[0].barX) + Math.floor(series.data[0].pointWidth)))*2;

                $series = $('.highcharts-series').first();
                $series.find('rect').each ( function ( e ) {
                    var $this = $(this);
                    $this.attr('width', (Math.floor(series.data[0].pointWidth) + Math.floor(in_between/2)));
                    $this.attr('x', $this.attr('x') - Math.floor(in_between/4));
                    $this.css('opacity', 100);
                });
            }

            function hide_weekends ( $ )
            {
                var $ = jQuery;

                series = chart.get('contacts');

                $series = $('.highcharts-series').first();
                $series.find('rect').each ( function ( e ) {
                    var $this = $(this);
                    $this.css('opacity', 10);
                });
            }

            function create_chart ( $ )
            {
                var $ = jQuery;

                $('#contacts_chart').highcharts({
                    chart: {
                        type: 'column',
                        style: {
                            fontFamily: "Open-Sans"
                        }
                    },
                    credits: {
                        enabled: false
                    },
                    title: {
                        text: ''
                    },
                    xAxis: {
                        categories: [ <?php echo $this->stats_dashboard->x_axis_labels; ?> ],
                        tickInterval: 2,
                        tickmarkPlacement: 'on',
                        labels: {
                            style: {
                                color: '#aaa',
                                fontFamily: 'Open Sans'
                            }
                        },
                        crosshair: true
                    },
                    yAxis: {
                        min: 0,
                        title: {
                            text: ''
                        },
                        gridLineColor: '#ddd',
                        labels: {
                            style: {
                                color: '#aaa',
                                fontFamily: 'Open Sans'
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        valueDecimals: 0,
                        borderColor: '#ccc',
                        borderRadius: 0,
                        shadow: false
                    },
                    plotOptions: {
                        column: {
                            borderWidth: 0, 
                            borderColor: 'rgba(0,0,0,0)',
                            showInLegend: false,
                            colorByPoint: true,
                            states: {
                                brightness: 0
                            }
                        },
                        line: {
                            enableMouseTracking: false,
                            linkedTo: ':previous',
                            dashStyle: 'ShortDash',
                            dataLabels: {
                                enabled: false
                            },
                            marker: {
                                enabled: false
                            },
                            color: '#F16B18',
                            tooltip: {
                                enabled: false
                            },
                            showInLegend: false
                        }
                    },
                    series: [{
                        type: 'column',
                        name: '<?php _e('Unique Visitors','inboundrocket');?>',
                        id: 'visits',
                        data: [ <?php echo $this->stats_dashboard->visits_data; ?> ],
                        tooltip: {
							enabled: true
            			},
                        zIndex: 4,
                        index: 4
                    }, {
                        type: 'column',
                        name: '<?php _e('Contacts','inboundrocket');?>',
                        id: 'contacts',
                        data: [ <?php echo $this->stats_dashboard->column_data; ?> ],
                        zIndex: 3,
                        index: 3
                    }, {
                        type: 'line',
                        name: '<?php _e('Average','inboundrocket');?>',
                        animation: false,
                        data: [ <?php echo $this->stats_dashboard->average_data; ?> ],
                        zIndex: 2,
                        index: 2
                    }, {
                        type: 'column',
                        name: '<?php _e('Weekends','inboundrocket');?>',
                        animation: false,
                        minPointLength: 500,
                        grouping: false,
                        tooltip: {
                            enabled: false
                        },
                        enableMouseTracking: false,
                        data: [ <?php echo $this->stats_dashboard->weekend_column_data; ?> ],
                        zIndex: 1,
                        index: 1,
                        id: 'weekends',
                        events: {
                            mouseOut: function ( event ) { event.preventDefault(); },
                            halo: false
                        },
                        states: {
                            hover: {
                                enabled: false
                            }
                        }
                    }]
            });
        }

        var $series;
        var chart;
        var $ = jQuery;

        $(document).ready( function ( e ) {
            
            create_chart();

            chart = $('#contacts_chart').highcharts();
            create_weekends();
        });

        var delay = (function(){
          var timer = 0;
          return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
          };
        })();

        // Takes care of figuring out the weekend widths based on the new column widths
        $(window).resize(function() {
            hide_weekends();
            height = chart.height
            width = $("#contacts_chart").width();
            chart.setSize(width, height);
            delay(function(){
                create_weekends();
            }, 500);
        });

	    </script>
		<?php
	}
    
    /**
     * GET and set url actions into readable strings
     * @return string if actions are set,   bool if no actions set
    */
    protected function inboundrocket_current_action()
    {
        if ( isset($_REQUEST['action']) && -1 != $_REQUEST['action'] )
            return $_REQUEST['action'];

        return FALSE;
    }  
}
?>