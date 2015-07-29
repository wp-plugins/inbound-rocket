<?php
/*
Plugin Name: Inbound Rocket
Plugin URI: http://wordpress.org/extend/plugins/inbound-rocket/
Description: Introducing a new way of generating traffic and converting them into leads on WordPress. Inbound Rocket is an easy-to-use marketing automation plugin for Wordpress. It features visitor activity tracking and the management of incoming leads to better understand your web visitors. It also offers great power-ups to help you get even more visitors and help them convert to leads, subscribers and customers.
Version: 1.0.4
Author: Inbound Rocket
Author URI: http://inboundrocket.co/
License: GPLv2
*/

//=============================================
// Define Constants
//=============================================

if ( !defined('ABSPATH') ) exit;

if ( !defined('INBOUNDROCKET_PATH') )
    define('INBOUNDROCKET_PATH', untrailingslashit(plugins_url('', __FILE__ )));

if ( !defined('INBOUNDROCKET_PLUGIN_DIR') )
	define('INBOUNDROCKET_PLUGIN_DIR', untrailingslashit(dirname( __FILE__ )));
	
if ( !defined('INBOUNDROCKET_PLUGIN_SLUG') )
	define('INBOUNDROCKET_PLUGIN_SLUG', basename(dirname(__FILE__)));

if ( !defined('INBOUNDROCKET_DB_VERSION') )
	define('INBOUNDROCKET_DB_VERSION', '1.0');

if ( !defined('INBOUNDROCKET_PLUGIN_VERSION') )
	define('INBOUNDROCKET_PLUGIN_VERSION', '1.0.4');

if ( !defined('INBOUNDROCKET_MIXPANEL_PROJECT_TOKEN') )
    define('INBOUNDROCKET_MIXPANEL_PROJECT_TOKEN', '0dea951b7b7bac8bc60040ab8b707fe9');
    
if ( !defined('INBOUNDROCKET_ENABLE_MIXPANEL_DEBUG') )
	define('INBOUNDROCKET_ENABLE_MIXPANEL_DEBUG', false);    

if ( !defined('INBOUNDROCKET_ENABLE_DEBUG') )
	define('INBOUNDROCKET_ENABLE_DEBUG', false);

if ( !defined('INBOUNDROCKET_MC_KEY') )
    define('INBOUNDROCKET_MC_KEY', '60f6b1fa1750db2301b15dd6b7be7c50-us9');
    
if ( !defined('INBOUNDROCKET_MC_LIST') )
    define('INBOUNDROCKET_MC_LIST', '3d432d3c03');

if ( !defined('INBOUNDROCKET_SOURCE') )
    define('INBOUNDROCKET_SOURCE', 'inboundrocket.co');

//=============================================
// Include Needed Files
//=============================================
// Admin
require_once(INBOUNDROCKET_PLUGIN_DIR . '/admin/inboundrocket-admin.php');

// General
require_once(INBOUNDROCKET_PLUGIN_DIR . '/inc/lib/mixpanel/Mixpanel.php');
require_once(INBOUNDROCKET_PLUGIN_DIR . '/inc/inboundrocket-functions.php');
require_once(INBOUNDROCKET_PLUGIN_DIR . '/inc/class-notifier.php');
require_once(INBOUNDROCKET_PLUGIN_DIR . '/inc/inboundrocket-ajax-functions.php');
require_once(INBOUNDROCKET_PLUGIN_DIR . '/inc/class-inboundrocket.php');

