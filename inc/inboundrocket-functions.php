<?php
if ( !defined('ABSPATH') ) exit;

if ( !defined('INBOUNDROCKET_PLUGIN_VERSION') ) 
{
    header('HTTP/1.0 403 Forbidden');
    die;
}

/**
 * Updates an option in the multi-dimensional option array
 *
 * @param   string   $option        option_name in wp_options
 * @param   string   $option_key    key for array
 * @param   string   $option        new value for array
 *
 * @return  bool            True if option value has changed, false if not or if update failed.
 */
function inboundrocket_update_option ( $option, $option_key, $new_value ) 
{
    $options_array = get_option($option);

    if ( isset($options_array[$option_key]) )
    {
        if ( $options_array[$option_key] === $new_value )
            return false; // Don't update an option if it already is set to the value
    }

    if ( !is_array( $options_array ) ) {
        $options_array = array();
    }

    $options_array[$option_key] = esc_attr($new_value);
    update_option($option, $options_array);

    $options_array = get_option($option);
    return update_option($option, $options_array);
}

/**
 * Prints a number with a singular or plural label depending on number
 *
 * @param   int
 * @param   string
 * @param   string
 * @return  string 
 */
function inboundrocket_single_plural_label ( $number, $singular_label, $plural_label ) 
{
    //Set number = 0 when the variable is blank
    $number = ( !is_numeric($number) ? 0 : $number );

    return ( $number != 1 ? $number . " $plural_label" : $number . " $singular_label" );
}
	
/**
 * Get Inbound Rocket user
 *
 * @return  array
 */
function inboundrocket_get_current_user ()
{
    global $wp_version;
    global $current_user;

    get_currentuserinfo();
    $ir_user_id = md5(get_site_url());

    $ir_options = get_option('inboundrocket_options');
    
    if ( isset($ir_options['ir_email']) ) {
        $ir_user_email = esc_attr($ir_options['ir_email']);
    } 
    else {
        $ir_user_email = $current_user->user_email;
    }
    
    $plugins = wp_get_active_and_valid_plugins();

    $inboundrocket_user = array(
        'user_id' => $ir_user_id,
        'email' => $ir_user_email,
        'alias' => $current_user->display_name,
        'wp_url' => get_site_url(),
        'ir_version' => INBOUNDROCKET_PLUGIN_VERSION,
        'wp_version' => $wp_version,
        'total_contacts' => get_total_contacts(),
        'wp_plugins' => $plugins,
    );

    return $inboundrocket_user;
}

/**
 * Gets the total number of contacts, comments and subscribers for above the table
 */
function get_total_contacts ()
{
    global $wpdb;

    if ( ! isset($wpdb->ir_leads) )
        return 0;

    $q = "SELECT COUNT(DISTINCT hashkey) AS total_contacts FROM $wpdb->ir_leads WHERE lead_email != '' AND lead_deleted = 0 AND hashkey != ''";

    $total_contacts = $wpdb->get_var($q);
    return $total_contacts;
}

/**
 * Register Inbound Rocket user
 *
 * @return  bool
 */
