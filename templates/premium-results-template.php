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
            <div class="mileage-information-row">
                <span class="mileage-information-label">Mileage Issues</span>
                <span class="mileage-information-value mileage-issues-red">The odometer reading reduced by 886 miles between 01/08/2022 and 02/09/2022.</span>
            </div>
            <div class="mileage-information-row">
                <span class="mileage-information-label">Average Mileage for year</span>
                <span class="mileage-information-value">27300</span>
            </div>
            <div class="mileage-information-row">
                <span class="mileage-information-label">Mileage Average</span>
                <span class="mileage-information-value">above average</span>
            </div>
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
                <div class="mot-history-row">
                    <div class="mot-date-cell">
                        <div class="mot-date">03/01/2023</div>
                    </div>
                    <div class="mot-result-cell">
                        <span class="mot-result-badge pass">Pass</span>
                    </div>
                    <div class="mot-mileage-cell">
                        <div class="mot-mileage-label">Mileage :</div>
                        <div class="mot-mileage-value">49413 miles</div>
                        <div class="mot-advisory-notices">
                            <div class="advisory-title">Advisory Notices</div>
                            <div class="advisory-item">
                                • Nearside rear tyre worn close to legal limit (4.1.e.i (e))
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mot-history-row">
                    <div class="mot-date-cell">
                        <div class="mot-date">06/01/2022</div>
                    </div>
                    <div class="mot-result-cell">
                        <span class="mot-result-badge pass">Pass</span>
                    </div>
                    <div class="mot-mileage-cell">
                        <div class="mot-mileage-label">Mileage :</div>
                        <div class="mot-mileage-value">39454 miles</div>
                    </div>
                </div>
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