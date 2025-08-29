<?php
if (!defined('ABSPATH')) {
    exit;
}

// Проверяем, что данные переданы
if (!isset($data) || empty($data)) {
    echo '<div class="vrm-check-error">';
    echo '<p>' . __('No vehicle data available.', 'vrm-check-plugin') . '</p>';
    echo '</div>';
    return;
}

// Данные готовы для отображения в шаблоне

// Функция для вычисления возраста
if (!function_exists('calculate_age')) {
    function calculate_age($year) {
        if (empty($year)) return '';
        $current_year = date('Y');
        $age_years = $current_year - $year;
        $age_months = date('n') - 1; // Примерно
        
        if ($age_months < 0) {
            $age_years--;
            $age_months += 12;
        }
        
        return $age_years . ' years ' . $age_months . ' months old';
    }
}

// Функция для вычисления времени с даты
if (!function_exists('calculate_time_ago')) {
    function calculate_time_ago($date_string) {
        if (empty($date_string)) return '';
        
        try {
            $date = new DateTime($date_string);
            $now = new DateTime();
            $diff = $now->diff($date);
            
            $years = $diff->y;
            $months = $diff->m;
            
            return $years . ' years ' . $months . ' months ago';
        } catch (Exception $e) {
            return '';
        }
    }
}
?>

