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
            <img src="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'assets/images/' . esc_attr($data['image'])); ?>" 
                 alt="Vehicle Image" 
                 class="vehicle-main-image" />
        </div>
        
        <div class="vehicle-info-section">
            <div class="report-result">
                <div class="result-status warning">
                    <span class="status-text">WARNING</span>
                </div>
            </div>
            
            <div class="vehicle-details">
                <h3 class="vehicle-title">Vehicle Make and Model</h3>
                <div class="vehicle-make"><?php echo esc_html($data['make']); ?></div>
                <div class="vehicle-model"><?php echo esc_html($data['model']); ?></div>
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
                    <span class="status-badge status-fail">FAIL</span>
                </div>
            </div>
            
            <div class="summary-row">
                <div class="summary-label">Information</div>
                <div class="summary-value summary-info">
                    Report failed, proceed with caution
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
                            <span class="status-badge status-no">No</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Exported</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-no">No</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Scrapped</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-no">No</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Unscrapped</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-no">No</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Safety Recalls</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-no">No</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Previous Keepers</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="check-number">2</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Plate Changes</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="check-number">1</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">MOT</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-valid">Valid</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Road Tax</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-valid">Valid</span>
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
                            <span class="status-badge status-fail">Fail</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Salvage History</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-fail">Fail</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Stolen</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-no">No</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Colour Changes</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-no">No</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Mileage Issues</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-yes">Yes</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Ex-Taxi</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-no">No</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">VIN Check</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-pass">Pass</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Outstanding Finance</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-fail">Fail</span>
                        </div>
                    </div>
                    
                    <div class="check-row">
                        <div class="check-label">
                            <span class="check-name">Market Value</span>
                            <span class="check-info">ⓘ</span>
                        </div>
                        <div class="check-status">
                            <span class="status-badge status-yes">Yes</span>
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
                        <div class="description-value">Black</div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Transmission</div>
                        <div class="description-value">Semi Automatic</div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Year of manufacture</div>
                        <div class="description-value"><?php echo esc_html($data['year']); ?></div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Engine Size</div>
                        <div class="description-value">3.0 litres</div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="description-column">
                    <div class="description-row">
                        <div class="description-label">Fuel Type</div>
                        <div class="description-value">Diesel</div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">No. of Seats</div>
                        <div class="description-value">4 Seats</div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Vehicle Type</div>
                        <div class="description-value">Car</div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Engine Size (cc)</div>
                        <div class="description-value">2996 cc</div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">BHP</div>
                        <div class="description-value">385 BHP</div>
                    </div>
                    
                    <div class="description-row">
                        <div class="description-label">Vehicle Age</div>
                        <div class="description-value">5 years 11 months</div>
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
                    <div class="manual-check-value">8495</div>
                </div>
                
                <div class="manual-check-row">
                    <div class="manual-check-label">Engine Number</div>
                    <div class="manual-check-value">65192133O101</div>
                </div>
                
                <div class="manual-check-row">
                    <div class="manual-check-label">V5C logbook date</div>
                    <div class="manual-check-value">06 September 2022</div>
                </div>
                
                <div class="vin-check-section">
                    <h4 class="vin-check-title">VIN check</h4>
                    <p class="vin-check-description">Enter this vehicle's 17 digit Vehicle Identification Number (VIN) in the field below to see if it matches our records.</p>
                    
                    <div class="vin-input-container">
                        <input type="text" class="vin-input" value="*************8495" readonly>
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
                    <div class="keeper-info-value">13 August 2022</div>
                </div>
                
                <div class="keeper-info-row">
                    <div class="keeper-info-label">Current Ownership</div>
                    <div class="keeper-info-value">0 years 4 months</div>
                </div>
                
                <div class="keeper-info-row">
                    <div class="keeper-info-label">Registered Date</div>
                    <div class="keeper-info-value">17 January 2017</div>
                </div>
                
                <div class="keeper-info-row">
                    <div class="keeper-info-label">Prev. Keeper Acq.</div>
                    <div class="keeper-info-value">01 February 2022</div>
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
                    <div class="mot-value">19 Jun 2023</div>
                </div>
                
                <div class="mot-row">
                    <div class="mot-label">Days Remaining</div>
                    <div class="mot-value">XXX days</div>
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

</div>