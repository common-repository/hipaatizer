<?php

/**
 * Plugin Name:       HIPAAtizer
 * Plugin URI:        https://hipaatizer.com/free-developer-account
 * Description:       HIPAAtizer - Helps you create and manage HIPAA-Compliant web forms.
 * Version:           1.3.4
 * Author:            HIPAAtizer
 * Author URI:        https://hipaatizer.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       hipaatizer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}
/**
 * Define Constants
 */
if (!defined('HIPAATIZER_BASE_PATH')) {
	define('HIPAATIZER_BASE_PATH', __FILE__);
}

if (!defined('HIPAATIZER_PATH')) {
	define('HIPAATIZER_PATH', untrailingslashit(plugins_url('', HIPAATIZER_BASE_PATH)));
}

if (!defined('HIPAATIZER_PLUGIN_DIR')) {
	define('HIPAATIZER_PLUGIN_DIR', untrailingslashit(dirname(HIPAATIZER_BASE_PATH)));
}
if (!defined('HIPAATIZER_APP')) {
	define('HIPAATIZER_APP', 'https://app.hipaatizer.com');
}

/**
 * Currentl plugin version.
 */
define('HIPAATIZER_VERSION', '1.3.3');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-hipaatizer-activator.php
 */
function activate_hipaatizer()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-hipaatizer-activator.php';
	HIPAAtizer_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-hipaatizer-deactivator.php
 */
function deactivate_hipaatizer()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-hipaatizer-deactivator.php';
	HIPAAtizer_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_hipaatizer');
register_deactivation_hook(__FILE__, 'deactivate_hipaatizer');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-hipaatizer.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 */
function run_hipaatizer()
{

	$plugin = new HIPAAtizer();
	$plugin->run();
}
run_hipaatizer();
