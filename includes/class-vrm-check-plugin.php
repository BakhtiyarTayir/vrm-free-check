<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class VrmCheckPlugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Initialize other classes
        new Admin();
        new Ajax();
        new Shortcode();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('vrm-check-plugin', false, dirname(plugin_basename(VRM_CHECK_PLUGIN_PATH)) . '/languages/');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style(
            'vrm-check-plugin-style',
            VRM_CHECK_PLUGIN_URL . 'assets/css/vrm-check-style.css',
            array(),
            VRM_CHECK_PLUGIN_VERSION
        );
        
        // Подключаем стили модального окна
        wp_enqueue_style(
            'vrm-modal-style',
            VRM_CHECK_PLUGIN_URL . 'assets/css/vrm-modal.css',
            array(),
            VRM_CHECK_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'vrm-check-script',
            VRM_CHECK_PLUGIN_URL . 'assets/js/vrm-check-script.js',
            array('jquery'),
            VRM_CHECK_PLUGIN_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('vrm-check-script', 'vrmCheckAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vrm_check_nonce'),
            'messages' => array(
                'empty_vrm' => __('Please enter a vehicle registration number', 'vrm-check-plugin'),
                'invalid_vrm' => __('Please enter a valid UK registration number', 'vrm-check-plugin'),
                'general_error' => __('An error occurred while checking the vehicle. Please try again.', 'vrm-check-plugin'),
                'timeout_error' => __('Request timeout. Please try again.', 'vrm-check-plugin'),
                'network_error' => __('Network error. Please check your connection.', 'vrm-check-plugin')
            )
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_vrm-check-plugin' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'vrm-check-admin-style',
            VRM_CHECK_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            VRM_CHECK_PLUGIN_VERSION
        );
    }
    

}