function inboundrocket_register_user()
{

    if ( ! function_exists('curl_init') )
        return false;
    
    // @push mixpanel event for updated email
    $inboundrocket_user = inboundrocket_get_current_user();
    $mp = Mixpanel::getInstance(INBOUNDROCKET_MIXPANEL_PROJECT_TOKEN, array("debug" => INBOUNDROCKET_ENABLE_MIXPANEL_DEBUG));
    $mp->identify($inboundrocket_user['user_id']);
    $mp->createAlias( $inboundrocket_user['user_id'], $inboundrocket_user['alias']);
    $mp->people->set( $inboundrocket_user['user_id'], array(
	    '$ir-version'   => $inboundrocket_user['ir_version'],
        '$email'        => $inboundrocket_user['email'],
        '$wp-url'       => $inboundrocket_user['wp_url'],
        '$wp-version'   => $inboundrocket_user['wp_version'],
        '$wp-plugins'	=> $inboundrocket_user['wp_plugins']
    ));

    $mp->people->setOnce( $inboundrocket_user['user_id'], array(
        '$ir-source'    => INBOUNDROCKET_SOURCE,
        '$created'      => date('Y-m-d H:i:s')
    ));
    
    $options = get_option('inboundrocket_options');
    if(isset($options['ir_updates_subscription']) && $options['ir_updates_subscription']===1 && isset($options['ir_email'])):
	    if(strpos($options['ir_email'],",")===true){
		    $emails = explode(",", $options['ir_email']);
		    foreach($emails as $email){
		    	inboundrocket_subscribe_user_updates( true, $email );
		    }
		    return true;
	    } else {
		    inboundrocket_subscribe_user_updates( true, $options['ir_email'] );
		    return true;
	    }
	elseif(isset($options['ir_updates_subscription']) && $options['ir_updates_subscription']===0 && isset($options['ir_email'])):
		if(strpos($options['ir_email'],",")===true){
		    $emails = explode(",", $options['ir_email']);
		    foreach($emails as $email){
		    	inboundrocket_subscribe_user_updates( false, $email );
		    }
		    return true;
	    } else {
		    inboundrocket_subscribe_user_updates( false, $options['ir_email'] );
		    return true;
	    }
    endif;
    
    if(isset($options['ir_updates_subscription']) && $options['ir_updates_subscription']=="1" && isset($inboundrocket_user['email'])):
    	inboundrocket_subscribe_user_updates( true, $inboundrocket_user['email'] );
		return true;
	elseif(isset($options['ir_updates_subscription']) && $options['ir_updates_subscription']=="0" && isset($inboundrocket_user['email'])):
		inboundrocket_subscribe_user_updates( false, $inboundrocket_user['email'] );
		return true;
	endif;
	
	return false;
}


function inboundrocket_update_user ()
{
    if ( ! function_exists('curl_init') )
        return false;

    $inboundrocket_user = inboundrocket_get_current_user();
 
    $mp = Mixpanel::getInstance(INBOUNDROCKET_MIXPANEL_PROJECT_TOKEN, array("debug" => true));
    $mp->people->set( $inboundrocket_user['user_id'], array(
        "distinct_id"   => md5(get_site_url()),
        '$ir-version'   => $inboundrocket_user['ir_version'],
        '$wp-url'       => get_site_url(),
        '$wp-version'   => $inboundrocket_user['wp_version'],
        '$wp-plugins'	=> $inboundrocket_user['wp_plugins']
    ));

    inboundrocket_track_plugin_activity("Upgraded Plugin");

    return true;
}

/**
 * Subscribe user to user updates in MailChimp
 *
 * @return  bool
 *
 */
function inboundrocket_subscribe_user_updates($subscribe, $user_email)
{
 
    // Sync to email to MailChimp
    if (isset($subscribe) && ($subscribe === true)) { 
	    $productupdate = 'Off course!';
    } else {
	    $productupdate = 'Rather not :-/';
    }

    $MailChimp = new MailChimp(INBOUNDROCKET_MC_KEY);
    $contact_synced = $MailChimp->call("lists/subscribe", array(
        "id"                => INBOUNDROCKET_MC_LIST,
        "email"             => array('email' => $user_email),
        "send_welcome"      => FALSE,
        "email_type"        => 'html',
        "update_existing"   => TRUE,
        'replace_interests' => FALSE,
        'double_optin'      => FALSE,
        "merge_vars"        => array('EMAIL' => $user_email, 'DELETED' => 'No', 'NEWSLETTER' => 'Yes please!', 'PRODUCTUPD' => $productupdate, 'INSTALLED' => 'Yes', 'INSTALLURL' => get_site_url(), 'INSTALLDAT' => date('m/d/Y',strtotime("now")), 'VERSION' => INBOUNDROCKET_PLUGIN_VERSION, 'PREMIUM' => 'Non-Premium' )
    ));

    inboundrocket_track_plugin_activity('Onboarding Opted-into User Updates');

    return $contact_synced;
}

/**
 * Marks user as deleted in MailChimp
 *
 * @return  bool
 *
 */