// Power-ups
require_once(INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/contacts.php');
require_once(INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/selection-sharer.php');
require_once(INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/click-to-tweet.php');
require_once(INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/welcome-bar.php');
//=============================================
// Hooks & Filters
//=============================================

add_action( 'plugins_loaded', create_function( '', '$inboundrocket_wp = new WPInboundRocket;' ) );


if ( is_admin() ) 
{
	// Activate + install Inbound Rocket
	register_activation_hook( __FILE__, 'activate_inboundrocket');

	// Deactivate Inbound Rocket
	register_deactivation_hook( __FILE__, 'deactivate_inboundrocket');

	// Uninstall Inbound Rocket
	register_uninstall_hook(__FILE__, 'uninstall_inboundrocket');
	
	if(is_multisite()){
		// Activate on newly created wpmu blog
		add_action('wpmu_new_blog', 'activate_inboundrocket_on_new_blog', 10, 6);
	}
	
	// Redirect on activation of plugin for the first time for onboarding
	add_action('admin_init', 'inboundrocket_redirect');
}

/**
 * Activate the plugin
 */
function activate_inboundrocket($network_wide)
{
	// Check activation on entire network or one blog
	if ( is_multisite() && $network_wide ) 
	{ 
		global $wpdb;
 
		// Get this so we can switch back to it later
		$current_blog = $wpdb->blogid;
		// For storing the list of activated blogs
		$activated = array();
 
		// Get all blogs in the network and activate plugin on each one
		$q = "SELECT blog_id FROM {$wpdb->blogs}";
		$blog_ids = $wpdb->get_col($q);
		foreach ( $blog_ids as $blog_id ) 
		{
			switch_to_blog($blog_id);
			add_inboundrocket_defaults();
	        $activated[] = $blog_id;
			inboundrocket_track_plugin_registration_hook(TRUE);
		}
 
		// Store the array for a later function
		update_site_option('inboundrocket_activated', $activated);
		add_option('inboundrocket_do_onboard_redirect', true);
	}
	else
	{
		inboundrocket_track_plugin_registration_hook(TRUE);
		add_inboundrocket_defaults();
		add_option('inboundrocket_do_onboard_redirect', true);
	}
}

function inboundrocket_redirect()
{
	if (get_option('inboundrocket_do_onboard_redirect')) {
        delete_option('inboundrocket_do_onboard_redirect');
		wp_redirect(admin_url('admin.php?page=inboundrocket_settings'));
		exit;
	}
}

/**
 * Check inboundrocket installation and set options
 */
function add_inboundrocket_defaults()
{
	global $wpdb;

	$options = get_option('inboundrocket_options');

	if ( !isset($options['ir_installed']) || $options['ir_installed'] != 1 || !is_array($options) )
	{
		$opt = array(			
			'ir_installed'				=> 1,
			'inboundrocket_version'		=> INBOUNDROCKET_PLUGIN_VERSION,
			'ir_db_version'				=> INBOUNDROCKET_DB_VERSION,
			'ir_email' 					=> get_bloginfo('admin_email'),
			'ir_updates_subscription'	=> 1,
			'onboarding_step'			=> 1,
			'onboarding_complete'		=> 0,
			'names_added_to_contacts'	=> 1,
			'premium'					=> 0			
		);
		
		$email_opt = array(
			'ir_emails_welcome_send'	=> 0,
			'ir_emails_welcome_subject' => '',
			'ir_emails_welcome_content' => ''
		);
		
		if ( is_multisite() )
		{
			update_site_option( 'inboundrocket_options', $opt );
			update_site_option( 'inboundrocket_email_options', $email_opt );
		} else {
			update_option( 'inboundrocket_options', $opt );	
			update_option( 'inboundrocket_email_options', $email_opt );
		}
			
		inboundrocket_db_install();

		$wpdb->query("INSERT INTO {$wpdb->ir_tags}
		        ( tag_text, tag_slug, tag_form_selectors, tag_synced_lists, tag_order ) 
		    VALUES ('Leads', 'leads', '', '', 1),
		    	('Contact', 'contact', '', '', 2),
		        ('Contacted', 'contacted', '', '', 3),
		        ('Customers', 'customers', '', '', 4),
		        ('Ambassadors', 'ambassadors', '', '', 5),
		        ('Commenters', 'commenters', '#commentform', '', 6),
		        ('Subscribers', 'subscribers', '#welcome_bar', '', 7)", "");
	}

	$inboundrocket_active_power_ups = get_option('inboundrocket_active_power_ups');

	if ( !$inboundrocket_active_power_ups )
	{
		$auto_activate = array(
			'contacts'
		);
		update_option('inboundrocket_active_power_ups', serialize($auto_activate));
	}
}

/**
 * Deactivate inboundrocket plugin hook
 */
function deactivate_inboundrocket( $network_wide )
{
	if ( is_multisite() && $network_wide ) 
	{ 
		global $wpdb;
 
		// Get this so we can switch back to it later
		$current_blog = $wpdb->blogid;
 
		// Get all blogs in the network and activate plugin on each one
		$q = "SELECT blog_id FROM {$wpdb->blogs}";
		$blog_ids = $wpdb->get_col($q);
		foreach ( $blog_ids as $blog_id ) 
		{
			switch_to_blog($blog_id);
			inboundrocket_track_plugin_registration_hook(FALSE);
		}
 
		// Switch back to the current blog
		switch_to_blog($current_blog);
	}
	else
		inboundrocket_track_plugin_registration_hook(FALSE);
		
}

function activate_inboundrocket_on_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta )
{
	global $wpdb;
	
	if ( is_plugin_active_for_network(INBOUNDROCKET_PATH.'/inbound-rocket.php') )
	{
		$current_blog = $wpdb->blogid;
		switch_to_blog($blog_id);
		
		$options = get_option('inboundrocket_options');
		if(!isset($options) || empty($options)) add_inboundrocket_defaults();
		
		switch_to_blog($current_blog);
	}
	
	
}

//=============================================
// Database functions
//=============================================

/**
 * Drops or uninstalls the inboundrocket tables
 */
function uninstall_inboundrocket()
{	
	if( !defined( 'ABSPATH') ) exit();
    
	global $wpdb;
	
	$options = get_option('inboundrocket_options');
	$email = isset($options['ir_email']) ? $options['ir_email'] : get_bloginfo('admin_email');
	
	inboundrocket_mark_deleted_user($email);
				
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->ir_leads}");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->ir_pageviews}");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->ir_shares}");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->ir_submissions}");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->ir_tag_relationships}");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->ir_tags}");
	$wpdb->query("DROP TABLE IF EXISTS {$wpdb->ir_emails}");
	
	unregister_setting('inboundrocket_settings_options','inboundrocket_settings_options');
	delete_option( 'inboundrocket_options' );
	delete_site_option( 'inboundrocket_options' );
	
	unregister_setting('inboundrocket_settings_options','inboundrocket_active_power_ups');
	delete_option( 'inboundrocket_active_power_ups' );
	delete_site_option( 'inboundrocket_active_power_ups' );
	
	unregister_setting('inboundrocket_ss_options','inboundrocket_ss_options');
	delete_option( 'inboundrocket_ss_options' );
	delete_site_option( 'inboundrocket_ss_options' );
	
	unregister_setting('inboundrocket_ctt_options','inboundrocket_ctt_options');
	delete_option( 'inboundrocket_ctt_options' );
	delete_site_option( 'inboundrocket_ctt_options' );
	
	unregister_setting('inboundrocket_is_options','inboundrocket_is_options');
	delete_option( 'inboundrocket_is_options' );
	delete_site_option( 'inboundrocket_is_options' );
	
	unregister_setting('inboundrocket_wb_options','inboundrocket_wb_options');
	delete_option( 'inboundrocket_wb_options' );
	delete_site_option( 'inboundrocket_wb_options' );
	
	unregister_setting('inboundrocket_email_options','inboundrocket_email_options');
	delete_option( 'inboundrocket_email_options' );
	delete_site_option( 'inboundrocket_email_options' );
	
	unregister_setting('inboundrocket_settings_options','inboundrocket_subscribe_options');
	delete_option( 'inboundrocket_subscribe_options' );
	delete_site_option( 'inboundrocket_subscribe_options' );
	
	inboundrocket_track_plugin_activity("Plugin Uninstalled");
}

