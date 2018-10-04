<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://jeffmikels.org
 * @since             0.1.0
 * @package           jm_app_support
 *
 * @wordpress-plugin
 * Plugin Name:       Jeff Mikels App Support
 * Plugin URI:        http://jeffmikels.info
 * Description:       This plugin generates menu and configuration information for the Jeff Mikels Mobile App.
 * Version:           0.1.0
 * Author:            Jeff Mikels
 * Author URI:        http://jeffmikels.org/
 * Text Domain:       jm-app-menu-generator
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
define( 'jmapp_MENU_FILE', 'jm_app.json');
define( 'JMAPP_MENU_PATH', ABSPATH . '/' . jmapp_MENU_FILE);

include "jm-app-functions.php";
include "jm-app-providers.php";


// WORDPRESS ACTIVATION / DEACTIVATION
register_activation_hook(__FILE__, 'jmapp_install');
register_deactivation_hook(__FILE__, 'jmapp_uninstall');
function jmapp_install()
{
	$role = get_role( 'administrator' );
	$role->add_cap('jmapp_admin');
	remove_role('jmapp_admin');
}

function jmapp_uninstall()
{
	$role = get_role( 'administrator' );
	$role->remove_cap('jmapp_admin');
	remove_role('jmapp_admin');
}


/* Administration Pages */
if ( is_admin() ) {
	add_action ('admin_menu', 'jmapp_admin_menu');
	add_action ('admin_init', 'jmapp_register_options');
	wp_enqueue_style('material-icons','https://fonts.googleapis.com/icon?family=Material+Icons');
}
function jmapp_admin_menu()
{
	add_menu_page('My Mobile App', 'Mobile App Settings', 'manage_options', 'jmapp-options', 'jmapp_options_page');
	add_submenu_page('jmapp-options', 'Menu Maker', 'Mobile App Menu Maker', 'manage_options', 'jmapp-menu', 'jmapp_menu_page');
}

function jmapp_register_options()
{
	register_setting('jmapp_options','jmapp_options'); // one group to store all options as an array
}

function jmapp_menu_page()
{
	if ( !current_user_can('manage_options') ) wp_die( __('You do not have sufficient permissions to access this page.' ) );
	include "jm-app-menu-page.php";
}

function jmapp_options_page()
{
	if ( !current_user_can('manage_options') ) wp_die( __('You do not have sufficient permissions to access this page.' ) );
	include "jm-app-options-page.php";
}

add_action('wp_ajax_jmapp_get_menu', 'jmapp_ajax_get_menu');
function jmapp_ajax_get_menu()
{
	jmapp_ajax('read');
}

add_action('wp_ajax_jmapp_save_menu', 'jmapp_ajax_save_menu');
function jmapp_ajax_save_menu()
{
	// print_r($_POST['menu_data']);
	$menu_data = json_decode(stripslashes($_POST['menu_data']), TRUE);
	jmapp_ajax('write', $menu_data);
}

add_action( 'admin_notices', 'jmapp_notices' );
function jmapp_notices()
{
	if (array_key_exists('jmapp_err', $_GET))
	{
		
		?>

	    <div class="notice notice-error is-dismissible">
	        <p><?php echo $_GET['jmapp_err'];?></p>
	    </div>

		<?php
	}

	if (array_key_exists('jmapp_msg', $_GET))
	{
		
		?>

	    <div class="notice notice-success is-dismissible">
	        <p><?php echo $_GET['jmapp_msg'];?></p>
	    </div>

		<?php
	}
}

include "jm-app-json-api.php";
// include "jm-app-onesignal-handler.php";
include "jm-app-notifications-handler.php";
