<?php
/**
 * Plugin Name: VRM Check Plugin
 * Plugin URI: https://motcheck.org
 * Description: UK Vehicle Registration Mark (VRM) checker using Vehicle Data Global API. Provides comprehensive vehicle information including DVLA data, MOT history, tax status, and more. Now with paid credits system!
 * Version: 1.0.3
 * Author: MOT Check
 * License: GPL v2 or later
 * Text Domain: vrm-check-plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VRM_CHECK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VRM_CHECK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VRM_CHECK_PLUGIN_VERSION', '1.0.3');

// Include required files
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-logger.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-database.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-credits-manager.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-history-manager.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-login-redirect.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-history-shortcode.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-woocommerce-integration.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-report-page.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-pdf-generator.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-order-manager.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-vrm-check-plugin.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-admin.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-ajax.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-shortcode.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-api-client.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-premium-api-client.php';
require_once VRM_CHECK_PLUGIN_PATH . 'includes/class-activator.php';

// Plugin activation/deactivation hooks
register_activation_hook(__FILE__, array('VrmCheckPlugin\Activator', 'activate'));
register_deactivation_hook(__FILE__, array('VrmCheckPlugin\Activator', 'deactivate'));
register_uninstall_hook(__FILE__, array('VrmCheckPlugin\Activator', 'uninstall'));

// Initialize the plugin
function vrm_check_plugin_init() {
    VrmCheckPlugin\VrmCheckPlugin::get_instance();
    
    // Initialize AJAX handler
    new VrmCheckPlugin\Ajax();
    
    // Initialize Report Page
    VRM_Report_Page::init();
    
    // Initialize Order Manager
    VrmCheckPlugin\OrderManager::init();
    
    // Initialize WooCommerce Integration
    VRM_WooCommerce_Integration::init();
    
    // Initialize History Shortcode
    VRM_History_Shortcode::init();
    
    // Initialize Login Redirect
    VRM_Login_Redirect::init();
    
    // Initialize PDF Generator
    VRM_PDF_Generator::init();
}
add_action('plugins_loaded', 'vrm_check_plugin_init');