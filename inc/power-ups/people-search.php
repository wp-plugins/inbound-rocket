<?php
/**
	* Power-up Name: People Search
	* Power-up Class: WPPeopleSearch
	* Power-up Menu Text: 
	* Power-up Menu Link: settings
	* Power-up Slug: people-search
	* Power-up URI: http://inboundrocket.co/features/people-search/
	* Power-up Description: See social profiles and company information for every contact.
	* Power-up Icon: powerup-icon-people-search
	* Power-up Icon Small: 
	* First Introduced: 1.x
	* Power-up Tags: PeopleSearch
	* Auto Activate: No
	* Permanently Enabled: No
	* Hidden: No
	* cURL Required: Yes
	* Options Name: inboundrocket_ps_options
*/
if(!defined('ABSPATH') || !defined('INBOUNDROCKET_PATH')) die('Security');

//=============================================
// Define Constants
//=============================================

if ( !defined('INBOUNDROCKET_PEOPLESEARCH_PATH') )
    define('INBOUNDROCKET_PEOPLESEARCH_PATH', INBOUNDROCKET_PATH . '/power-ups/people-search');

if ( !defined('INBOUNDROCKET_PEOPLESEARCH_PLUGIN_DIR') )
	define('INBOUNDROCKET_PEOPLESEARCH_PLUGIN_DIR', INBOUNDROCKET_PLUGIN_DIR . '/power-ups/lookups');

if ( !defined('INBOUNDROCKET_PEOPLESEARCH_PLUGIN_SLUG') )
	define('INBOUNDROCKET_PEOPLESEARCH_PLUGIN_SLUG', basename(dirname(__FILE__)));

//=============================================
// Include Needed Files
//=============================================

require_once(INBOUNDROCKET_PEOPLESEARCH_PLUGIN_DIR . '/admin/people-search-admin.php');

//=============================================
// WPPeopleSearch Class
//=============================================
class WPPeopleSearch extends WPInboundRocket {
	
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

		global $inboundrocket_peoplesearch;
		$inboundrocket_peoplesearch = $this;
	}
	
	public function admin_init ( )
	{
		$admin_class = get_class($this) . 'Admin';
		$this->admin = new $admin_class($this->icon_small);
	}

	function power_up_setup_callback ( )
	{
		$this->admin->power_up_setup_callback();
	}
}

global $inboundrocket_peoplesearch;

?>