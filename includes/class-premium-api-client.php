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
            if (empty($vrm)) {
                $logger->log('error', 'Пустой VRM передан в get_vehicle_data_with_image');
                return false;
            }
            
            $logger->log('info', 'Запрос к премиум API для VRM: ' . $vrm);
            
            // Подготавливаем параметры запроса
            $params = array(
                'packagename' => 'VehicleDetailsWithImage',
                'apikey' => $this->api_key,
                'vrm' => $vrm
            );
            
            $url = $this->api_url . '?' . http_build_query($params);
            
            $logger->log('info', 'Отправка запроса к премиум API: ' . $url);
            
            // Выполняем запрос
            $response = wp_remote_get($url, array(
                'timeout' => 25, // Уменьшаем общий таймаут
                'headers' => array(
                    'User-Agent' => 'VRM Check Plugin/1.0'
                )
            ));
            
            if (is_wp_error($response)) {
                $logger->log('error', 'Ошибка запроса к премиум API: ' . $response->get_error_message());
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                $logger->log('error', 'Премиум API вернул HTTP код: ' . $response_code);
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                $logger->log('error', 'Пустой ответ от премиум API');
                return false;
            }
            
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $logger->log('error', 'Ошибка декодирования JSON ответа премиум API: ' . json_last_error_msg());
                return false;
            }
            
            $logger->log('info', 'Получен ответ от премиум API: ' . substr($body, 0, 500) . '...');
            
            // Проверяем успешность ответа
            if (!isset($data['ResponseInformation']['IsSuccessStatusCode']) || !$data['ResponseInformation']['IsSuccessStatusCode']) {
                $logger->log('error', 'Премиум API вернул ошибку: ' . ($data['ResponseInformation']['StatusMessage'] ?? 'Unknown error'));
                return false;
            }
            
            // Проверяем наличие основных данных
            if (!isset($data['Results']) || empty($data['Results'])) {
                $logger->log('error', 'Премиум API вернул пустые результаты');
                return false;
            }
            
            // Обрабатываем и возвращаем данные
            return $this->process_api_response($data);
            
        } catch (\Exception $e) {
            $logger->log_exception($e, array('vrm' => $vrm, 'context' => 'premium_api_request'));
            return false;
        } catch (\Error $e) {
            $logger->log_exception($e, array('vrm' => $vrm, 'context' => 'premium_api_fatal_error'));
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
        $logger = $this->get_logger();
        
        try {
            $vehicle_details = $api_data['Results']['VehicleDetails'] ?? array();
            $model_details = $api_data['Results']['ModelDetails'] ?? array();
            $image_details = $api_data['Results']['VehicleImageDetails'] ?? array();
            
            // Получаем данные о статусе автомобиля и истории
            $vehicle_status = $vehicle_details['VehicleStatus'] ?? array();
            $vehicle_history = $vehicle_details['VehicleHistory'] ?? array();
            
            // Получаем данные о пробеге и финансах с обработкой ошибок
            $mileage_finance_data = array();
            $vrm = $vehicle_details['VehicleIdentification']['Vrm'] ?? '';
            
            if (!empty($vrm)) {
                $logger->log('info', 'Запрос данных о пробеге и финансах для VRM: ' . $vrm);
                $mileage_finance_data = $this->get_mileage_finance_data($vrm);
            } else {
                $logger->log('info', 'VRM не найден в данных автомобиля, пропускаем запрос пробега/финансов');
            }
        
        // Получаем данные для проверок
        $mileage_check_details = $mileage_finance_data['MileageCheckDetails'] ?? array();
        $finance_details = $mileage_finance_data['FinanceDetails'] ?? array();
        
        // Инициализируем массив premium_checks
        $premium_checks = array(
            'imported' => array(
                'status' => isset($vehicle_status['IsImported']) && $vehicle_status['IsImported'] ? 'warning' : 'pass',
                'message' => isset($vehicle_status['IsImported']) && $vehicle_status['IsImported'] ? 'Vehicle has been imported' : 'Vehicle has not been imported'
            ),
            'exported' => array(
                'status' => isset($vehicle_status['IsExported']) && $vehicle_status['IsExported'] ? 'warning' : 'pass',
                'message' => isset($vehicle_status['IsExported']) && $vehicle_status['IsExported'] ? 'Vehicle has been exported' : 'Vehicle has not been exported'
            ),
            'scrapped' => array(
                'status' => isset($vehicle_status['IsScrapped']) && $vehicle_status['IsScrapped'] ? 'fail' : 'pass',
                'message' => isset($vehicle_status['IsScrapped']) && $vehicle_status['IsScrapped'] ? 'Vehicle has been scrapped' : 'Vehicle has not been scrapped'
            ),
            'unscrapped' => array(
                'status' => isset($vehicle_status['IsUnscrapped']) && $vehicle_status['IsUnscrapped'] ? 'pass' : 'fail',
                'message' => isset($vehicle_status['IsUnscrapped']) && $vehicle_status['IsUnscrapped'] ? 'Vehicle has been unscrapped' : 'Vehicle has not been unscrapped'
            ),
            'stolen' => array(
                'status' => isset($vehicle_status['IsStolen']) && $vehicle_status['IsStolen'] ? 'fail' : 'pass',
                'message' => isset($vehicle_status['IsStolen']) && $vehicle_status['IsStolen'] ? 'Vehicle has been reported stolen' : 'Vehicle has not been reported stolen'
            ),
            'written_off' => array(
                'status' => isset($vehicle_status['IsWrittenOff']) && $vehicle_status['IsWrittenOff'] ? 'fail' : 'pass',
                'message' => isset($vehicle_status['IsWrittenOff']) && $vehicle_status['IsWrittenOff'] ? 'Vehicle has been written off' : 'Vehicle has not been written off'
            ),
            'mileage_issues' => array(
                'status' => 'pass',
                'message' => 'No mileage issues detected'
            ),
            'outstanding_finance' => array(
                'status' => 'pass',
                'message' => 'No outstanding finance detected'
            )
        );

        // Update Mileage Issues based on mileage_check_details
        if (!empty($mileage_check_details) && isset($mileage_check_details['AnomalyDetected']) && $mileage_check_details['AnomalyDetected']) {
            $premium_checks['mileage_issues']['status'] = 'warning';
            $premium_checks['mileage_issues']['message'] = 'Mileage anomaly detected - please review mileage history';
        }
 
        // Update Outstanding Finance based on finance_details
        if (!empty($finance_details) && isset($finance_details['IsFinanced']) && $finance_details['IsFinanced']) {
            $premium_checks['outstanding_finance']['status'] = 'fail';
            $premium_checks['outstanding_finance']['message'] = 'Outstanding finance detected';
        }
        
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
            'VehicleHistory' => $vehicle_history,
            
            // Данные о пробеге и финансах
            'MileageCheckDetails' => $mileage_finance_data['MileageCheckDetails'] ?? array(),
            'FinanceDetails' => $mileage_finance_data['FinanceDetails'] ?? array(),
            'VehicleCodes' => $mileage_finance_data['VehicleCodes'] ?? array(),
            
            // Premium checks
            'premium_checks' => $premium_checks
        );
        
        return $processed_data;
        
        } catch (\Exception $e) {
            $logger->log_exception('Ошибка при обработке ответа премиум API', $e);
            
            // Возвращаем минимальные данные в случае ошибки
            return array(
                'vrm' => $api_data['Results']['VehicleDetails']['VehicleIdentification']['Vrm'] ?? '',
                'make' => $api_data['Results']['VehicleDetails']['VehicleIdentification']['DvlaMake'] ?? '',
                'model' => $api_data['Results']['VehicleDetails']['VehicleIdentification']['DvlaModel'] ?? '',
                'year' => $api_data['Results']['VehicleDetails']['VehicleIdentification']['YearOfManufacture'] ?? '',
                'colour' => $api_data['Results']['VehicleDetails']['VehicleHistory']['ColourDetails']['CurrentColour'] ?? '',
                'fuel_type' => $api_data['Results']['VehicleDetails']['VehicleIdentification']['DvlaFuelType'] ?? '',
                'body_type' => $api_data['Results']['VehicleDetails']['VehicleIdentification']['DvlaBodyType'] ?? '',
                'engine_size' => $api_data['Results']['VehicleDetails']['DvlaTechnicalDetails']['EngineCapacityCc'] ?? '',
                'seats' => $api_data['Results']['VehicleDetails']['DvlaTechnicalDetails']['NumberOfSeats'] ?? '',
                'vehicle_image_url' => '',
                'VehicleDetails' => $api_data['Results']['VehicleDetails'] ?? array(),
                'ModelDetails' => $api_data['Results']['ModelDetails'] ?? array(),
                'VehicleImageDetails' => array(),
                'VehicleStatus' => array(),
                'VehicleHistory' => array(),
                'MileageCheckDetails' => array(),
                'FinanceDetails' => array(),
                'VehicleCodes' => array(),
                'premium_checks' => array(
                    'imported' => array('status' => 'unknown', 'message' => 'Data unavailable'),
                    'exported' => array('status' => 'unknown', 'message' => 'Data unavailable'),
                    'scrapped' => array('status' => 'unknown', 'message' => 'Data unavailable'),
                    'unscrapped' => array('status' => 'unknown', 'message' => 'Data unavailable'),
                    'stolen' => array('status' => 'unknown', 'message' => 'Data unavailable'),
                    'written_off' => array('status' => 'unknown', 'message' => 'Data unavailable'),
                    'mileage_issues' => array('status' => 'unknown', 'message' => 'Data unavailable'),
                    'outstanding_finance' => array('status' => 'unknown', 'message' => 'Data unavailable')
                )
            );
        }
    }
    
    /**
     * Получает данные о пробеге и финансах из API
     */
    private function get_mileage_finance_data($vrm) {
        $logger = $this->get_logger();
        
        if (empty($vrm)) {
            $logger->log('error', 'Пустой VRM для запроса данных о пробеге и финансах');
            return array();
        }
        
        try {
            $api_key = 'AAEF08BA-E98B-42A0-BB63-FEE0492243A7';
            $url = 'https://uk.api.vehicledataglobal.com/r2/lookup?packagename=MileageFinanceDetails&apikey=' . $api_key . '&vrm=' . urlencode($vrm);
            
            $logger->log('info', 'Запрос данных о пробеге и финансах: ' . $url);
            
            $response = wp_remote_get($url, array(
                'timeout' => 15, // Уменьшаем таймаут для избежания долгих ожиданий
                'headers' => array(
                    'User-Agent' => 'VRM Check Plugin/1.0'
                )
            ));
            
            if (is_wp_error($response)) {
                $logger->log_error('Ошибка API пробега и финансов: ' . $response->get_error_message());
                return array();
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                $logger->log_error('API пробега и финансов вернул код: ' . $response_code);
                return array();
            }
            
            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                $logger->log_error('Пустой ответ от API пробега и финансов');
                return array();
            }
            
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $logger->log_error('Ошибка JSON в API пробега и финансов: ' . json_last_error_msg());
                return array();
            }
            
            // Проверяем успешность ответа API
            if (!isset($data['ResponseInformation']['IsSuccessStatusCode']) || !$data['ResponseInformation']['IsSuccessStatusCode']) {
                $logger->log_error('API пробега и финансов вернул ошибку: ' . ($data['ResponseInformation']['StatusMessage'] ?? 'Unknown error'));
                return array();
            }
            
            $logger->log('info', 'Успешно получены данные о пробеге и финансах');
            return $data['Results'] ?? array();
            
        } catch (\Exception $e) {
            $logger->log_exception('Исключение при запросе данных о пробеге и финансах', $e);
            return array();
        }
    }

}