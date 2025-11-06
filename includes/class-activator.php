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
        
        // Создаём таблицы базы данных
        Database::create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        // API Key - без дефолтного значения
        if (get_option('vrm_check_api_key') === false) {
            add_option('vrm_check_api_key', '');
        }
        
        // Cache settings
        if (get_option('vrm_check_cache_enabled') === false) {
            add_option('vrm_check_cache_enabled', '1');
        }
        
        if (get_option('vrm_check_cache_duration') === false) {
            add_option('vrm_check_cache_duration', '1');
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