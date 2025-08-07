<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class ApiClient {
    
    private $api_key;
    private $api_url;
    
    public function __construct() {
        $this->api_key = get_option('vrm_check_api_key', '15D0A432-A308-4B28-89B4-6E07F0C55DCE');
        $this->api_url = 'https://uk.api.vehicledataglobal.com/r2/lookup';
    }
    
    public function get_vehicle_data($vrm) {
        if (empty($vrm)) {
            return array(
                'success' => false,
                'error' => 'VRM не может быть пустым'
            );
        }
        
        // Очищаем VRM от пробелов и приводим к верхнему регистру
        $vrm = strtoupper(trim(str_replace(' ', '', $vrm)));
        
        // Делаем запрос к MOT API
        $mot_response = $this->make_api_request($vrm, 'MotHistoryDetails');
        
        if (!$mot_response['success']) {
            return $mot_response;
        }
        
        // Делаем запрос к Tax API
        $tax_response = $this->make_api_request($vrm, 'GetTax');
        
        // Преобразуем данные API в нужный формат
        return $this->transform_response($mot_response['data'], $tax_response['success'] ? $tax_response['data'] : null);
    }
    
    private function make_api_request($vrm, $package_name = 'MotHistoryDetails') {
        // Формируем URL с параметрами
        $url = add_query_arg(array(
            'packagename' => $package_name,
            'apikey' => $this->api_key,
            'vrm' => $vrm
        ), $this->api_url);
        
        // Настройки для HTTP запроса
        $args = array(
            'method' => 'GET',
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'VRM-Check-Plugin/1.0'
            )
        );
        
        // Выполняем HTTP запрос
        $response = wp_remote_get($url, $args);
        
        // Проверяем на ошибки WordPress
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => 'Ошибка соединения: ' . $response->get_error_message()
            );
        }
        
        // Получаем код ответа
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'error' => 'API вернул ошибку: код ' . $response_code
            );
        }
        
        // Получаем тело ответа
        $response_body = wp_remote_retrieve_body($response);
        
        // Парсим JSON
        $data = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'error' => 'Ошибка парсинга JSON ответа'
            );
        }
        
        // Проверяем успешность ответа API
        if (!isset($data['ResponseInformation']['IsSuccessStatusCode']) || 
            $data['ResponseInformation']['IsSuccessStatusCode'] !== true) {
            return array(
                'success' => false,
                'error' => 'API вернул ошибку: ' . ($data['ResponseInformation']['StatusMessage'] ?? 'Неизвестная ошибка')
            );
        }
        
        return array(
            'success' => true,
            'data' => $data
        );
    }
    
    private function transform_response($mot_api_data, $tax_api_data = null) {
        // Получаем данные из MotHistoryDetails
        $mot_details = $mot_api_data['Results']['MotHistoryDetails'] ?? array();
        
        // Получаем данные из VehicleTaxDetails
        $tax_details = $tax_api_data['Results']['VehicleTaxDetails'] ?? array();
        
        // Извлекаем год из FirstUsedDate
        $year = 'N/A';
        $year_age = '';
        if (!empty($mot_details['FirstUsedDate'])) {
            $date = new \DateTime($mot_details['FirstUsedDate']);
            $year = $date->format('Y');
            
            // Вычисляем возраст автомобиля
            $now = new \DateTime();
            $diff = $now->diff($date);
            $year_age = $diff->y . ' years ' . $diff->m . ' months old';
        }
        
        // Форматируем дату регистрации
        $registered_date = 'N/A';
        if (!empty($mot_details['FirstUsedDate'])) {
            $date = new \DateTime($mot_details['FirstUsedDate']);
            $registered_date = $date->format('d M Y');
        }
        
        // Форматируем дату V5C (используем дату обновления если V5C не доступна)
        $v5c_issue_date = 'N/A';
        $v5c_age = '';
        if (!empty($mot_details['UpdateTimeStamp'])) {
            $date = new \DateTime($mot_details['UpdateTimeStamp']);
            $v5c_issue_date = $date->format('d M Y');
            
            $now = new \DateTime();
            $diff = $now->diff($date);
            $v5c_age = $diff->y . ' years ' . $diff->m . ' months ago';
        }
        
        // Форматируем дату MOT
        $mot_due_date = 'N/A';
        $mot_due_text = 'MOT information not available';
        if (!empty($mot_details['MotDueDate'])) {
            $date = new \DateTime($mot_details['MotDueDate']);
            $mot_due_date = $date->format('d M Y');
            
            $now = new \DateTime();
            $diff = $now->diff($date);
            if ($date > $now) {
                $mot_due_text = 'MOT due in ' . $diff->m . ' months';
            } else {
                $mot_due_text = 'MOT overdue by ' . $diff->m . ' months';
            }
        }
        
        // Форматируем дату последнего теста
        $latest_test_date = 'N/A';
        if (!empty($mot_details['LatestTestDate'])) {
            $date = new \DateTime($mot_details['LatestTestDate']);
            $latest_test_date = $date->format('d M Y');
        }
        
        // Получаем пробег из последнего MOT теста
        $estimated_mileage = 'N/A';
        if (!empty($mot_details['MotTestDetailsList']) && is_array($mot_details['MotTestDetailsList'])) {
            $latest_test = $mot_details['MotTestDetailsList'][0];
            if (!empty($latest_test['OdometerReading'])) {
                $mileage = number_format($latest_test['OdometerReading']);
                $unit = $latest_test['OdometerUnit'] ?? 'mi';
                $estimated_mileage = $mileage . ' ' . $unit;
            }
        }
        
        // Обрабатываем налоговые данные
        $tax_status = 'N/A';
        $tax_due_date = 'N/A';
        $tax_due_text = 'Tax information not available';
        $co2_emissions = 'N/A';
        $tax_band = 'N/A';
        
        if (!empty($tax_details)) {
            // Статус налога
            $tax_status = $tax_details['TaxStatus'] ?? 'N/A';
            
            // Дата окончания налога
            if (!empty($tax_details['TaxDueDate'])) {
                $date = new \DateTime($tax_details['TaxDueDate']);
                $tax_due_date = $date->format('d M Y');
                
                $now = new \DateTime();
                $diff = $now->diff($date);
                if ($date > $now) {
                    $tax_due_text = 'Tax due in ' . $diff->days . ' days';
                } else {
                    $tax_due_text = 'Tax overdue by ' . $diff->days . ' days';
                }
            }
            
            // CO2 выбросы
            if (!empty($tax_details['Co2Emissions'])) {
                $co2_emissions = $tax_details['Co2Emissions'] . ' g/km';
            }
            
            // Налоговая группа из VED данных
            if (!empty($tax_details['VehicleExciseDutyDetails']['DvlaBand'])) {
                $tax_band = $tax_details['VehicleExciseDutyDetails']['DvlaBand'];
            }
        }
        
        // Возвращаем данные в расширенном формате
        return array(
            'success' => true,
            'data' => array(
                'registration' => $mot_details['Vrm'] ?? '',
                'make' => $mot_details['Make'] ?? 'N/A',
                'model' => $mot_details['Model'] ?? 'N/A',
                'year' => $year,
                'year_age' => $year_age,
                'v5c_issue_date' => $v5c_issue_date,
                'v5c_age' => $v5c_age,
                'colour' => $mot_details['Colour'] ?? 'N/A',
                'registered' => $registered_date,
                'fuel_type' => $mot_details['FuelType'] ?? 'N/A',
                'mot_due_date' => $mot_due_date,
                'mot_due_text' => $mot_due_text,
                'first_used_date' => $registered_date,
                'latest_test_date' => $latest_test_date,
                'estimated_mileage' => $estimated_mileage,
                // Additional fields for Vehicle Specification
                'engine_capacity' => 'N/A', // Not available in current API response
                'vehicle_type' => 'Car', // Default value
                'body_type' => '', // Not available in current API response
                'co2_emissions' => $co2_emissions,
                'tax_band' => $tax_band,
                'revenue_weight' => 'No Data', // Not available in current API response
                'type_approval' => 'M1', // Default value
                'euro_status' => 'N/A', // Not available in current API response
                // Tax information
                'tax_status' => $tax_status,
                'tax_due_date' => $tax_due_date,
                'tax_due_text' => $tax_due_text
            )
        );
    }
}