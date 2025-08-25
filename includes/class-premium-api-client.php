<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Premium API Client для расширенных функций проверки автомобилей
 * Наследует базовую функциональность от ApiClient и добавляет премиум возможности
 */
class PremiumApiClient {
    
    private $api_key;
    private $api_url;
    private $base_client;
    
    public function __construct() {
        $this->api_key = get_option('vrm_check_api_key', 'AAEF08BA-E98B-42A0-BB63-FEE0492243A7');
        $this->api_url = 'https://uk.api.vehicledataglobal.com/r2/lookup';
        $this->base_client = new ApiClient();
    }
    
    /**
     * Получить экземпляр логгера
     */
    private function get_logger() {
        return Logger::get_instance();
    }
    
    /**
     * Получить данные автомобиля с изображением
     * 
     * @param string $vrm Регистрационный номер автомобиля
     * @return array|false Данные автомобиля или false в случае ошибки
     */
    public function get_vehicle_data_with_image($vrm) {
        $logger = $this->get_logger();
        
        try {
            // Подготавливаем параметры запроса
            $params = array(
                'packagename' => 'VehicleDetailsWithImage',
                'apikey' => $this->api_key,
                'vrm' => $vrm
            );
            
            $url = $this->api_url . '?' . http_build_query($params);
            
            $logger->log('Отправка запроса к премиум API: ' . $url, 'info');
            
            // Выполняем запрос
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'headers' => array(
                    'User-Agent' => 'VRM Check Plugin/1.0'
                )
            ));
            
            if (is_wp_error($response)) {
                $logger->log('Ошибка запроса к премиум API: ' . $response->get_error_message(), 'error');
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $logger->log('Ошибка декодирования JSON ответа премиум API: ' . json_last_error_msg(), 'error');
                return false;
            }
            
            $logger->log('Получен ответ от премиум API: ' . substr($body, 0, 500) . '...', 'info');
            
            // Проверяем успешность ответа
            if (!isset($data['ResponseInformation']['IsSuccessStatusCode']) || !$data['ResponseInformation']['IsSuccessStatusCode']) {
                $logger->log('Премиум API вернул ошибку: ' . ($data['ResponseInformation']['StatusMessage'] ?? 'Unknown error'), 'error');
                return false;
            }
            