function inboundrocket_mark_deleted_user($user_email)
{
    $MailChimp = new MailChimp(INBOUNDROCKET_MC_KEY);
    $contact_synced = $MailChimp->call("lists/subscribe", array(
        "id"                => INBOUNDROCKET_MC_LIST,
        "email"             => array('email' => $user_email),
        "send_welcome"      => FALSE,
        "email_type"        => 'html',
        "update_existing"   => TRUE,
        'replace_interests' => FALSE,
        'double_optin'      => FALSE,
        "merge_vars"        => array('EMAIL' => $user_email, 'INSTALLED' => 'No', 'DELETED' => 'Yes', 'DELETEDAT' => date('m/d/Y',strtotime("now")) )
    ));

    inboundrocket_track_plugin_activity('User marked deleted from MailChimp');

    return $contact_synced;
}

/**
 * Set Beta propertey on Inbound Rocket user in Mixpanel
 *
 * @return  bool
 */
function inboundrocket_set_beta_tester_property ( $beta_tester )
{
    if ( ! function_exists('curl_init') )
        return false;
    
    
    $inboundrocket_user = inboundrocket_get_current_user();
    $mp = Mixpanel::getInstance(INBOUNDROCKET_MIXPANEL_PROJECT_TOKEN, array("debug" => true));
    $mp->people->set( $inboundrocket_user['user_id'], array(
        '$beta_tester'  => $beta_tester
    ));
}

/**
 * Set Premium property on Inbound Rocket user in Mixpanel
 *
 * @return  bool
 */
function inboundrocket_set_premium_user_property ( $premium_user )
{
    if ( ! function_exists('curl_init') )
        return false;
    
    $inboundrocket_user = inboundrocket_get_current_user();
    $mp = Mixpanel::getInstance(INBOUNDROCKET_MIXPANEL_PROJECT_TOKEN, array("debug" => true));
    $mp->people->set( $inboundrocket_user['user_id'], array(
        '$prremium_user'  => $premium_user
    ));
}

/**
 * Set the status property (activated, deactivated, bad url)
 *
 * @return  bool
 */
function inboundrocket_set_install_status ( $ir_status )
{
    if ( ! function_exists('curl_init') )
        return false;

    $inboundrocket_user = inboundrocket_get_current_user();

    $properties = array(
        '$ir-status'  => $ir_status
    );

    if ( $ir_status == 'activated' )
        $properties['$last_activated'] = date('Y-m-d H:i:s'); 
    else
        $properties['$last_deactivated'] = date('Y-m-d H:i:s');

    $mp = Mixpanel::getInstance(INBOUNDROCKET_MIXPANEL_PROJECT_TOKEN, array("debug" => true));
    $mp->people->set( $inboundrocket_user['user_id'], $properties);
}

/**
 * Send Mixpanel event when plugin is activated/deactivated
 *
 * @param   bool
 *
 * @return  bool
 */
function inboundrocket_track_plugin_registration_hook ( $activated )
{
    if ( $activated )
    {
        inboundrocket_track_plugin_activity("Activated Plugin");
        inboundrocket_set_install_status('activated');
    }
    else
    {
        inboundrocket_track_plugin_activity("Deactivated Plugin");
        inboundrocket_set_install_status('deactivated');
    }

    return TRUE;
}

/**
 * Track plugin activity in MixPanel
 *
 * @param   string
 *
 * @return  array
 */
function inboundrocket_track_plugin_activity ( $activity_desc, $custom_properties = array() )
{   
    if ( ! function_exists('curl_init') )
        return false;
    

    $inboundrocket_user = inboundrocket_get_current_user();

    global $wp_version;
    global $current_user;
    get_currentuserinfo();
    $user_id = md5(get_site_url());
    $plugins = wp_get_active_and_valid_plugins();

    $default_properties = array(
        "distinct_id" => $user_id,
        '$wp-url' => get_site_url(),
        '$wp-version' => $wp_version,
        '$ir-version' => INBOUNDROCKET_PLUGIN_VERSION,
        '$wp-plugins' => $plugins
    );

    $properties = array_merge((array)$default_properties, (array)$custom_properties);

    $mp = Mixpanel::getInstance(INBOUNDROCKET_MIXPANEL_PROJECT_TOKEN, array("debug" => true));
    $mp->track($activity_desc, $properties);

    return true;
}

/**
 * Logs a debug statement to /wp-content/debug.log
 *
 * @param   string
 */
