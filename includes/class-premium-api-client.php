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
     * Читает URL-адреса из файла api.txt
     * 
     * @return array Массив URL-адресов
     */
    private function read_api_urls() {
        $api_file = plugin_dir_path(dirname(__FILE__)) . 'api.txt';
        
        if (!file_exists($api_file)) {
            $this->get_logger()->log('API file not found: ' . $api_file, 'error');
            return array();
        }
        
        $urls = file($api_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($urls === false) {
            $this->get_logger()->log('Failed to read API file: ' . $api_file, 'error');
            return array();
        }
        
        return array_filter($urls, function($url) {
            return filter_var(trim($url), FILTER_VALIDATE_URL);
        });
    }
    
    /**
     * Выполняет HTTP запрос к указанному URL
     * 
     * @param string $url URL для запроса
     * @return array|false Декодированный JSON ответ или false при ошибке
     */
    private function make_api_request($url) {
        $this->get_logger()->log('Making API request to URL: ' . $url, 'debug');
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'VRM Check Plugin/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            $this->get_logger()->log('API request failed: ' . $response->get_error_message(), 'error');
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $this->get_logger()->log('API request returned code: ' . $response_code, 'error');
            $body = wp_remote_retrieve_body($response);
            $this->get_logger()->log('Response body: ' . $body, 'error');
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $this->get_logger()->log('API response body: ' . substr($body, 0, 500) . '...', 'debug');
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->get_logger()->log('Failed to decode JSON response: ' . json_last_error_msg(), 'error');
            $this->get_logger()->log('Raw response body: ' . $body, 'error');
            return false;
        }
        
        $this->get_logger()->log('Successfully decoded API response', 'debug');
        
        return $data;
    }
    
    /**
     * Объединяет массивы Results из разных API ответов, удаляя дублирующиеся поля
     * 
     * @param array $responses Массив ответов от API
     * @return array Объединенный массив данных
     */
    private function merge_api_results($responses) {
        $merged_data = array();
        
        foreach ($responses as $response) {
            if (isset($response['Results']) && is_array($response['Results'])) {
                $merged_data = array_merge_recursive($merged_data, $response['Results']);
            }
        }
        
        // Удаляем дублирующиеся значения в массивах
        array_walk_recursive($merged_data, function(&$value) {
            if (is_array($value)) {
                $value = array_unique($value);
            }
        });
        
        return $merged_data;
    }
    
    /**
     * Получает полные данные о транспортном средстве из всех доступных API
     * 
     * @param string $vrm VRM номер для замены в URL (опционально)
     * @return array Объединенные данные от всех API
     */
    public function get_comprehensive_vehicle_data($vrm = null) {
        $urls = $this->read_api_urls();
        
        if (empty($urls)) {
            $this->get_logger()->log('No API URLs found', 'error');
            return array('error' => 'No API URLs configured');
        }
        
        // Заменяем VRM в URL если указан
        if ($vrm) {
            $urls = array_map(function($url) use ($vrm) {
                return preg_replace('/vrm=[A-Z0-9]+/', 'vrm=' . urlencode($vrm), $url);
            }, $urls);
        }
        
        $responses = array();
        $successful_requests = 0;
        
        foreach ($urls as $url) {
            $this->get_logger()->log('Making API request to: ' . $url, 'info');
            
            $response = $this->make_api_request($url);
            
            if ($response !== false) {
                $responses[] = $response;
                $successful_requests++;
                $this->get_logger()->log('API request successful', 'info');
            } else {
                $this->get_logger()->log('API request failed for URL: ' . $url, 'warning');
            }
        }
        
        if ($successful_requests === 0) {
            $this->get_logger()->log('All API requests failed', 'error');
            return array('error' => 'All API requests failed');
        }
        
        $this->get_logger()->log('Successfully completed ' . $successful_requests . ' out of ' . count($urls) . ' API requests', 'info');
        
        // Объединяем результаты
        $merged_data = $this->merge_api_results($responses);
        
        // Добавляем метаданные
        $merged_data['_meta'] = array(
            'total_requests' => count($urls),
            'successful_requests' => $successful_requests,
            'timestamp' => current_time('mysql'),
            'vrm' => $vrm
        );
        
        return $merged_data;
    }
    
    /**
     * Получает данные для конкретного VRM и подготавливает их для отображения
     * 
     * @param string $vrm VRM номер
     * @return array Подготовленные данные для шаблона
     */
    public function get_vehicle_report($vrm) {
        if (empty($vrm)) {
            $this->get_logger()->log('VRM is empty', 'error');
            return array('error' => 'VRM is required');
        }
        
        // Валидация VRM
        $vrm = strtoupper(trim($vrm));
        if (!preg_match('/^[A-Z0-9]{1,8}$/', $vrm)) {
            $this->get_logger()->log('Invalid VRM format: ' . $vrm, 'error');
            return array('error' => 'Invalid VRM format');
        }
        
        $this->get_logger()->log('Getting comprehensive vehicle report for VRM: ' . $vrm, 'info');
        
        $data = $this->get_comprehensive_vehicle_data($vrm);
        
        // Логируем полученные данные для отладки
        $this->get_logger()->log('Raw API data received: ' . print_r($data, true), 'debug');
        
        if (isset($data['error'])) {
            $this->get_logger()->log('API returned error: ' . $data['error'], 'error');
            return array(
                'success' => false,
                'error' => $data['error']
            );
        }
        
        // Проверяем, есть ли вообще какие-то данные
        if (empty($data) || (is_array($data) && count($data) === 0)) {
            $this->get_logger()->log('No data returned from API for VRM: ' . $vrm, 'warning');
            return array(
                'success' => false,
                'error' => 'No vehicle data found for this VRM'
            );
        }
        
        // Добавляем VRM в основные данные для шаблона
        $data['vrm'] = $vrm;
        
        // Добавляем год из данных если доступен
        if (isset($data['VehicleDetails']['VehicleIdentification']['YearOfManufacture'])) {
            $data['year'] = $data['VehicleDetails']['VehicleIdentification']['YearOfManufacture'];
        }
        
        // Добавляем изображение по умолчанию если не найдено
        if (!isset($data['vehicle_image_url']) && !isset($data['image'])) {
            $data['image'] = 'default-car.png';
        }
        
        $this->get_logger()->log('Final processed data for template: ' . print_r($data, true), 'debug');
        
        return array(
            'success' => true,
            'data' => $data
        );
    }

}