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
        add_action('wp_ajax_vrm_check', array($this, 'handle_vrm_check'));
        add_action('wp_ajax_nopriv_vrm_check', array($this, 'handle_vrm_check'));
    }
    
    public function handle_vrm_check() {
        try {
            // Verify nonce for security
            // Verify nonce with debug logging
            $this->logger->log('info', 'Starting nonce verification');
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vrm_check_nonce')) {
            $this->logger->log_error('Nonce verification failed', ['received_nonce' => $_POST['nonce'] ?? 'not provided']);
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'vrm-check-plugin')
            ));
        }
        
        // Get and validate VRM
        // Validate and log VRM input
        $raw_vrm = $_POST['vrm'] ?? '';
        $this->logger->log('info', 'Raw VRM input: ' . $raw_vrm);
        $vrm = sanitize_text_field($raw_vrm);
        
        if (empty($vrm)) {
            $this->logger->log_error('Empty VRM submitted');
            wp_send_json_error(array(
                'message' => __('Please enter a vehicle registration number.', 'vrm-check-plugin')
            ));
        }
        
        // Clean and validate VRM format
        $vrm = strtoupper(trim(str_replace(' ', '', $vrm)));
        if (!$this->is_valid_vrm($vrm)) {
            wp_send_json_error(array(
                'message' => __('Please enter a valid UK registration number.', 'vrm-check-plugin')
            ));
        }
        
        // Check if premium version is requested
        $is_premium = isset($_POST['is_premium']) && $_POST['is_premium'];
        
        // Make API request (basic or premium)
        if ($is_premium) {
            $this->logger->log('info', 'Starting premium API request for VRM: ' . $vrm);
            $result = $this->premium_api_client->get_vehicle_report($vrm);
            
            // Проверяем результат премиум API
            // Если есть ключ 'success' со значением false, значит произошла ошибка
            if (isset($result['success']) && $result['success'] === false) {
                $error_message = isset($result['error']) ? $result['error'] : 'Unknown error occurred';
                $this->logger->error('Premium API request failed for VRM: ' . $vrm, ['error' => $error_message]);
                wp_send_json_error(array(
                    'message' => $error_message
                ));
            }
            
            // Проверяем, что результат не пустой (для случая когда нет ключа success)
            if (empty($result)) {
                $this->logger->error('Premium API returned empty result for VRM: ' . $vrm);
                wp_send_json_error(array(
                    'message' => 'No data received from premium API'
                ));
            }
        } else {
            $result = $this->api_client->get_vehicle_data($vrm);
            if (!$result['success']) {
                wp_send_json_error(array(
                    'message' => $result['error']
                ));
            }
        }
        
        // Генерируем HTML из полученных данных
        ob_start();
        
        if ($is_premium) {
            // Для премиум API данные уже готовы для шаблона
            $data = $result;
            include(plugin_dir_path(dirname(__FILE__)) . 'templates/premium-results-template.php');
        } else {
            // Для обычного API извлекаем данные из структуры success/data
            $data = $result['data'];
            include(plugin_dir_path(dirname(__FILE__)) . 'templates/results-template.php');
        }
        
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html
        ));
        
        } catch (\Exception $e) {
            $this->logger->log_exception($e, array(
                'context' => 'ajax_handler_exception',
                'vrm' => isset($vrm) ? $vrm : 'unknown',
                'is_premium' => isset($is_premium) ? $is_premium : false
            ));
            wp_send_json_error(array(
                'message' => __('An unexpected error occurred. Please try again.', 'vrm-check-plugin'),
                'error_code' => 'unexpected_error'
            ));
        } catch (\Error $e) {
            $this->logger->log_exception($e, array(
                'context' => 'ajax_handler_fatal_error',
                'vrm' => isset($vrm) ? $vrm : 'unknown',
                'is_premium' => isset($is_premium) ? $is_premium : false
            ));
            wp_send_json_error(array(
                'message' => __('A critical error occurred. Please contact support.', 'vrm-check-plugin'),
                'error_code' => 'fatal_error'
            ));
        }
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