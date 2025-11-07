<?php
/**
 * Order Manager
 * 
 * Управляет покупками VRM проверок через WooCommerce
 *
 * @package VRM_Check_Plugin
 */

namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class OrderManager {
    
    /**
     * ID товара VRM Check
     */
    const VRM_CHECK_PRODUCT_ID = 1054;
    
    /**
     * Инициализация
     */
    public static function init() {
        // Хук после успешной оплаты заказа
        add_action('woocommerce_order_status_completed', array(__CLASS__, 'handle_completed_order'));
        add_action('woocommerce_payment_complete', array(__CLASS__, 'handle_payment_complete'));
        
        // Добавить мета-поле к товару
        add_action('woocommerce_product_options_general_product_data', array(__CLASS__, 'add_product_meta_field'));
        add_action('woocommerce_process_product_meta', array(__CLASS__, 'save_product_meta_field'));
    }
    
    /**
     * Обработка завершённого заказа
     */
    public static function handle_completed_order($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Проверяем, не обработан ли уже этот заказ
        if ($order->get_meta('_vrm_checks_granted')) {
            return;
        }
        
        $user_id = $order->get_user_id();
        
        if (!$user_id) {
            return;
        }
        
        // Подсчитываем количество VRM проверок в заказе
        $checks_count = 0;
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            // Проверяем, является ли это товаром VRM Check
            if (self::is_vrm_check_product($product_id)) {
                $checks_count += $item->get_quantity();
            }
        }
        
        if ($checks_count > 0) {
            // Добавляем проверки пользователю
            self::grant_checks($user_id, $checks_count, $order_id);
            
            // Отмечаем заказ как обработанный
            $order->update_meta_data('_vrm_checks_granted', $checks_count);
            $order->update_meta_data('_vrm_checks_granted_date', current_time('mysql'));
            $order->save();
            
            // Добавляем заметку к заказу
            $order->add_order_note(
                sprintf(__('%d VRM check(s) granted to user #%d', 'vrm-check-plugin'), $checks_count, $user_id)
            );
            
            // Логируем
            $logger = Logger::get_instance();
            $logger->info('VRM checks granted', [
                'order_id' => $order_id,
                'user_id' => $user_id,
                'checks_count' => $checks_count
            ]);
        }
    }
    
    /**
     * Обработка успешной оплаты
     */
    public static function handle_payment_complete($order_id) {
        self::handle_completed_order($order_id);
    }
    
    /**
     * Проверить, является ли товар VRM Check продуктом
     */
    public static function is_vrm_check_product($product_id) {
        // Проверяем по ID
        if ($product_id == self::VRM_CHECK_PRODUCT_ID) {
            return true;
        }
        
        // Проверяем по мета-полю
        $is_vrm_product = get_post_meta($product_id, '_vrm_check_product', true);
        return $is_vrm_product === 'yes';
    }
    
    /**
     * Выдать проверки пользователю
     */
    public static function grant_checks($user_id, $count, $order_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'user_vrm_checks';
        
        // Получаем текущее количество
        $current = self::get_user_checks($user_id);
        $new_total = $current + $count;
        
        // Обновляем или создаём запись
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d",
            $user_id
        ));
        
        if ($exists) {
            $wpdb->update(
                $table,
                ['checks_available' => $new_total],
                ['user_id' => $user_id],
                ['%d'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $table,
                [
                    'user_id' => $user_id,
                    'checks_available' => $count
                ],
                ['%d', '%d']
            );
        }
        
        // Логируем транзакцию
        self::log_transaction($user_id, $count, 'purchase', $order_id);
        
        return $new_total;
    }
    
    /**
     * Получить количество доступных проверок
     */
    public static function get_user_checks($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'user_vrm_checks';
        
        $checks = $wpdb->get_var($wpdb->prepare(
            "SELECT checks_available FROM $table WHERE user_id = %d",
            $user_id
        ));
        
        return $checks ? (int)$checks : 0;
    }
    
    /**
     * Использовать одну проверку
     */
    public static function use_check($user_id, $vrm, $history_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'user_vrm_checks';
        
        $current = self::get_user_checks($user_id);
        
        if ($current <= 0) {
            return false;
        }
        
        $new_total = $current - 1;
        
        $result = $wpdb->update(
            $table,
            ['checks_available' => $new_total],
            ['user_id' => $user_id],
            ['%d'],
            ['%d']
        );
        
        if ($result) {
            self::log_transaction($user_id, -1, 'used', null, $vrm, $history_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Логировать транзакцию
     */
    private static function log_transaction($user_id, $amount, $type, $order_id = null, $vrm = null, $history_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'vrm_check_transactions';
        
        $wpdb->insert($table, [
            'user_id' => $user_id,
            'amount' => $amount,
            'type' => $type,
            'order_id' => $order_id,
            'vrm' => $vrm,
            'history_id' => $history_id,
            'created_at' => current_time('mysql')
        ], [
            '%d', '%d', '%s', '%d', '%s', '%d', '%s'
        ]);
    }
    
    /**
     * Добавить мета-поле к товару
     */
    public static function add_product_meta_field() {
        global $post;
        
        $value = get_post_meta($post->ID, '_vrm_check_product', true);
        
        echo '<div class="options_group">';
        woocommerce_wp_checkbox([
            'id' => '_vrm_check_product',
            'label' => __('VRM Check Product', 'vrm-check-plugin'),
            'description' => __('Check this if this product grants VRM checks', 'vrm-check-plugin'),
            'value' => $value
        ]);
        echo '</div>';
    }
    
    /**
     * Сохранить мета-поле товара
     */
    public static function save_product_meta_field($post_id) {
        $value = isset($_POST['_vrm_check_product']) ? 'yes' : 'no';
        update_post_meta($post_id, '_vrm_check_product', $value);
    }
}
