<?php

//=============================================
// WPInboundRocket Class
//=============================================
class WPInboundRocket {
	
    var $power_ups;
    /**
     * Class constructor
     */
    function __construct ()
    {
        global $pagenow;

        inboundrocket_set_wpdb_tables();
        inboundrocket_set_mysql_timezone_offset();

        $this->power_ups = self::get_available_power_ups();

        if ( is_user_logged_in() && is_admin() )
            add_action('admin_bar_menu', array($this, 'add_inboundrocket_link_to_admin_bar'), 999);
 
        if ( is_admin() )
        {
            if ( ! defined('DOING_AJAX') || ! DOING_AJAX )
	            $ir_wp_admin = new WPInboundRocketAdmin($this->power_ups);
        }
        else
        {
            if ( in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php')) )
                add_action('login_enqueue_scripts', array($this, 'add_inboundrocket_frontend_scripts'));
            else
                add_action('wp_enqueue_scripts', array($this, 'add_inboundrocket_frontend_scripts'));
        }
        
        add_action('wp_ajax_ir_ec_callback', array($this,'inboundrocket_ec_callback'));
		add_action('wp_ajax_nopriv_ir_ec_callback', array($this,'inboundrocket_ec_callback'));
    }
    
    //=============================================
    // Scripts & Styles
    //=============================================

    /**
     * Adds front end javascript + initializes ajax object
     */
    function add_inboundrocket_frontend_scripts()
    {
	    wp_register_script('inboundrocket-tracking-swf-script', INBOUNDROCKET_PATH . '/inc/assets/js/swfobject-2.2.min.js', null, false, true);
        wp_enqueue_script('inboundrocket-tracking-swf-script');
        wp_register_script('inboundrocket-tracking-ever-script', INBOUNDROCKET_PATH . '/inc/assets/js/evercookie.js', null, false, true);
        wp_enqueue_script('inboundrocket-tracking-ever-script');
        wp_localize_script(
            'inboundrocket-tracking-ever-script',
            'ir_var',
            array(
	            'lib_path' => INBOUNDROCKET_PATH . '/inc/lib/evercookie'
            )
        );
            
        if (INBOUNDROCKET_ENABLE_DEBUG==true) {
       		wp_register_script('inboundrocket-tracking', INBOUNDROCKET_PATH . '/inc/assets/js/inboundrocket-tracking.js', array ('jquery'), FALSE, TRUE);
        } else {
	        wp_register_script('inboundrocket-tracking', INBOUNDROCKET_PATH . '/inc/assets/js/inboundrocket-tracking.min.js', array ('jquery'), FALSE, TRUE);
        }
        wp_enqueue_script('inboundrocket-tracking');

        // replace https with http for admin-ajax calls for SSLed backends 
        wp_localize_script(
            'inboundrocket-tracking', 
            'ir_ajax', 
            array(
            	'ajax_url' => ( is_ssl() ? str_replace('http:', 'https:', admin_url('admin-ajax.php')) : str_replace('https:', 'http:', admin_url('admin-ajax.php')) ),
				'ir_nonce' => wp_create_nonce('ir-nonce-verify')
			)
        );
    }
    
    
    function inboundrocket_ec_callback()
    {
	    if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) exit();
	    
	    $hashkey = sanitize_textfield($_POST['hashkey']);
	    
	    include_once(INBOUNDROCKET_PLUGIN_DIR."/inc/class-notifier.php");
		$ir_notifier = new IR_Notifier();
		$ir_notifier->send_new_lead_email($hashkey);
    }
    

    /**
     * Adds Inbound Rocket link to top-level admin bar
     */
    function add_inboundrocket_link_to_admin_bar( $wp_admin_bar ) {
        global $wp_version;
        
        if ( ! current_user_can('activate_plugins') )
        {
            if ( ! array_key_exists('ir_grant_access_to_' . inboundrocket_get_user_role(), get_option('inboundrocket_options') ) )
                return FALSE;
        }

        $args = array(
            'id'     => 'inboundrocket-admin-menu',
            'title'  => '<span class="ab-icon" '. ( $wp_version < 3.8 && !is_plugin_active('mp6/mp6.php') ? ' style="margin-top: 3px;"' : ''). '><img src="' . content_url() . '/plugins/inbound-rocket/img/inboundrocket-svg-icon.svg" style="height:16px; width:16px;"></span><span class="ab-label">Inbound Rocket</span>', // alter the title of existing node
            'parent' => FALSE,   // set parent to false to make it a top level (parent) node
            'href' => admin_url('admin.php?page=inboundrocket_stats'),
            'meta' => array('title' => 'Inbound Rocket')
        );

        $wp_admin_bar->add_node( $args );
    }
    
