<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class ApiClient {
    
    private $api_key;
    private $api_url;
    
    public function __construct() {
        $this->api_key = get_option('vrm_check_api_key', 'AAEF08BA-E98B-42A0-BB63-FEE0492243A7');
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
        
        // Делаем запрос к VehicleDetails API
        $vehicle_details_response = $this->make_api_request($vrm, 'VehicleDetails');
        
        // Делаем запрос к MileageFinanceDetails API
        $mileage_response = $this->make_api_request($vrm, 'MileageFinanceDetails');
        
        // Трансформируем данные
        return $this->transform_response(
            $mot_response['data'], 
            $tax_response['success'] ? $tax_response['data'] : null,
            $vehicle_details_response['success'] ? $vehicle_details_response['data'] : null,
            $mileage_response['success'] ? $mileage_response['data'] : null
        );
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
    
    private function transform_response($mot_api_data, $tax_api_data = null, $vehicle_details_api_data = null, $mileage_api_data = null) {
        // Получаем базовые данные из разных источников
        $mot_data = $this->transform_mot_data($mot_api_data);
        $tax_data = $this->transform_tax_data($tax_api_data);
        $vehicle_details_data = $this->transform_vehicle_details_data($vehicle_details_api_data);
        $mileage_data = $this->transform_mileage_data($mileage_api_data);
        
        // Объединяем все данные
        $combined_data = array_merge($mot_data, $tax_data, $vehicle_details_data, $mileage_data);
        
        return array(
            'success' => true,
            'data' => $combined_data
        );
    }
    
    /**
     * Обрабатывает данные MOT (техосмотр)
     */
    private function transform_mot_data($mot_api_data) {
        $mot_details = $mot_api_data['Results']['MotHistoryDetails'] ?? array();
        
        // Обрабатываем основную информацию об автомобиле
        $vehicle_info = $this->extract_vehicle_info($mot_details);
        
        // Обрабатываем даты
        $date_info = $this->extract_date_info($mot_details);
        
        // Обрабатываем информацию о MOT
        $mot_info = $this->extract_mot_info($mot_details);
        
        // Обрабатываем пробег
        $mileage_info = $this->extract_mileage_info($mot_details);
        
        return array_merge($vehicle_info, $date_info, $mot_info, $mileage_info);
    }
    
    /**
     * Обрабатывает налоговые данные
     */
    private function transform_tax_data($tax_api_data) {
        if (empty($tax_api_data)) {
            return $this->get_default_tax_data();
        }
        
        $tax_details = $tax_api_data['Results']['VehicleTaxDetails'] ?? array();
        
        if (empty($tax_details)) {
            return $this->get_default_tax_data();
        }
        
        return array(
            'tax_status' => $tax_details['TaxStatus'] ?? 'N/A',
            'tax_due_date' => $this->format_date($tax_details['TaxDueDate'] ?? ''),
            'tax_due_text' => $this->calculate_due_text($tax_details['TaxDueDate'] ?? '', 'Tax'),
            'co2_emissions' => $this->format_co2_emissions($tax_details['Co2Emissions'] ?? ''),
            'tax_band' => $tax_details['VehicleExciseDutyDetails']['DvlaBand'] ?? 'N/A'
        );
    }
    
    /**
     * Обрабатывает данные VehicleDetails
     */
    private function transform_vehicle_details_data($vehicle_details_api_data) {
        if (empty($vehicle_details_api_data)) {
            return $this->get_default_vehicle_details_data();
        }
        
        $vehicle_details = $vehicle_details_api_data['Results']['VehicleDetails'] ?? array();
        $model_details = $vehicle_details_api_data['Results']['ModelDetails'] ?? array();
        
        if (empty($vehicle_details)) {
            return $this->get_default_vehicle_details_data();
        }
        
        // Извлекаем данные из разных секций
        $technical_details = $vehicle_details['DvlaTechnicalDetails'] ?? array();
        $vehicle_status = $vehicle_details['VehicleStatus'] ?? array();
        $vehicle_identification = $vehicle_details['VehicleIdentification'] ?? array();
        $model_classification = $model_details['ModelClassification'] ?? array();
        $body_details = $model_details['BodyDetails'] ?? array();
        $weights = $model_details['Weights'] ?? array();
        $powertrain = $model_details['Powertrain'] ?? array();
        $emissions = $model_details['Emissions'] ?? array();
        $performance = $model_details['Performance'] ?? array();
        
        return array(
            // Engine Capacity (EngineCapacityCc -> engine_capacity)
            'engine_capacity' => $this->format_engine_capacity($technical_details['EngineCapacityCc'] ?? $powertrain['IceDetails']['EngineCapacityCc'] ?? ''),
            
            // Fuel Type (DvlaFuelType -> fuel_type)
            'fuel_type' => $vehicle_identification['DvlaFuelType'] ?? $powertrain['FuelType'] ?? 'N/A',
            
            // Vehicle Class (VehicleClass -> vehicle_type)
            'vehicle_type' => $model_classification['VehicleClass'] ?? 'N/A',
            'body_type' => $body_details['BodyStyle'] ?? $vehicle_identification['DvlaBodyType'] ?? '',
            
            // CO2 Emissions and Tax Band (DvlaCo2, DvlaCo2Band -> co2_emissions, tax_band)
            'co2_emissions' => $this->format_co2_emissions($vehicle_status['VehicleExciseDutyDetails']['DvlaCo2'] ?? $emissions['ManufacturerCo2'] ?? ''),
            'tax_band' => $vehicle_status['VehicleExciseDutyDetails']['DvlaCo2Band'] ?? 'N/A',
            
            // VED Tax Rates (VedRate -> ved_six_months, ved_twelve_months)
            'ved_six_months' => $vehicle_status['VehicleExciseDutyDetails']['VedRate']['Standard']['SixMonths'] ?? 'N/A',
            'ved_twelve_months' => $vehicle_status['VehicleExciseDutyDetails']['VedRate']['Standard']['TwelveMonths'] ?? 'N/A',
            
            // Gross Vehicle Weight (GrossVehicleWeightKg -> revenue_weight)
            'revenue_weight' => $this->format_weight($weights['GrossVehicleWeightKg'] ?? ''),
            'type_approval' => $model_classification['TypeApprovalCategory'] ?? 'N/A',
            
            // Euro Status (EuroStatus -> euro_status)
            'euro_status' => $emissions['EuroStatus'] ?? 'N/A',
            
            // Performance data
            'bhp' => $performance['Power']['Bhp'] ?? 'N/A',
            'top_speed_mph' => $performance['Statistics']['MaxSpeedMph'] ?? 'N/A',
            'zero_to_sixty' => $performance['Statistics']['ZeroToOneHundredKph'] ?? 'N/A',
            
            // Fuel Economy data
            'urban_mpg' => $performance['FuelEconomy']['UrbanColdMpg'] ?? 'N/A',
            'extra_urban_mpg' => $performance['FuelEconomy']['ExtraUrbanMpg'] ?? 'N/A',
            'combined_mpg' => $performance['FuelEconomy']['CombinedMpg'] ?? 'N/A'
        );
    }
    
    /**
     * Извлекает основную информацию об автомобиле
     */
    private function extract_vehicle_info($mot_details) {
        return array(
            'registration' => $mot_details['Vrm'] ?? '',
            'make' => $mot_details['Make'] ?? 'N/A',
            'model' => $mot_details['Model'] ?? 'N/A',
            'colour' => $mot_details['Colour'] ?? 'N/A',
            'fuel_type' => $mot_details['FuelType'] ?? 'N/A',
            // Дополнительные поля для спецификации автомобиля
            'engine_capacity' => 'N/A',
            'vehicle_type' => 'Car',
            'body_type' => '',
            'revenue_weight' => 'No Data',
            'type_approval' => 'M1',
            'euro_status' => 'N/A'
        );
    }
    
    /**
     * Извлекает и форматирует информацию о датах
     */
    private function extract_date_info($mot_details) {
        $first_used_date = $mot_details['FirstUsedDate'] ?? '';
        $update_timestamp = $mot_details['UpdateTimeStamp'] ?? '';
        
        return array(
            'year' => $this->extract_year($first_used_date),
            'year_age' => $this->calculate_vehicle_age($first_used_date),
            'registered' => $this->format_date($first_used_date),
            'first_used_date' => $this->format_date($first_used_date),
            'v5c_issue_date' => $this->format_date($update_timestamp),
            'v5c_age' => $this->calculate_age_text($update_timestamp)
        );
    }
    
    /**
     * Извлекает информацию о MOT
     */
    private function extract_mot_info($mot_details) {
        return array(
            'mot_due_date' => $this->format_date($mot_details['MotDueDate'] ?? ''),
            'mot_due_text' => $this->calculate_due_text($mot_details['MotDueDate'] ?? '', 'MOT'),
            'latest_test_date' => $this->format_date($mot_details['LatestTestDate'] ?? '')
        );
    }
    
    /**
     * Извлекает информацию о пробеге
     */
    private function extract_mileage_info($mot_details) {
        $estimated_mileage = 'N/A';
        
        if (!empty($mot_details['MotTestDetailsList']) && is_array($mot_details['MotTestDetailsList'])) {
            $latest_test = $mot_details['MotTestDetailsList'][0];
            if (!empty($latest_test['OdometerReading'])) {
                $mileage = number_format($latest_test['OdometerReading']);
                $unit = $latest_test['OdometerUnit'] ?? 'mi';
                $estimated_mileage = $mileage . ' ' . $unit;
            }
        }
        
        return array('estimated_mileage' => $estimated_mileage);
    }
    
    /**
     * Возвращает данные по умолчанию для налоговой информации
     */
    private function get_default_tax_data() {
        return array(
            'tax_status' => 'N/A',
            'tax_due_date' => 'N/A',
            'tax_due_text' => 'Tax information not available',
            'co2_emissions' => 'N/A',
            'tax_band' => 'N/A'
        );
    }
    
    /**
     * Возвращает данные по умолчанию для VehicleDetails
     */
    private function get_default_vehicle_details_data() {
        return array(
            'engine_capacity' => 'N/A',
            'fuel_type' => 'N/A',
            'vehicle_type' => 'N/A',
            'body_type' => 'N/A',
            'co2_emissions' => 'N/A',
            'tax_band' => 'N/A',
            'ved_six_months' => 'N/A',
            'ved_twelve_months' => 'N/A',
            'revenue_weight' => 'N/A',
            'type_approval' => 'N/A',
            'euro_status' => 'N/A',
            'bhp' => 'N/A',
            'top_speed_mph' => 'N/A',
            'zero_to_sixty' => 'N/A',
            'urban_mpg' => 'N/A',
            'extra_urban_mpg' => 'N/A',
            'combined_mpg' => 'N/A'
        );
    }
    
    /**
     * Обрабатывает данные о пробеге из MileageFinanceDetails API
     */
    private function transform_mileage_data($mileage_api_data) {
        if (empty($mileage_api_data)) {
            return array(
                'estimated_mileage' => 'N/A',
                'current_mileage' => 'N/A',
                'mileage_last_year' => '0 miles',
                'average_mileage' => 'N/A'
            );
        }
        
        $mileage_details = $mileage_api_data['Results']['MileageCheckDetails'] ?? array();
        
        if (empty($mileage_details['MileageResultList']) || !is_array($mileage_details['MileageResultList'])) {
            return array(
                'estimated_mileage' => 'N/A',
                'current_mileage' => 'N/A',
                'mileage_last_year' => '0 miles',
                'average_mileage' => 'N/A'
            );
        }
        
        // Получаем список записей о пробеге
        $mileage_list = $mileage_details['MileageResultList'];
        
        // Находим запись с самой последней датой
        $latest_mileage_record = null;
        $latest_date = null;
        
        foreach ($mileage_list as $record) {
            if (!empty($record['DateRecorded']) && !empty($record['Mileage'])) {
                try {
                    $record_date = new \DateTime($record['DateRecorded']);
                    
                    if ($latest_date === null || $record_date > $latest_date) {
                        $latest_date = $record_date;
                        $latest_mileage_record = $record;
                    }
                } catch (\Exception $e) {
                    // Пропускаем записи с некорректными датами
                    continue;
                }
            }
        }
        
        // Рассчитываем пробег за последний год
        $current_year = date('Y');
        $last_year = $current_year - 1;
        
        $current_year_mileage = null;
        $last_year_mileage = null;
        
        foreach ($mileage_list as $record) {
            if (!empty($record['DateRecorded']) && !empty($record['Mileage'])) {
                try {
                    $record_date = new \DateTime($record['DateRecorded']);
                    $record_year = $record_date->format('Y');
                    
                    if ($record_year == $current_year && $current_year_mileage === null) {
                        $current_year_mileage = $record['Mileage'];
                    }
                    if ($record_year == $last_year) {
                        $last_year_mileage = $record['Mileage'];
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        
        // Вычисляем пробег за последний год
        $mileage_last_year = '0 miles';
        if ($current_year_mileage !== null && $last_year_mileage !== null) {
            $yearly_mileage = $current_year_mileage - $last_year_mileage;
            if ($yearly_mileage > 0) {
                $mileage_last_year = number_format($yearly_mileage) . ' miles';
            }
        }
        
        // Получаем среднегодовой пробег из API
        $average_mileage = 'N/A';
        if (!empty($mileage_details['CalculatedAverageAnnualMileage'])) {
            $average_mileage = number_format($mileage_details['CalculatedAverageAnnualMileage']) . ' p/year';
        }
        
        // Анализируем аномалии пробега
        $mileage_anomaly_detected = !empty($mileage_details['MileageAnomalyDetected']) ? $mileage_details['MileageAnomalyDetected'] : false;
        $mileage_issue_description = '';
        $has_mileage_issues = false;
        
        if ($mileage_anomaly_detected && count($mileage_list) >= 2) {
            // Сортируем записи по дате для анализа
            usort($mileage_list, function($a, $b) {
                try {
                    $date_a = new \DateTime($a['DateRecorded']);
                    $date_b = new \DateTime($b['DateRecorded']);
                    return $date_a <=> $date_b;
                } catch (\Exception $e) {
                    return 0;
                }
            });
            
            // Ищем случаи уменьшения пробега
            for ($i = 1; $i < count($mileage_list); $i++) {
                $prev_record = $mileage_list[$i - 1];
                $curr_record = $mileage_list[$i];
                
                if ($curr_record['Mileage'] < $prev_record['Mileage']) {
                    $mileage_reduction = $prev_record['Mileage'] - $curr_record['Mileage'];
                    $prev_date = $this->format_date($prev_record['DateRecorded']);
                    $curr_date = $this->format_date($curr_record['DateRecorded']);
                    
                    $mileage_issue_description = "The odometer reading reduced by " . number_format($mileage_reduction) . " miles between " . $prev_date . " and " . $curr_date . ".";
                    $has_mileage_issues = true;
                    break; // Берем первый найденный случай
                }
            }
        }
        
        // Форматируем результат
        if ($latest_mileage_record !== null) {
            $mileage = number_format($latest_mileage_record['Mileage']);
            $data_source = $latest_mileage_record['DataSource'] ?? '';
            $date_recorded = $this->format_date($latest_mileage_record['DateRecorded']);
            
            return array(
                'estimated_mileage' => $mileage . ' miles',
                'current_mileage' => $mileage . ' miles',
                'mileage_date' => $date_recorded,
                'mileage_source' => $data_source,
                'mileage_last_year' => $mileage_last_year,
                'average_mileage' => $average_mileage,
                'has_mileage_issues' => $has_mileage_issues,
                'mileage_issue_description' => $mileage_issue_description
            );
        }
        
        return array(
            'estimated_mileage' => 'N/A',
            'current_mileage' => 'N/A',
            'mileage_last_year' => '0 miles',
            'average_mileage' => $average_mileage,
            'has_mileage_issues' => $has_mileage_issues,
            'mileage_issue_description' => $mileage_issue_description
        );
    }
    
    /**
     * Форматирует объем двигателя
     */
    private function format_engine_capacity($capacity) {
        if (empty($capacity) || $capacity === 'N/A') {
            return 'N/A';
        }
        
        // Если это уже строка с единицами измерения, возвращаем как есть
        if (is_string($capacity) && (strpos($capacity, 'cc') !== false || strpos($capacity, 'L') !== false)) {
            return $capacity;
        }
        
        // Если это число, добавляем единицы измерения
        if (is_numeric($capacity)) {
            return $capacity . ' cc';
        }
        
        return $capacity;
    }
    
    /**
     * Форматирует вес
     */
    private function format_weight($weight) {
        if (empty($weight) || $weight === 'N/A') {
            return 'N/A';
        }
        
        // Если это уже строка с единицами измерения, возвращаем как есть
        if (is_string($weight) && (strpos($weight, 'kg') !== false || strpos($weight, 'Kg') !== false)) {
            return $weight;
        }
        
        // Если это число, добавляем единицы измерения
        if (is_numeric($weight)) {
            return $weight . ' kg';
        }
        
        return $weight;
    }
    
    /**
     * Форматирует дату в читаемый формат
     */
    private function format_date($date_string) {
        if (empty($date_string)) {
            return 'N/A';
        }
        
        try {
            $date = new \DateTime($date_string);
            return $date->format('d M Y');
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
    
    /**
     * Извлекает год из даты
     */
    private function extract_year($date_string) {
        if (empty($date_string)) {
            return 'N/A';
        }
        
        try {
            $date = new \DateTime($date_string);
            return $date->format('Y');
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
    
    /**
     * Вычисляет возраст автомобиля
     */
    private function calculate_vehicle_age($date_string) {
        if (empty($date_string)) {
            return '';
        }
        
        try {
            $date = new \DateTime($date_string);
            $now = new \DateTime();
            $diff = $now->diff($date);
            return $diff->y . ' years ' . $diff->m . ' months old';
        } catch (\Exception $e) {
            return '';
        }
    }
    
    /**
     * Вычисляет текст возраста (для V5C)
     */
    private function calculate_age_text($date_string) {
        if (empty($date_string)) {
            return '';
        }
        
        try {
            $date = new \DateTime($date_string);
            $now = new \DateTime();
            $diff = $now->diff($date);
            return $diff->y . ' years ' . $diff->m . ' months ago';
        } catch (\Exception $e) {
            return '';
        }
    }
    
    /**
     * Вычисляет текст о сроке действия (MOT/Tax)
     */
    private function calculate_due_text($date_string, $type) {
        if (empty($date_string)) {
            return $type . ' information not available';
        }
        
        try {
            $date = new \DateTime($date_string);
            $now = new \DateTime();
            $diff = $now->diff($date);
            
            if ($date > $now) {
                if ($type === 'Tax') {
                    return $type . ' due in ' . $diff->days . ' days';
                } else {
                    return $type . ' due in ' . $diff->m . ' months';
                }
            } else {
                if ($type === 'Tax') {
                    return $type . ' overdue by ' . $diff->days . ' days';
                } else {
                    return $type . ' overdue by ' . $diff->m . ' months';
                }
            }
        } catch (\Exception $e) {
            return $type . ' information not available';
        }
    }
    
    /**
     * Форматирует данные о выбросах CO2
     */
    private function format_co2_emissions($co2_value) {
        if (empty($co2_value)) {
            return 'N/A';
        }
        
        return $co2_value . ' g/km';
    }
}