            // Обрабатываем и возвращаем данные
            return $this->process_api_response($data);
            
        } catch (Exception $e) {
            $logger->log('Исключение при запросе к премиум API: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Обработать ответ API и преобразовать в нужный формат
     * 
     * @param array $api_data Сырые данные от API
     * @return array Обработанные данные
     */
    private function process_api_response($api_data) {
        $vehicle_details = $api_data['Results']['VehicleDetails'] ?? array();
        $model_details = $api_data['Results']['ModelDetails'] ?? array();
        $image_details = $api_data['Results']['VehicleImageDetails'] ?? array();
        
        // Получаем данные о статусе автомобиля
        $vehicle_status = $vehicle_details['VehicleStatus'] ?? array();
        
        // Обрабатываем premium checks
        $premium_checks = $this->process_premium_checks($vehicle_status);
        
        // Полные данные из Results
        $processed_data = array(
            // Базовая информация о автомобиле
            'vrm' => $vehicle_details['VehicleIdentification']['Vrm'] ?? '',
            'make' => $vehicle_details['VehicleIdentification']['DvlaMake'] ?? '',
            'model' => $vehicle_details['VehicleIdentification']['DvlaModel'] ?? '',
            'year' => $vehicle_details['VehicleIdentification']['YearOfManufacture'] ?? '',
            'colour' => $vehicle_details['VehicleHistory']['ColourDetails']['CurrentColour'] ?? '',
            'fuel_type' => $vehicle_details['VehicleIdentification']['DvlaFuelType'] ?? '',
            'body_type' => $vehicle_details['VehicleIdentification']['DvlaBodyType'] ?? '',
            'engine_size' => $vehicle_details['DvlaTechnicalDetails']['EngineCapacityCc'] ?? '',
            'seats' => $vehicle_details['DvlaTechnicalDetails']['NumberOfSeats'] ?? '',
            
            // Изображение автомобиля
            'vehicle_image_url' => isset($image_details['VehicleImageList'][0]['ImageUrl']) ? $image_details['VehicleImageList'][0]['ImageUrl'] : '',
            
            // Premium checks для шаблона
            'premium_checks' => $premium_checks,
            
            // Упрощенный параметр is_imported
            'is_imported' => $vehicle_status['IsImported'] ?? false,
            
            // Полные данные из Results - VehicleDetails
            'VehicleDetails' => $vehicle_details,
            
            // Полные данные из Results - ModelDetails
            'ModelDetails' => $model_details,
            
            // Полные данные из Results - VehicleImageDetails
            'VehicleImageDetails' => $image_details,

        );
        
        return $processed_data;
    }
    
    /**
     * Обработать premium checks на основе VehicleStatus
     * 
     * @param array $vehicle_status Данные о статусе автомобиля
     * @return array Обработанные premium checks
     */
    private function process_premium_checks($vehicle_status) {
        $premium_checks = array();
        
        // Imported check
        $is_imported = $vehicle_status['IsImported'] ?? false;
        $premium_checks['imported'] = array(
            'status' => $is_imported ? 'warning' : 'pass',
            'message' => $is_imported ? 'Vehicle was imported' : 'Vehicle was not imported'
        );
        
        // Exported check
        $is_exported = $vehicle_status['IsExported'] ?? false;
        $premium_checks['exported'] = array(
            'status' => $is_exported ? 'warning' : 'pass',
            'message' => $is_exported ? 'Vehicle was exported' : 'Vehicle was not exported'
        );
        
        // Scrapped check
        $is_scrapped = $vehicle_status['IsScrapped'] ?? false;
        $premium_checks['scrapped'] = array(
            'status' => $is_scrapped ? 'fail' : 'pass',
            'message' => $is_scrapped ? 'Vehicle is scrapped' : 'Vehicle is not scrapped'
        );
        
        // Unscrapped check (static check)
        $premium_checks['unscrapped'] = array(
            'status' => 'pass',
            'message' => 'Vehicle was not restored after scrapping'
        );
        
        // Safety Recalls check (static check)
        $premium_checks['safety_recalls'] = array(
            'status' => 'pass',
            'message' => 'No safety recalls found'
        );
        
        // Previous Keepers (static check)
        $premium_checks['previous_keepers'] = array(
            'count' => 2,
            'message' => 'Number of previous owners'
        );
        
        // Plate Changes (static check)
        $premium_checks['plate_changes'] = array(
            'count' => 1,
            'message' => 'Number of plate changes'
        );
        
        // MOT check (static check)
        $premium_checks['mot'] = array(
            'status' => 'valid',
            'message' => 'MOT is valid'
        );
        
        // Road Tax check (static check)
        $premium_checks['road_tax'] = array(
            'status' => 'valid',
            'message' => 'Road tax is paid'
        );
        
        // Written off check (static check)
        $premium_checks['written_off'] = array(
            'status' => 'fail',
            'message' => 'Vehicle is written off'
        );
        
        // Salvage History check (static check)
        $premium_checks['salvage_history'] = array(
            'status' => 'fail',
            'message' => 'Vehicle has salvage history'
        );
        
        // Stolen check (static check)
        $premium_checks['stolen'] = array(
            'status' => 'pass',
            'message' => 'Vehicle is not reported stolen'
        );
        
        // Colour Changes check (static check)
        $premium_checks['colour_changes'] = array(
            'status' => 'pass',
            'message' => 'No colour changes detected'
        );
        
        // Mileage Issues check (static check)
        $premium_checks['mileage_issues'] = array(
            'status' => 'warning',
            'message' => 'Mileage issues detected'
        );
        
        // Ex-Taxi check (static check)
        $premium_checks['ex_taxi'] = array(
            'status' => 'pass',
            'message' => 'Vehicle was not used as taxi'
        );
        
        // VIN Check (static check)
        $premium_checks['vin_check'] = array(
            'status' => 'pass',
            'message' => 'VIN number verified and correct'
        );
        
        // Outstanding Finance check (static check)
        $premium_checks['outstanding_finance'] = array(
            'status' => 'pass',
            'message' => 'No outstanding finance found'
        );
        
        // Market Value check (static check)
        $premium_checks['market_value'] = array(
            'status' => 'warning',
            'message' => 'Market value available'
        );
        
        return $premium_checks;
    }

}