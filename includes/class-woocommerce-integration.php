<?php
/**
 * WooCommerce Integration
 * 
 * Интеграция с WooCommerce My Account
 *
 * @package VRM_Check_Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

use VrmCheckPlugin\HistoryManager;

class VRM_WooCommerce_Integration {
    
    /**
     * Инициализация
     */
    public static function init() {
        // Добавить пункт меню в My Account
        add_filter('woocommerce_account_menu_items', array(__CLASS__, 'add_menu_items'), 10, 1);
        
        // Зарегистрировать endpoint
        add_action('init', array(__CLASS__, 'add_endpoints'));
        
        // Добавить query var
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'), 0);
        
        // Добавить контент для endpoint (основной способ)
        add_action('woocommerce_account_vrm-reports_endpoint', array(__CLASS__, 'vrm_reports_content'));
        
        // Изменить URL для New Check на прямую ссылку
        add_filter('woocommerce_get_endpoint_url', array(__CLASS__, 'custom_endpoint_url'), 10, 4);
        
        // Добавить заголовок страницы
        add_filter('the_title', array(__CLASS__, 'endpoint_title'), 10, 2);
        
        // Использовать template файл
        add_filter('woocommerce_locate_template', array(__CLASS__, 'locate_template'), 10, 3);
    }
    
    /**
     * Добавить query vars
     */
    public static function add_query_vars($vars) {
        $vars[] = 'vrm-reports';
        return $vars;
    }
    
    /**
     * Locate template файл
     */
    public static function locate_template($template, $template_name, $template_path) {
        // Проверяем, это наш template
        if ($template_name === 'myaccount/vrm-reports.php') {
            $plugin_template = VRM_CHECK_PLUGIN_PATH . 'templates/' . $template_name;
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Добавить пункты меню
     */
    public static function add_menu_items($items) {
        // Вставляем "My Reports", "New Check" и "Free Check" после "Dashboard"
        $new_items = array();
        
        foreach ($items as $key => $value) {
            $new_items[$key] = $value;
            
            // После Dashboard добавляем My Reports, New Check и Free Check
            if ($key === 'dashboard') {
                $new_items['vrm-reports'] = __('My Reports', 'vrm-check-plugin');
                $new_items['new-check'] = __('New Check', 'vrm-check-plugin');
                $new_items['free-check'] = __('Free Check', 'vrm-check-plugin');
            }
        }
        
        return $new_items;
    }
    
    /**
     * Зарегистрировать endpoints
     */
    public static function add_endpoints() {
        add_rewrite_endpoint('vrm-reports', EP_ROOT | EP_PAGES);
        
        // Сбросить rewrite rules при активации плагина
        if (get_option('vrm_check_flush_rewrite_rules') !== 'done') {
            flush_rewrite_rules();
            update_option('vrm_check_flush_rewrite_rules', 'done');
        }
    }
    
    /**
     * Контент для страницы My Reports
     */
    public static function vrm_reports_content() {
        // Отладка
        echo '<!-- VRM Reports Content Called -->';
        
        // Проверяем, что пользователь авторизован
        if (!is_user_logged_in()) {
            echo '<div class="woocommerce-info">';
            echo '<p>' . __('Please log in to view your reports.', 'vrm-check-plugin') . '</p>';
            echo '</div>';
            return;
        }
        
        // Проверяем, что класс шорткода существует
        if (!class_exists('VRM_History_Shortcode')) {
            echo '<div class="woocommerce-error">';
            echo '<p>Error: VRM_History_Shortcode class not found.</p>';
            echo '</div>';
            return;
        }
        
        // Используем шорткод для отображения истории
        echo do_shortcode('[vrm_check_history]');
    }
    
    /**
     * Изменить URL для пунктов меню New Check и Free Check
     */
    public static function custom_endpoint_url($url, $endpoint, $value, $permalink) {
        // Для new-check возвращаем прямую ссылку на full-check-page
        if ($endpoint === 'new-check') {
            return home_url('/full-check-page/');
        }
        // Для free-check возвращаем прямую ссылку на free-check-2
        if ($endpoint === 'free-check') {
            return home_url('/free-check-2/');
        }
        return $url;
    }
    
    /**
     * Изменить заголовок страницы
     */
    public static function endpoint_title($title, $id) {
        global $wp_query;
        
        if (isset($wp_query->query_vars['vrm-reports']) && 
            is_account_page() && 
            is_main_query() && 
            in_the_loop()) {
            $title = __('My Reports', 'vrm-check-plugin');
        }
        
        return $title;
    }
}

// Инициализация
VRM_WooCommerce_Integration::init();
