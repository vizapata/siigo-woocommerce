<?php

/**
 * @link              https://github.com/vizapata
 * @since             1.0.0
 * @package           Vizapata_sw_integration
 *
 * @wordpress-plugin
 * Plugin Name:       Siigo Woocommerce Integration
 * Plugin URI:        https://github.com/vizapata/siigo-woocommerce
 * Description:       A plugin to allow the integration between a woocommerce store and the siiglo cloud system
 * Version:           1.0.0
 * Author:            Victor Zapata
 * Author URI:        https://github.com/vizapata
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       vizapata_sw_integration
 * Domain Path:       /languages
 */

if (!defined('WPINC')) {
	die;
}

define('VIZAPATA_SW_INTEGRATION_VERSION', '1.0.0');

function activate_vizapata_sw_integration()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-vizapata_sw_integration-activator.php';
	Vizapata_sw_integration_Activator::activate();
}

function deactivate_vizapata_sw_integration()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-vizapata_sw_integration-deactivator.php';
	Vizapata_sw_integration_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_vizapata_sw_integration');
register_deactivation_hook(__FILE__, 'deactivate_vizapata_sw_integration');

require plugin_dir_path(__FILE__) . 'includes/class-vizapata_sw_integration.php';

function run_vizapata_sw_integration()
{

	$plugin = new Vizapata_sw_integration();
	$plugin->run();
}
run_vizapata_sw_integration();
