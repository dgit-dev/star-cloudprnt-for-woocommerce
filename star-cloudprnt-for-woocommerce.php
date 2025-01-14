<?php

/**
 * Plugin Name: Star CloudPRNT for WooCommerce
 * Description: Star CloudPRNT for WooCommerce enables cloud printing technology with your Star Receipt printer.
 * Version: 2.0.3.10
 * Author: dgit
 * Plugin URI: https://github.com/dgit-dev/star-cloudprnt-for-woocommerce
 * Requires at least: 5.0
 * Tested up to: 5.7
 * WC requires at least: 4.0
 * WC tested up to: 5.1
 */

// Block direct access to this script
if (!defined('ABSPATH')) exit;

// Include printer files
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') include_once(plugin_dir_path(__FILE__) . 'cloudprnt\\printer.inc.php');
else include_once(plugin_dir_path(__FILE__) . 'cloudprnt/printer.inc.php');
include_once(plugin_dir_path(__FILE__) . star_cloudprnt_get_os_path('cloudprnt/printer_star_line.inc.php'));
include_once(plugin_dir_path(__FILE__) . star_cloudprnt_get_os_path('cloudprnt/printer_text_plain.inc.php'));
include_once(plugin_dir_path(__FILE__) . star_cloudprnt_get_os_path('cloudprnt/printer_star_prnt.inc.php'));

// Include plugin page settings and woo commerce hooks
include_once(plugin_dir_path(__FILE__) . star_cloudprnt_get_os_path('create-settings.php'));
include_once(plugin_dir_path(__FILE__) . star_cloudprnt_get_os_path('order-handler.php'));
include_once(plugin_dir_path(__FILE__) . star_cloudprnt_get_os_path('kds.php'));
include_once(plugin_dir_path(__FILE__) . star_cloudprnt_get_os_path('report.php'));

star_cloudprnt_register_settings();

// Run page setup and woo commerce hooks
star_cloudprnt_create_settings_page();
star_cloudprnt_setup_order_handler();

// Add a settings link on the plugins page
function my_plugin_settings_link($links) {
	$settings_link = '<a href="options-general.php?page=star-cloudprnt-settings-admin">Settings</a>';
	array_unshift($links, $settings_link);
	return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'my_plugin_settings_link');