    /**
     * List available power-ups
     */
    public static function get_available_power_ups ( $min_version = FALSE, $max_version = FALSE ) {
        static $power_ups = null;

        if ( ! isset( $power_ups ) ) {
            $files = self::glob_php( INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups' );

            $power_ups = array();

            foreach ( $files as $file ) {

                if ( ! $headers = self::get_power_up($file) ) {
                    continue;
                }

                $power_up = new $headers['class']($headers['activated']);
                $power_up->power_up_name    = $headers['name'];
                $power_up->menu_text        = $headers['menu_text'];
                $power_up->menu_link        = $headers['menu_link'];
                $power_up->slug             = $headers['slug'];
                $power_up->link_uri         = $headers['uri'];
                $power_up->description      = $headers['description'];
                $power_up->icon             = $headers['icon'];
                $power_up->permanent        = ( $headers['permanent'] == 'Yes' ? 1 : 0 );
                $power_up->auto_activate    = ( $headers['auto_activate'] == 'Yes' ? 1 : 0 );
                $power_up->hidden           = ( $headers['hidden'] == 'Yes' ? 1 : 0 );
                $power_up->curl_required    = ( $headers['curl_required'] == 'Yes' ? 1 : 0 );
                $power_up->options_name		= $headers['options_name'];
                $power_up->activated        = $headers['activated'];

                // Set the small icons HTML for the settings page
                if ( strstr($headers['icon_small'], 'dashicons') )
                    $power_up->icon_small = '<span class="dashicons ' . $headers['icon_small'] . '"></span>';
                else
                    $power_up->icon_small = '<img src="' . INBOUNDROCKET_PATH . '/img/power-ups/' . $headers['icon_small'] . '.png" class="power-up-settings-icon"/>';

                array_push($power_ups, $power_up);
            }
        }

        return $power_ups;       
    }

    /**
     * Extract a power-up's slug from its full path.
     */
    public static function get_power_up_slug ( $file ) {
        return str_replace( '.php', '', basename( $file ) );
    }

    /**
     * Generate a power-up's path from its slug.
     */
    public static function get_power_up_path ( $slug ) {
        return INBOUNDROCKET_PLUGIN_DIR . "/inc/power-ups/$slug.php";
    }

    /**
     * Load power-up data from power-up file. Headers differ from WordPress
     * plugin headers to avoid them being identified as standalone
     * plugins on the WordPress plugins page.
     *
     * @param $power_up The file path for the power-up
     * @return $pu array of power-up attributes
     */
    public static function get_power_up ( $power_up )
    {
        $headers = array(
            'name'              => 'Power-up Name',
            'class'             => 'Power-up Class',
            'menu_text'         => 'Power-up Menu Text',
            'menu_link'         => 'Power-up Menu Link',
            'slug'              => 'Power-up Slug',
            'uri'               => 'Power-up URI',
            'description'       => 'Power-up Description',
            'icon'              => 'Power-up Icon',
            'icon_small'        => 'Power-up Icon Small',
            'introduced'        => 'First Introduced',
            'auto_activate'     => 'Auto Activate',
            'permanent'         => 'Permanently Enabled',
            'power_up_tags'     => 'Power-up Tags',
            'hidden'            => 'Hidden',
            'curl_required'     => 'cURL Required',
            'options_name'		=> 'Options Name'
        );

        $file = self::get_power_up_path( self::get_power_up_slug( $power_up ) );
        if ( ! file_exists( $file ) )
            return FALSE;

        $pu = get_file_data( $file, $headers );

        if ( empty( $pu['name'] ) )
            return FALSE;

        $pu['activated'] = self::is_power_up_active($pu['slug']);

        return $pu;
    }

    /**
     * Returns an array of all PHP files in the specified absolute path.
     * Equivalent to glob( "$absolute_path/*.php" ).
     *
     * @param string $absolute_path The absolute path of the directory to search.
     * @return array Array of absolute paths to the PHP files.
     */
    public static function glob_php( $absolute_path ) {
        $absolute_path = untrailingslashit( $absolute_path );
        $files = array();
        if ( ! $dir = @opendir( $absolute_path ) ) {
            return $files;
        }

        while ( FALSE !== $file = readdir( $dir ) ) {
            if ( '.' == substr( $file, 0, 1 ) || '.php' != substr( $file, -4 ) ) {
                continue;
            }

            $file = "$absolute_path/$file";

            if ( ! is_file( $file ) ) {
                continue;
            }

            $files[] = $file;
        }

        $files = inboundrocket_sort_power_ups($files, array(
        	INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/contacts.php', 
        	INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/selection-sharer.php', 
        	INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/click-to-tweet.php',
            INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/welcome-bar.php', 
        ));

        closedir( $dir );

        return $files;
    }

    /**
     * Check whether or not a Inbound Rocket power-up is active.
     *
     * @param string $power_up The slug of a power-up
     * @return bool
     *
     * @static
     */
    public static function is_power_up_active ( $power_up_slug )
    {
        return in_array($power_up_slug, self::get_active_power_ups());
    }

    /**
     * Get a list of activated modules as an array of module slugs.
     */
    public static function get_active_power_ups ()
    {
        $activated_power_ups = get_option('inboundrocket_active_power_ups');
        if ( $activated_power_ups )
            return array_unique(unserialize($activated_power_ups));
        else
            return array();
    }

    public static function activate_power_up( $power_up_slug, $exit = TRUE )
    {
        if ( ! strlen( $power_up_slug ) )
            return FALSE;

        // If it's already active, then don't do it again
        $active = self::is_power_up_active($power_up_slug);
        if ( $active )
            return TRUE;

        $activated_power_ups = get_option('inboundrocket_active_power_ups');
        
        if ( $activated_power_ups )
        {
            $activated_power_ups = unserialize($activated_power_ups);
            $activated_power_ups[] = $power_up_slug;
        }
        else
        {
            $activated_power_ups = array($power_up_slug);
        }

        update_option('inboundrocket_active_power_ups', serialize($activated_power_ups));


        if ( $exit )
        {
            exit;
        }
    }

    public static function deactivate_power_up( $power_up_slug, $exit = TRUE )
    {
        if ( ! strlen( $power_up_slug ) )
            return FALSE;

        // If it's already active, then don't do it again
        $active = self::is_power_up_active($power_up_slug);
        if ( ! $active )
            return TRUE;

        $activated_power_ups = get_option('inboundrocket_active_power_ups');
        
        $power_ups_left = inboundrocket_array_delete(unserialize($activated_power_ups), $power_up_slug);
        update_option('inboundrocket_active_power_ups', serialize($power_ups_left));
        
        if ( $exit )
        {
            exit;
        }

    }

}

//=============================================
// Inbound Rocket Init
//=============================================

global $ir_wp_admin;