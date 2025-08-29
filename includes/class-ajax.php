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
            if (!$result['success']) {
                $this->logger->error('Premium API request failed for VRM: ' . $vrm, ['error' => $result['error']]);
                wp_send_json_error(array(
                    'message' => $result['error']
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
        
        // Адаптируем данные для премиум шаблона
        if ($is_premium) {
            $result['data'] = $this->adapt_premium_data($result['data']);
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
    
    private function adapt_premium_data($data) {
        error_log('VRM Check: adapt_premium_data called with data: ' . print_r($data, true));
        
        // Функция для извлечения первого значения из массива или возврата самого значения
        $extract_value = function($value) {
            if (is_array($value) && !empty($value)) {
                return $value[0];
            }
            return $value;
        };
        
        // Адаптируем основные поля
        $adapted = array();
        
        // Копируем VRM и год
        $adapted['vrm'] = isset($data['vrm']) ? $extract_value($data['vrm']) : '';
        $adapted['year'] = isset($data['year']) ? $extract_value($data['year']) : '';
        $adapted['image'] = isset($data['image']) ? $data['image'] : 'default-car.png';
        
        // Адаптируем VehicleDetails
        if (isset($data['VehicleDetails'])) {
            $adapted['VehicleDetails'] = array();
            
            if (isset($data['VehicleDetails']['VehicleIdentification'])) {
                $adapted['VehicleDetails']['VehicleIdentification'] = array();
                $vi = $data['VehicleDetails']['VehicleIdentification'];
                
                $adapted['VehicleDetails']['VehicleIdentification']['DvlaMake'] = 
                    isset($vi['DvlaMake']) ? $extract_value($vi['DvlaMake']) : '';
                $adapted['VehicleDetails']['VehicleIdentification']['DvlaModel'] = 
                    isset($vi['DvlaModel']) ? $extract_value($vi['DvlaModel']) : '';
                $adapted['VehicleDetails']['VehicleIdentification']['Vrm'] = 
                    isset($vi['Vrm']) ? $extract_value($vi['Vrm']) : '';
                $adapted['VehicleDetails']['VehicleIdentification']['VinLast5'] = 
                    isset($vi['VinLast5']) ? $extract_value($vi['VinLast5']) : '';
            }
            
            // Адаптируем VehicleRegistration
            if (isset($data['VehicleDetails']['VehicleRegistration'])) {
                $adapted['VehicleDetails']['VehicleRegistration'] = array();
                $vr = $data['VehicleDetails']['VehicleRegistration'];
                
                $adapted['VehicleDetails']['VehicleRegistration']['DateOfFirstRegistration'] = 
                    isset($vr['DateOfFirstRegistration']) ? $extract_value($vr['DateOfFirstRegistration']) : '';
                $adapted['VehicleDetails']['VehicleRegistration']['YearOfManufacture'] = 
                    isset($vr['YearOfManufacture']) ? $extract_value($vr['YearOfManufacture']) : '';
            }
            
            // Адаптируем VehicleCharacteristics
            if (isset($data['VehicleDetails']['VehicleCharacteristics'])) {
                $adapted['VehicleDetails']['VehicleCharacteristics'] = array();
                $vc = $data['VehicleDetails']['VehicleCharacteristics'];
                
                $adapted['VehicleDetails']['VehicleCharacteristics']['Colour'] = 
                    isset($vc['Colour']) ? $extract_value($vc['Colour']) : '';
                $adapted['VehicleDetails']['VehicleCharacteristics']['EngineCapacity'] = 
                    isset($vc['EngineCapacity']) ? $extract_value($vc['EngineCapacity']) : '';
                $adapted['VehicleDetails']['VehicleCharacteristics']['FuelType'] = 
                    isset($vc['FuelType']) ? $extract_value($vc['FuelType']) : '';
                $adapted['VehicleDetails']['VehicleCharacteristics']['SeatingCapacity'] = 
                    isset($vc['SeatingCapacity']) ? $extract_value($vc['SeatingCapacity']) : '';
                $adapted['VehicleDetails']['VehicleCharacteristics']['NumberOfDoors'] = 
                    isset($vc['NumberOfDoors']) ? $extract_value($vc['NumberOfDoors']) : '';
            }
        }
        
        // Адаптируем VehicleStatus
        if (isset($data['VehicleStatus'])) {
            $adapted['VehicleStatus'] = array();
            $vs = $data['VehicleStatus'];
            
            $adapted['VehicleStatus']['IsScrapped'] = 
                isset($vs['IsScrapped']) ? $extract_value($vs['IsScrapped']) : false;
            $adapted['VehicleStatus']['IsExported'] = 
                isset($vs['IsExported']) ? $extract_value($vs['IsExported']) : false;
            $adapted['VehicleStatus']['CertificateOfDestructionIssued'] = 
                isset($vs['CertificateOfDestructionIssued']) ? $extract_value($vs['CertificateOfDestructionIssued']) : false;
        }
        
        // Копируем остальные данные как есть
        $adapted['mot_history'] = isset($data['mot_history']) ? $data['mot_history'] : array();
        $adapted['_meta'] = isset($data['_meta']) ? $data['_meta'] : array();
        
        // Добавляем простые поля для совместимости с шаблоном
        $adapted['make'] = isset($adapted['VehicleDetails']['VehicleIdentification']['DvlaMake']) ? 
            $adapted['VehicleDetails']['VehicleIdentification']['DvlaMake'] : '';
        $adapted['model'] = isset($adapted['VehicleDetails']['VehicleIdentification']['DvlaModel']) ? 
            $adapted['VehicleDetails']['VehicleIdentification']['DvlaModel'] : '';
        
        error_log('VRM Check: adapt_premium_data result: ' . print_r($adapted, true));
        
        return $adapted;
    }
}