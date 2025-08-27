<?php
/**
 * MOT History API Client
 *
 * Класс для работы с API MOT History Details от Vehicle Data Global
 *
 * @package VRM_Check_Plugin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Подключаем класс Logger
require_once dirname(__FILE__) . '/../class-logger.php';

/**
 * Класс для работы с MOT History API
 */
class VRM_Check_MOT_History_API_Client {
    
    /**
     * Базовый URL API
     *
     * @var string
     */
    private $api_base_url = 'https://uk.api.vehicledataglobal.com/r2/lookup';
    
    /**
     * API ключ
     *
     * @var string
     */
    private $api_key;
    
    /**
     * Логгер
     *
     * @var \VrmCheckPlugin\Logger
     */
    private $logger;
    
    /**
     * Конструктор
     *
     * @param string $api_key API ключ
     */
    public function __construct($api_key = '') {
        $this->api_key = !empty($api_key) ? $api_key : 'AAEF08BA-E98B-42A0-BB63-FEE0492243A7';
        $this->logger = \VrmCheckPlugin\Logger::get_instance();
    }
    
    /**
     * Получить данные MOT History для VRM
     *
     * @param string $vrm Регистрационный номер автомобиля
     * @return array|WP_Error Обработанные данные или ошибка
     */
    public function get_mot_history($vrm) {
        if (empty($vrm)) {
            return new WP_Error('invalid_vrm', 'VRM не может быть пустым');
        }
        
        // Очищаем VRM от лишних символов
        $vrm = sanitize_text_field(strtoupper(trim($vrm)));
        
        $this->logger->log('Запрос MOT History для VRM: ' . $vrm);
        
        // Выполняем запрос к API
        $response = $this->make_api_request($vrm);
        
        if (is_wp_error($response)) {
            $this->logger->log('Ошибка API запроса: ' . $response->get_error_message(), 'error');
            return $response;
        }
        
        // Обрабатываем ответ
        $processed_data = $this->process_api_response($response, $vrm);
        
        if (is_wp_error($processed_data)) {
            $this->logger->log('Ошибка обработки данных: ' . $processed_data->get_error_message(), 'error');
            return $processed_data;
        }
        
        $this->logger->log('MOT History данные успешно получены для VRM: ' . $vrm);
        
        return $processed_data;
    }
    