function inboundrocket_log_debug ( $message )
{
    if ( WP_DEBUG === TRUE )
    {
        if ( is_array($message) || is_object($message) )
            error_log(print_r($message, TRUE));
        else 
            error_log($message);
    }
}

/**
 * Deletes an element or elements from an array
 *
 * @param   array
 * @param   wildcard
 * @return  array
 */
function inboundrocket_array_delete ( $array, $element )
{
    if ( !is_array($element) )
        $element = array($element);

    return array_diff($array, $element);
}

/**
 * Sorts the powerups into a predefined order in class-inboundrocket.php line 201
 *
 * @param   array
 * @param   array
 * @return  array
 */
function inboundrocket_sort_power_ups ( $power_ups, $ordered_power_ups ) 
{ 
    $ordered = array();
    $i = 0;
    foreach ( $ordered_power_ups as $key )
    {
        if ( in_array($key, $power_ups) )
        {
            array_push($ordered, $key);
            $i++;
        }
    }

    return $ordered;
}

/**
 * Deletes an element or elements from an array
 *
 * @param   array
 * @param   wildcard
 * @return  array
 */
function inboundrocket_get_value_by_key ( $key_value, $array )
{
    foreach ( $array as $key => $value )
    {
        if ( is_array($value) && $value['label'] == $key_value )
            return $value['value'];
    }

    return null;
}

/**
 * Encodes special HTML quote characters into utf-8 safe entities
 *
 * @param   string
 * @return  string
 */
function inboundrocket_user_encode_quotes ( $string ) 
{ 
    $string = str_replace(array("’", "‘", '&#039;', '“', '”'), array("'", "'", "'", '"', '"'), $string);
    return $string;
}

/**
 * Converts all carriage returns into HTML line breaks 
 *
 * @param   string
 * @return  string
 */
function inboundrocket_html_line_breaks ( $string ) 
{
    return stripslashes(str_replace('\n', '<br>', $string));
}

/**
 * Strip url get parameters off a url and return the base url
 *
 * @param   string
 * @return  string
 */
function inboundrocket_strip_params_from_url ( $url ) 
{ 
    $url_parts = parse_url($url);
    $base_url = ( isset($url_parts['host']) ? 'http://' . rtrim($url_parts['host'], '/') : '' ); 
    $base_url .= ( isset($url_parts['path']) ? '/' . ltrim($url_parts['path'], '/') : '' ); 
    
    if ( isset($url_parts['path'] ) )
        ltrim($url_parts['path'], '/');

    $base_url = urldecode(ltrim($base_url, '/'));

    return strtolower($base_url);
}

/**
 * Search an object by for a value and return the associated index key
 *
 * @param   object 
 * @param   string
 * @param   string
 * @return  key for array index if present, false otherwise
 */
function inboundrocket_search_object_by_value ( $haystack, $needle, $search_key )
{
   foreach ( $haystack as $key => $value )
   {
      if ( $value->$search_key === $needle )
         return $key;
   }

   return FALSE;
}

/**
 * Check if date is a weekend day
 *
 * @param   string
 * @return  bool
 */
function inboundrocket_is_weekend ( $date )
{
    return (date('N', strtotime($date)) >= 6);
}

/**
 * Tie a tag to a contact in ir_tag_relationships
 *
 * @param   int 
 * @param   int
 * @param   int
 * @return  bool    successful insert
 */
function inboundrocket_apply_list_to_contact ( $list_id, $contact_hashkey, $form_hashkey )
{
    global $wpdb;

    $q = $wpdb->prepare("SELECT tag_id FROM {$wpdb->ir_tag_relationships} WHERE tag_id = %d AND contact_hashkey = %s", $list_id, $contact_hashkey);
    $exists = $wpdb->get_var($q);

    if ( ! $exists )
    {
        $q = $wpdb->prepare("INSERT INTO {$wpdb->ir_tag_relationships} ( tag_id, contact_hashkey, form_hashkey ) VALUES ( %d, %s, %s )", $list_id, $contact_hashkey, $form_hashkey);
        return $wpdb->query($q);
    }
}

/**
 * Check multidimensional arrray for an existing value
 *
 * @param   string 
 * @param   array
 * @return  bool
 */