<div class="vrm-check-premium-results">
    <!-- Main Report Content -->
    <div class="premium-report-content">
        <div class="vehicle-image-section">
            <?php 
            // Используем изображение из API, если доступно, иначе изображение по умолчанию
            $image_url = '';
            if (!empty($data['vehicle_image_url'])) {
                $image_url = esc_url($data['vehicle_image_url']);
            } else {
                $image_url = esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/images/' . esc_attr($data['image']));
            }
            ?>
            <img src="<?php echo $image_url; ?>" 
                 alt="Vehicle Image" 
                 class="vehicle-main-image" />
        </div>
        
        <div class="vehicle-info-section">
            
            <div class="vehicle-details">
                <h3 class="vehicle-title">Vehicle Make and Model</h3>
                <div class="vehicle-make"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['DvlaMake'] ?? 'N/A'); ?></div>
                <div class="vehicle-model"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['DvlaModel'] ?? 'N/A'); ?></div>
            </div>
        </div>
        
        <div class="vehicle-registration-section">
            <div class="registration-info">
                <h4>Year</h4>
                <div class="year-value"><?php echo esc_html($data['year']); ?></div>
                
                <h4>Vehicle Registration</h4>
                <div class="registration-value"><?php echo esc_html($data['vrm']); ?></div>
            </div>
        </div>
    </div>
    <!-- Report Summary Block -->
    <div class="report-summary-block">
        <div class="summary-header">
            <h3 class="summary-title title-gray">Report Summary</h3>
        </div>
        
        <div class="summary-content">
            <div class="summary-row">
                <div class="summary-label">Result</div>
                <div class="summary-value">
                    <?php 
                    // Определяем общий результат на основе критических проверок
                    $is_scrapped = isset($data['VehicleStatus']['IsScrapped']) ? $data['VehicleStatus']['IsScrapped'] : false;
                    $is_exported = isset($data['VehicleStatus']['IsExported']) ? $data['VehicleStatus']['IsExported'] : false;
                    $certificate_issued = isset($data['VehicleStatus']['CertificateOfDestructionIssued']) ? $data['VehicleStatus']['CertificateOfDestructionIssued'] : false;
                    
                    // Проверяем статус MOT
                    $mot_data = isset($data['mot_history']) ? $data['mot_history'] : array();
                    $mot_status = isset($mot_data['mot_status']) ? strtolower($mot_data['mot_status']) : 'unknown';
                    $mot_expired = ($mot_status === 'expired');
                    
                    // Определяем общий статус
                    if ($is_scrapped || $certificate_issued) {
                        $overall_status = 'fail';
                        $status_class = 'status-fail';
                        $status_text = 'FAIL';
                    } elseif ($is_exported || $mot_expired) {
                        $overall_status = 'warning';
                        $status_class = 'status-warning';
                        $status_text = 'WARNING';
                    } else {
                        $overall_status = 'pass';
                        $status_class = 'status-pass';
                        $status_text = 'PASS';
                    }
                    ?>
                    <span class="status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
                </div>
            </div>
            
            <div class="summary-row">
                <div class="summary-label">Information</div>
                <div class="summary-value summary-info">
                    <?php 
                    // Генерируем информационное сообщение на основе статуса
                    if ($overall_status === 'fail') {
                        if ($is_scrapped) {
                            echo 'Vehicle is scrapped - do not purchase';
                        } elseif ($certificate_issued) {
                            echo 'Certificate of destruction issued - vehicle should not be on the road';
                        }
                    } elseif ($overall_status === 'warning') {
                        $warnings = array();
                        if ($is_exported) {
                            $warnings[] = 'vehicle was exported';
                        }
                        if ($mot_expired) {
                            $warnings[] = 'MOT has expired';
                        }
                        echo 'Proceed with caution: ' . implode(', ', $warnings);
                    } else {
                        echo 'No major issues found with this vehicle';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- All Checks Block -->
    <div class="all-checks-block">
        <div class="checks-header">
            <h3 class="checks-title title-gray">All Checks</h3>
        </div>
        
        <div class="checks-content">
            <div class="checks-grid">
                <!-- Left Column -->
                <div class="checks-column">
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Imported</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            // Получаем данные напрямую из VehicleStatus
                            $is_imported = isset($data['VehicleStatus']['IsImported']) ? $data['VehicleStatus']['IsImported'] : false;
                            $imported_status = $is_imported ? 'warning' : 'pass';
                            $imported_message = $is_imported ? 'Vehicle was imported' : 'Vehicle was not imported';
                            $status_class = $imported_status === 'pass' ? 'status-no' : ($imported_status === 'warning' ? 'status-yes' : 'status-unknown');
                            $status_text = $imported_status === 'pass' ? 'No' : ($imported_status === 'warning' ? 'Yes' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($imported_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Exported</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            // Получаем данные напрямую из VehicleStatus
                            $is_exported = isset($data['VehicleStatus']['IsExported']) ? $data['VehicleStatus']['IsExported'] : false;
                            $exported_status = $is_exported ? 'warning' : 'pass';
                            $exported_message = $is_exported ? 'Vehicle was exported' : 'Vehicle was not exported';
                            $status_class = $exported_status === 'pass' ? 'status-no' : ($exported_status === 'warning' ? 'status-yes' : 'status-unknown');
                            $status_text = $exported_status === 'pass' ? 'No' : ($exported_status === 'warning' ? 'Yes' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($exported_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Scrapped</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            // Получаем данные напрямую из VehicleStatus
                            $is_scrapped = isset($data['VehicleStatus']['IsScrapped']) ? $data['VehicleStatus']['IsScrapped'] : false;
                            $scrapped_status = $is_scrapped ? 'fail' : 'pass';
                            $scrapped_message = $is_scrapped ? 'Vehicle is scrapped' : 'Vehicle is not scrapped';
                            $status_class = $scrapped_status === 'pass' ? 'status-no' : ($scrapped_status === 'fail' ? 'status-fail' : 'status-unknown');
                            $status_text = $scrapped_status === 'pass' ? 'No' : ($scrapped_status === 'fail' ? 'Yes' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($scrapped_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Unscrapped</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            // Получаем данные напрямую из VehicleStatus
                            $is_unscrapped = isset($data['VehicleStatus']['IsUnscrapped']) ? $data['VehicleStatus']['IsUnscrapped'] : false;
                            $unscrapped_status = $is_unscrapped ? 'fail' : 'pass';
                            $unscrapped_message = $is_unscrapped ? 'Vehicle was unscrapped' : 'Vehicle was not unscrapped';
                            $status_class = $unscrapped_status === 'pass' ? 'status-no' : ($unscrapped_status === 'fail' ? 'status-fail' : 'status-unknown');
                            $status_text = $unscrapped_status === 'pass' ? 'No' : ($unscrapped_status === 'fail' ? 'Yes' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($unscrapped_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Safety Recalls</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            // Статическое значение для Safety Recalls
                            $safety_recalls_status = 'pass';
                            $safety_recalls_message = 'No safety recalls found';
                            $status_class = 'status-no';
                            $status_text = 'No';
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($safety_recalls_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Previous Keepers</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            // Получаем данные напрямую из VehicleHistory
                            $previous_keepers_count = isset($data['VehicleHistory']['PreviousKeepers']) ? count($data['VehicleHistory']['PreviousKeepers']) : 0;
                            $previous_keepers_message = $previous_keepers_count > 0 ? "Vehicle had {$previous_keepers_count} previous keepers" : 'No previous keepers found';
                            ?>
                            <span class="check-number" title="<?php echo esc_attr($previous_keepers_message); ?>"><?php echo esc_html($previous_keepers_count); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Plate Changes</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            // Получаем данные напрямую из VehicleHistory
                            $plate_changes_count = isset($data['VehicleHistory']['PlateChanges']) ? count($data['VehicleHistory']['PlateChanges']) : 0;
                            $plate_changes_message = $plate_changes_count > 0 ? "Vehicle had {$plate_changes_count} plate changes" : 'No plate changes found';
                            ?>
                            <span class="check-number" title="<?php echo esc_attr($plate_changes_message); ?>"><?php echo esc_html($plate_changes_count); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">MOT</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            // Получаем данные MOT из API
                            $mot_data = isset($data['mot_history']) ? $data['mot_history'] : array();
                            $mot_status = isset($mot_data['mot_status']) ? $mot_data['mot_status'] : 'Unknown';
                            $mot_expiry = isset($mot_data['mot_expiry_date']) ? $mot_data['mot_expiry_date'] : '';
                            
                            // Определяем статус и класс
                            if (strtolower($mot_status) === 'valid' || strtolower($mot_status) === 'current') {
                                $status_class = 'status-valid';
                                $status_text = 'Valid';
                                $mot_message = 'MOT is valid' . ($mot_expiry ? ' until ' . $mot_expiry : '');
                            } elseif (strtolower($mot_status) === 'expired') {
                                $status_class = 'status-fail';
                                $status_text = 'Expired';
                                $mot_message = 'MOT has expired';
                            } else {
                                $status_class = 'status-unknown';
                                $status_text = 'Unknown';
                                $mot_message = 'MOT status unknown';
                            }
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($mot_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Road Tax</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            // Статическое значение для Road Tax
                            $road_tax_status = 'valid';
                            $road_tax_message = 'Road tax status valid';
                            $status_class = 'status-valid';
                            $status_text = 'Valid';
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($road_tax_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="checks-column">
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Written off</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            // Получаем данные напрямую из VehicleStatus
                            $is_scrapped = isset($data['VehicleStatus']['IsScrapped']) ? $data['VehicleStatus']['IsScrapped'] : false;
                            $certificate_issued = isset($data['VehicleStatus']['CertificateOfDestructionIssued']) ? $data['VehicleStatus']['CertificateOfDestructionIssued'] : false;
                            $date_scrapped = isset($data['VehicleStatus']['DateScrapped']) ? $data['VehicleStatus']['DateScrapped'] : null;
                            
                            // Автомобиль считается written off если он списан или выдан сертификат об утилизации
                            $is_written_off = $is_scrapped || $certificate_issued;
                            $written_off_status = $is_written_off ? 'fail' : 'pass';
                            
                            if ($is_written_off && $date_scrapped) {
                                $formatted_date = date('d/m/Y', strtotime($date_scrapped));
                                $written_off_message = "Vehicle was written off on {$formatted_date}";
                            } elseif ($is_written_off) {
                                $written_off_message = 'Vehicle is written off';
                            } else {
                                $written_off_message = 'Vehicle is not written off';
                            }
                            
                            $status_class = $written_off_status === 'pass' ? 'status-no' : 'status-fail';
                            $status_text = $written_off_status === 'pass' ? 'No' : 'Yes';
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($written_off_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Salvage History</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            $salvage_history_status = isset($data['premium_checks']['salvage_history']['status']) ? $data['premium_checks']['salvage_history']['status'] : 'unknown';
                            $salvage_history_message = isset($data['premium_checks']['salvage_history']['message']) ? $data['premium_checks']['salvage_history']['message'] : 'Данные недоступны';
                            $status_class = $salvage_history_status === 'pass' ? 'status-no' : ($salvage_history_status === 'fail' ? 'status-fail' : 'status-unknown');
                            $status_text = $salvage_history_status === 'pass' ? 'Pass' : ($salvage_history_status === 'fail' ? 'Fail' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($salvage_history_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Stolen</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            $stolen_status = isset($data['premium_checks']['stolen']['status']) ? $data['premium_checks']['stolen']['status'] : 'unknown';
                            $stolen_message = isset($data['premium_checks']['stolen']['message']) ? $data['premium_checks']['stolen']['message'] : 'Данные недоступны';
                            $status_class = $stolen_status === 'pass' ? 'status-no' : ($stolen_status === 'fail' ? 'status-fail' : 'status-unknown');
                            $status_text = $stolen_status === 'pass' ? 'No' : ($stolen_status === 'fail' ? 'Yes' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($stolen_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Colour Changes</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            // Получаем данные напрямую из VehicleHistory
                            $colour_changes_count = isset($data['VehicleHistory']['ColourChanges']) ? count($data['VehicleHistory']['ColourChanges']) : 0;
                            $colour_changes_status = $colour_changes_count > 0 ? 'warning' : 'pass';
                            $colour_changes_message = $colour_changes_count > 0 ? "Vehicle had {$colour_changes_count} colour changes" : 'No colour changes found';
                            $status_class = $colour_changes_status === 'pass' ? 'status-no' : ($colour_changes_status === 'warning' ? 'status-yes' : 'status-unknown');
                            $status_text = $colour_changes_status === 'pass' ? 'No' : ($colour_changes_status === 'warning' ? 'Yes' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($colour_changes_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Mileage Issues</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            $mileage_issues_status = isset($data['premium_checks']['mileage_issues']['status']) ? $data['premium_checks']['mileage_issues']['status'] : 'unknown';
                            $mileage_issues_message = isset($data['premium_checks']['mileage_issues']['message']) ? $data['premium_checks']['mileage_issues']['message'] : 'Данные недоступны';
                            $status_class = $mileage_issues_status === 'pass' ? 'status-no' : ($mileage_issues_status === 'warning' ? 'status-yes' : 'status-unknown');
                            $status_text = $mileage_issues_status === 'pass' ? 'No' : ($mileage_issues_status === 'warning' ? 'Yes' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($mileage_issues_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Ex-Taxi</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            $ex_taxi_status = isset($data['premium_checks']['ex_taxi']['status']) ? $data['premium_checks']['ex_taxi']['status'] : 'unknown';
                            $ex_taxi_message = isset($data['premium_checks']['ex_taxi']['message']) ? $data['premium_checks']['ex_taxi']['message'] : 'Данные недоступны';
                            $status_class = $ex_taxi_status === 'pass' ? 'status-no' : ($ex_taxi_status === 'warning' ? 'status-yes' : 'status-unknown');
                            $status_text = $ex_taxi_status === 'pass' ? 'No' : ($ex_taxi_status === 'warning' ? 'Yes' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($ex_taxi_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">VIN Check</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            $vin_check_status = isset($data['premium_checks']['vin_check']['status']) ? $data['premium_checks']['vin_check']['status'] : 'unknown';
                            $vin_check_message = isset($data['premium_checks']['vin_check']['message']) ? $data['premium_checks']['vin_check']['message'] : 'Данные недоступны';
                            $status_class = $vin_check_status === 'pass' ? 'status-pass' : ($vin_check_status === 'fail' ? 'status-fail' : 'status-unknown');
                            $status_text = $vin_check_status === 'pass' ? 'Pass' : ($vin_check_status === 'fail' ? 'Fail' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($vin_check_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Outstanding Finance</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            $finance_status = isset($data['premium_checks']['outstanding_finance']['status']) ? $data['premium_checks']['outstanding_finance']['status'] : 'unknown';
                            $finance_message = isset($data['premium_checks']['outstanding_finance']['message']) ? $data['premium_checks']['outstanding_finance']['message'] : 'Данные недоступны';
                            $status_class = $finance_status === 'pass' ? 'status-no' : ($finance_status === 'fail' ? 'status-fail' : 'status-unknown');
                            $status_text = $finance_status === 'pass' ? 'No' : ($finance_status === 'fail' ? 'Yes' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($finance_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Market Value</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <?php 
                            $market_value_status = isset($data['premium_checks']['market_value']['status']) ? $data['premium_checks']['market_value']['status'] : 'unknown';
                            $market_value_message = isset($data['premium_checks']['market_value']['message']) ? $data['premium_checks']['market_value']['message'] : 'Данные недоступны';
                            $status_class = $market_value_status === 'available' ? 'status-yes' : ($market_value_status === 'unavailable' ? 'status-no' : 'status-unknown');
                            $status_text = $market_value_status === 'available' ? 'Yes' : ($market_value_status === 'unavailable' ? 'No' : 'Unknown');
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($market_value_message); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle Description Block -->
    <div class="vehicle-description-block">
        <div class="description-header">
            <h3 class="description-title title-gray">Vehicle Description</h3>
        </div>
        
        <div class="description-content">
            <div class="description-grid">
                <!-- Left Column -->
                <div class="description-column">
                    <div class="description-row">
                        <div class="description-label">Manufacturer</div>
                        <div class="description-value"><?php echo esc_html($data['make']); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Model</div>
                        <div class="description-value"><?php echo esc_html($data['model']); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Colour</div>
                        <div class="description-value"><?php echo esc_html($data['colour'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Transmission</div>
                        <div class="description-value"><?php echo esc_html($data['transmission'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Year of manufacture</div>
                        <div class="description-value"><?php echo esc_html($data['year']); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Engine Size</div>
                        <div class="description-value"><?php echo esc_html($data['engine_size'] ?? 'Not Available'); ?></div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="description-column">
                    <div class="description-row">
                        <div class="description-label">Fuel Type</div>
                        <div class="description-value"><?php echo esc_html($data['fuel_type'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">No. of Seats</div>
                        <div class="description-value"><?php echo esc_html($data['seats'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Vehicle Type</div>
                        <div class="description-value"><?php echo esc_html($data['vehicle_type'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Engine Size (cc)</div>
                        <div class="description-value"><?php echo esc_html($data['engine_size_cc'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">BHP</div>
                        <div class="description-value"><?php echo esc_html($data['bhp'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Vehicle Age</div>
                        <div class="description-value"><?php 
                            if (isset($data['year']) && is_numeric($data['year'])) {
                                $current_year = date('Y');
                                $vehicle_year = intval($data['year']);
                                $age_years = $current_year - $vehicle_year;
                                $age_months = date('n') - 1; // Current month minus 1 for approximate calculation
                                if ($age_months < 0) {
                                    $age_years--;
                                    $age_months += 12;
                                }
                                echo esc_html($age_years . ' years ' . $age_months . ' months');
                            } else {
                                echo 'Not Available';
                            }
                        ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Checks and Keeper Information Blocks -->
    <div class="manual-keeper-container">
        <!-- Your Manual Checks Block -->
        <div class="manual-checks-block">
            <div class="manual-checks-header">
                <h3 class="manual-checks-title title-gray">Your Manual Checks</h3>
            </div>
            
            <div class="manual-checks-content">
                <div class="manual-checks-info">
                    <em>Check this information carefully before purchasing the vehicle to confirm its identity.</em>
                </div>
                
                <div class="manual-check-row">
                    <div class="manual-check-label">VIN ends with</div>
                    <div class="manual-check-value"><?php 
                        if (isset($data['vin']) && strlen($data['vin']) >= 4) {
                            echo esc_html(substr($data['vin'], -4));
                        } else {
                            echo 'Not Available';
                        }
                    ?></div>
                </div>
                
                <div class="manual-check-row">
                    <div class="manual-check-label">Engine Number</div>
                    <div class="manual-check-value"><?php echo esc_html($data['engine_number'] ?? 'Not Available'); ?></div>
                </div>
                
                <div class="manual-check-row">
                    <div class="manual-check-label">V5C logbook date</div>
                    <div class="manual-check-value"><?php echo esc_html($data['v5c_date'] ?? 'Not Available'); ?></div>
                </div>
                
                <div class="vin-check-section">
                    <h4 class="vin-check-title">VIN check</h4>
                    <p class="vin-check-description">Enter this vehicle's 17 digit Vehicle Identification Number (VIN) in the field below to see if it matches our records.</p>
                    
                    <div class="vin-input-container">
                        <input type="text" class="vin-input" value="<?php 
                            if (isset($data['vin']) && strlen($data['vin']) >= 4) {
                                echo esc_attr(str_repeat('*', strlen($data['vin']) - 4) . substr($data['vin'], -4));
                            } else {
                                echo '*************';
                            }
                        ?>" readonly>
                        <button class="vin-check-button">Check</button>
                    </div>
                    
                    <div class="vin-result">
                        <span class="vin-success">VIN matched successfully.</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Keeper Information Block -->
        <div class="keeper-info-block">
            <div class="keeper-info-header">
                <h3 class="keeper-info-title title-gray">Keeper Information</h3>
            </div>
            
            <div class="keeper-info-content">
                <div class="keeper-info-row">
                    <div class="keeper-info-label">Current Keeper Acq.</div>
                    <div class="keeper-info-value"><?php echo esc_html($data['current_keeper_date'] ?? 'Not Available'); ?></div>
                </div>
                
                <div class="keeper-info-row">
                    <div class="keeper-info-label">Current Ownership</div>
                    <div class="keeper-info-value"><?php 
                        if (isset($data['current_keeper_date']) && !empty($data['current_keeper_date'])) {
                            $keeper_date = DateTime::createFromFormat('Y-m-d', $data['current_keeper_date']);
                            if ($keeper_date) {
                                $current_date = new DateTime();
                                $interval = $current_date->diff($keeper_date);
                                echo esc_html($interval->y . ' years ' . $interval->m . ' months');
                            } else {
                                echo 'Not Available';
                            }
                        } else {
                            echo 'Not Available';
                        }
                    ?></div>
                </div>
                
                <div class="keeper-info-row">
                    <div class="keeper-info-label">Registered Date</div>
                    <div class="keeper-info-value"><?php echo esc_html($data['registration_date'] ?? 'Not Available'); ?></div>
                </div>
                
                <div class="keeper-info-row">
                    <div class="keeper-info-label">Prev. Keeper Acq.</div>
                    <div class="keeper-info-value"><?php echo esc_html($data['previous_keeper_date'] ?? 'Not Available'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MOT and Road Tax Blocks -->
    <div class="mot-roadtax-container">
        <!-- MOT Block -->
        <div class="mot-block">
            <div class="mot-header">
                <h3 class="mot-title title-green">MOT</h3>
            </div>
            
            <div class="mot-content">
                <div class="mot-row">
                    <div class="mot-label">MOT Expiry</div>
                    <div class="mot-value">
                        <?php 
                        $mot_data = isset($data['mot_history']) ? $data['mot_history'] : array();
                        $mot_expiry = isset($mot_data['mot_expiry_date']) ? $mot_data['mot_expiry_date'] : 'N/A';
                        echo esc_html($mot_expiry);
                        ?>
                    </div>
                </div>
                
                <div class="mot-row">
                    <div class="mot-label">Days Remaining</div>
                    <div class="mot-value">
                        <?php 
                        $days_remaining = 'N/A';
                        if (!empty($mot_data['mot_expiry_date'])) {
                            try {
                                $expiry_date = new DateTime($mot_data['mot_expiry_date']);
                                $current_date = new DateTime();
                                $diff = $current_date->diff($expiry_date);
                                
                                if ($expiry_date > $current_date) {
                                    $days_remaining = $diff->days . ' days';
                                } else {
                                    $days_remaining = 'Expired ' . $diff->days . ' days ago';
                                }
                            } catch (Exception $e) {
                                $days_remaining = 'N/A';
                            }
                        }
                        echo esc_html($days_remaining);
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Road Tax Block -->
        <div class="roadtax-block">
            <div class="roadtax-header">
                <h3 class="roadtax-title title-green">Road Tax</h3>
            </div>
            
            <div class="roadtax-content">
                <div class="roadtax-row">
                    <div class="roadtax-label">Road Tax Expiry</div>
                    <div class="roadtax-value">01 Aug 2023</div>
                </div>
                
                <div class="roadtax-row">
                    <div class="roadtax-label">Days Remaining</div>
                    <div class="roadtax-value">XXX days</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stolen Check Block -->
    <div class="stolen-check-block">
        <div class="stolen-header">
            <h3 class="stolen-title title-green">Stolen Check</h3>
        </div>
        
        <div class="stolen-content">
            <div class="stolen-row">
                <div class="stolen-label">Stolen Police</div>
                <div class="stolen-value">No</div>
            </div>
            
            <div class="stolen-row">
                <div class="stolen-label">Stolen Insurance</div>
                <div class="stolen-value">No</div>
            </div>
        </div>
    </div>

    <!-- Written Off Block -->
    <div class="written-off-block">
        <div class="written-off-header">
            <h3 class="written-off-title">Written Off</h3>
        </div>
        
        <div class="written-off-description">
            <p><em>Checks to verify if a vehicle has been involved in an insurance claim.</em></p>
        </div>
        
        <div class="written-off-content">
            <div class="written-off-row">
                <div class="written-off-label">Written off Category</div>
                <div class="written-off-value">S</div>
            </div>
            
            <div class="written-off-row">
                <div class="written-off-label">Loss Date</div>
                <div class="written-off-value">19 May 2021</div>
            </div>
            
            <div class="written-off-row">
                <div class="written-off-label">Damage Areas</div>
                <div class="written-off-value">a . Front<br>b . FrontNearside</div>
            </div>
            
            <div class="written-off-row">
                <div class="written-off-label">Cause of Damage</div>
                <div class="written-off-value">Accident</div>
            </div>
        </div>
    </div>

    <!-- Salvage History Block -->
    <div class="salvage-history-block">
        <div class="salvage-history-header">
            <h3 class="salvage-history-title">Salvage History</h3>
        </div>
        
        <div class="salvage-history-description">
            <p><em>Checks to verify if a vehicle had previously been sold on salvage auction sites.</em></p>
        </div>
        
        <div class="salvage-history-content">
            <div class="salvage-history-row">
                <div class="salvage-history-label">Date</div>
                <div class="salvage-history-value">19 January 2021</div>
            </div>
            
            <div class="salvage-history-row">
                <div class="salvage-history-label">Mileage</div>
                <div class="salvage-history-value">15943</div>
            </div>
            
            <div class="salvage-history-row">
                <div class="salvage-history-label">Damage Photos</div>
                <div class="salvage-history-value"><a href="#" style="color: #dc3545; text-decoration: underline;">Click to View</a></div>
            </div>
            
            <div class="salvage-history-row">
                <div class="salvage-history-label">Location</div>
                <div class="salvage-history-value">ROCHFORD</div>
            </div>
        </div>
        
        <div class="salvage-history-footer">
            <a href="#" style="color: #dc3545; text-decoration: underline; font-size: 14px;">What is a Salvage Check?</a>
        </div>
    </div>

    <!-- Important Checks Block -->
    <div class="important-checks-block">
        <div class="important-checks-header">
            <h3 class="important-checks-title title-green">Important Checks</h3>
        </div>
        
        <div class="important-checks-content">
            <div class="important-checks-row">
                <div class="important-checks-label">Import Date</div>
                <div class="important-checks-value">Not Imported</div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">Date Exported</div>
                <div class="important-checks-value">Not Exported</div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">Q Registration</div>
                <div class="important-checks-value">Not Q Registered</div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">VIC Inspected</div>
                <div class="important-checks-value">Not VIC Inspected</div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">Imported from Northen Ireland</div>
                <div class="important-checks-value">Not Imported</div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">Date Scrapped</div>
                <div class="important-checks-value">Not Scrapped</div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">Unscrapped</div>
                <div class="important-checks-value">Not Unscrapped</div>
            </div>
        </div>
    </div>

    <!-- Outstanding Finance Block -->
    <div class="outstanding-finance-block">
        <div class="outstanding-finance-header">
            <h3 class="outstanding-finance-title">Outstanding Finance</h3>
        </div>
        <div class="outstanding-finance-description">
            <em>Finance check is powered by Experian</em>
        </div>
        <div class="outstanding-finance-content">
            <div class="outstanding-finance-row">
                <span class="outstanding-finance-label">Agreement Date</span>
                <span class="outstanding-finance-value">18 August 2020</span>
            </div>
            <div class="outstanding-finance-row">
                <span class="outstanding-finance-label">Agreement Number</span>
                <span class="outstanding-finance-value">25623928</span>
            </div>
            <div class="outstanding-finance-row">
                <span class="outstanding-finance-label">Agreement Term</span>
                <span class="outstanding-finance-value">48</span>
            </div>
            <div class="outstanding-finance-row">
                <span class="outstanding-finance-label">Agreement Type</span>
                <span class="outstanding-finance-value">Hire Purchase</span>
            </div>
            <div class="outstanding-finance-row">
                <span class="outstanding-finance-label">Contact Number</span>
                <span class="outstanding-finance-value">0845 604 6123</span>
            </div>
            <div class="outstanding-finance-row">
                <span class="outstanding-finance-label">Date Of Transaction</span>
                <span class="outstanding-finance-value">16 August 2020</span>
            </div>
            <div class="outstanding-finance-row">
                <span class="outstanding-finance-label">Finance Company</span>
                <span class="outstanding-finance-value">Asset Finance (UK) Ltd</span>
            </div>
            <div class="outstanding-finance-row">
                <span class="outstanding-finance-label">Vehicle Description</span>
                <span class="outstanding-finance-value">Mercedes C Class</span>
            </div>
        </div>
    </div>

    <!-- Ownership Block -->
    <div class="ownership-block">
        <div class="ownership-header">
            <h3 class="ownership-title title-gray">Ownership</h3>
        </div>
        <div class="ownership-content">
            <div class="ownership-row">
                <span class="ownership-label">No. Previous Keepers</span>
                <span class="ownership-value">2</span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Current Keeper Acq.</span>
                <span class="ownership-value">13 August 2022</span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Current Ownership</span>
                <span class="ownership-value">0 years 4 months</span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Prev. Keeper Sold</span>
                <span class="ownership-value">12 August 2022</span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Prev. Keeper Acq.</span>
                <span class="ownership-value">01 February 2022</span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Vehicle Age</span>
                <span class="ownership-value">5 years 11 months</span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Registration</span>
                <span class="ownership-value">17 January 2017</span>
            </div>
        </div>
    </div>

    <!-- Keeper History Block -->
    <div class="keeper-history-block">
        <div class="keeper-history-header">
            <h3 class="keeper-history-title title-gray">Keeper History</h3>
        </div>
        <div class="keeper-history-description">
            <em>The below durations may include time the vehicle spent at a registered motor trader whilst awaiting sale.</em>
        </div>
        <div class="keeper-history-content">
            <div class="keeper-history-table">
                <div class="keeper-history-table-header">
                    <div class="keeper-history-table-cell header-cell">Keeper Number</div>
                    <div class="keeper-history-table-cell header-cell">Acquired</div>
                    <div class="keeper-history-table-cell header-cell">Ownership Duration</div>
                </div>
                <div class="keeper-history-table-row">
                    <div class="keeper-history-table-cell">1</div>
                    <div class="keeper-history-table-cell">2017-01-17</div>
                    <div class="keeper-history-table-cell">3 years and X months</div>
                </div>
                <div class="keeper-history-table-row">
                    <div class="keeper-history-table-cell">2</div>
                    <div class="keeper-history-table-cell">2022-02-01</div>
                    <div class="keeper-history-table-cell">X months and X days</div>
                </div>
                <div class="keeper-history-table-row">
                    <div class="keeper-history-table-cell">3(Current)</div>
                    <div class="keeper-history-table-cell">2022-08-13</div>
                    <div class="keeper-history-table-cell">X months and X days</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Market Value Block -->
    <div class="market-value-block">
        <div class="market-value-header">
            <h3 class="market-value-title title-gray">Market Value</h3>
        </div>
        <div class="market-value-description">
            <em>This valuation reflects the average market value of your chosen make, model, and mileage, based on its fair condition for its age.</em>
        </div>
        <div class="market-value-content">
            <div class="market-value-row">
                <span class="market-value-label">Description</span>
                <span class="market-value-value">Mercedes-Benz, C220</span>
            </div>
            <div class="market-value-row">
                <span class="market-value-label">Based on Mileage</span>
                <span class="market-value-value">30,000 miles</span>
            </div>
            <div class="market-value-row">
                <span class="market-value-label">Average Trade Value</span>
                <span class="market-value-value">£26,110 - £27,324</span>
            </div>
            <div class="market-value-row">
                <span class="market-value-label">Average Private Value</span>
                <span class="market-value-value">£23,970 - £25,956</span>
            </div>
        </div>
    </div>

    <!-- Plate Change History Block -->
    <div class="plate-change-history-block">
        <div class="plate-change-history-header">
            <h3 class="plate-change-history-title title-gray">Plate Change History</h3>
        </div>
        <div class="plate-change-history-content">
            <div class="plate-change-history-row">
                <span class="plate-change-history-label">Registration</span>
                <span class="plate-change-history-value">A91UO</span>
            </div>
            <div class="plate-change-history-row">
                <span class="plate-change-history-label">Transfer Off</span>
                <span class="plate-change-history-value">Until 14 May 2021</span>
            </div>
        </div>
    </div>

    <!-- Mileage Information Block -->
    <div class="mileage-information-block">
        <div class="mileage-information-header">
            <h3 class="mileage-information-title title-gray">Mileage Information</h3>
        </div>
        <div class="mileage-information-content">
            <?php if (!empty($data['MileageCheckDetails']['MileageAnomalyDetected'])): ?>
            <div class="mileage-information-row">
                <span class="mileage-information-label">Mileage Issues</span>
                <span class="mileage-information-value mileage-issues-red"><?php echo esc_html($data['MileageCheckDetails']['MileageAnomalyDetected']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($data['MileageCheckDetails']['AverageMileagePerYear'])): ?>
            <div class="mileage-information-row">
                <span class="mileage-information-label">Average Mileage for year</span>
                <span class="mileage-information-value"><?php echo esc_html($data['MileageCheckDetails']['AverageMileagePerYear']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($data['MileageCheckDetails']['MileageStatus'])): ?>
            <div class="mileage-information-row">
                <span class="mileage-information-label">Mileage Average</span>
                <span class="mileage-information-value"><?php echo esc_html($data['MileageCheckDetails']['MileageStatus']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Start Advanced Mileage History Block -->
    <div class="advanced-mileage-history-block">
        <div class="advanced-mileage-history-header">
            <h3 class="advanced-mileage-history-title">Advanced Mileage History</h3>
        </div>
        <div class="advanced-mileage-history-sources">
            <div class="sources-intro">Various sources checked including:</div>
            <div class="sources-list">
                <div class="source-item">
                    <span class="source-label">DVLA:</span>
                    <span class="source-description">Driver and Vehicle Licensing Agency</span>
                </div>
                <div class="source-item">
                    <span class="source-label">Manufacturers:</span>
                    <span class="source-description">Including Retailers and Garages</span>
                </div>
                <div class="source-item">
                    <span class="source-label">BVRLA:</span>
                    <span class="source-description">British Vehicle Rental and Leasing Association</span>
                </div>
                <div class="source-item">
                    <span class="source-label">RMI:</span>
                    <span class="source-description">Retail Motor Industry Federation</span>
                </div>
                <div class="source-item">
                    <span class="source-label">NAMA:</span>
                    <span class="source-description">National Association of Motor Auctions</span>
                </div>
                <div class="source-item">
                    <span class="source-label">VMC:</span>
                    <span class="source-description">Vehicle Mileage Check</span>
                </div>
            </div>
        </div>
        
        <!-- Mileage Chart Section -->
        <div class="mileage-chart-section">
            <canvas id="mileageChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Mileage Data Table Section -->
        <div class="mileage-data-table-section">
            <table class="mileage-data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Mileage</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data['MileageCheckDetails']['MileageRecordList'])): ?>
                        <?php foreach ($data['MileageCheckDetails']['MileageRecordList'] as $record): ?>
                            <tr<?php echo (!empty($record['IsAnomaly']) && $record['IsAnomaly']) ? ' class="reduced-mileage"' : ''; ?>>
                                <td><?php echo esc_html($record['RecordedDate'] ?? 'N/A'); ?></td>
                                <td<?php echo (!empty($record['IsAnomaly']) && $record['IsAnomaly']) ? ' class="mileage-reduced"' : ''; ?>>
                                    <?php echo esc_html($record['Mileage'] ?? 'N/A'); ?>
                                    <?php if (!empty($record['IsAnomaly']) && $record['IsAnomaly']): ?>
                                        (Anomaly)
                                    <?php endif; ?>
                                </td>
                                <td class="source-<?php echo esc_attr(strtolower($record['Source'] ?? 'unknown')); ?>">
                                    <?php echo esc_html($record['Source'] ?? 'Unknown'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback static data if no API data available -->
                        <tr>
                            <td>03/01/2023</td>
                            <td>49413</td>
                            <td class="source-mot">MOT</td>
                        </tr>
                        <tr class="reduced-mileage">
                            <td>02/09/2022</td>
                            <td class="mileage-reduced">46113 (Reduced -886)</td>
                            <td class="source-vmc">VMC</td>
                        </tr>
                        <tr>
                            <td>01/08/2022</td>
                            <td>47003</td>
                            <td class="source-dvla">DVLA</td>
                        </tr>
                        <tr>
                            <td>06/01/2022</td>
                            <td>39454</td>
                            <td class="source-mot">MOT</td>
                        </tr>
                        <tr>
                            <td>02/09/2021</td>
                            <td>39003</td>
                            <td class="source-dvla">DVLA</td>
                        </tr>
                        <tr>
                            <td>19/11/2020</td>
                            <td>38413</td>
                            <td class="source-nama">NAMA</td>
                        </tr>
                        <tr>
                            <td>11/02/2019</td>
                            <td>35409</td>
                            <td class="source-rmi">RMI</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- End Advanced Mileage History Block -->

    <!-- MOT History Block -->
    <div class="premium-block mot-history-block">
        <div class="premium-block-header">
            <div class="premium-block-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h3 class="premium-block-title">MOT History</h3>
        </div>
        <div class="premium-block-content">
            <div class="mot-history-table">
                <div class="mot-history-header">
                    <div class="mot-header-item">Date</div>
                    <div class="mot-header-item">Result</div>
                    <div class="mot-header-item">Mileage</div>
                </div>
                
                <?php 
                $mot_data = isset($data['mot_history']) ? $data['mot_history'] : array();
                $test_history = isset($mot_data['test_history']) ? $mot_data['test_history'] : array();
                
                if (!empty($test_history)) {
                    foreach ($test_history as $test) {
                        $test_result = isset($test['test_result']) ? strtolower($test['test_result']) : 'unknown';
                        $result_class = ($test_result === 'passed' || $test_result === 'pass') ? 'pass' : 'fail';
                        $result_text = ($test_result === 'passed' || $test_result === 'pass') ? 'Pass' : 'Fail';
                ?>
                <div class="mot-history-row">
                    <div class="mot-date-cell">
                        <div class="mot-date"><?php echo esc_html($test['test_date'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="mot-result-cell">
                        <span class="mot-result-badge <?php echo esc_attr($result_class); ?>"><?php echo esc_html($result_text); ?></span>
                    </div>
                    <div class="mot-mileage-cell">
                        <div class="mot-mileage-label">Mileage :</div>
                        <div class="mot-mileage-value"><?php echo esc_html($test['odometer_value'] ?? 'N/A'); ?> <?php echo esc_html($test['odometer_unit'] ?? 'miles'); ?></div>
                        
                        <?php if (!empty($test['rfrAndComments'])) { ?>
                        <div class="mot-advisory-notices">
                            <div class="advisory-title">Advisory Notices</div>
                            <?php foreach ($test['rfrAndComments'] as $comment) { 
                                $comment_type = isset($comment['type']) ? strtolower($comment['type']) : 'advisory';
                            ?>
                            <div class="advisory-item">
                                • <?php echo esc_html($comment['text'] ?? ''); ?>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php 
                    }
                } else {
                ?>
                <div class="mot-history-row">
                    <div class="mot-no-data">
                        <p>No MOT history data available for this vehicle.</p>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <!-- End MOT History Block -->

    <!-- Two Column Block Container -->
    <div class="two-column-container">
        <!-- Fuel Economy Block -->
        <div class="premium-block fuel-economy-block">
            <div class="premium-block-header">
                <h3 class="premium-block-title">Fuel Economy</h3>
            </div>
            <div class="premium-block-content">
                <div class="fuel-economy-table">
                    <div class="fuel-economy-row">
                        <div class="fuel-type-cell">
                            <span class="fuel-type-label">Urban</span>
                        </div>
                        <div class="fuel-value-cell">
                            <span class="fuel-value">23.7 MPG</span>
                        </div>
                    </div>
                    <div class="fuel-economy-row">
                        <div class="fuel-type-cell">
                            <span class="fuel-type-label">Extra-Urban</span>
                        </div>
                        <div class="fuel-value-cell">
                            <span class="fuel-value">35.3 MPG</span>
                        </div>
                    </div>
                    <div class="fuel-economy-row">
                        <div class="fuel-type-cell">
                            <span class="fuel-type-label">Combined</span>
                        </div>
                        <div class="fuel-value-cell">
                            <span class="fuel-value">30.1 MPG</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Fuel Economy Block -->

        <!-- Road Tax Information Block -->
        <div class="premium-block road-tax-block">
            <div class="premium-block-header">
                <h3 class="premium-block-title">Road Tax Information</h3>
            </div>
            <div class="premium-block-content">
                <div class="road-tax-info">
                    <div class="tax-notice">
                        <p class="tax-notice-text">Road tax rates are indicative (check with DVLA to confirm rates).</p>
                        <p class="tax-surcharge-text">This vehicle incurs an annual surcharge of up to £520 until 2025-04-30</p>
                    </div>
                    <div class="tax-details-table">
                        <div class="tax-detail-row">
                            <div class="tax-label-cell">
                                <span class="tax-label">VED for 12 months</span>
                            </div>
                            <div class="tax-value-cell">
                                <span class="tax-value">£165</span>
                            </div>
                        </div>
                        <div class="tax-detail-row">
                            <div class="tax-label-cell">
                                <span class="tax-label">VED for 6 months</span>
                            </div>
                            <div class="tax-value-cell">
                                <span class="tax-value">£90.75</span>
                            </div>
                        </div>
                        <div class="tax-detail-row">
                            <div class="tax-label-cell">
                                <span class="tax-label">CO2 Emissions</span>
                            </div>
                            <div class="tax-value-cell">
                                <span class="tax-value">212 g/km</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CO2 Emissions Rating Chart -->
                    <div class="co2-rating-chart">
                        <h4 class="chart-title">CO2 Emissions Rating</h4>
                        <div class="chart-container">
                            <canvas id="co2RatingChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                    <!-- End CO2 Emissions Rating Chart -->
                </div>
            </div>
        </div>
        <!-- End Road Tax Information Block -->
    </div>
    <!-- End Two Column Container -->
    <!-- Vehicle Performance Block -->
    <div class="vehicle-performance-block">
        <div class="performance-header">
            <h3 class="performance-title title-gray">Vehicle Performance</h3>
        </div>
        
        
        <div class="performance-content">
            <div class="performance-row">
                <div class="performance-label">Top Speed</div>
                <div class="performance-value">155 MPH</div>
            </div>
            
            <div class="performance-row">
                <div class="performance-label">0-60 MPH</div>
                <div class="performance-value">4.70</div>
            </div>
        </div>
    </div>
    <!-- End Vehicle Performance Block -->

    <!-- Engine Specification Block -->
    <div class="engine-specification-block">
        <div class="specification-header">
            <h3 class="specification-title title-gray">Engine Specification</h3>
        </div>
        
        <div class="specification-content">
            <div class="specification-row">
                <div class="specification-label">Engine Code</div>
                <div class="specification-value">HR12DE</div>
            </div>
            
            <div class="specification-row">
                <div class="specification-label">Body</div>
                <div class="specification-value">2 Axle Rigid Body</div>
            </div>
            
            <div class="specification-row">
                <div class="specification-label">Position</div>
                <div class="specification-value">Front</div>
            </div>
            
            <div class="specification-row">
                <div class="specification-label">Alignment</div>
                <div class="specification-value">Transverse</div>
            </div>
            
            <div class="specification-row">
                <div class="specification-label">Valves</div>
                <div class="specification-value">12 Valves</div>
            </div>
            
            <div class="specification-row">
                <div class="specification-label">Cylinders</div>
                <div class="specification-value">Inline</div>
            </div>
            
            <div class="specification-row">
                <div class="specification-label">Number Of Cylinders</div>
                <div class="specification-value">3 Cylinders</div>
            </div>
            
            <div class="specification-row">
                <div class="specification-label">Fuel Delivery</div>
                <div class="specification-value">Injection</div>
            </div>
            
            <div class="specification-row">
                <div class="specification-label">BHP</div>
                <div class="specification-value">385 BHP</div>
            </div>
            
            <div class="specification-row">
                <div class="specification-label">Power Output</div>
                <div class="specification-value">58 kW</div>
            </div>
        </div>
    </div>
    <!-- End Engine Specification Block -->

    <!-- Additional Information Block -->
    <div class="additional-information-block">
        <div class="additional-header">
            <h3 class="additional-title title-gray">Additional Information</h3>
        </div>
        
        <div class="additional-content">
            <div class="additional-row">
                <div class="additional-label">Vehicle Type</div>
                <div class="additional-value">Car</div>
            </div>
            
            <div class="additional-row">
                <div class="additional-label">Width</div>
                <div class="additional-value">1665 mm</div>
            </div>
            
            <div class="additional-row">
                <div class="additional-label">Length</div>
                <div class="additional-value">3780 mm</div>
            </div>
            
            <div class="additional-row">
                <div class="additional-label">Height</div>
                <div class="additional-value">1525 mm</div>
            </div>
            
            <div class="additional-row">
                <div class="additional-label">Wheel Base</div>
                <div class="additional-value">2450 mm</div>
            </div>
            
            <div class="additional-row">
                <div class="additional-label">Wheel Plan</div>
                <div class="additional-value">2 Axle Rigid Body</div>
            </div>
            
            <div class="additional-row">
                <div class="additional-label">Transmission</div>
                <div class="additional-value">2 Axle Rigid Body</div>
            </div>
            
            <div class="additional-row">
                <div class="additional-label">No. Of Seats</div>
                <div class="additional-value">CVT</div>
            </div>
            
            <div class="additional-row">
                <div class="additional-label">No. Of Doors</div>
                <div class="additional-value">5 Doors</div>
            </div>
            
            <div class="additional-row">
                <div class="additional-label">Drive Type</div>
                <div class="additional-value">Front Wheel Drive</div>
            </div>
            
            <div class="additional-row">
                <div class="additional-label">Driving Position</div>
                <div class="additional-value">RHD</div>
            </div>
        </div>
    </div>
    <!-- End Additional Information Block -->

    <!-- Insurance Block -->
    <div class="insurance-block">
        <div class="insurance-header">
            <h3 class="insurance-title title-gray">Insurance</h3>
        </div>
        
        <div class="insurance-content">
            <div class="insurance-row">
                <div class="insurance-label">Insurance Group</div>
                <div class="insurance-value">43</div>
            </div>
            
            <div class="insurance-row">
                <div class="insurance-label">Insurance Status</div>
                <div class="insurance-value insurance-check">Check if it's Insured</div>
            </div>
        </div>
    </div>
    <!-- End Insurance Block -->

    <!-- ULEZ Block -->
    <div class="ulez-block">
        <div class="ulez-header">
            <h3 class="ulez-title title-gray">ULEZ</h3>
        </div>
        
        <div class="ulez-content">
            <div class="ulez-row">
                <div class="ulez-label">Euro status</div>
                <div class="ulez-value">6ag</div>
            </div>
            
            <div class="ulez-row">
                <div class="ulez-label">ULEZ Status</div>
                <div class="ulez-value ulez-check">Check ULEZ emissions</div>
            </div>
        </div>
    </div>
    <!-- End ULEZ Block -->

    <!-- Instant Report Block -->
    <div class="instant-report-block">
        <div class="instant-report-header">
            <h3 class="instant-report-title title-gray">Instant Report</h3>
        </div>
        <div class="instant-report-content">
            <p class="instant-report-description">
                Your report will be instantly available upon payment, and a PDF version will also be sent to the provided email address.
            </p>
            <p class="instant-report-contact">
                If you have any questions then please <a href="#" class="contact-link">contact us</a> or email <a href="mailto:enquiries@fullcarchecks.co.uk" class="email-link">enquiries@fullcarchecks.co.uk</a>
            </p>
        </div>
    </div>
    <!-- End Instant Report Block -->

</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo plugin_dir_url(__FILE__) . '../assets/js/mileage-chart.js'; ?>"></script>
<script src="<?php echo plugin_dir_url(__FILE__) . '../assets/js/co2-rating-chart.js'; ?>"></script>