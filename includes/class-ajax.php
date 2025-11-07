<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}



class Ajax {
    private $api_client;
    private $premium_api_client;

    
    private $logger;
    
    public function __construct() {
        $this->api_client = new ApiClient();
        $this->premium_api_client = new PremiumApiClient();
        
        $this->logger = Logger::get_instance();
        // Регистрируем обработчики для обычной и премиум проверки
        add_action('wp_ajax_vrm_check', array($this, 'handle_vrm_check'));
        add_action('wp_ajax_nopriv_vrm_check', array($this, 'handle_vrm_check'));
        add_action('wp_ajax_vrm_check_premium', array($this, 'handle_vrm_check_premium'));
        add_action('wp_ajax_nopriv_vrm_check_premium', array($this, 'handle_vrm_check_premium'));
    }
    
    /**
     * Обработчик для обычной проверки VRM
     */
    public function handle_vrm_check() {
        try {
            // Проверяем nonce для безопасности
            $this->logger->log('info', 'Starting basic VRM check - nonce verification');
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vrm_check_nonce')) {
                $this->logger->log_error('Nonce verification failed', ['received_nonce' => $_POST['nonce'] ?? 'not provided']);
                wp_send_json_error(array(
                    'message' => __('Security check failed. Please refresh the page and try again.', 'vrm-check-plugin')
                ));
            }
            
            // Получаем и валидируем VRM
            $vrm = $this->validate_and_clean_vrm();
            
            // Выполняем обычный API запрос
            $this->logger->log('info', 'Starting basic API request for VRM: ' . $vrm);
            $result = $this->api_client->get_vehicle_data($vrm);
            
            if (!$result['success']) {
                wp_send_json_error(array(
                    'message' => $result['error']
                ));
            }
            
            // Генерируем HTML из полученных данных
            ob_start();
            $data = $result['data'];
            include(plugin_dir_path(dirname(__FILE__)) . 'templates/results-template.php');
            $html = ob_get_clean();
            
            wp_send_json_success(array(
                'html' => $html
            ));
            
        } catch (Exception $e) {
            $this->logger->log_error('Exception in basic VRM check: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while processing your request.', 'vrm-check-plugin')
            ));
        }
    }
    
    /**
     * Обработчик для премиум проверки VRM
     */
    public function handle_vrm_check_premium() {
        try {
            // 1. Получаем VRM из запроса (нужно для сохранения в сессии)
            $vrm_raw = isset($_POST['vrm']) ? sanitize_text_field($_POST['vrm']) : '';
            
            // 2. Проверка авторизации
            if (!is_user_logged_in()) {
                $this->logger->log('warning', 'Unauthorized premium check attempt', ['vrm' => $vrm_raw]);
                
                // Сохраняем VRM и текущую страницу в сессии для редиректа после логина
                if (!session_id()) {
                    session_start();
                }
                $_SESSION['vrm_check_pending'] = $vrm_raw;
                $_SESSION['vrm_check_redirect'] = $_SERVER['HTTP_REFERER'] ?? home_url('/full-check-page/');
                
                // Используем WooCommerce My Account страницу
                $myaccount_page_id = get_option('woocommerce_myaccount_page_id');
                $myaccount_url = $myaccount_page_id ? get_permalink($myaccount_page_id) : wp_login_url(get_permalink());
                
                wp_send_json_error(array(
                    'message' => __('Please log in to use premium vehicle checks.', 'vrm-check-plugin'),
                    'login_required' => true,
                    'login_url' => $myaccount_url,
                    'register_url' => $myaccount_url
                ));
            }
            
            $user_id = get_current_user_id();
            
            // 3. Проверка на существование VRM в истории
            $existing_check = HistoryManager::get_user_check_by_vrm($user_id, $vrm_raw);
            
            if ($existing_check) {
                $this->logger->log('info', 'VRM already checked, redirecting to reports', [
                    'user_id' => $user_id,
                    'vrm' => $vrm_raw,
                    'check_id' => $existing_check->id
                ]);
                
                // Перенаправляем на страницу My Reports
                $myaccount_page_id = get_option('woocommerce_myaccount_page_id');
                $reports_url = $myaccount_page_id ? get_permalink($myaccount_page_id) . 'vrm-reports/' : home_url('/my-reports/');
                
                wp_send_json_error(array(
                    'message' => __('This vehicle has already been checked. Redirecting to your reports...', 'vrm-check-plugin'),
                    'already_checked' => true,
                    'redirect_url' => $reports_url,
                    'check_id' => $existing_check->id
                ));
            }
            
            // 4. Проверка доступных проверок
            $checks = \VrmCheckPlugin\OrderManager::get_user_checks($user_id);
            $this->logger->log('info', 'User checks available', ['user_id' => $user_id, 'checks' => $checks]);
            
            if ($checks <= 0) {
                $this->logger->log('warning', 'No checks available', ['user_id' => $user_id]);
                
                // Получаем URL товара VRM Check
                $product_url = get_permalink(\VrmCheckPlugin\OrderManager::VRM_CHECK_PRODUCT_ID);
                
                wp_send_json_error(array(
                    'message' => __('You have no VRM checks available. Please purchase a check to continue.', 'vrm-check-plugin'),
                    'checks_required' => true,
                    'shop_url' => $product_url ? $product_url : get_permalink(wc_get_page_id('shop'))
                ));
            }
            
            // 5. Проверяем nonce для безопасности
            $this->logger->log('info', 'Starting premium VRM check - nonce verification');
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vrm_check_nonce')) {
                $this->logger->log_error('Nonce verification failed', ['received_nonce' => $_POST['nonce'] ?? 'not provided']);
                wp_send_json_error(array(
                    'message' => __('Security check failed. Please refresh the page and try again.', 'vrm-check-plugin')
                ));
            }
            
            // 6. Получаем и валидируем VRM
            $vrm = $this->validate_and_clean_vrm();
            
            // 7. Выполняем премиум API запрос
            $this->logger->log('info', 'Starting premium API request for VRM: ' . $vrm);
            $result = $this->premium_api_client->get_vehicle_report($vrm);
            
            // Проверяем результат премиум API
            if (isset($result['success']) && $result['success'] === false) {
                $error_message = isset($result['error']) ? $result['error'] : 'Unknown error occurred';
                $this->logger->error('Premium API request failed for VRM: ' . $vrm, ['error' => $error_message]);
                wp_send_json_error(array(
                    'message' => $error_message
                ));
            }
            
            // Проверяем, что результат не пустой
            if (empty($result)) {
                $this->logger->error('Premium API returned empty result for VRM: ' . $vrm);
                wp_send_json_error(array(
                    'message' => 'No data received from premium API'
                ));
            }
            
            // 6. Сохраняем в историю
            $history_id = HistoryManager::save_check($user_id, $vrm, $result, 'premium', 9.99);
            $this->logger->log('info', 'Check saved to history', ['history_id' => $history_id, 'user_id' => $user_id, 'vrm' => $vrm]);
            
            // 7. Используем одну проверку
            if (!\VrmCheckPlugin\OrderManager::use_check($user_id, $vrm, $history_id)) {
                $this->logger->error('Failed to use check', ['user_id' => $user_id]);
                wp_send_json_error(array(
                    'message' => __('Error processing check. Please contact support.', 'vrm-check-plugin')
                ));
            }
            
            // 8. Генерируем HTML из полученных данных
            ob_start();
            $data = $result;
            include(plugin_dir_path(dirname(__FILE__)) . 'templates/premium-results-template.php');
            $html = ob_get_clean();
            
            // 9. Возвращаем результат с обновлённым балансом
            $remaining_checks = \VrmCheckPlugin\OrderManager::get_user_checks($user_id);
            wp_send_json_success(array(
                'html' => $html,
                'checks_remaining' => $remaining_checks,
                'history_id' => $history_id
            ));
            
        } catch (Exception $e) {
            $this->logger->log_error('Exception in premium VRM check: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while processing your request.', 'vrm-check-plugin')
            ));
        }
    }
    
    /**
     * Валидация и очистка VRM (общий метод для обоих обработчиков)
     */
    private function validate_and_clean_vrm() {
        $raw_vrm = $_POST['vrm'] ?? '';
        $this->logger->log('info', 'Raw VRM input: ' . $raw_vrm);
        $vrm = sanitize_text_field($raw_vrm);
        
        if (empty($vrm)) {
            $this->logger->log_error('Empty VRM submitted');
            wp_send_json_error(array(
                'message' => __('Please enter a vehicle registration number.', 'vrm-check-plugin')
            ));
        }
        
        // Очищаем и валидируем формат VRM
        $vrm = strtoupper(trim(str_replace(' ', '', $vrm)));
        if (!$this->is_valid_vrm($vrm)) {
            wp_send_json_error(array(
                'message' => __('Please enter a valid UK registration number.', 'vrm-check-plugin')
            ));
        }
        
        return $vrm;
    }
    
    private function is_valid_vrm($vrm) {
        if (empty($vrm) || strlen($vrm) < 2 || strlen($vrm) > 8) {
            return false;
        }
        
        // UK VRM validation patterns
        $patterns = array(
            '/^[A-Z]{2}[0-9]{2}[A-Z]{3}$/',  // Current format: AB12CDE
            '/^[A-Z][0-9]{1,3}[A-Z]{3}$/',   // Prefix format: A123BCD
            '/^[A-Z]{3}[0-9]{1,3}[A-Z]$/',   // Suffix format: ABC123D
            '/^[0-9]{1,4}[A-Z]{1,3}$/',      // Dateless format: 1234AB
            '/^[A-Z]{1,3}[0-9]{1,4}$/'       // Reversed dateless: AB1234
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $vrm)) {
                return true;
            }
        }
        
        return false;
    }

}