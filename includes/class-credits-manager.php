<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class CreditsManager {
    
    /**
     * Получить количество кредитов пользователя
     * 
     * @param int $user_id ID пользователя
     * @return int Количество кредитов
     */
    public static function get_user_credits($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'user_credits';
        
        $credits = $wpdb->get_var($wpdb->prepare(
            "SELECT credits FROM $table WHERE user_id = %d",
            $user_id
        ));
        
        return $credits !== null ? (int)$credits : 0;
    }
    
    /**
     * Добавить кредиты пользователю
     * 
     * @param int $user_id ID пользователя
     * @param int $amount Количество кредитов для добавления
     * @return bool Успех операции
     */
    public static function add_credits($user_id, $amount) {
        global $wpdb;
        $table = $wpdb->prefix . 'user_credits';
        
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (user_id, credits, total_purchased, updated_at) 
             VALUES (%d, %d, %d, NOW())
             ON DUPLICATE KEY UPDATE 
             credits = credits + %d,
             total_purchased = total_purchased + %d,
             updated_at = NOW()",
            $user_id, $amount, $amount, $amount, $amount
        ));
        
        // Логируем
        $logger = Logger::get_instance();
        $logger->info('Credits added', [
            'user_id' => $user_id,
            'amount' => $amount,
            'new_balance' => self::get_user_credits($user_id)
        ]);
        
        return $result !== false;
    }
    
    /**
     * Списать один кредит у пользователя
     * 
     * @param int $user_id ID пользователя
     * @return bool Успех операции
     */
    public static function deduct_credit($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'user_credits';
        
        // Проверяем баланс
        $current = self::get_user_credits($user_id);
        if ($current <= 0) {
            $logger = Logger::get_instance();
            $logger->warning('Insufficient credits', ['user_id' => $user_id]);
            return false;
        }
        
        // Списываем кредит
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table 
             SET credits = credits - 1, 
                 total_used = total_used + 1,
                 updated_at = NOW()
             WHERE user_id = %d AND credits > 0",
            $user_id
        ));
        
        // Логируем
        $logger = Logger::get_instance();
        $logger->info('Credit deducted', [
            'user_id' => $user_id,
            'remaining' => self::get_user_credits($user_id)
        ]);
        
        return $result > 0;
    }
    
    /**
     * Получить статистику пользователя
     * 
     * @param int $user_id ID пользователя
     * @return array Статистика
     */
    public static function get_user_stats($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'user_credits';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT credits, total_purchased, total_used, updated_at 
             FROM $table 
             WHERE user_id = %d",
            $user_id
        ), ARRAY_A);
        
        if (!$stats) {
            return [
                'credits' => 0,
                'total_purchased' => 0,
                'total_used' => 0,
                'updated_at' => null
            ];
        }
        
        return $stats;
    }
    
    /**
     * Проверить, есть ли у пользователя кредиты
     * 
     * @param int $user_id ID пользователя
     * @return bool
     */
    public static function has_credits($user_id) {
        return self::get_user_credits($user_id) > 0;
    }
}
