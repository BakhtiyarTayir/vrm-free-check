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
    private $cache_enabled;
    private $cache_duration;
    private $packagenames = [
        'VehicleDetailsWithImage',
        'GetTax',
        'MileageFinanceDetails',
        'MotHistoryDetails',
        'SpecAndOptionsDetails',
        'TyreDetails',
        'ValuationDetails',
        'VDICheck',
        'VehicleDetails',
       'commercial'
    ];
    
    public function __construct() {
        $this->api_key = get_option('vrm_check_api_key', 'AAEF08BA-E98B-42A0-BB63-FEE0492243A7');
        $this->api_url = 'https://uk.api.vehicledataglobal.com/r2/lookup';
        $this->base_client = new ApiClient();
        
        // Настройки кэширования
        $this->cache_enabled = get_option('vrm_check_cache_enabled', true);
        $this->cache_duration = get_option('vrm_check_cache_duration', 3600); // 1 час по умолчанию
    }
    
    /**
     * Получить экземпляр логгера
     */
    private function get_logger() {
        return Logger::get_instance();
    }
    
    /**
     * Формирует URL-адреса для API запросов на основе массива packagenames
     * 
     * @param string $vrm VRM номер для включения в URL (опционально)
     * @return array Массив сформированных URL-адресов
     */
    private function build_api_urls($vrm = null) {
        $urls = array();
        
        // Используем VRM по умолчанию если не указан
        $vrm_param = $vrm ? $vrm : 'KA57DPO';
        
        // Формируем URL для каждого packagename
        foreach ($this->packagenames as $packagename) {
            $url = $this->api_url . '?' . http_build_query(array(
                'packagename' => $packagename,
                'apikey' => $this->api_key,
                'vrm' => $vrm_param
            ));
            
            $urls[] = $url;
        }
        
        $this->get_logger()->log('Built ' . count($urls) . ' API URLs for packages: ' . implode(', ', $this->packagenames), 'debug');
        
        return $urls;
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
     * @param string $vrm VRM номер для кэширования (опционально)
     * @return array Объединенный массив данных
     */
    private function merge_api_results($responses, $vrm = null) {
        // Проверяем кэш если передан VRM
        if ($vrm && $this->cache_enabled) {
            $cached_data = $this->get_cached_data($vrm);
            if ($cached_data !== false) {
                return $cached_data;
            }
        }
        
        $merged_data = array();
        
        foreach ($responses as $response) {
            if (isset($response['Results']) && is_array($response['Results'])) {
                $merged_data = array_merge_recursive($merged_data, $response['Results']);
            }
        }
        
        // Правильно удаляем дубликаты и оптимизируем данные
        $merged_data = $this->remove_duplicates_and_optimize($merged_data);
        
        // Сохраняем в кэш если передан VRM
        if ($vrm && $this->cache_enabled) {
            $this->set_cached_data($vrm, $merged_data);
        }
        
        // Записываем оптимизированные данные в файл для отладки
        $debug_file = plugin_dir_path(dirname(__FILE__)) . 'debug_merged_data.json';
        $optimized_data = $merged_data;
        file_put_contents($debug_file, json_encode($optimized_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return $merged_data;
    }
    
    /**
     * Рекурсивно удаляет дубликаты и оптимизирует структуру данных
     * 
     * @param array $data Данные для обработки
     * @return array Оптимизированные данные
     */
    private function remove_duplicates_and_optimize($data) {
        if (!is_array($data)) {
            return $data;
        }
        
        $result = array();
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Рекурсивно обрабатываем вложенные массивы
                $processed_value = $this->remove_duplicates_and_optimize($value);
                
                // Если это массив значений (не ассоциативный)
                if ($this->is_indexed_array($processed_value)) {
                    // Удаляем дубликаты
                    $unique_values = array_unique($processed_value, SORT_REGULAR);
                    
                    // Если остался только один уникальный элемент, сохраняем как единичное значение
                    if (count($unique_values) === 1) {
                        $result[$key] = reset($unique_values);
                    } else {
                        $result[$key] = array_values($unique_values);
                    }
                } else {
                    $result[$key] = $processed_value;
                }
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Проверяет, является ли массив индексированным (не ассоциативным)
     * 
     * @param array $array Массив для проверки
     * @return bool True если массив индексированный
     */
    private function is_indexed_array($array) {
        if (!is_array($array)) {
            return false;
        }
        
        return array_keys($array) === range(0, count($array) - 1);
    }
    
    /**
     * Подготавливает данные для отладочного файла с дополнительной информацией
     * 
     * @param array $data Исходные данные
     * @return array Данные с метаинформацией
     */
    private function prepare_debug_data($data) {
        $debug_info = array(
            'generated_at' => date('Y-m-d H:i:s'),
            'data_size' => $this->calculate_data_size($data),
            'optimization_applied' => true,
            'data' => $data
        );
        
        return $debug_info;
    }
    
    /**
     * Вычисляет приблизительный размер данных
     * 
     * @param array $data Данные для анализа
     * @return array Информация о размере
     */
    private function calculate_data_size($data) {
        $json_string = json_encode($data);
        
        return array(
            'json_length' => strlen($json_string),
            'array_elements' => $this->count_array_elements($data),
            'memory_usage' => memory_get_usage(true)
        );
    }
    
    /**
     * Подсчитывает общее количество элементов в многомерном массиве
     * 
     * @param array $data Данные для подсчета
     * @return int Количество элементов
     */
    private function count_array_elements($data) {
        $count = 0;
        
        foreach ($data as $value) {
            if (is_array($value)) {
                $count += $this->count_array_elements($value);
            } else {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Получает полные данные о транспортном средстве из всех доступных API
     * 
     * @param string $vrm VRM номер для замены в URL (опционально)
     * @return array Объединенные данные от всех API
     */
    public function get_comprehensive_vehicle_data($vrm = null) {
        // Формируем URL с учетом переданного VRM
        $urls = $this->build_api_urls($vrm);
        
        if (empty($urls)) {
            $this->get_logger()->log('No API URLs generated', 'error');
            return array('error' => 'No API URLs configured');
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
        $merged_data = $this->merge_api_results($responses, $vrm);
        
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
        // $this->get_logger()->log('Raw API data received: ' . print_r($data, true), 'debug');
        
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
        
        return $data;
    }
    
    /**
     * Генерирует ключ кэша для VRM
     * 
     * @param string $vrm VRM номер
     * @return string Ключ кэша
     */
    private function get_cache_key($vrm) {
        return 'vrm_check_premium_' . sanitize_text_field($vrm);
    }
    
    /**
     * Получает данные из кэша
     * 
     * @param string $vrm VRM номер
     * @return array|false Данные из кэша или false если не найдены
     */
    private function get_cached_data($vrm) {
        if (!$this->cache_enabled) {
            return false;
        }
        
        $cache_key = $this->get_cache_key($vrm);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            $this->get_logger()->log('Cache hit for VRM: ' . $vrm, 'debug');
            return $cached_data;
        }
        
        $this->get_logger()->log('Cache miss for VRM: ' . $vrm, 'debug');
        return false;
    }
    
    /**
     * Сохраняет данные в кэш
     * 
     * @param string $vrm VRM номер
     * @param array $data Данные для сохранения
     * @return bool Результат сохранения
     */
    private function set_cached_data($vrm, $data) {
        if (!$this->cache_enabled) {
            return false;
        }
        
        $cache_key = $this->get_cache_key($vrm);
        $result = set_transient($cache_key, $data, $this->cache_duration);
        
        if ($result) {
            $this->get_logger()->log('Data cached for VRM: ' . $vrm . ' (duration: ' . $this->cache_duration . 's)', 'debug');
        } else {
            $this->get_logger()->log('Failed to cache data for VRM: ' . $vrm, 'error');
        }
        
        return $result;
    }
    
    /**
     * Очищает кэш для конкретного VRM
     * 
     * @param string $vrm VRM номер
     * @return bool Результат очистки
     */
    public function clear_cache($vrm) {
        $cache_key = $this->get_cache_key($vrm);
        $result = delete_transient($cache_key);
        
        if ($result) {
            $this->get_logger()->log('Cache cleared for VRM: ' . $vrm, 'debug');
        }
        
        return $result;
    }
    
    /**
     * Очищает весь кэш премиум API
     * 
     * @return bool Результат очистки
     */
    public function clear_all_cache() {
        global $wpdb;
        
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_vrm_check_premium_%'
            )
        );
        
        // Также удаляем timeout записи
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_vrm_check_premium_%'
            )
        );
        
        $this->get_logger()->log('All premium cache cleared. Removed ' . $result . ' entries', 'debug');
        
        return $result !== false;
    }
}