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

?>

<div class="vrm-check-premium-results">
    <!-- Main Report Content -->
    <div class="premium-report-content">
        <div class="vehicle-image-section">
            <?php 
            // Получаем изображение из оптимизированных данных API
            $image_url = '';
            
            // Проверяем наличие изображений в VehicleImageDetails
            if (!empty($data['VehicleImageDetails']['VehicleImageList']) && is_array($data['VehicleImageDetails']['VehicleImageList'])) {
                // Берем первое доступное изображение
                $first_image = $data['VehicleImageDetails']['VehicleImageList'][0];
                if (!empty($first_image['ImageUrl'])) {
                    $image_url = esc_url($first_image['ImageUrl']);
                }
            }
            
            // Если изображение из API недоступно, используем изображение по умолчанию
            if (empty($image_url)) {
                // Получаем марку автомобиля для изображения по умолчанию
                $vehicle_make = $data['VehicleDetails']['VehicleIdentification']['DvlaMake'] ?? 'default';
                $default_image = strtolower(str_replace(' ', '_', $vehicle_make)) . '.png';
                $image_url = esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/images/' . $default_image);
                
                // Если файл марки не существует, используем изображение по умолчанию
                $image_path = plugin_dir_path(dirname(__FILE__)) . 'assets/images/' . $default_image;
                if (!file_exists($image_path)) {
                    $image_url = esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/images/default_vehicle.png');
                }
            }
            ?>
            <img src="<?php echo $image_url; ?>" 
                 alt="<?php echo esc_attr($data['VehicleDetails']['VehicleIdentification']['DvlaMake'] ?? 'Vehicle'); ?> Image" 
                 class="vehicle-main-image" 
                 onerror="this.src='<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/images/default_vehicle.png'); ?>'" />
        </div>
        
        <div class="vehicle-info-section">
            <div class="vehicle-details">
                <h3 class="vehicle-title">Vehicle Make and Model</h3>
                <div class="vehicle-make"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['DvlaMake'] ?? 'N/A'); ?></div>
                <div class="vehicle-model"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['DvlaModel'] ?? 'N/A'); ?></div>
                
                <?php if (!empty($data['VehicleDetails']['VehicleIdentification']['DvlaBodyType'])): ?>
                <div class="vehicle-body-type">
                    <small><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['DvlaBodyType']); ?></small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="vehicle-registration-section">
            <div class="registration-info">
                <h4>Year</h4>
                <div class="year-value"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['YearOfManufacture'] ?? 'N/A'); ?></div>
                
                <h4>Vehicle Registration</h4>
                <div class="registration-value"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['Vrm'] ?? 'N/A'); ?></div>
                
                <?php if (!empty($data['VehicleDetails']['VehicleIdentification']['VinLast5'])): ?>
                <h4>VIN (Last 5)</h4>
                <div class="vin-value"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['VinLast5']); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Report Summary Block -->
    <?php 
    // Подключаем класс для генерации отчетов
    require_once plugin_dir_path(dirname(__FILE__)) . 'utils/class-summary-report-generator.php';
    
    // Генерируем отчет на основе данных
    $summary_report = VRM_Summary_Report_Generator::generate_summary_report($data);
    ?>
    
    <div class="report-summary-block">
        <div class="summary-header">
            <h3 class="summary-title title-gray">Report Summary</h3>
        </div>
        
        <div class="summary-content">
            <div class="summary-row">
                <div class="summary-label">Result</div>
                <div class="summary-value">
                    <span class="status-badge <?php echo esc_attr($summary_report['status_class']); ?>">
                        <?php echo esc_html($summary_report['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="summary-row">
                <div class="summary-label">Information</div>
                <div class="summary-value summary-info">
                    <?php echo esc_html($summary_report['summary_text']); ?>
                </div>
            </div>
            
            <?php if (!empty($summary_report['recommendations'])): ?>
            <div class="summary-row">
                <div class="summary-label">Recommendations</div>
                <div class="summary-value summary-recommendations">
                    <ul>
                        <?php foreach ($summary_report['recommendations'] as $recommendation): ?>
                        <li><?php echo esc_html($recommendation); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
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
                    <?php 
                    // Process all checks data using the utility class
                    require_once plugin_dir_path(__FILE__) . '../utils/class-all-checks-processor.php';
                    $checks_data = VRM_All_Checks_Processor::process_checks_data($data);
                    ?>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Imported</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($checks_data['imported']['class']); ?>" title="<?php echo esc_attr($checks_data['imported']['message']); ?>"><?php echo esc_html($checks_data['imported']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Exported</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($checks_data['exported']['class']); ?>" title="<?php echo esc_attr($checks_data['exported']['message']); ?>"><?php echo esc_html($checks_data['exported']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Scrapped</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($checks_data['scrapped']['class']); ?>" title="<?php echo esc_attr($checks_data['scrapped']['message']); ?>"><?php echo esc_html($checks_data['scrapped']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Unscrapped</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($checks_data['unscrapped']['class']); ?>" title="<?php echo esc_attr($checks_data['unscrapped']['message']); ?>"><?php echo esc_html($checks_data['unscrapped']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Safety Recalls</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($checks_data['safety_recalls']['class']); ?>" title="<?php echo esc_attr($checks_data['safety_recalls']['message']); ?>"><?php echo esc_html($checks_data['safety_recalls']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Previous Keepers</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="check-number" title="<?php echo esc_attr($checks_data['previous_keepers']['message']); ?>"><?php echo esc_html($checks_data['previous_keepers']['count']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Plate Changes</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="check-number" title="<?php echo esc_attr($checks_data['plate_changes']['message']); ?>"><?php echo esc_html($checks_data['plate_changes']['count']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">MOT</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($checks_data['mot']['class']); ?>" title="<?php echo esc_attr($checks_data['mot']['message']); ?>"><?php echo esc_html($checks_data['mot']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Road Tax</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($checks_data['road_tax']['class']); ?>" title="<?php echo esc_attr($checks_data['road_tax']['message']); ?>"><?php echo esc_html($checks_data['road_tax']['text']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="checks-column">
                    <?php 
                    // Process extended checks data using the utility class
                    $extended_checks = VRM_All_Checks_Processor::process_extended_checks_data($data);
                    ?>
                    
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Written off</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($extended_checks['written_off']['class']); ?>" title="<?php echo esc_attr($extended_checks['written_off']['message']); ?>"><?php echo esc_html($extended_checks['written_off']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Salvage History</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($extended_checks['salvage_history']['class']); ?>" title="<?php echo esc_attr($extended_checks['salvage_history']['message']); ?>"><?php echo esc_html($extended_checks['salvage_history']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Stolen</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($extended_checks['stolen']['class']); ?>" title="<?php echo esc_attr($extended_checks['stolen']['message']); ?>"><?php echo esc_html($extended_checks['stolen']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Colour Changes</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($extended_checks['colour_changes']['class']); ?>" title="<?php echo esc_attr($extended_checks['colour_changes']['message']); ?>"><?php echo esc_html($extended_checks['colour_changes']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Mileage Issues</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($extended_checks['mileage_issues']['class']); ?>" title="<?php echo esc_attr($extended_checks['mileage_issues']['message']); ?>"><?php echo esc_html($extended_checks['mileage_issues']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Ex-Taxi</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($extended_checks['ex_taxi']['class']); ?>" title="<?php echo esc_attr($extended_checks['ex_taxi']['message']); ?>"><?php echo esc_html($extended_checks['ex_taxi']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">VIN Check</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($extended_checks['vin_check']['class']); ?>" title="<?php echo esc_attr($extended_checks['vin_check']['message']); ?>"><?php echo esc_html($extended_checks['vin_check']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Outstanding Finance</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($extended_checks['outstanding_finance']['class']); ?>" title="<?php echo esc_attr($extended_checks['outstanding_finance']['message']); ?>"><?php echo esc_html($extended_checks['outstanding_finance']['text']); ?></span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Market Value</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge <?php echo esc_attr($extended_checks['market_value']['class']); ?>" title="<?php echo esc_attr($extended_checks['market_value']['message']); ?>"><?php echo esc_html($extended_checks['market_value']['text']); ?></span>
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
                        <div class="description-value"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['DvlaMake'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Model</div>
                        <div class="description-value"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['DvlaModel'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Colour</div>
                        <div class="description-value"><?php echo esc_html($data['VehicleDetails']['VehicleHistory']['ColourDetails']['CurrentColour'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Transmission</div>
                        <div class="description-value"><?php echo esc_html($data['ModelDetails']['Powertrain']['Transmission']['TransmissionType'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Year of manufacture</div>
                        <div class="description-value"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['YearOfManufacture'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Engine Size</div>
                        <div class="description-value"><?php 
                            $engine_size = $data['VehicleDetails']['DvlaTechnicalDetails']['EngineCapacityCc'] ?? '';
                            echo esc_html($engine_size ? $engine_size . ' cc' : 'Not Available'); 
                        ?></div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="description-column">
                    <div class="description-row">
                        <div class="description-label">Fuel Type</div>
                        <div class="description-value"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['DvlaFuelType'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">No. of Seats</div>
                        <div class="description-value"><?php echo esc_html($data['VehicleDetails']['DvlaTechnicalDetails']['NumberOfSeats'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Vehicle Type</div>
                        <div class="description-value"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['DvlaBodyType'] ?? 'Not Available'); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Engine Size (cc)</div>
                        <div class="description-value"><?php 
                            $engine_size = $data['VehicleDetails']['DvlaTechnicalDetails']['EngineCapacityCc'] ?? '';
                            echo esc_html($engine_size ? $engine_size . ' cc' : 'Not Available'); 
                        ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">BHP</div>
                        <div class="description-value"><?php 
                            $bhp = $data['ModelDetails']['Performance']['Power']['Bhp'] ?? '';
                            echo esc_html($bhp ? $bhp . ' BHP' : 'Not Available'); 
                        ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Vehicle Age</div>
                        <div class="description-value"><?php 
                            $year = $data['VehicleDetails']['VehicleIdentification']['YearOfManufacture'] ?? '';
                            if (!empty($year) && is_numeric($year)) {
                                $current_year = date('Y');
                                $vehicle_year = intval($year);
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
                        $vin = $data['VehicleDetails']['VehicleIdentification']['Vin'] ?? '';
                        if (!empty($vin) && strlen($vin) >= 4) {
                            echo esc_html(substr($vin, -4));
                        } else {
                            echo 'Not Available';
                        }
                    ?></div>
                </div>
                
                <div class="manual-check-row">
                    <div class="manual-check-label">Engine Number</div>
                    <div class="manual-check-value"><?php echo esc_html($data['VehicleDetails']['VehicleIdentification']['EngineNumber'] ?? 'Not Available'); ?></div>
                </div>
                
                <div class="manual-check-row">
                    <div class="manual-check-label">V5C logbook date</div>
                    <div class="manual-check-value"><?php echo esc_html($data['VehicleDetails']['VehicleHistory']['V5CDetails']['V5CIssueDate'] ?? 'Not Available'); ?></div>
                </div>
                
                <div class="vin-check-section">
                    <h4 class="vin-check-title">VIN check</h4>
                    <p class="vin-check-description">Enter this vehicle's 17 digit Vehicle Identification Number (VIN) in the field below to see if it matches our records.</p>
                    
                    <div class="vin-input-container">
                        <input type="text" class="vin-input" value="<?php 
                            $vin = $data['VehicleDetails']['VehicleIdentification']['Vin'] ?? '';
                            if (!empty($vin) && strlen($vin) >= 4) {
                                echo esc_attr(str_repeat('*', strlen($vin) - 4) . substr($vin, -4));
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
                    <div class="keeper-info-value"><?php 
                        // Получаем дату начала владения текущего владельца (первый элемент в списке)
                        $current_keeper_date = '';
                        if (isset($data['VehicleDetails']['VehicleHistory']['KeeperChangeList'][0]['KeeperStartDate'])) {
                            $current_keeper_date = $data['VehicleDetails']['VehicleHistory']['KeeperChangeList'][0]['KeeperStartDate'];
                            // Форматируем дату из ISO формата в читаемый вид
                            $date_obj = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $current_keeper_date);
                            if ($date_obj) {
                                echo esc_html($date_obj->format('Y-m-d'));
                            } else {
                                echo esc_html($current_keeper_date);
                            }
                        } else {
                            echo 'Not Available';
                        }
                    ?></div>
                </div>
                
                <div class="keeper-info-row">
                    <div class="keeper-info-label">Current Ownership</div>
                    <div class="keeper-info-value"><?php 
                        // Рассчитываем время владения текущего владельца
                        if (isset($data['VehicleDetails']['VehicleHistory']['KeeperChangeList'][0]['KeeperStartDate'])) {
                            $keeper_date_str = $data['VehicleDetails']['VehicleHistory']['KeeperChangeList'][0]['KeeperStartDate'];
                            $keeper_date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $keeper_date_str);
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
                    <div class="keeper-info-value"><?php 
                        // Используем DateFirstRegisteredInUk для даты регистрации
                        $reg_date = $data['VehicleDetails']['VehicleIdentification']['DateFirstRegisteredInUk'] ?? '';
                        if (!empty($reg_date)) {
                            $date_obj = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $reg_date);
                            if ($date_obj) {
                                echo esc_html($date_obj->format('Y-m-d'));
                            } else {
                                echo esc_html($reg_date);
                            }
                        } else {
                            echo 'Not Available';
                        }
                    ?></div>
                </div>
                
                <div class="keeper-info-row">
                    <div class="keeper-info-label">Prev. Keeper Acq.</div>
                    <div class="keeper-info-value"><?php 
                        // Получаем дату начала владения предыдущего владельца (второй элемент в списке)
                        if (isset($data['VehicleDetails']['VehicleHistory']['KeeperChangeList'][1]['KeeperStartDate'])) {
                            $prev_keeper_date = $data['VehicleDetails']['VehicleHistory']['KeeperChangeList'][1]['KeeperStartDate'];
                            $date_obj = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $prev_keeper_date);
                            if ($date_obj) {
                                echo esc_html($date_obj->format('Y-m-d'));
                            } else {
                                echo esc_html($prev_keeper_date);
                            }
                        } else {
                            echo 'Not Available';
                        }
                    ?></div>
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
                        // Получаем дату истечения MOT из MotHistoryDetails
                        $mot_expiry = '';
                        if (isset($data['MotHistoryDetails']['MotTestDetailsList'][0]['ExpiryDate'])) {
                            $expiry_date_str = $data['MotHistoryDetails']['MotTestDetailsList'][0]['ExpiryDate'];
                            if (!empty($expiry_date_str)) {
                                // Форматируем дату из ISO формата в читаемый вид
                                $date_obj = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $expiry_date_str);
                                if ($date_obj) {
                                    $mot_expiry = $date_obj->format('Y-m-d');
                                } else {
                                    $mot_expiry = $expiry_date_str;
                                }
                            } else {
                                $mot_expiry = 'N/A';
                            }
                        } else {
                            $mot_expiry = 'N/A';
                        }
                        echo esc_html($mot_expiry);
                        ?>
                    </div>
                </div>
                
                <div class="mot-row">
                    <div class="mot-label">Days Remaining</div>
                    <div class="mot-value">
                        <?php 
                        // Рассчитываем количество оставшихся дней до истечения MOT
                        $days_remaining = 'N/A';
                        if (isset($data['MotHistoryDetails']['MotTestDetailsList'][0]['ExpiryDate'])) {
                            $expiry_date_str = $data['MotHistoryDetails']['MotTestDetailsList'][0]['ExpiryDate'];
                            if (!empty($expiry_date_str)) {
                                $expiry_date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $expiry_date_str);
                                if ($expiry_date) {
                                    $current_date = new DateTime();
                                    $interval = $current_date->diff($expiry_date);
                                    
                                    // Проверяем, истек ли срок
                                    if ($expiry_date < $current_date) {
                                        $days_remaining = 'Expired ' . $interval->days . ' days ago';
                                    } else {
                                        $days_remaining = $interval->days . ' days';
                                    }
                                }
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
                    <div class="roadtax-value">
                        <?php 
                        // Извлекаем дату истечения налога из VehicleTaxDetails
                        if (isset($merged_data['VehicleTaxDetails']['TaxDueDate']) && !empty($merged_data['VehicleTaxDetails']['TaxDueDate'])) {
                            $tax_due_date = $merged_data['VehicleTaxDetails']['TaxDueDate'];
                            // Преобразуем из ISO формата в читаемый вид
                            $formatted_date = date('d M Y', strtotime($tax_due_date));
                            echo esc_html($formatted_date);
                        } else {
                            echo 'Not Available';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="roadtax-row">
                    <div class="roadtax-label">Days Remaining</div>
                    <div class="roadtax-value">
                        <?php 
                        // Отображаем оставшиеся дни до истечения налога
                        if (isset($merged_data['VehicleTaxDetails']['TaxDaysRemaining'])) {
                            $days_remaining = $merged_data['VehicleTaxDetails']['TaxDaysRemaining'];
                            if ($days_remaining <= 0) {
                                echo '<span style="color: #e74c3c;">Expired</span>';
                            } else {
                                echo esc_html($days_remaining . ' days');
                            }
                        } else {
                            echo 'Not Available';
                        }
                        ?>
                    </div>
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
                <div class="stolen-value <?php 
                    // Extract stolen status from PncDetails
                    $is_stolen = isset($data['PncDetails']['IsStolen']) ? $data['PncDetails']['IsStolen'] : false;
                    // Add CSS class based on stolen status
                    $status_class = $is_stolen ? 'status-badge status-yes' : 'status-badge status-no';
                    echo esc_attr($status_class);
                ?>">
                    <?php 
                    $stolen_status = $is_stolen ? 'Yes' : 'No';
                    echo esc_html($stolen_status);
                    ?>
                </div>
            </div>
            
            <div class="stolen-row">
                <div class="stolen-label">Date Reported</div>
                <div class="stolen-value">
                    <?php 
                    // Extract date reported stolen from PncDetails
                    $date_reported = isset($data['PncDetails']['DateReportedStolen']) ? $data['PncDetails']['DateReportedStolen'] : null;
                    if ($date_reported && $date_reported !== null) {
                        $formatted_date = date('d M Y', strtotime($date_reported));
                        echo esc_html($formatted_date);
                    } else {
                        echo esc_html('Not Reported');
                    }
                    ?>
                </div>
            </div>
            
            <div class="stolen-row">
                <div class="stolen-label">Police Force</div>
                <div class="stolen-value">
                    <?php 
                    // Extract police force name from PncDetails
                    $police_force = isset($data['PncDetails']['PoliceForceName']) ? $data['PncDetails']['PoliceForceName'] : null;
                    if ($police_force && $police_force !== null) {
                        echo esc_html($police_force);
                    } else {
                        echo esc_html('N/A');
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Written Off Block -->
    <div class="written-off-block">
        <?php 
        // Check WriteOffRecordList status for header styling
        $write_off_records = isset($data['MiaftrDetails']['WriteOffRecordList']) ? $data['MiaftrDetails']['WriteOffRecordList'] : array();
        $has_write_off_data = !empty($write_off_records);
        $header_style = $has_write_off_data ? 'background-color: #dc3545;' : 'background-color: #28a745;';
        ?>
        <div class="written-off-header" style="<?php echo esc_attr($header_style); ?>">
            <h3 class="written-off-title">Written Off</h3>
        </div>
        
        <div class="written-off-description">
            <p><em>Checks to verify if a vehicle has been involved in an insurance claim.</em></p>
        </div>
        
        <div class="written-off-content">
            <?php 
            // Check if vehicle is written off from VehicleStatus
            $is_scrapped = isset($data['VehicleDetails']['VehicleStatus']['IsScrapped']) ? $data['VehicleDetails']['VehicleStatus']['IsScrapped'] : false;
            $certificate_issued = isset($data['VehicleDetails']['VehicleStatus']['CertificateOfDestructionIssued']) ? $data['VehicleDetails']['VehicleStatus']['CertificateOfDestructionIssued'] : false;
            $is_written_off = $is_scrapped || $certificate_issued;
            
            // Get WriteOffRecordList from MiaftrDetails (already defined above for header styling)
            // $write_off_records and $has_write_off_data are already available
            
            // Get the first write-off record if available
            $write_off_record = $has_write_off_data ? $write_off_records[0] : null;
            
            if ($has_write_off_data) {
                // Display actual write-off data when records exist
                ?>
                <div class="written-off-row">
                    <div class="written-off-label">Written off Category</div>
                    <div class="written-off-value">
                        <?php 
                        if ($write_off_record && isset($write_off_record['Category'])) {
                            echo esc_html($write_off_record['Category']);
                        } else {
                            echo esc_html('Unknown');
                        }
                        ?>
                    </div>
                </div>
                
                <div class="written-off-row">
                    <div class="written-off-label">Loss Date</div>
                    <div class="written-off-value">
                        <?php 
                        if ($write_off_record && isset($write_off_record['LossDate'])) {
                            $loss_date = $write_off_record['LossDate'];
                            if ($loss_date && $loss_date !== null) {
                                $formatted_date = date('d M Y', strtotime($loss_date));
                                echo esc_html($formatted_date);
                            } else {
                                echo esc_html('Not Available');
                            }
                        } else {
                            echo esc_html('Not Available');
                        }
                        ?>
                    </div>
                </div>
                
                <div class="written-off-row">
                    <div class="written-off-label">Damage Areas</div>
                    <div class="written-off-value">
                        <?php 
                        if ($write_off_record && isset($write_off_record['DamageAreas']) && is_array($write_off_record['DamageAreas'])) {
                            $damage_areas = $write_off_record['DamageAreas'];
                            if (!empty($damage_areas)) {
                                $formatted_areas = array();
                                foreach ($damage_areas as $index => $area) {
                                    $letter = chr(97 + $index); // a, b, c, etc.
                                    $formatted_areas[] = $letter . ' . ' . esc_html($area);
                                }
                                echo implode('<br>', $formatted_areas);
                            } else {
                                echo esc_html('Not Specified');
                            }
                        } elseif ($write_off_record && isset($write_off_record['DamageArea'])) {
                            echo esc_html($write_off_record['DamageArea']);
                        } else {
                            echo esc_html('Not Available');
                        }
                        ?>
                    </div>
                </div>
                
                <div class="written-off-row">
                    <div class="written-off-label">Cause of Damage</div>
                    <div class="written-off-value">
                        <?php 
                        if ($write_off_record && isset($write_off_record['CauseOfDamage'])) {
                            echo esc_html($write_off_record['CauseOfDamage']);
                        } elseif ($write_off_record && isset($write_off_record['Cause'])) {
                            echo esc_html($write_off_record['Cause']);
                        } else {
                            echo esc_html('Not Available');
                        }
                        ?>
                    </div>
                </div>
                <?php 
            } else {
                // Display "No records found" message when WriteOffRecordList is empty
                ?>
                <div class="written-off-row">
                    <div class="written-off-label">Status</div>
                    <div class="written-off-value" style="color: #28a745; font-weight: bold;">
                        <?php echo esc_html('No Write-Off Records Found'); ?>
                    </div>
                </div>
                
                <div class="written-off-row">
                    <div class="written-off-label">Vehicle Status</div>
                    <div class="written-off-value">
                        <?php 
                        if ($is_written_off) {
                            echo esc_html('Vehicle may be scrapped or have certificate issued');
                        } else {
                            echo esc_html('Vehicle appears to be in good standing');
                        }
                        ?>
                    </div>
                </div>
                <?php 
            }
            ?>
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
                <div class="important-checks-value"><?php 
                    // Проверяем дату импорта
                    $date_imported = $data['VehicleDetails']['VehicleStatus']['DateImported'] ?? null;
                    $is_imported = $data['VehicleDetails']['VehicleStatus']['IsImported'] ?? false;
                    
                    if (!empty($date_imported)) {
                        $date_obj = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $date_imported);
                        if ($date_obj) {
                            echo esc_html($date_obj->format('d M Y'));
                        } else {
                            echo esc_html($date_imported);
                        }
                    } elseif ($is_imported) {
                        echo 'Imported (Date Unknown)';
                    } else {
                        echo 'Not Imported';
                    }
                ?></div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">Date Exported</div>
                <div class="important-checks-value"><?php 
                    // Проверяем дату экспорта
                    $date_exported = $data['VehicleDetails']['VehicleStatus']['DateExported'] ?? null;
                    $is_exported = $data['VehicleDetails']['VehicleStatus']['IsExported'] ?? false;
                    
                    if (!empty($date_exported)) {
                        $date_obj = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $date_exported);
                        if ($date_obj) {
                            echo esc_html($date_obj->format('d M Y'));
                        } else {
                            echo esc_html($date_exported);
                        }
                    } elseif ($is_exported) {
                        echo 'Exported (Date Unknown)';
                    } else {
                        echo 'Not Exported';
                    }
                ?></div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">Q Registration</div>
                <div class="important-checks-value"><?php 
                    // Проверяем Q регистрацию через VRM или другие индикаторы
                    $vrm = $data['VehicleDetails']['VehicleIdentification']['Vrm'] ?? '';
                    $is_q_registered = (strpos($vrm, 'Q') === 0); // Q регистрация начинается с Q
                    
                    echo $is_q_registered ? 'Q Registered' : 'Not Q Registered';
                ?></div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">VIC Inspected</div>
                <div class="important-checks-value"><?php 
                    // VIC инспекция обычно связана с импортированными автомобилями
                    $is_imported = $data['VehicleDetails']['VehicleStatus']['IsImported'] ?? false;
                    $is_imported_outside_eu = $data['VehicleDetails']['VehicleStatus']['IsImportedFromOutsideEu'] ?? false;
                    
                    // VIC требуется для автомобилей, импортированных из-за пределов ЕС
                    if ($is_imported_outside_eu) {
                        echo 'VIC Required/Inspected';
                    } elseif ($is_imported) {
                        echo 'VIC May Be Required';
                    } else {
                        echo 'Not VIC Inspected';
                    }
                ?></div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">Imported from Northern Ireland</div>
                <div class="important-checks-value"><?php 
                    $is_imported_from_ni = $data['VehicleDetails']['VehicleStatus']['IsImportedFromNi'] ?? false;
                    
                    echo $is_imported_from_ni ? 'Imported from NI' : 'Not Imported from NI';
                ?></div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">Date Scrapped</div>
                <div class="important-checks-value"><?php 
                    // Проверяем дату утилизации
                    $date_scrapped = $data['VehicleDetails']['VehicleStatus']['DateScrapped'] ?? null;
                    $is_scrapped = $data['VehicleDetails']['VehicleStatus']['IsScrapped'] ?? false;
                    
                    if (!empty($date_scrapped)) {
                        $date_obj = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $date_scrapped);
                        if ($date_obj) {
                            echo esc_html($date_obj->format('d M Y'));
                        } else {
                            echo esc_html($date_scrapped);
                        }
                    } elseif ($is_scrapped) {
                        echo 'Scrapped (Date Unknown)';
                    } else {
                        echo 'Not Scrapped';
                    }
                ?></div>
            </div>
            
            <div class="important-checks-row">
                <div class="important-checks-label">Unscrapped</div>
                <div class="important-checks-value"><?php 
                    // Проверяем статус восстановления после утилизации
                    $is_unscrapped = $data['VehicleDetails']['VehicleStatus']['IsUnscrapped'] ?? false;
                    
                    echo $is_unscrapped ? 'Unscrapped' : 'Not Unscrapped';
                ?></div>
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
            <?php
            // Получаем данные о финансах из API
            $finance_details = $data['FinanceDetails'] ?? [];
            $finance_record = $finance_details['FinanceRecordList'] ?? [];
            
            // Проверяем, есть ли данные о финансах
            if (!empty($finance_record) && is_array($finance_record)) {
                // Если FinanceRecordList содержит массив записей, берем первую
                if (isset($finance_record[0])) {
                    $finance_record = $finance_record[0];
                }
                
                // Форматируем дату соглашения
                $agreement_date = '';
                if (!empty($finance_record['AgreementDate'])) {
                    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $finance_record['AgreementDate']);
                    if ($date) {
                        $agreement_date = $date->format('j F Y');
                    } else {
                        $agreement_date = esc_html($finance_record['AgreementDate']);
                    }
                }
                ?>
                <div class="outstanding-finance-row">
                    <span class="outstanding-finance-label">Agreement Date</span>
                    <span class="outstanding-finance-value"><?php echo $agreement_date ?: 'Not Available'; ?></span>
                </div>
                <div class="outstanding-finance-row">
                    <span class="outstanding-finance-label">Agreement Number</span>
                    <span class="outstanding-finance-value"><?php echo esc_html($finance_record['AgreementNumber'] ?? 'Not Available'); ?></span>
                </div>
                <div class="outstanding-finance-row">
                    <span class="outstanding-finance-label">Agreement Term</span>
                    <span class="outstanding-finance-value"><?php echo esc_html($finance_record['AgreementTerm'] ?? 'Not Available'); ?></span>
                </div>
                <div class="outstanding-finance-row">
                    <span class="outstanding-finance-label">Agreement Type</span>
                    <span class="outstanding-finance-value"><?php echo esc_html($finance_record['AgreementType'] ?? 'Not Available'); ?></span>
                </div>
                <div class="outstanding-finance-row">
                    <span class="outstanding-finance-label">Contact Number</span>
                    <span class="outstanding-finance-value"><?php echo esc_html($finance_record['ContactNumber'] ?? 'Not Available'); ?></span>
                </div>
                <div class="outstanding-finance-row">
                    <span class="outstanding-finance-label">Date Of Transaction</span>
                    <span class="outstanding-finance-value"><?php echo $agreement_date ?: 'Not Available'; ?></span>
                </div>
                <div class="outstanding-finance-row">
                    <span class="outstanding-finance-label">Finance Company</span>
                    <span class="outstanding-finance-value"><?php echo esc_html($finance_record['FinanceCompany'] ?? 'Not Available'); ?></span>
                </div>
                <div class="outstanding-finance-row">
                    <span class="outstanding-finance-label">Vehicle Description</span>
                    <span class="outstanding-finance-value"><?php echo esc_html($finance_record['VehicleDescription'] ?? 'Not Available'); ?></span>
                </div>
                <?php
            } else {
                // Если данных о финансах нет, показываем сообщение
                ?>
                <div class="outstanding-finance-row">
                    <span class="outstanding-finance-label">Status</span>
                    <span class="outstanding-finance-value">No Outstanding Finance Found</span>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <!-- Ownership Block -->
    <?php
    // Получаем данные о владельцах из API
    $keeper_change_list = $data['VehicleDetails']['VehicleHistory']['KeeperChangeList'] ?? [];
    $vehicle_identification = $data['VehicleDetails']['VehicleIdentification'] ?? [];
    
    // Инициализируем переменные
    $previous_keepers = 'Not Available';
    $current_keeper_acq = 'Not Available';
    $prev_keeper_sold = 'Not Available';
    $prev_keeper_acq = 'Not Available';
    $current_ownership = 'Not Available';
    $vehicle_age = 'Not Available';
    $registration_date = 'Not Available';
    
    // Обрабатываем данные о владельцах в зависимости от структуры
    if (!empty($keeper_change_list)) {
        $current_keeper = null;
        $previous_keeper = null;
        
        // Случай 1: KeeperChangeList - это объект с данными текущего владельца
        if (isset($keeper_change_list['NumberOfPreviousKeepers'])) {
            $current_keeper = $keeper_change_list;
        }
        // Случай 2: KeeperChangeList - это массив объектов
        elseif (is_array($keeper_change_list) && !empty($keeper_change_list)) {
            // Берем первый элемент как текущего владельца
            $current_keeper = $keeper_change_list[0];
            // Если есть второй элемент, это предыдущий владелец
            if (isset($keeper_change_list[1])) {
                $previous_keeper = $keeper_change_list[1];
            }
        }
        
        // Обрабатываем данные текущего владельца
        if ($current_keeper) {
            // Количество предыдущих владельцев
            $previous_keepers = $current_keeper['NumberOfPreviousKeepers'] ?? 'Not Available';
            
            // Дата приобретения текущим владельцем
            if (!empty($current_keeper['KeeperStartDate'])) {
                $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $current_keeper['KeeperStartDate']);
                if ($date) {
                    $current_keeper_acq = $date->format('j F Y');
                } else {
                    $current_keeper_acq = esc_html($current_keeper['KeeperStartDate']);
                }
            }
            
            // Дата продажи предыдущим владельцем (из данных текущего владельца)
            if (!empty($current_keeper['PreviousKeeperDisposalDate'])) {
                $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $current_keeper['PreviousKeeperDisposalDate']);
                if ($date) {
                    $prev_keeper_sold = $date->format('j F Y');
                } else {
                    $prev_keeper_sold = esc_html($current_keeper['PreviousKeeperDisposalDate']);
                }
            }
            
            // Вычисляем текущий период владения
            if (!empty($current_keeper['KeeperStartDate'])) {
                $start_date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $current_keeper['KeeperStartDate']);
                if ($start_date) {
                    $now = new DateTime();
                    $interval = $start_date->diff($now);
                    $years = $interval->y;
                    $months = $interval->m;
                    
                    if ($years > 0 && $months > 0) {
                        $current_ownership = $years . ' years ' . $months . ' months';
                    } elseif ($years > 0) {
                        $current_ownership = $years . ' years';
                    } elseif ($months > 0) {
                        $current_ownership = $months . ' months';
                    } else {
                        $current_ownership = 'Less than 1 month';
                    }
                }
            }
        }
        
        // Обрабатываем данные предыдущего владельца (если есть)
        if ($previous_keeper && !empty($previous_keeper['KeeperStartDate'])) {
            $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $previous_keeper['KeeperStartDate']);
            if ($date) {
                $prev_keeper_acq = $date->format('j F Y');
            } else {
                $prev_keeper_acq = esc_html($previous_keeper['KeeperStartDate']);
            }
        }
    }
    
    // Обрабатываем данные о регистрации автомобиля
    if (!empty($vehicle_identification['DateOfFirstRegistration'])) {
        $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $vehicle_identification['DateOfFirstRegistration']);
        if ($date) {
            $registration_date = $date->format('j F Y');
        } else {
            $registration_date = esc_html($vehicle_identification['DateOfFirstRegistration']);
        }
    }
    
    // Вычисляем возраст автомобиля
    if (!empty($vehicle_identification['DateOfFirstRegistration'])) {
        $reg_date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $vehicle_identification['DateOfFirstRegistration']);
        if ($reg_date) {
            $now = new DateTime();
            $interval = $reg_date->diff($now);
            $years = $interval->y;
            $months = $interval->m;
            
            if ($years > 0 && $months > 0) {
                $vehicle_age = $years . ' years ' . $months . ' months';
            } elseif ($years > 0) {
                $vehicle_age = $years . ' years';
            } elseif ($months > 0) {
                $vehicle_age = $months . ' months';
            } else {
                $vehicle_age = 'Less than 1 month';
            }
        }
    }
    ?>
    <div class="ownership-block">
        <div class="ownership-header">
            <h3 class="ownership-title title-gray">Ownership</h3>
        </div>
        <div class="ownership-content">
            <div class="ownership-row">
                <span class="ownership-label">No. Previous Keepers</span>
                <span class="ownership-value"><?php echo esc_html($previous_keepers); ?></span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Current Keeper Acq.</span>
                <span class="ownership-value"><?php echo $current_keeper_acq ?: 'Not Available'; ?></span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Current Ownership</span>
                <span class="ownership-value"><?php echo esc_html($current_ownership); ?></span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Prev. Keeper Sold</span>
                <span class="ownership-value"><?php echo $prev_keeper_sold ?: 'Not Available'; ?></span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Prev. Keeper Acq.</span>
                <span class="ownership-value"><?php echo $prev_keeper_acq ?: 'Not Available'; ?></span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Vehicle Age</span>
                <span class="ownership-value"><?php echo esc_html($vehicle_age); ?></span>
            </div>
            <div class="ownership-row">
                <span class="ownership-label">Registration</span>
                <span class="ownership-value"><?php echo $registration_date ?: 'Not Available'; ?></span>
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
                <?php
                // Создаем массив владельцев для отображения истории
                $keepers_history = [];
                
                if (!empty($keeper_change_list)) {
                    // Если KeeperChangeList - это объект с данными текущего владельца
                    if (isset($keeper_change_list['NumberOfPreviousKeepers'])) {
                        $keepers_history[] = $keeper_change_list;
                    }
                    // Если KeeperChangeList - это массив объектов
                    elseif (is_array($keeper_change_list) && !empty($keeper_change_list)) {
                        $keepers_history = $keeper_change_list;
                    }
                }
                
                if (!empty($keepers_history)) {
                     $total_keepers = count($keepers_history);
                     
                     // Переворачиваем массив, чтобы текущий владелец был последним
                     $reversed_keepers = array_reverse($keepers_history);
                     
                     foreach ($reversed_keepers as $index => $keeper) {
                         $keeper_number = $index + 1;
                         $is_current = ($index === ($total_keepers - 1)); // Последний элемент - текущий владелец
                         $keeper_label = $is_current ? $keeper_number . ' (Current)' : $keeper_number;
                        
                        // Дата приобретения
                        $acquired_date = 'Not Available';
                        if (!empty($keeper['KeeperStartDate'])) {
                            $date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $keeper['KeeperStartDate']);
                            if ($date) {
                                $acquired_date = $date->format('j F Y');
                            } else {
                                $acquired_date = esc_html($keeper['KeeperStartDate']);
                            }
                        }
                        
                        // Продолжительность владения
                        $ownership_duration = 'Not Available';
                        if (!empty($keeper['KeeperStartDate'])) {
                            $start_date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $keeper['KeeperStartDate']);
                            if ($start_date) {
                                $end_date = new DateTime(); // Для текущего владельца - сегодня
                                
                                // Для предыдущих владельцев ищем дату окончания
                                if (!$is_current && !empty($keeper['KeeperEndDate'])) {
                                    $end_date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $keeper['KeeperEndDate']);
                                } elseif (!$is_current && !empty($keeper['PreviousKeeperDisposalDate'])) {
                                    $end_date = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $keeper['PreviousKeeperDisposalDate']);
                                }
                                
                                if ($end_date) {
                                    $interval = $start_date->diff($end_date);
                                    $years = $interval->y;
                                    $months = $interval->m;
                                    $days = $interval->d;
                                    
                                    $duration_parts = [];
                                    if ($years > 0) {
                                        $duration_parts[] = $years . ' year' . ($years > 1 ? 's' : '');
                                    }
                                    if ($months > 0) {
                                        $duration_parts[] = $months . ' month' . ($months > 1 ? 's' : '');
                                    }
                                    if ($days > 0 && $years == 0) {
                                        $duration_parts[] = $days . ' day' . ($days > 1 ? 's' : '');
                                    }
                                    
                                    if (!empty($duration_parts)) {
                                        $ownership_duration = implode(' and ', $duration_parts);
                                    } else {
                                        $ownership_duration = 'Less than 1 day';
                                    }
                                }
                            }
                        }
                        
                        echo '<div class="keeper-history-table-row">';
                        echo '<div class="keeper-history-table-cell">' . esc_html($keeper_label) . '</div>';
                        echo '<div class="keeper-history-table-cell">' . esc_html($acquired_date) . '</div>';
                        echo '<div class="keeper-history-table-cell">' . esc_html($ownership_duration) . '</div>';
                        echo '</div>';
                        
                        $keeper_number++;
                    }
                } else {
                    // Если данных нет, показываем сообщение
                    echo '<div class="keeper-history-table-row">';
                    echo '<div class="keeper-history-table-cell" colspan="3">No keeper history data available</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <?php
    // Обработка данных ValuationDetails для блока Market Value
    $valuation_data = null;
    $vehicle_description = 'N/A';
    $valuation_mileage = 'N/A';
    $trade_value_range = 'N/A';
    $private_value_range = 'N/A';
    
    if (isset($data['ValuationDetails'])) {
        $valuation_data = $data['ValuationDetails'];
        
        // Описание автомобиля
        if (!empty($valuation_data['VehicleDescription'])) {
            $vehicle_description = esc_html($valuation_data['VehicleDescription']);
        }
        
        // Пробег для валюации
        if (!empty($valuation_data['ValuationMileage'])) {
            $valuation_mileage = number_format($valuation_data['ValuationMileage']) . ' miles';
        }
        
        // Торговая стоимость (диапазон)
        if (!empty($valuation_data['ValuationFigures'])) {
            $figures = $valuation_data['ValuationFigures'];
            
            // Торговая стоимость: TradeAverage и TradeRetail
            if (isset($figures['TradeAverage']) && isset($figures['TradeRetail'])) {
                $trade_min = min($figures['TradeAverage'], $figures['TradeRetail']);
                $trade_max = max($figures['TradeAverage'], $figures['TradeRetail']);
                $trade_value_range = '£' . number_format($trade_min) . ' - £' . number_format($trade_max);
            }
            
            // Частная стоимость: PrivateAverage и PrivateClean
            if (isset($figures['PrivateAverage']) && isset($figures['PrivateClean'])) {
                $private_min = min($figures['PrivateAverage'], $figures['PrivateClean']);
                $private_max = max($figures['PrivateAverage'], $figures['PrivateClean']);
                $private_value_range = '£' . number_format($private_min) . ' - £' . number_format($private_max);
            }
        }
    }
    ?>

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
                <span class="market-value-value"><?php echo $vehicle_description; ?></span>
            </div>
            <div class="market-value-row">
                <span class="market-value-label">Based on Mileage</span>
                <span class="market-value-value"><?php echo $valuation_mileage; ?></span>
            </div>
            <div class="market-value-row">
                <span class="market-value-label">Average Trade Value</span>
                <span class="market-value-value"><?php echo $trade_value_range; ?></span>
            </div>
            <div class="market-value-row">
                <span class="market-value-label">Average Private Value</span>
                <span class="market-value-value"><?php echo $private_value_range; ?></span>
            </div>
        </div>
    </div>

    <?php
    // Обработка данных PlateChangeList для блока Plate Change History
    $plate_changes = [];
    $has_plate_changes = false;
    
    // Проверяем наличие данных PlateChangeList в VehicleDetails -> VehicleHistory
    if (isset($data['VehicleDetails']['VehicleHistory']['PlateChangeList'])) {
        $plate_change_data = $data['VehicleDetails']['VehicleHistory']['PlateChangeList'];
        
        // Обрабатываем различные форматы данных
        if (is_array($plate_change_data) && !empty($plate_change_data)) {
            // Если это массив с данными
            $plate_changes = $plate_change_data;
            $has_plate_changes = true;
        } elseif (is_object($plate_change_data)) {
            // Если это объект, преобразуем в массив
            $plate_changes = [$plate_change_data];
            $has_plate_changes = true;
        }
        // Если пустой массив или null - $has_plate_changes остается false
    }
    ?>

    <!-- Plate Change History Block -->
    <div class="plate-change-history-block">
        <div class="plate-change-history-header">
            <h3 class="plate-change-history-title title-gray">Plate Change History</h3>
        </div>
        <div class="plate-change-history-content">
            <?php if ($has_plate_changes): ?>
                <?php foreach ($plate_changes as $index => $plate_change): ?>
                    <?php
                    // Извлекаем данные о смене номерных знаков
                    $registration = isset($plate_change['Registration']) ? esc_html($plate_change['Registration']) : 'N/A';
                    $transfer_date = 'N/A';
                    
                    // Обрабатываем дату передачи
                    if (isset($plate_change['TransferOffDate']) && !empty($plate_change['TransferOffDate'])) {
                        $transfer_date_obj = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $plate_change['TransferOffDate']);
                        if ($transfer_date_obj) {
                            $transfer_date = 'Until ' . $transfer_date_obj->format('j F Y');
                        }
                    } elseif (isset($plate_change['TransferOnDate']) && !empty($plate_change['TransferOnDate'])) {
                        $transfer_date_obj = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $plate_change['TransferOnDate']);
                        if ($transfer_date_obj) {
                            $transfer_date = 'From ' . $transfer_date_obj->format('j F Y');
                        }
                    }
                    ?>
                    <div class="plate-change-history-row">
                        <span class="plate-change-history-label">Registration</span>
                        <span class="plate-change-history-value"><?php echo $registration; ?></span>
                    </div>
                    <div class="plate-change-history-row">
                        <span class="plate-change-history-label">Transfer</span>
                        <span class="plate-change-history-value"><?php echo $transfer_date; ?></span>
                    </div>
                    <?php if ($index < count($plate_changes) - 1): ?>
                        <hr class="plate-change-separator">
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="plate-change-history-row">
                    <span class="plate-change-history-value" style="text-align: center; width: 100%; color: #666;">No plate change history available</span>
                </div>
            <?php endif; ?>
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