    /**
     * Выполнить запрос к API
     *
     * @param string $vrm Регистрационный номер
     * @return array|WP_Error Ответ API или ошибка
     */
    private function make_api_request($vrm) {
        $url = add_query_arg([
            'packagename' => 'MotHistoryDetails',
            'apikey' => $this->api_key,
            'vrm' => $vrm
        ], $this->api_base_url);
        
        $args = [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'VRM-Check-Plugin/1.0'
            ]
        ];
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_request_failed', 'Не удалось выполнить запрос к API: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'API вернул код ошибки: ' . $response_code);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_decode_error', 'Ошибка декодирования JSON: ' . json_last_error_msg());
        }
        
        // Проверяем статус ответа API
        if (!isset($data['ResponseInformation']['IsSuccessStatusCode']) || !$data['ResponseInformation']['IsSuccessStatusCode']) {
            $error_message = isset($data['ResponseInformation']['StatusMessage']) ? $data['ResponseInformation']['StatusMessage'] : 'Неизвестная ошибка API';
            return new WP_Error('api_response_error', 'API ошибка: ' . $error_message);
        }
        
        return $data;
    }
    
    /**
     * Обработать ответ API
     *
     * @param array $api_response Ответ от API
     * @param string $vrm Регистрационный номер
     * @return array Обработанные данные
     */
    private function process_api_response($api_response, $vrm) {
        $processed_data = [
            'vrm' => $vrm,
            'request_info' => $api_response['RequestInformation'] ?? [],
            'response_info' => $api_response['ResponseInformation'] ?? [],
            'billing_info' => $api_response['BillingInformation'] ?? [],
            'mot_history' => [],
            'vehicle_info' => [],
            'latest_mot' => [],
            'mot_statistics' => []
        ];
        
        // Извлекаем данные MOT History
        if (isset($api_response['Results']['MotHistoryDetails'])) {
            $mot_data = $api_response['Results']['MotHistoryDetails'];
            
            // Основная информация о автомобиле
            $processed_data['vehicle_info'] = [
                'vrm' => $mot_data['Vrm'] ?? $vrm,
                'make' => $mot_data['Make'] ?? '',
                'model' => $mot_data['Model'] ?? '',
                'fuel_type' => $mot_data['FuelType'] ?? '',
                'colour' => $mot_data['Colour'] ?? '',
                'first_used_date' => $mot_data['FirstUsedDate'] ?? '',
                'update_timestamp' => $mot_data['UpdateTimeStamp'] ?? ''
            ];
            
            // Информация о последнем MOT
            $processed_data['latest_mot'] = [
                'latest_test_date' => $mot_data['LatestTestDate'] ?? '',
                'mot_due_date' => $mot_data['MotDueDate'] ?? '',
                'days_since_last_mot' => $mot_data['DaysSinceLastMot'] ?? 0
            ];
            
            // История MOT тестов
            if (isset($mot_data['MotTestDetailsList']) && is_array($mot_data['MotTestDetailsList'])) {
                $processed_data['mot_history'] = $this->process_mot_test_details($mot_data['MotTestDetailsList']);
            }
            
            // Статистика MOT
            $processed_data['mot_statistics'] = $this->calculate_mot_statistics($mot_data['MotTestDetailsList'] ?? []);
        }
        
        // Извлекаем Vehicle Codes если есть
        if (isset($api_response['Results']['VehicleCodes'])) {
            $processed_data['vehicle_codes'] = $api_response['Results']['VehicleCodes'];
        }
        
        return $processed_data;
    }
    
    /**
     * Обработать детали MOT тестов
     *
     * @param array $mot_tests Массив MOT тестов
     * @return array Обработанные данные тестов
     */
    private function process_mot_test_details($mot_tests) {
        $processed_tests = [];
        
        foreach ($mot_tests as $test) {
            $processed_test = [
                'test_date' => $test['TestDate'] ?? '',
                'test_passed' => $test['TestPassed'] ?? false,
                'expiry_date' => $test['ExpiryDate'] ?? '',
                'odometer_reading' => $test['OdometerReading'] ?? '',
                'odometer_unit' => $test['OdometerUnit'] ?? '',
                'odometer_result_type' => $test['OdometerResultType'] ?? '',
                'test_number' => $test['TestNumber'] ?? '',
                'days_since_last_test' => $test['DaysSinceLastTest'] ?? 0,
                'days_since_last_pass' => $test['DaysSinceLastPass'] ?? 0,
                'days_out_of_mot' => $test['DaysOutOfMot'] ?? 0,
                'is_retest' => $test['IsRetest'] ?? false,
                'annotations' => []
            ];
            
            // Обрабатываем аннотации (дефекты, предупреждения)
            if (isset($test['AnnotationList']) && is_array($test['AnnotationList'])) {
                foreach ($test['AnnotationList'] as $annotation) {
                    $processed_test['annotations'][] = [
                        'type' => $annotation['Type'] ?? '',
                        'text' => $annotation['Text'] ?? '',
                        'is_dangerous' => $annotation['IsDangerous'] ?? false
                    ];
                }
            }
            
            $processed_tests[] = $processed_test;
        }
        
        return $processed_tests;
    }
    
    /**
     * Вычислить статистику MOT
     *
     * @param array $mot_tests Массив MOT тестов
     * @return array Статистика
     */
    private function calculate_mot_statistics($mot_tests) {
        $stats = [
            'total_tests' => count($mot_tests),
            'passed_tests' => 0,
            'failed_tests' => 0,
            'retests' => 0,
            'total_advisories' => 0,
            'total_failures' => 0,
            'dangerous_defects' => 0,
            'average_mileage_per_year' => 0,
            'mileage_issues' => []
        ];
        
        $mileage_readings = [];
        $previous_mileage = null;
        
        foreach ($mot_tests as $test) {
            // Подсчет пройденных/провалленных тестов
            if (isset($test['TestPassed'])) {
                if ($test['TestPassed']) {
                    $stats['passed_tests']++;
                } else {
                    $stats['failed_tests']++;
                }
            }
            
            // Подсчет ретестов
            if (isset($test['IsRetest']) && $test['IsRetest']) {
                $stats['retests']++;
            }
            
            // Анализ аннотаций
            if (isset($test['AnnotationList']) && is_array($test['AnnotationList'])) {
                foreach ($test['AnnotationList'] as $annotation) {
                    $type = strtoupper($annotation['Type'] ?? '');
                    
                    if ($type === 'ADVISORY') {
                        $stats['total_advisories']++;
                    } elseif (in_array($type, ['FAIL', 'MAJOR', 'MINOR'])) {
                        $stats['total_failures']++;
                    }
                    
                    if (isset($annotation['IsDangerous']) && $annotation['IsDangerous']) {
                        $stats['dangerous_defects']++;
                    }
                }
            }
            
            // Анализ пробега
            if (isset($test['OdometerReading']) && is_numeric($test['OdometerReading'])) {
                $current_mileage = (int) $test['OdometerReading'];
                $mileage_readings[] = $current_mileage;
                
                // Проверка на уменьшение пробега
                if ($previous_mileage !== null && $current_mileage < $previous_mileage) {
                    $stats['mileage_issues'][] = [
                        'type' => 'reduction',
                        'previous' => $previous_mileage,
                        'current' => $current_mileage,
                        'difference' => $previous_mileage - $current_mileage,
                        'test_date' => $test['TestDate'] ?? ''
                    ];
                }
                
                $previous_mileage = $current_mileage;
            }
        }
        
        // Вычисление среднего пробега в год
        if (count($mileage_readings) >= 2) {
            $first_reading = end($mileage_readings);
            $last_reading = reset($mileage_readings);
            $first_date = end($mot_tests)['TestDate'] ?? '';
            $last_date = reset($mot_tests)['TestDate'] ?? '';
            
            if (!empty($first_date) && !empty($last_date)) {
                try {
                    $first_datetime = new DateTime($first_date);
                    $last_datetime = new DateTime($last_date);
                    $years_diff = $last_datetime->diff($first_datetime)->days / 365.25;
                    
                    if ($years_diff > 0) {
                        $stats['average_mileage_per_year'] = round(($last_reading - $first_reading) / $years_diff);
                    }
                } catch (Exception $e) {
                    // Игнорируем ошибки парсинга дат
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Получить данные для шаблона premium-results
     *
     * @param string $vrm Регистрационный номер
     * @return array Данные для шаблона
     */
    public function get_template_data($vrm) {
        $mot_data = $this->get_mot_history($vrm);
        
        if (is_wp_error($mot_data)) {
            return [
                'error' => true,
                'error_message' => $mot_data->get_error_message(),
                'vrm' => $vrm
            ];
        }
        
        // Формируем данные для шаблона в формате, совместимом с premium-results-template.php
        $template_data = [
            'vrm' => $vrm,
            'year' => '',
            'image' => 'default-car.png', // Изображение по умолчанию
            'vehicle_image_url' => '',
            
            // Данные автомобиля
            'VehicleDetails' => [
                'VehicleIdentification' => [
                    'DvlaMake' => $mot_data['vehicle_info']['make'] ?? '',
                    'DvlaModel' => $mot_data['vehicle_info']['model'] ?? '',
                    'Colour' => $mot_data['vehicle_info']['colour'] ?? '',
                    'FuelType' => $mot_data['vehicle_info']['fuel_type'] ?? ''
                ]
            ],
            
            // MOT данные
            'MotHistory' => $mot_data['mot_history'] ?? [],
            'LatestMot' => $mot_data['latest_mot'] ?? [],
            'MotStatistics' => $mot_data['mot_statistics'] ?? [],
            
            // Статус автомобиля (заглушки для совместимости)
            'VehicleStatus' => [
                'IsImported' => false,
                'IsExported' => false,
                'IsScrapped' => false,
                'IsUnscrapped' => false,
                'CertificateOfDestructionIssued' => false,
                'DateScrapped' => null
            ],
            
            // История автомобиля (заглушки для совместимости)
            'VehicleHistory' => [
                'PreviousKeepers' => [],
                'PlateChanges' => [],
                'ColourChanges' => []
            ]
        ];
        
        // Извлекаем год из даты первого использования
        if (!empty($mot_data['vehicle_info']['first_used_date'])) {
            try {
                $first_used = new DateTime($mot_data['vehicle_info']['first_used_date']);
                $template_data['year'] = $first_used->format('Y');
            } catch (Exception $e) {
                // Игнорируем ошибки парсинга даты
            }
        }
        
        return $template_data;
    }
}