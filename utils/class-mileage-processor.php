<?php

/**
 * Class MileageProcessor
 * 
 * Обрабатывает данные пробега из MotHistoryDetails для отображения в блоках
 * Mileage Information и Advanced Mileage History
 */
class MileageProcessor {
    
    private $mileage_data;
    private $mot_history;
    
    /**
     * Конструктор
     * 
     * @param array $data Массив данных из API
     */
    public function __construct($data) {
        // Используем данные из MotHistoryDetails вместо MileageCheckDetails
        $this->mot_history = isset($data['MotHistoryDetails']) ? $data['MotHistoryDetails'] : [];
        $this->mileage_data = $this->convertMotDataToMileageFormat();
    }
    
    /**
     * Получить информацию о пробеге для блока Mileage Information
     * 
     * @return array
     */
    public function getMileageInformation() {
        $info = [
            'has_anomaly' => false,
            'anomaly_detected' => 'No',
            'anomaly_status' => 'No anomalies detected',
            'average_annual_mileage' => 'N/A',
            'average_for_age' => 'N/A',
            'mileage_status' => 'Normal'
        ];
        
        if (empty($this->mileage_data)) {
            return $info;
        }
        
        // Проверка аномалий пробега
        if (isset($this->mileage_data['MileageAnomalyDetected'])) {
            $info['has_anomaly'] = $this->mileage_data['MileageAnomalyDetected'];
            $info['anomaly_detected'] = $this->mileage_data['MileageAnomalyDetected'] ? 'Yes' : 'No';
            $info['anomaly_status'] = $this->mileage_data['MileageAnomalyDetected'] ? 'Anomalies detected' : 'No anomalies detected';
        }
        
        // Средний годовой пробег
        if (isset($this->mileage_data['CalculatedAverageAnnualMileage'])) {
            $info['average_annual_mileage'] = number_format($this->mileage_data['CalculatedAverageAnnualMileage']) . ' miles/year';
        }
        
        // Средний пробег для возраста
        if (isset($this->mileage_data['AverageMileageForAge'])) {
            $info['average_for_age'] = number_format($this->mileage_data['AverageMileageForAge']) . ' miles';
        }
        
        // Определение статуса пробега
        $info['mileage_status'] = $this->calculateMileageStatus();
        
        return $info;
    }
    
