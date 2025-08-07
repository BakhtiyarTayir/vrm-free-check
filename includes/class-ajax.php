<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax {
    
    public function __construct() {
        add_action('wp_ajax_vrm_check', array($this, 'handle_vrm_check'));
        add_action('wp_ajax_nopriv_vrm_check', array($this, 'handle_vrm_check'));
    }
    
    public function handle_vrm_check() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'vrm_check_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'vrm-check-plugin')
            ));
        }
        
        // Get and validate VRM
        $vrm = sanitize_text_field($_POST['vrm']);
        if (empty($vrm)) {
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
        
        // Make API request
        $api_client = new ApiClient();
        $result = $api_client->get_vehicle_data($vrm);
        
        if (!$result['success']) {
            wp_send_json_error(array(
                'message' => $result['error']
            ));
        }
        
        // Generate HTML from template
        $html = $this->generate_results_html($result['data'], $vrm);
        
        wp_send_json_success(array(
            'html' => $html
        ));
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
    
    private function generate_results_html($data, $vrm) {
        ob_start();
        
        // Include the results template
        $template_path = VRM_CHECK_PLUGIN_PATH . 'templates/results-template.php';
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