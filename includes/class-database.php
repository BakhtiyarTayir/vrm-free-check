<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class Database {
    
    /**
     * Создание таблиц базы данных
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Таблица истории проверок
        $table_history = $wpdb->prefix . 'vrm_check_history';
        $sql_history = "CREATE TABLE IF NOT EXISTS $table_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            vrm VARCHAR(10) NOT NULL,
            check_type ENUM('basic', 'premium') DEFAULT 'premium',
            api_data LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            cost DECIMAL(10,2) DEFAULT 0.00,
            order_id BIGINT UNSIGNED DEFAULT NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_vrm (vrm),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";
        
        // Таблица кредитов пользователей
        $table_credits = $wpdb->prefix . 'user_credits';
        $sql_credits = "CREATE TABLE IF NOT EXISTS $table_credits (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL UNIQUE,
            credits INT UNSIGNED DEFAULT 0,
            total_purchased INT UNSIGNED DEFAULT 0,
            total_used INT UNSIGNED DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_history);
        dbDelta($sql_credits);
        
        // Логируем создание таблиц
        $logger = Logger::get_instance();
        $logger->info('Database tables created/updated', [
            'tables' => [$table_history, $table_credits]
        ]);
    }
    
    /**
     * Удаление таблиц при деактивации (опционально)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $table_history = $wpdb->prefix . 'vrm_check_history';
        $table_credits = $wpdb->prefix . 'user_credits';
        
        $wpdb->query("DROP TABLE IF EXISTS $table_history");
        $wpdb->query("DROP TABLE IF EXISTS $table_credits");
        
        $logger = Logger::get_instance();
        $logger->info('Database tables dropped', [
            'tables' => [$table_history, $table_credits]
        ]);
    }
    
    /**
     * Проверка существования таблиц
     */
    public static function tables_exist() {
        global $wpdb;
        
        $table_history = $wpdb->prefix . 'vrm_check_history';
        $table_credits = $wpdb->prefix . 'user_credits';
        
        $history_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_history'") === $table_history;
        $credits_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_credits'") === $table_credits;
        
        return $history_exists && $credits_exists;
    }
}
