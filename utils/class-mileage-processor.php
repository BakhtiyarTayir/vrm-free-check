<?php

/**
 * Class MileageProcessor
 * 
 * Обрабатывает данные пробега из MileageCheckDetails для отображения в блоках
 * Mileage Information и Advanced Mileage History
 */
class MileageProcessor {
    
    private $mileage_data;
    
    /**
     * Конструктор
     * 
     * @param array $data Массив данных из API
     */
    public function __construct($data) {
        $this->mileage_data = isset($data['MileageCheckDetails']) ? $data['MileageCheckDetails'] : [];
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
        
        if (empty($this->mileage_data['MileageResultList'])) {
            return $history;
        }
        
        foreach ($this->mileage_data['MileageResultList'] as $record) {
            $formatted_record = [
                'date' => $this->formatDate($record['DateRecorded'] ?? ''),
                'formatted_date' => $this->formatDate($record['DateRecorded'] ?? ''),
                'mileage' => isset($record['Mileage']) ? number_format($record['Mileage']) : 'N/A',
                'formatted_mileage' => isset($record['Mileage']) ? number_format($record['Mileage']) : 'N/A',
                'source' => $record['DataSource'] ?? 'Unknown',
                'in_sequence' => $record['InSequence'] ?? true,
                'is_anomaly' => !($record['InSequence'] ?? true),
                'raw_mileage' => $record['Mileage'] ?? 0
            ];
            
            // Добавляем информацию об аномалии
            if (!$formatted_record['in_sequence']) {
                $formatted_record['anomaly_info'] = $this->calculateAnomalyInfo($record, $history);
            }
            
            $history[] = $formatted_record;
        }
        
        // Сортируем по дате (новые записи сверху)
        usort($history, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
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
        
        if (empty($this->mileage_data['MileageResultList'])) {
            return $chart_data;
        }
        
        $sorted_records = $this->mileage_data['MileageResultList'];
        
        // Сортируем по дате
        usort($sorted_records, function($a, $b) {
            return strtotime($a['DateRecorded']) - strtotime($b['DateRecorded']);
        });
        
        foreach ($sorted_records as $record) {
            $chart_data['labels'][] = $this->formatDateForChart($record['DateRecorded'] ?? '');
            $chart_data['mileage'][] = $record['Mileage'] ?? 0;
            $chart_data['anomalies'][] = !($record['InSequence'] ?? true);
        }
        
        return $chart_data;
    }
    
    /**
     * Проверить наличие данных о пробеге
     * 
     * @return bool
     */
    public function hasMileageData() {
        return !empty($this->mileage_data['MileageResultList']);
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
}