function inboundrocket_in_array_deep ( $needle, $haystack ) 
{
    if ( in_array($needle, $haystack) )
        return TRUE;

    foreach ( $haystack as $element ) 
    {
        if ( is_array($element) && inboundrocket_in_array_deep($needle, $element) )
            return TRUE;
    }

    return FALSE;
}

/**
 * Check multidimensional arrray for an existing value
 *
 * @param   string      needle 
 * @param   array       haystack
 * @return  string      key if found, null if not
 */
function inboundrocket_array_search_deep ( $needle, $array, $index ) 
{
    foreach ( $array as $key => $val ) 
    {
        if ( $val[$index] == $needle )
            return $key;
    }

   return NULL;
}

/**
 * Creates a list of filtered contacts into a comma separated string of hashkeys
 * 
 * @param object
 * @return string    sorted array
 */
function inboundrocket_merge_filtered_contacts ( $filtered_contacts, $all_contacts = array() )
{
    if ( ! count($all_contacts) )
        return $filtered_contacts;

    if ( count($filtered_contacts) )
    {
        foreach ( $all_contacts as $key => $contact )
        {
            if ( ! inboundrocket_in_array_deep($contact['lead_hashkey'], $filtered_contacts) )
                unset($all_contacts[$key]);
        }

        return $all_contacts;
    }
    else
        return FALSE;
}

/**
 * Creates a list of filtered contacts into a comma separated string of hashkeys
 * 
 * @param object
 * @return string    sorted array
 */
function inboundrocket_explode_filtered_contacts ( $contacts )
{
    if ( count($contacts) )
    {
        $contacts = array_values($contacts);

        $hashkeys = '';
        for ( $i = 0; $i < count($contacts); $i++ )
            $hashkeys .= "'" . $contacts[$i]['lead_hashkey'] . "'" . ( $i != (count($contacts) - 1) ? ', ' : '' );

        return $hashkeys;
    }
    else
        return FALSE;
}

/**
 * Calculates the hour difference between MySQL timestamps and the current local WordPress time
 * 
 */
function inboundrocket_set_mysql_timezone_offset ()
{
    global $wpdb;

    $mysql_timestamp = $wpdb->get_var("SELECT CURRENT_TIMESTAMP");
    $diff = strtotime($mysql_timestamp) - strtotime(current_time('mysql'));
    $hours = $diff / (60 * 60);

    $wpdb->db_hour_offset = $hours;
}

/**
 * Gets current URL with parameters
 * 
 */
function inboundrocket_get_current_url ( )
{
    return ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Gets current IP address
 * 
 */
function inboundrocket_get_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * Returns the user role for the current user
 * 
 */
function inboundrocket_get_user_role()
{
    global $current_user;
    get_currentuserinfo();

    $user_roles = $current_user->roles;
    $user_role = array_shift($user_roles);

    return $user_role;
}

/**
 * Checks whether or not to ignore the logged in user in the Inbound Rocket tracking scripts
 * 
 */
function inboundrocket_ignore_logged_in_user()
{
    // ignore logged in users if defined in settings
    if ( is_user_logged_in() )
    {
	    $options = get_option('inboundrocket_options');
	    if ( array_key_exists('ir_do_not_track_' . inboundrocket_get_user_role(), $options) )
            return TRUE;
        else 
            return FALSE;
    }
    else
        return FALSE;
}

function inboundrocket_safe_social_profile_url($url)
{
    $url = str_replace('∖', '/', $url);
    return $url;
}

/**
 * Checks to see if an installation is Inbound Rocket Premium enabled
 * 
 * @return bool
 */
function inboundrocket_check_premium_user()
{
    $options = get_option('inboundrocket_options');
    if ( isset($options['premium']) && ($options['premium']=='1'))
        return TRUE;
    else
        return FALSE;
}

/**
 * Checks the first entry in the pageviews table 
 *
 */
function inboundrocket_check_first_pageview_data()
{
    global $wpdb;

    $q = "SELECT pageview_date FROM {$wpdb->ir_pageviews} ORDER BY pageview_date ASC LIMIT 1";
    $date = $wpdb->get_var($q);

    if ( $date )
    {
        if ( strtotime($date) < strtotime('-30 days') )
            return TRUE;
        else
            return FALSE;
    }
    else
    {
       return FALSE;
    }
}
?>