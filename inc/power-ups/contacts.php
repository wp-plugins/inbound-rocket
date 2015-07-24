<?php
/**
	* Power-up Name: Contacts
	* Power-up Class: WPContacts
	* Power-up Menu Text: Contacts
	* Power-up Menu Link: contacts
	* Power-up Slug: contacts
	* Power-up URI: http://inboundrocket.co/features/crm-and-lead-management/
	* Power-up Description: Get an in-depth view of each contact in your database.
	* Power-up Icon: powerup-icon-contacts
	* Power-up Icon Small: powerup-icon-contacts
	* First Introduced: 1.0
	* Power-up Tags: Contacts
	* Auto Activate: Yes
	* Permanently Enabled: Yes
	* Hidden: No
	* cURL Required: No
	* Options Name: inboundrocket_ct_options
*/
if(!defined('ABSPATH') || !defined('INBOUNDROCKET_PATH')) die('Security');

//=============================================
// Define Constants
//=============================================

if ( !defined('INBOUNDROCKET_CONTACTS_PATH') )
    define('INBOUNDROCKET_CONTACTS_PATH', INBOUNDROCKET_PATH . '/inc/power-ups/contacts');

if ( !defined('INBOUNDROCKET_CONTACTS_PLUGIN_DIR') )
	define('INBOUNDROCKET_CONTACTS_PLUGIN_DIR', INBOUNDROCKET_PLUGIN_DIR . '/inc/power-ups/contacts');

if ( !defined('INBOUNDROCKET_CONTACTS_PLUGIN_SLUG') )
	define('INBOUNDROCKET_CONTACTS_PLUGIN_SLUG', basename(dirname(__FILE__)));

//=============================================
// Include Needed Files
//=============================================

require_once(INBOUNDROCKET_CONTACTS_PLUGIN_DIR . '/admin/contacts-admin.php');

//=============================================
// WPInboundRocketSelectionSharer Class
//=============================================
class WPContacts extends WPInboundRocket {
	
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

		global $inboundrocket_contacts;
		$inboundrocket_contacts = $this;
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

//=============================================
// InboundRocket Init
//=============================================

global $inboundrocket_contacts;
?>