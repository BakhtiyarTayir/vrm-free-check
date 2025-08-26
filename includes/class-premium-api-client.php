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
        
        // Получаем данные о статусе автомобиля и истории
        $vehicle_status = $vehicle_details['VehicleStatus'] ?? array();
        $vehicle_history = $vehicle_details['VehicleHistory'] ?? array();
        
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
            
            // Полные данные из Results - VehicleDetails
            'VehicleDetails' => $vehicle_details,
            
            // Полные данные из Results - ModelDetails
            'ModelDetails' => $model_details,
            
            // Полные данные из Results - VehicleImageDetails
            'VehicleImageDetails' => $image_details,
            
            // Данные о статусе и истории автомобиля для прямого доступа
            'VehicleStatus' => $vehicle_status,
            'VehicleHistory' => $vehicle_history
        );
        
        return $processed_data;
    }


}