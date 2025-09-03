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
            // Проверяем nonce для безопасности
            $this->logger->log('info', 'Starting premium VRM check - nonce verification');
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vrm_check_nonce')) {
                $this->logger->log_error('Nonce verification failed', ['received_nonce' => $_POST['nonce'] ?? 'not provided']);
                wp_send_json_error(array(
                    'message' => __('Security check failed. Please refresh the page and try again.', 'vrm-check-plugin')
                ));
            }
            
            // Получаем и валидируем VRM
            $vrm = $this->validate_and_clean_vrm();
            
            // Выполняем премиум API запрос
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
            
            // Генерируем HTML из полученных данных
            ob_start();
            $data = $result;
            include(plugin_dir_path(dirname(__FILE__)) . 'templates/premium-results-template.php');
            $html = ob_get_clean();
            
            wp_send_json_success(array(
                'html' => $html
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