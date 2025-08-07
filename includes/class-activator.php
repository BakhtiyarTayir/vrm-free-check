<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class Activator {
    
    /**
     * Plugin activation hook
     */
    public static function activate() {
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        if (get_option('vrm_check_api_key') === false) {
            add_option('vrm_check_api_key', '15D0A432-A308-4B28-89B4-6E07F0C55DCE');
        }
    }
    
    /**
     * Plugin deactivation hook
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall hook
     */
    public static function uninstall() {
        // Only run if user has proper permissions
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Remove plugin options
        delete_option('vrm_check_api_key');
    }
}