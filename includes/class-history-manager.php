<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class HistoryManager {
    
    /**
     * Сохранить проверку в историю
     * 
     * @param int $user_id ID пользователя
     * @param string $vrm VRM номер
     * @param array $data Данные API
     * @param string $type Тип проверки (basic/premium)
     * @param float $cost Стоимость
     * @param int $order_id ID заказа WooCommerce (опционально)
     * @return int|false ID записи или false при ошибке
     */
    public static function save_check($user_id, $vrm, $data, $type = 'premium', $cost = 0, $order_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'vrm_check_history';
        
        $result = $wpdb->insert($table, [
            'user_id' => $user_id,
            'vrm' => strtoupper($vrm),
            'check_type' => $type,
            'api_data' => json_encode($data),
            'cost' => $cost,
            'order_id' => $order_id,
            'created_at' => current_time('mysql')
        ], [
            '%d', '%s', '%s', '%s', '%f', '%d', '%s'
        ]);
        
        if ($result) {
            $insert_id = $wpdb->insert_id;
            
            // Логируем
            $logger = Logger::get_instance();
            $logger->info('Check saved to history', [
                'id' => $insert_id,
                'user_id' => $user_id,
                'vrm' => $vrm,
                'type' => $type
            ]);
            
            return $insert_id;
        }
        
        return false;
    }
    
    /**
     * Получить историю проверок пользователя
     * 
     * @param int $user_id ID пользователя
     * @param array|int $args Массив параметров или лимит (для обратной совместимости)
     * @param int $offset Смещение (если $args это число)
     * @return array Массив проверок
     */
    public static function get_user_history($user_id, $args = array(), $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'vrm_check_history';
        
        // Поддержка старого формата (limit, offset)
        if (is_numeric($args)) {
            $limit = (int)$args;
        } else {
            // Новый формат с массивом параметров
            $defaults = array(
                'limit' => 20,
                'offset' => 0,
                'check_type' => '', // premium, basic или пусто для всех
                'order_by' => 'created_at',
                'order' => 'DESC'
            );
            $args = wp_parse_args($args, $defaults);
            $limit = (int)$args['limit'];
            $offset = (int)$args['offset'];
        }
        
        $query = $wpdb->prepare(
            "SELECT id, vrm, check_type, api_data, cost, created_at, order_id
             FROM $table 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id, $limit, $offset
        );
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Получить проверку пользователя по VRM
     * 
     * @param int $user_id ID пользователя
     * @param string $vrm VRM номер
     * @return object|null Данные проверки или null если не найдено
     */
    public static function get_user_check_by_vrm($user_id, $vrm) {
        global $wpdb;
        $table = $wpdb->prefix . 'vrm_check_history';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT id, vrm, check_type, api_data, cost, created_at, order_id
             FROM $table 
             WHERE user_id = %d AND vrm = %s
             ORDER BY created_at DESC
             LIMIT 1",
            $user_id, strtoupper(trim($vrm))
        ));
    }
    
    /**
     * Получить проверку по ID
     * 
     * @param int $id ID проверки
     * @param int $user_id ID пользователя (для безопасности)
     * @return object|null Данные проверки
     */
    public static function get_check_by_id($id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vrm_check_history';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND user_id = %d",
            $id, $user_id
        ));
    }
    
    /**
     * Получить количество проверок пользователя
     * 
     * @param int $user_id ID пользователя
     * @return int Количество проверок
     */
    public static function get_user_check_count($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vrm_check_history';
        
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Получить последнюю проверку пользователя
     * 
     * @param int $user_id ID пользователя
     * @return object|null Последняя проверка
     */
    public static function get_last_check($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vrm_check_history';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT 1",
            $user_id
        ));
    }
    
    /**
     * Удалить старые проверки (старше X дней)
     * 
     * @param int $days Количество дней
     * @return int Количество удалённых записей
     */
    public static function cleanup_old_checks($days = 90) {
        global $wpdb;
        $table = $wpdb->prefix . 'vrm_check_history';
        
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        $logger = Logger::get_instance();
        $logger->info('Old checks cleaned up', [
            'days' => $days,
            'deleted' => $result
        ]);
        
        return $result;
    }
}
