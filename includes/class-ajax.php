<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

// Подключаем класс MOT History API Client
require_once plugin_dir_path(__FILE__) . 'utilities/class-mot-history-api-client.php';

class Ajax {
    private $api_client;
    private $premium_api_client;
    private $mot_history_client;
    private $logger;
    
    public function __construct() {
        $this->api_client = new ApiClient();
        $this->premium_api_client = new PremiumApiClient();
        $this->mot_history_client = new \VRM_Check_MOT_History_API_Client();
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
            try {
                $this->logger->log('info', 'Starting premium API request for VRM: ' . $vrm);
                $data = $this->premium_api_client->get_vehicle_data_with_image($vrm);
                
                if ($data === false) {
                    $this->logger->log_error('Premium API returned false', ['vrm' => $vrm]);
                    wp_send_json_error(array(
                        'message' => __('Error retrieving premium vehicle data', 'vrm-check-plugin')
                    ));
                }
            } catch (\Exception $e) {
                $this->logger->log_exception($e, array('vrm' => $vrm, 'context' => 'ajax_premium_api_error'));
                wp_send_json_error(array(
                    'message' => __('Service temporarily unavailable', 'vrm-check-plugin'),
                    'error_code' => 'api_error'
                ));
            }
            
            // Get MOT History data for premium requests
            $mot_data = $this->mot_history_client->get_template_data($vrm);
            if ($mot_data !== false) {
                // Merge MOT data with premium data
                $data['mot_history'] = $mot_data;
            }
            
            $result = array('success' => true, 'data' => $data);
        } else {
            $result = $this->api_client->get_vehicle_data($vrm);
            if (!$result['success']) {
                wp_send_json_error(array(
                    'message' => $result['error']
                ));
            }
        }
        
        // Generate HTML from template
        $html = $this->generate_results_html($result['data'], $vrm, $is_premium);
        
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
    
    private function generate_results_html($data, $vrm, $is_premium = false) {
        ob_start();
        
        // Choose template based on premium status
        if ($is_premium) {
            $template_path = VRM_CHECK_PLUGIN_PATH . 'templates/premium-results-template.php';
        } else {
            $template_path = VRM_CHECK_PLUGIN_PATH . 'templates/results-template.php';
        }
        
        // Include the appropriate results template
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback simple HTML if template is missing
            echo '<div class="vrm-check-results">';
            echo '<h2>' . esc_html($vrm) . '</h2>';
            echo '<p>' . __('Vehicle data retrieved successfully.', 'vrm-check-plugin') . '</p>';
            echo '</div>';
        }
        
        return ob_get_clean();
    }
}