/**
 * Creates or updates the inboundrocket tables
 */
function inboundrocket_db_install()
{
	global $wpdb;
		
	$charset_collate = $wpdb->get_charset_collate();
	
	inboundrocket_set_wpdb_tables();
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->ir_leads} (
		  `lead_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `lead_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `hashkey` varchar(16) DEFAULT NULL,
		  `lead_ip` varchar(40) DEFAULT NULL,
		  `lead_source` text,
		  `lead_email` varchar(255) DEFAULT NULL,
		  `lead_first_name` varchar(255) NOT NULL,
  		  `lead_last_name` varchar(255) NOT NULL,
		  `lead_status` set('leads','contact','contacted','customers','ambassadors','commenters','subscribers') NOT NULL DEFAULT 'leads',
		  `merged_hashkeys` text,
		  `lead_deleted` int(1) NOT NULL DEFAULT '0',
		  `blog_id` int(11) unsigned NOT NULL,
		  `company_data` mediumtext NOT NULL,
  		  `social_data` mediumtext NOT NULL,
		  PRIMARY KEY (`lead_id`),
		  KEY `hashkey` (`hashkey`)
		) {$charset_collate};";
		
	dbDelta($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->ir_pageviews} (
		  `pageview_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `pageview_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `lead_hashkey` varchar(16) NOT NULL,
		  `pageview_title` varchar(255) NOT NULL,
		  `pageview_url` text NOT NULL,
		  `pageview_source` text NOT NULL,
		  `pageview_session_start` int(1) NOT NULL,
		  `pageview_deleted` int(1) NOT NULL DEFAULT '0',
		  `blog_id` int(11) unsigned NOT NULL,
		  PRIMARY KEY (`pageview_id`),
		  KEY `lead_hashkey` (`lead_hashkey`)
		) {$charset_collate};";
		
	dbDelta($sql);

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->ir_submissions} (
		  `form_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `form_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `lead_hashkey` varchar(16) NOT NULL,
		  `form_page_title` varchar(255) NOT NULL,
		  `form_page_url` text NOT NULL,
		  `form_fields` text NOT NULL,
		  `form_selector_id` mediumtext NOT NULL,
		  `form_selector_classes` mediumtext NOT NULL,
		  `form_hashkey` varchar(16) NOT NULL,
		  `form_deleted` int(1) NOT NULL DEFAULT '0',
		  `blog_id` int(11) unsigned NOT NULL,
		  PRIMARY KEY (`form_id`),
		  KEY `lead_hashkey` (`lead_hashkey`)
		) {$charset_collate};";
		
	dbDelta($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->ir_shares} (
		  `share_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `share_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `lead_hashkey` varchar(16) NOT NULL,
		  `share_type` set('contact','ss-twitter-text','is-twitter-image','click-to-tweet','ss-email-text','ss-email-image','ss-facebook-text','is-facebook-image','ss-linkedin-text','is-pinterest-image') NOT NULL,
		  `share_deleted` int(1) NOT NULL DEFAULT '0',
		  `share` text NOT NULL,
		  `post_id` int(11) NOT NULL,
		  `blog_id` int(11) unsigned NOT NULL,
		  PRIMARY KEY (`share_id`),
		  KEY `lead_hashkey` (`lead_hashkey`)
		) {$charset_collate};";
		
	dbDelta($sql);
   		
   	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->ir_sharer_stats} (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
   			`share_id` int(11) unsigned NOT NULL,
   			`timestamp` timestamp NOT NULL,
   			`type` TEXT NOT NULL,
   			PRIMARY KEY (`id`),
   			CONSTRAINT `{$wpdb->ir_sharer_stats}` FOREIGN KEY (`share_id`) REFERENCES `{$wpdb->ir_shares}` (`id`) ON DELETE CASCADE
   		) {$charset_collate};";
		
	dbDelta($sql);
   		
   	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->ir_tags} (
		  `tag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `tag_text` varchar(255) NOT NULL,
		  `tag_slug` varchar(255) NOT NULL,
		  `tag_form_selectors` mediumtext NOT NULL,
		  `tag_synced_lists` mediumtext NOT NULL,
		  `tag_order` int(11) unsigned NOT NULL,
		  `blog_id` int(11) unsigned NOT NULL,
		  `tag_deleted` int(1) NOT NULL,
		  PRIMARY KEY (`tag_id`)
		) {$charset_collate};";
		
	dbDelta($sql);

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->ir_tag_relationships} (
		  `tag_relationship_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `tag_id` int(11) unsigned NOT NULL,
		  `contact_hashkey` varchar(16) NOT NULL,
  		  `form_hashkey` varchar(16) NOT NULL,
		  `tag_relationship_deleted` int(1) unsigned NOT NULL,
		  `blog_id` int(11) unsigned NOT NULL,
		  PRIMARY KEY (`tag_relationship_id`)
		) {$charset_collate};";
		
	dbDelta($sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->ir_emails} (
		  `email_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `email_subject` varchar(255) NOT NULL,
		  `email_text_content` varchar(255) NOT NULL,
		  `email_html_content` mediumtext NOT NULL,
		  `email_deleted` int(1) NOT NULL,
		  PRIMARY KEY (`email_id`)
		) {$charset_collate};";
		
	dbDelta($sql);

    inboundrocket_update_option('inboundrocket_options', 'ir_db_version', INBOUNDROCKET_DB_VERSION);
	inboundrocket_track_plugin_activity("Databases Installed");
}

/**
 * Sets the wpdb tables to the current blog
 * 
 */
function inboundrocket_set_wpdb_tables ()
{
    global $wpdb;
    
    $wpdb->ir_leads       				= $wpdb->prefix . 'ir_leads';
    $wpdb->ir_pageviews       			= $wpdb->prefix . 'ir_pageviews';
    $wpdb->ir_submissions       		= $wpdb->prefix . 'ir_submissions';
    $wpdb->ir_shares       				= $wpdb->prefix . 'ir_shares'; 
    $wpdb->ir_sharer_stats				= $wpdb->prefix . 'ir_sharer_stats';
    $wpdb->ir_tags 						= $wpdb->prefix . 'ir_tags';
    $wpdb->ir_tag_relationships			= $wpdb->prefix . 'ir_tag_relationships';
    $wpdb->ir_emails					= $wpdb->prefix . 'ir_emails';
}
?>
