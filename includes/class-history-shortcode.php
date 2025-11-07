<?php
/**
 * History Shortcode Handler
 * 
 * Отображает историю проверок VRM для текущего пользователя
 *
 * @package VRM_Check_Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

use VrmCheckPlugin\HistoryManager;
use VrmCheckPlugin\CreditsManager;

class VRM_History_Shortcode {
    
    /**
     * Регистрация шорткода
     */
    public static function init() {
        add_shortcode('vrm_check_history', array(__CLASS__, 'render_history'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }
    
    /**
     * Подключение стилей и скриптов
     */
    public static function enqueue_scripts() {
        // Проверяем, есть ли шорткод на странице или это WooCommerce My Account
        global $post, $wp_query;
        
        $should_enqueue = false;
        
        // Проверка для обычных страниц с шорткодом
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'vrm_check_history')) {
            $should_enqueue = true;
        }
        
        // Проверка для WooCommerce endpoint
        if (is_account_page() && isset($wp_query->query_vars['vrm-reports'])) {
            $should_enqueue = true;
        }
        
        if ($should_enqueue) {
            wp_enqueue_style(
                'vrm-history-style',
                VRM_CHECK_PLUGIN_URL . 'assets/css/vrm-history.css',
                array(),
                VRM_CHECK_PLUGIN_VERSION . '.' . time() // Добавляем timestamp для принудительного обновления
            );
            
            wp_enqueue_script(
                'vrm-history-script',
                VRM_CHECK_PLUGIN_URL . 'assets/js/vrm-history.js',
                array('jquery'),
                VRM_CHECK_PLUGIN_VERSION . '.' . time(),
                true
            );
            
            wp_localize_script('vrm-history-script', 'vrmHistory', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vrm_history_nonce')
            ));
        }
    }
    
    /**
     * Рендер истории проверок
     */
    public static function render_history($atts) {
        // Проверка авторизации
        if (!is_user_logged_in()) {
            return self::render_login_message();
        }
        
        $user_id = get_current_user_id();
        
        // Получаем параметры
        $atts = shortcode_atts(array(
            'per_page' => 10,
            'show_filters' => 'yes'
        ), $atts);
        
        // Получаем историю
        $history = \VrmCheckPlugin\HistoryManager::get_user_history($user_id, array(
            'limit' => (int)$atts['per_page']
        ));
        
        // Получаем статистику
        $stats = self::get_user_stats($user_id);
        
        ob_start();
        ?>
        <style>
            .vrm-history-stats {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
                gap: 16px !important;
                margin-bottom: 30px !important;
            }
            .vrm-stat-card {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                padding: 24px !important;
                border-radius: 16px !important;
                text-align: center !important;
            }
        </style>
        <div class="vrm-history-container">
            
            <!-- Статистика -->
            <div class="vrm-history-stats">
                <div class="vrm-stat-card">
                    <div class="vrm-stat-icon">📊</div>
                    <div class="vrm-stat-content">
                        <div class="vrm-stat-value"><?php echo esc_html($stats['total_checks']); ?></div>
                        <div class="vrm-stat-label">Total Checks</div>
                    </div>
                </div>
                
                <div class="vrm-stat-card">
                    <div class="vrm-stat-icon">🚗</div>
                    <div class="vrm-stat-content">
                        <div class="vrm-stat-value"><?php echo esc_html($stats['unique_vehicles']); ?></div>
                        <div class="vrm-stat-label">Unique Vehicles</div>
                    </div>
                </div>
                
                <div class="vrm-stat-card">
                    <div class="vrm-stat-icon">📅</div>
                    <div class="vrm-stat-content">
                        <div class="vrm-stat-value"><?php echo esc_html($stats['this_month']); ?></div>
                        <div class="vrm-stat-label">This Month</div>
                    </div>
                </div>
            </div>
            
            <?php if ($atts['show_filters'] === 'yes'): ?>
            <!-- Фильтры -->
            <div class="vrm-history-filters">
                <input type="text" 
                       id="vrm-search" 
                       class="vrm-search-input" 
                       placeholder="Search by VRM...">
                
                <select id="vrm-filter-type" class="vrm-filter-select">
                    <option value="">All Types</option>
                    <option value="premium">Premium</option>
                    <option value="basic">Basic</option>
                </select>
                
                <button id="vrm-clear-filters" class="vrm-btn-secondary">Clear Filters</button>
            </div>
            <?php endif; ?>
            
            <!-- История -->
            <div class="vrm-history-list">
                <?php if (empty($history)): ?>
                    <div class="vrm-history-empty">
                        <div class="vrm-empty-icon">📋</div>
                        <h3>No Check History</h3>
                        <p>You haven't performed any vehicle checks yet.</p>
                        <a href="<?php echo esc_url(home_url('/full-check-page/')); ?>" class="vrm-btn-primary">
                            Check Your First Vehicle
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($history as $check): ?>
                        <?php echo self::render_history_item($check); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (count($history) >= $atts['per_page']): ?>
            <!-- Пагинация -->
            <div class="vrm-history-pagination">
                <button class="vrm-btn-secondary" id="vrm-load-more">Load More</button>
            </div>
            <?php endif; ?>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Рендер одного элемента истории
     */
    private static function render_history_item($check) {
        $api_data = json_decode($check->api_data, true);
        $vehicle_info = self::extract_vehicle_info($api_data);
        
        ob_start();
        ?>
        <div class="vrm-history-item" data-id="<?php echo esc_attr($check->id); ?>">
            <div class="vrm-history-item-header">
                <div class="vrm-history-vrm">
                    <span class="vrm-badge"><?php echo esc_html($check->vrm); ?></span>
                    <?php if ($check->check_type === 'premium'): ?>
                        <span class="vrm-type-badge vrm-premium">Premium</span>
                    <?php else: ?>
                        <span class="vrm-type-badge vrm-basic">Basic</span>
                    <?php endif; ?>
                </div>
                <div class="vrm-history-date">
                    <?php echo esc_html(self::format_date($check->created_at)); ?>
                </div>
            </div>
            
            <div class="vrm-history-item-body">
                <?php if (!empty($vehicle_info)): ?>
                <div class="vrm-vehicle-info">
                    <div class="vrm-vehicle-detail">
                        <strong>Make:</strong> <?php echo esc_html($vehicle_info['make'] ?? 'N/A'); ?>
                    </div>
                    <div class="vrm-vehicle-detail">
                        <strong>Model:</strong> <?php echo esc_html($vehicle_info['model'] ?? 'N/A'); ?>
                    </div>
                    <div class="vrm-vehicle-detail">
                        <strong>Year:</strong> <?php echo esc_html($vehicle_info['year'] ?? 'N/A'); ?>
                    </div>
                    <div class="vrm-vehicle-detail">
                        <strong>Color:</strong> <?php echo esc_html($vehicle_info['color'] ?? 'N/A'); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="vrm-history-item-footer">
                <button class="vrm-btn-view" onclick="viewReport(<?php echo esc_attr($check->id); ?>)">
                    <span class="vrm-btn-icon">👁️</span>
                    View Full Report
                </button>
                <button class="vrm-btn-download" onclick="downloadReport(<?php echo esc_attr($check->id); ?>)">
                    <span class="vrm-btn-icon">⬇️</span>
                    Download PDF
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Извлечение информации о машине из API данных
     */
    private static function extract_vehicle_info($api_data) {
        if (empty($api_data)) {
            return array();
        }
        
        // Проверяем разные структуры API ответа
        $vehicle_details = $api_data['VehicleDetails'] ?? $api_data;
        $vehicle_id = $vehicle_details['VehicleIdentification'] ?? array();
        $vehicle_history = $vehicle_details['VehicleHistory'] ?? array();
        $color_details = $vehicle_history['ColourDetails'] ?? array();
        
        return array(
            'make' => $vehicle_id['DvlaMake'] ?? 
                     $api_data['VehicleRegistration']['Make'] ?? 
                     $api_data['Make'] ?? 
                     'Unknown',
            'model' => $vehicle_id['DvlaModel'] ?? 
                      $api_data['VehicleRegistration']['Model'] ?? 
                      $api_data['Model'] ?? 
                      'Unknown',
            'year' => $vehicle_id['YearOfManufacture'] ?? 
                     $api_data['VehicleRegistration']['YearOfManufacture'] ?? 
                     $api_data['YearOfManufacture'] ?? 
                     'Unknown',
            'color' => $color_details['CurrentColour'] ?? 
                      $api_data['VehicleRegistration']['Colour'] ?? 
                      $api_data['Colour'] ?? 
                      'Unknown'
        );
    }
    
    /**
     * Получение статистики пользователя
     */
    private static function get_user_stats($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vrm_check_history';
        
        // Общее количество проверок
        $total_checks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d",
            $user_id
        ));
        
        // Уникальные машины
        $unique_vehicles = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT vrm) FROM $table WHERE user_id = %d",
            $user_id
        ));
        
        // Проверки за текущий месяц
        $this_month = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
            WHERE user_id = %d 
            AND MONTH(created_at) = MONTH(CURRENT_DATE())
            AND YEAR(created_at) = YEAR(CURRENT_DATE())",
            $user_id
        ));
        
        // Оставшиеся кредиты
        $credits_remaining = \VrmCheckPlugin\CreditsManager::get_user_credits($user_id);
        
        return array(
            'total_checks' => (int)$total_checks,
            'unique_vehicles' => (int)$unique_vehicles,
            'this_month' => (int)$this_month,
            'credits_remaining' => (int)$credits_remaining
        );
    }
    
    /**
     * Форматирование даты
     */
    private static function format_date($date) {
        $timestamp = strtotime($date);
        $now = current_time('timestamp');
        $diff = $now - $timestamp;
        
        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
        } else {
            return date('j M Y, g:i a', $timestamp);
        }
    }
    
    /**
     * Сообщение для неавторизованных пользователей
     */
    private static function render_login_message() {
        $myaccount_url = get_permalink(get_option('woocommerce_myaccount_page_id'));
        
        ob_start();
        ?>
        <div class="vrm-history-login-required">
            <div class="vrm-login-icon">🔐</div>
            <h3>Login Required</h3>
            <p>Please log in to view your vehicle check history.</p>
            <a href="<?php echo esc_url($myaccount_url); ?>" class="vrm-btn-primary">
                Log In
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Инициализация
VRM_History_Shortcode::init();
