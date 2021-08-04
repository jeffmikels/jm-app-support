<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link			  http://jeffmikels.org
 * @since			 0.1.0
 * @package		   jm_app_support
 *
 * @wordpress-plugin
 * Plugin Name:	   Jeff Mikels App Support
 * Plugin URI:		http://jeffmikels.info
 * Description:	   This plugin generates menu and configuration information for the Jeff Mikels Mobile App. ---- requires JWT Authentication Plugin.
 * Version:		   0.1.0
 * Author:			Jeff Mikels
 * Author URI:		http://jeffmikels.org/
 * Text Domain:	   jm-app-menu-generator
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'jmapp_VERSION', '0.1.0' );

// plugin constants
define('JMAPP_MENU_FILE' , 'jm_app_v2.json');
define('JMAPP_MENU_PATH' , ABSPATH . '/' . JMAPP_MENU_FILE);
define('JMAPP_USER_ROLE' , 'jmapp_appuser');
define('JMAPP_PW_CAP'    , 'edit_prayer_walks');
define('JMAPP_ADMIN_ROLE', 'jmapp_admin');
define('JMAPP_ADMIN_CAP' , 'jmapp_admin');


include "jm-app-functions.php";
include "jm-app-post-meta.php";
include "jm-app-admin-hooks.php";
include "jm-app-user-meta.php";
include "jm-app-custom-types.php";
include "jm-app-prayer-walks.php";
include "jm-app-notifications.php";
include "jm-app-providers.php";
include "jm-app-game-scores.php";
include "jm-app-jwt-hooks.php";
include "jm-app-json-api.php";

/* THESE FILES OUTPUT HTML AND ARE INCLUDED INLINE WHEN CALLED FOR
not_included_here "jm-app-menu-page.php";
not_included_here "jm-app-options-page.php";
*/

// DEPRECATED
// include "jm-app-onesignal-handler.php";


// WORDPRESS ACTIVATION / DEACTIVATION
register_activation_hook(__FILE__, 'jmapp_install');
register_deactivation_hook(__FILE__, 'jmapp_uninstall');
function jmapp_install()
{
	global $wp_roles;

	// make sure users can't administer posts
	// we allow file uploads so they can submit profile photos (eventually)
	add_role(JMAPP_USER_ROLE, 'JM Apps User', [
		'read' => true,                // access the dashboard
		// 'edit_posts' => false,
		// 'delete_posts' => false,
		// 'publish_posts' => false,
		'upload_files' => true,
	]);
	add_role(JMAPP_ADMIN_ROLE, 'JM Apps Admin', [JMAPP_ADMIN_CAP => true]);
	
	$wp_roles->add_cap('administrator', JMAPP_ADMIN_CAP);
	$wp_roles->add_cap('administrator', JMAPP_PW_CAP);
	
	// add roles to existing users
	$users = get_users();
	foreach ($users as $user) {
		$user->add_role(JMAPP_USER_ROLE);
	}

	jmapp_add_prayer_walk_capabilities();
}

function jmapp_uninstall()
{
	global $wp_roles;
	
	$wp_roles->remove_cap('administrator', JMAPP_ADMIN_CAP);
	$wp_roles->remove_cap('administrator', JMAPP_PW_CAP);
	
	jmapp_add_prayer_walk_capabilities(FALSE);
	
	remove_role(JMAPP_ADMIN_ROLE);
	remove_role(JMAPP_USER_ROLE);
}

function jmapp_get_option($key = NULL, $default = NULL)
{
	$stored_options = get_option('jmapp_options', []);
	if ($key === NULL) return $stored_options;
	return $stored_options[$key] ?? $default;
}

function jmapp_set_option($key, $value = NULL)
{
	$stored_options = get_option('jmapp_options', []);
	$stored_options[$key] = $value;
	return update_option('jmapp_options', $stored_options);
}
