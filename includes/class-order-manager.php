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
        
        // Автоматически менять статус на completed после processing
        add_action('woocommerce_order_status_processing', array(__CLASS__, 'auto_complete_order'));
        
        // Сохранение VRM при создании заказа
        add_action('woocommerce_checkout_create_order', array(__CLASS__, 'save_vrm_to_order'), 10, 2);
        
        // Инициализация сессий для WordPress
        add_action('init', array(__CLASS__, 'start_session'));
        
        // Кастомная страница благодарности для VRM заказов
        add_action('woocommerce_thankyou', array(__CLASS__, 'custom_thankyou_page'), 10, 1);
        
        // Изменить текст кнопки для VRM товаров
        add_filter('woocommerce_product_single_add_to_cart_text', array(__CLASS__, 'change_add_to_cart_text'));
        add_filter('woocommerce_product_add_to_cart_text', array(__CLASS__, 'change_add_to_cart_text'));
        
        // Прямой переход на checkout после добавления VRM товара
        add_filter('woocommerce_add_to_cart_redirect', array(__CLASS__, 'redirect_to_checkout'));
        
        // Скрыть поле количества для VRM товаров
        add_filter('woocommerce_is_sold_individually', array(__CLASS__, 'make_vrm_products_sold_individually'), 10, 2);
        
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
            
            // Автоматически запускаем VRM проверку если есть сохранённый VRM
            self::auto_run_vrm_check($order_id, $user_id);
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
    
    /**
     * Автоматически завершить заказ после processing
     */
    public static function auto_complete_order($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Проверяем, содержит ли заказ только VRM Check товары
        $has_vrm_products = false;
        $has_other_products = false;
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            if (self::is_vrm_check_product($product_id)) {
                $has_vrm_products = true;
            } else {
                $has_other_products = true;
            }
        }
        
        // Если заказ содержит только VRM товары - автоматически завершаем
        if ($has_vrm_products && !$has_other_products) {
            $order->update_status('completed', __('Auto-completed VRM Check order', 'vrm-check-plugin'));
        }
    }
    
    /**
     * Сохранить VRM в мета заказа
     */
    public static function save_vrm_to_order($order, $data) {
        // Стартуем сессию если не запущена
        if (!session_id()) {
            session_start();
        }
        
        // Получаем VRM из сессии (если был сохранён при показе модалки)
        if (isset($_SESSION['pending_vrm_check'])) {
            $vrm = sanitize_text_field($_SESSION['pending_vrm_check']);
            $order->update_meta_data('_pending_vrm_check', $vrm);
            
            error_log('VRM saved to order: ' . $vrm . ' for order ID: ' . $order->get_id());
            
            // Очищаем из сессии
            unset($_SESSION['pending_vrm_check']);
        } else {
            error_log('No VRM found in session for order ID: ' . $order->get_id());
        }
    }
    
    /**
     * Автоматически запустить VRM проверку
     */
    public static function auto_run_vrm_check($order_id, $user_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Получаем сохранённый VRM
        $vrm = $order->get_meta('_pending_vrm_check');
        
        if (!$vrm) {
            return;
        }
        
        // Запускаем VRM проверку с повторными попытками
        $max_retries = 3;
        $retry_count = 0;
        $success = false;
        
        while ($retry_count < $max_retries && !$success) {
            try {
                $logger = Logger::get_instance();
                $logger->info('Attempting VRM check', [
                    'order_id' => $order_id,
                    'vrm' => $vrm,
                    'attempt' => $retry_count + 1
                ]);
                
                $premium_api = new PremiumApiClient();
                $result = $premium_api->get_comprehensive_vehicle_data($vrm);
                
                // Проверяем, что результат не содержит ошибку
                if (!isset($result['error']) && !empty($result)) {
                    // Сохраняем результат в историю
                    $history_id = HistoryManager::save_check(
                        $user_id,
                        $vrm,
                        $result,
                        'premium',
                        9.99, // Стоимость
                        $order_id
                    );
                    
                    // Используем одну проверку
                    self::use_check($user_id, $vrm, $history_id);
                    
                    // Сохраняем ID отчёта в заказе для перенаправления
                    $order->update_meta_data('_vrm_report_id', $history_id);
                    $order->save();
                    
                    // Добавляем заметку
                    $order->add_order_note(
                        sprintf(__('VRM check completed automatically for %s. Report ID: %d (attempt %d)', 'vrm-check-plugin'), $vrm, $history_id, $retry_count + 1)
                    );
                    
                    // Логируем успех
                    $logger->info('Auto VRM check completed', [
                        'order_id' => $order_id,
                        'user_id' => $user_id,
                        'vrm' => $vrm,
                        'history_id' => $history_id,
                        'attempt' => $retry_count + 1
                    ]);
                    
                    $success = true;
                    
                } else {
                    // Логируем ошибку API
                    $logger->error('Auto VRM check API error', [
                        'order_id' => $order_id,
                        'user_id' => $user_id,
                        'vrm' => $vrm,
                        'error' => $result['error'] ?? 'Unknown API error',
                        'attempt' => $retry_count + 1
                    ]);
                    
                    $retry_count++;
                    if ($retry_count < $max_retries) {
                        sleep(2); // Ждём 2 секунды перед повторной попыткой
                    }
                }
                
            } catch (Exception $e) {
                // Логируем исключение
                $logger = Logger::get_instance();
                $logger->error('Auto VRM check exception', [
                    'order_id' => $order_id,
                    'user_id' => $user_id,
                    'vrm' => $vrm,
                    'exception' => $e->getMessage(),
                    'attempt' => $retry_count + 1
                ]);
                
                $retry_count++;
                if ($retry_count < $max_retries) {
                    sleep(2); // Ждём 2 секунды перед повторной попыткой
                }
            }
        }
        
        // Если все попытки неудачны, создаём заглушку для ручной обработки
        if (!$success) {
            // Создаём запись в истории с пометкой "pending"
            $pending_data = [
                'vrm' => $vrm,
                'status' => 'pending',
                'error' => 'API timeout - manual processing required',
                'order_id' => $order_id,
                'timestamp' => current_time('mysql')
            ];
            
            $history_id = HistoryManager::save_check(
                $user_id,
                $vrm,
                $pending_data,
                'premium',
                9.99,
                $order_id
            );
            
            // Сохраняем ID для показа пользователю
            $order->update_meta_data('_vrm_report_id', $history_id);
            $order->update_meta_data('_vrm_check_pending', true);
            $order->save();
            
            // Добавляем заметку для админа
            $order->add_order_note(
                sprintf(__('VRM check for %s requires manual processing due to API timeout. History ID: %d', 'vrm-check-plugin'), $vrm, $history_id)
            );
            
            $logger = Logger::get_instance();
            $logger->error('Auto VRM check failed after all retries', [
                'order_id' => $order_id,
                'user_id' => $user_id,
                'vrm' => $vrm,
                'max_retries' => $max_retries
            ]);
        }
    }
    
    /**
     * Кастомная страница благодарности для VRM заказов
     */
    public static function custom_thankyou_page($order_id) {
        if (!$order_id) {
            return;
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Проверяем, содержит ли заказ VRM товары
        $has_vrm_products = false;
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            
            if (self::is_vrm_check_product($product_id)) {
                $has_vrm_products = true;
                break;
            }
        }
        
        if (!$has_vrm_products) {
            return; // Не VRM заказ
        }
        
        // Получаем VRM и ID отчёта
        $vrm = $order->get_meta('_pending_vrm_check');
        $report_id = $order->get_meta('_vrm_report_id');
        $is_pending = $order->get_meta('_vrm_check_pending');
        
        ?>
        <div class="vrm-thankyou-container" style="background: #f8f9fa; padding: 30px; border-radius: 12px; margin: 20px 0; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 20px;">🚗</div>
            
            <h2 style="color: #667eea; margin-bottom: 15px;">VRM Check <?php echo $is_pending ? 'Processing' : 'Complete'; ?>!</h2>
            
            <?php if ($vrm): ?>
                <p style="font-size: 18px; margin-bottom: 20px;">
                    Your vehicle check for <strong style="color: #333; font-size: 20px; letter-spacing: 1px;"><?php echo esc_html($vrm); ?></strong> 
                    <?php echo $is_pending ? 'is being processed' : 'has been completed'; ?>.
                </p>
            <?php endif; ?>
            
            <?php if ($report_id && !$is_pending): ?>
                <div style="margin: 30px 0;">
                    <a href="<?php echo home_url('/vrm-report/' . $report_id . '/'); ?>" 
                       class="button" 
                       style="background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold; display: inline-block; margin: 10px;">
                        📊 View Your Report
                    </a>
                    
                    <a href="<?php echo home_url('/my-account/vrm-reports/'); ?>" 
                       class="button" 
                       style="background: #10b981; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold; display: inline-block; margin: 10px;">
                        📋 View All Reports
                    </a>
                </div>
                
                <div style="background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <p style="color: #2d5a2d; margin: 0; font-weight: bold;">
                        ✅ Your report is ready! Click "View Your Report" to see the full vehicle details.
                    </p>
                </div>
            <?php elseif ($is_pending): ?>
                <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <p style="color: #856404; margin: 0; font-weight: bold;">
                        ⏳ Your VRM check is being processed due to high API demand. 
                    </p>
                    <p style="color: #856404; margin: 10px 0 0 0; font-size: 14px;">
                        We'll email you when it's ready, or check your reports page in 10-15 minutes.
                    </p>
                </div>
                
                <div style="margin: 30px 0;">
                    <a href="<?php echo home_url('/my-account/vrm-reports/'); ?>" 
                       class="button" 
                       style="background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold; display: inline-block;">
                        📋 Check My Reports
                    </a>
                </div>
            <?php else: ?>
                <div style="background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <p style="color: #856404; margin: 0;">
                        ⏳ Your VRM check is being processed. You'll receive an email when it's ready, or check your reports page in a few minutes.
                    </p>
                </div>
                
                <div style="margin: 30px 0;">
                    <a href="<?php echo home_url('/my-account/vrm-reports/'); ?>" 
                       class="button" 
                       style="background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold; display: inline-block;">
                        📋 Check My Reports
                    </a>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                <p style="color: #6c757d; font-size: 14px; margin: 0;">
                    Order #<?php echo $order->get_order_number(); ?> • <?php echo $order->get_date_created()->format('j F Y, H:i'); ?>
                </p>
            </div>
        </div>
        
        <script>
            // Автоматическое перенаправление на отчёт через 5 секунд (если отчёт готов)
            <?php if ($report_id): ?>
            setTimeout(function() {
                if (confirm('Your VRM report is ready! Would you like to view it now?')) {
                    window.location.href = '<?php echo home_url('/vrm-report/' . $report_id . '/'); ?>';
                }
            }, 3000);
            <?php endif; ?>
        </script>
        <?php
    }
    
    /**
     * Изменить текст кнопки "Add to Cart" на "Buy Now" для VRM товаров
     */
    public static function change_add_to_cart_text($text) {
        global $product;
        
        if (!$product) {
            return $text;
        }
        
        // Проверяем, является ли это VRM товаром
        if (self::is_vrm_check_product($product->get_id())) {
            return __('Buy Now - £9.99', 'vrm-check-plugin');
        }
        
        return $text;
    }
    
    /**
     * Перенаправить на checkout после добавления VRM товара в корзину
     */
    public static function redirect_to_checkout($url) {
        // Проверяем, есть ли VRM товары в корзине
        if (WC()->cart) {
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product_id = $cart_item['product_id'];
                
                if (self::is_vrm_check_product($product_id)) {
                    // Если есть VRM товар - перенаправляем на checkout
                    return wc_get_checkout_url();
                }
            }
        }
        
        return $url;
    }
    
    /**
     * Сделать VRM товары продаваемыми только по одной штуке (скрыть поле количества)
     */
    public static function make_vrm_products_sold_individually($sold_individually, $product) {
        if (self::is_vrm_check_product($product->get_id())) {
            return true;
        }
        
        return $sold_individually;
    }
    
    /**
     * Стартовать сессии WordPress
     */
    public static function start_session() {
        if (!session_id()) {
            session_start();
        }
    }
}