    /**
     * Получить историю пробега для таблицы
     * 
     * @return array
     */
    public function getMileageHistory() {
        $history = [];
        
        if (empty($this->mot_history['MotTestDetailsList'])) {
            return $history;
        }
        
        foreach ($this->mot_history['MotTestDetailsList'] as $record) {
            // Пропускаем записи без показаний одометра
            if (empty($record['OdometerReading']) || $record['OdometerReading'] === '0') {
                continue;
            }
            
            $formatted_record = [
                'date' => $this->formatDate($record['TestDate'] ?? ''),
                'formatted_date' => $this->formatDate($record['TestDate'] ?? ''),
                'mileage' => number_format($record['OdometerReading']),
                'formatted_mileage' => number_format($record['OdometerReading']),
                'source' => 'MOT Test',
                'in_sequence' => true, // Будем вычислять позже
                'is_anomaly' => false,
                'raw_mileage' => (int)$record['OdometerReading'],
                'test_passed' => $record['TestPassed'] ?? false,
                'test_result' => $record['TestPassed'] ? 'Pass' : 'Fail'
            ];
            
            $history[] = $formatted_record;
        }
        
        // Сортируем по дате (новые записи сверху)
        usort($history, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Проверяем аномалии пробега
        $this->detectMileageAnomalies($history);
        
        return $history;
    }
    
    /**
     * Получить данные для графика пробега
     * 
     * @return array
     */
    public function getChartData() {
        $chart_data = [
            'labels' => [],
            'mileage' => [],
            'anomalies' => []
        ];
        
        if (empty($this->mot_history['MotTestDetailsList'])) {
            return $chart_data;
        }
        
        $sorted_records = [];
        
        // Фильтруем записи с показаниями одометра
        foreach ($this->mot_history['MotTestDetailsList'] as $record) {
            if (!empty($record['OdometerReading']) && $record['OdometerReading'] !== '0') {
                $sorted_records[] = $record;
            }
        }
        
        // Сортируем по дате
        usort($sorted_records, function($a, $b) {
            return strtotime($a['TestDate']) - strtotime($b['TestDate']);
        });
        
        // Обнаруживаем аномалии
        $anomalies = $this->detectChartAnomalies($sorted_records);
        
        foreach ($sorted_records as $index => $record) {
            $chart_data['labels'][] = $this->formatDateForChart($record['TestDate'] ?? '');
            $chart_data['mileage'][] = (int)$record['OdometerReading'];
            $chart_data['anomalies'][] = $anomalies[$index] ?? false;
        }
        
        return $chart_data;
    }
    
    /**
     * Проверить наличие данных о пробеге
     * 
     * @return bool
     */
    public function hasMileageData() {
        if (empty($this->mot_history['MotTestDetailsList'])) {
            return false;
        }
        
        // Проверяем, есть ли записи с показаниями одометра
        foreach ($this->mot_history['MotTestDetailsList'] as $record) {
            if (!empty($record['OdometerReading']) && $record['OdometerReading'] !== '0') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Получить общую статистику
     * 
     * @return array
     */
    public function getStatistics() {
        $stats = [
            'total_records' => 0,
            'anomalies_count' => 0,
            'latest_mileage' => 'N/A',
            'first_record_date' => 'N/A',
            'latest_record_date' => 'N/A'
        ];
        
        if (empty($this->mileage_data['MileageResultList'])) {
            return $stats;
        }
        
        $records = $this->mileage_data['MileageResultList'];
        $stats['total_records'] = count($records);
        
        // Подсчет аномалий
        $stats['anomalies_count'] = count(array_filter($records, function($record) {
            return !($record['InSequence'] ?? true);
        }));
        
        // Сортируем по дате для получения первой и последней записи
        usort($records, function($a, $b) {
            return strtotime($a['DateRecorded']) - strtotime($b['DateRecorded']);
        });
        
        if (!empty($records)) {
            $stats['first_record_date'] = $this->formatDate($records[0]['DateRecorded']);
            $stats['latest_record_date'] = $this->formatDate(end($records)['DateRecorded']);
            $stats['latest_mileage'] = number_format(end($records)['Mileage']) . ' miles';
        }
        
        return $stats;
    }
    
    /**
     * Форматировать дату
     * 
     * @param string $date_string
     * @return string
     */
    private function formatDate($date_string) {
        if (empty($date_string)) {
            return 'N/A';
        }
        
        $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $date_string);
        if ($date) {
            return $date->format('d/m/Y');
        }
        
        return 'N/A';
    }
    
    /**
     * Форматировать дату для графика
     * 
     * @param string $date_string
     * @return string
     */
    private function formatDateForChart($date_string) {
        if (empty($date_string)) {
            return '';
        }
        
        $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $date_string);
        if ($date) {
            return $date->format('M Y');
        }
        
        return '';
    }
    
    /**
     * Вычислить статус пробега
     * 
     * @return string
     */
    private function calculateMileageStatus() {
        if (empty($this->mileage_data)) {
            return 'Unknown';
        }
        
        $has_anomaly = $this->mileage_data['MileageAnomalyDetected'] ?? false;
        
        if ($has_anomaly) {
            return 'Anomaly Detected';
        }
        
        $average_annual = $this->mileage_data['CalculatedAverageAnnualMileage'] ?? 0;
        
        if ($average_annual > 20000) {
            return 'High Mileage';
        } elseif ($average_annual < 5000) {
            return 'Low Mileage';
        } else {
            return 'Average Mileage';
        }
    }
    
    /**
     * Вычислить информацию об аномалии
     * 
     * @param array $current_record
     * @param array $previous_records
     * @return string
     */
    private function calculateAnomalyInfo($current_record, $previous_records) {
        if (empty($previous_records)) {
            return 'Anomaly';
        }
        
        // Находим предыдущую запись по дате
        $current_date = strtotime($current_record['DateRecorded']);
        $previous_record = null;
        
        foreach ($previous_records as $record) {
            $record_date = strtotime($record['date']);
            if ($record_date < $current_date) {
                if (!$previous_record || strtotime($previous_record['date']) < $record_date) {
                    $previous_record = $record;
                }
            }
        }
        
        if ($previous_record) {
            $difference = $current_record['Mileage'] - $previous_record['raw_mileage'];
            if ($difference < 0) {
                return 'Reduced ' . number_format(abs($difference));
            }
        }
        
        return 'Anomaly';
    }
    
    /**
     * Конвертировать данные MOT в формат данных пробега
     * 
     * @return array
     */
    private function convertMotDataToMileageFormat() {
        $converted_data = [
            'MileageAnomalyDetected' => false,
            'CalculatedAverageAnnualMileage' => 0,
            'AverageMileageForAge' => 0,
            'MileageResultList' => []
        ];
        
        if (empty($this->mot_history['MotTestDetailsList'])) {
            return $converted_data;
        }
        
        $valid_records = [];
        
        // Фильтруем и конвертируем записи
        foreach ($this->mot_history['MotTestDetailsList'] as $record) {
            if (!empty($record['OdometerReading']) && $record['OdometerReading'] !== '0') {
                $valid_records[] = [
                    'DateRecorded' => $record['TestDate'],
                    'Mileage' => (int)$record['OdometerReading'],
                    'DataSource' => 'MOT Test',
                    'InSequence' => true
                ];
            }
        }
        
        // Сортируем по дате
        usort($valid_records, function($a, $b) {
            return strtotime($a['DateRecorded']) - strtotime($b['DateRecorded']);
        });
        
        $converted_data['MileageResultList'] = $valid_records;
        
        // Вычисляем средний годовой пробег
        if (count($valid_records) >= 2) {
            $first_record = reset($valid_records);
            $last_record = end($valid_records);
            
            $years_diff = (strtotime($last_record['DateRecorded']) - strtotime($first_record['DateRecorded'])) / (365.25 * 24 * 3600);
            $mileage_diff = $last_record['Mileage'] - $first_record['Mileage'];
            
            if ($years_diff > 0) {
                $converted_data['CalculatedAverageAnnualMileage'] = $mileage_diff / $years_diff;
            }
        }
        
        // Обнаруживаем аномалии
        $converted_data['MileageAnomalyDetected'] = $this->detectAnomaliesInRecords($valid_records);
        
        return $converted_data;
    }
    
    /**
     * Обнаружить аномалии пробега в истории
     * 
     * @param array $history
     */
    private function detectMileageAnomalies(&$history) {
        for ($i = 1; $i < count($history); $i++) {
            $current = &$history[$i];
            $previous = $history[$i - 1];
            
            // Проверяем, уменьшился ли пробег
            if ($current['raw_mileage'] > $previous['raw_mileage']) {
                $current['is_anomaly'] = true;
                $current['in_sequence'] = false;
                $current['anomaly_info'] = 'Mileage decreased by ' . number_format($current['raw_mileage'] - $previous['raw_mileage']);
            }
        }
    }
    
    /**
     * Обнаружить аномалии для графика
     * 
     * @param array $records
     * @return array
     */
    private function detectChartAnomalies($records) {
        $anomalies = [];
        
        for ($i = 0; $i < count($records); $i++) {
            $anomalies[$i] = false;
            
            if ($i > 0) {
                $current_mileage = (int)$records[$i]['OdometerReading'];
                $previous_mileage = (int)$records[$i - 1]['OdometerReading'];
                
                // Аномалия если пробег уменьшился
                if ($current_mileage < $previous_mileage) {
                    $anomalies[$i] = true;
                }
            }
        }
        
        return $anomalies;
    }
    
    /**
     * Обнаружить аномалии в записях
     * 
     * @param array $records
     * @return bool
     */
    private function detectAnomaliesInRecords($records) {
        for ($i = 1; $i < count($records); $i++) {
            if ($records[$i]['Mileage'] < $records[$i - 1]['Mileage']) {
                return true;
            }
        }
        
        return false;
    }
}