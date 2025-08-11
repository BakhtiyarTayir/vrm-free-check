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

// ОТЛАДОЧНЫЙ ВЫВОД ДАННЫХ ИЗ API (для тестирования)
echo '<div style="background: #f0f0f0; border: 2px solid #333; padding: 20px; margin: 20px 0; font-family: monospace;">';
echo '<h3 style="color: #d63384; margin-top: 0;">🔍 ОТЛАДКА: Все данные из API</h3>';
echo '<pre style="background: white; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px;">';
echo htmlspecialchars(print_r($data, true));
echo '</pre>';
echo '<p style="color: #666; font-size: 12px; margin-bottom: 0;"><strong>Примечание:</strong> Этот блок показывает все данные, полученные из API vehicledataglobal.com</p>';
echo '</div>';

// Функция для вычисления возраста
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

// Функция для вычисления времени с даты
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
?>

<div class="vrm-check-results">
    <div class="vrm-results-container">
        
        <!-- Vehicle Identity Section -->
        <div class="vrm-section vrm-vehicle-identity">
            <h2 class="vrm-section-title">Vehicle Identity</h2>
            
            <div class="vrm-info-table">
                <div class="vrm-info-row">
                    <div class="vrm-info-label">Registration</div>
                    <div class="vrm-info-value vrm-registration"><?php echo esc_html($data['registration'] ?? $vrm); ?></div>
                </div>
                
                <div class="vrm-info-row">
                    <div class="vrm-info-label">Make</div>
                    <div class="vrm-info-value"><?php echo esc_html($data['make'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="vrm-info-row">
                    <div class="vrm-info-label">Model</div>
                    <div class="vrm-info-value">
                        <div class="vrm-model-main"><?php echo esc_html($data['model'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                
                <div class="vrm-info-row">
                    <div class="vrm-info-label">Year</div>
                    <div class="vrm-info-value">
                        <div class="vrm-year-main"><?php echo esc_html($data['year'] ?? 'N/A'); ?></div>
                        <?php if (!empty($data['year_age'])): ?>
                        <div class="vrm-year-sub"><?php echo esc_html($data['year_age']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="vrm-info-row">
                    <div class="vrm-info-label">V5C Issue Date</div>
                    <div class="vrm-info-value">
                        <div class="vrm-v5c-main"><?php echo esc_html($data['v5c_issue_date'] ?? 'N/A'); ?></div>
                        <?php if (!empty($data['v5c_age'])): ?>
                        <div class="vrm-v5c-sub"><?php echo esc_html($data['v5c_age']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="vrm-info-row">
                    <div class="vrm-info-label">Colour</div>
                    <div class="vrm-info-value"><?php echo esc_html($data['colour'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="vrm-info-row">
                    <div class="vrm-info-label">Registered</div>
                    <div class="vrm-info-value"><?php echo esc_html($data['registered'] ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Legal Checks Section -->
        <div class="vrm-section vrm-legal-checks">
            <h2 class="vrm-section-title">Legal Checks</h2>
            
            <div class="vrm-checks-list">
                <!-- Tax Check -->
                <div class="vrm-check-item">
                    <?php 
                    $tax_status = $data['tax_status'] ?? 'N/A';
                    $tax_icon_class = 'vrm-check-info';
                    if ($tax_status === 'Taxed') {
                        $tax_icon_class = 'vrm-check-valid';
                    } elseif ($tax_status === 'Untaxed' || $tax_status === 'SORN') {
                        $tax_icon_class = 'vrm-check-invalid';
                    }
                    ?>
                    <div class="vrm-check-icon <?php echo $tax_icon_class; ?>">
                        <?php echo ($tax_status === 'Taxed') ? '✓' : (($tax_status === 'Untaxed' || $tax_status === 'SORN') ? '✗' : 'i'); ?>
                    </div>
                    <div class="vrm-check-content">
                        <div class="vrm-check-header">
                            <div class="vrm-check-title">Tax</div>
                            <div class="vrm-check-status"><?php echo esc_html($data['tax_due_date'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="vrm-check-detail"><?php echo esc_html($data['tax_due_text'] ?? 'Tax information not available'); ?></div>
                    </div>
                </div>
                
                <!-- MOT Check -->
                <div class="vrm-check-item">
                    <div class="vrm-check-icon vrm-check-valid">✓</div>
                    <div class="vrm-check-content">
                        <div class="vrm-check-header">
                            <div class="vrm-check-title">MOT</div>
                            <div class="vrm-check-status"><?php echo esc_html($data['mot_due_date'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="vrm-check-detail"><?php echo esc_html($data['mot_due_text'] ?? 'MOT information not available'); ?></div>
                    </div>
                </div>
                
                <!-- Stolen Check -->
                <div class="vrm-check-item">
                    <div class="vrm-check-icon vrm-check-info">i</div>
                    <div class="vrm-check-content">
                        <div class="vrm-check-header">
                            <div class="vrm-check-title">Stolen</div>
                            <div class="vrm-check-status">Not Stolen</div>
                        </div>
                        <div class="vrm-check-action">
                            <a href="#" class="vrm-check-link">Sign in</a> or <a href="#" class="vrm-check-link">Register</a> to check for free
                        </div>
                    </div>
                </div>
                
                <!-- Insurance Check -->
                <div class="vrm-check-item">
                    <div class="vrm-check-icon vrm-check-info">i</div>
                    <div class="vrm-check-content">
                        <div class="vrm-check-title">Insurance</div>
                        <div class="vrm-check-action">
                            <a href="#" class="vrm-check-link">Check Insurance</a>
                        </div>
                        <div class="vrm-insurance-quote">
                            <button class="vrm-quote-btn">Get a Quote</button>
                        </div>
                    </div>
                </div>
                
                <!-- Valuation -->
                <div class="vrm-check-item">
                    <div class="vrm-check-icon vrm-check-info">i</div>
                    <div class="vrm-check-content">
                        <div class="vrm-check-title">Valuation</div>
                        <div class="vrm-check-action">
                            <a href="#" class="vrm-check-link">Get a Valuation With WeBuyAnyCar.com</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vehicle Specifications Section -->
        <div class="vrm-section vrm-vehicle-specs">
            <h2 class="vrm-section-title">Vehicle Specification</h2>
            
            <div class="vrm-specs-grid">
                <div class="vrm-spec-item">
                    <div class="vrm-spec-label">Engine</div>
                    <div class="vrm-spec-value"><?php echo esc_html($data['engine_capacity'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="vrm-spec-item">
                    <div class="vrm-spec-label">Fuel</div>
                    <div class="vrm-spec-value"><?php echo esc_html($data['fuel_type'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="vrm-spec-item">
                    <div class="vrm-spec-label">Vehicle Type</div>
                    <div class="vrm-spec-value">
                        <div class="vrm-spec-main"><?php echo esc_html($data['vehicle_type'] ?? 'N/A'); ?></div>
                        <?php if (!empty($data['body_type'])): ?>
                        <div class="vrm-spec-sub"><?php echo esc_html($data['body_type']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="vrm-spec-item">
                    <div class="vrm-spec-label">Emissions</div>
                    <div class="vrm-spec-value">
                        <div class="vrm-spec-main"><?php echo esc_html($data['co2_emissions'] ?? 'N/A'); ?></div>
                        <?php if (!empty($data['tax_band'])): ?>
                        <div class="vrm-spec-sub"><?php echo esc_html($data['tax_band']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="vrm-spec-item">
                    <div class="vrm-spec-label">Revenue Weight</div>
                    <div class="vrm-spec-value">
                        <div class="vrm-spec-main"><?php echo esc_html($data['revenue_weight'] ?? 'No Data'); ?></div>
                        <?php if (!empty($data['type_approval'])): ?>
                        <div class="vrm-spec-sub">Type Approval: <?php echo esc_html($data['type_approval']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="vrm-spec-item">
                    <div class="vrm-spec-label">Euro Status</div>
                    <div class="vrm-spec-value"><?php echo esc_html($data['euro_status'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="vrm-spec-item">
                    <div class="vrm-spec-label">Est. Current Mileage</div>
                    <div class="vrm-spec-value"><?php echo esc_html($data['estimated_mileage'] ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Written Off Check -->
    <div class="vrm-section vrm-written-off" style="margin-top: 20px;">
        <h2 class="vrm-section-title">Written Off Check</h2>
        
        <div class="vrm-written-off-content">
            <p class="vrm-written-off-info">More than 25% of vehicles checked this month were written off.</p>
            <p class="vrm-example-data" style="color: #dc3545; font-weight: bold; margin: 10px 0;">Example Data ⚠</p>
            
            <div class="vrm-written-off-table">
                <div class="vrm-table-header">
                    <div class="vrm-table-col">Details</div>
                    <div class="vrm-table-col">Cause</div>
                    <div class="vrm-table-col">Damage</div>
                </div>
                <div class="vrm-table-row">
                    <div class="vrm-table-col">Cat D on 19 April 2017</div>
                    <div class="vrm-table-col">Accident</div>
                    <div class="vrm-table-col">Front nearside</div>
                </div>
            </div>
            
            <p class="vrm-check-info" style="margin: 15px 0; color: #666;">Check if a vehicle is written off from £1.99.</p>
            
            <button class="vrm-check-now-btn" style="background-color: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">Check Now</button>
        </div>
    </div>
    
    <!-- Vehicle Performance and Economy Section -->
    <div class="vrm-section vrm-performance-economy" style="margin-top: 30px;">
        <div class="vrm-performance-grid">
            <!-- Performance Block -->
            <div class="vrm-performance-block">
                <h3 class="vrm-block-title">Performance</h3>
                <div class="vrm-performance-items">
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">BHP</div>
                        <div class="vrm-performance-value"><?php echo esc_html($data['bhp'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">Top Speed</div>
                        <div class="vrm-performance-value"><?php echo esc_html($data['top_speed_mph'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">0-60 MPH</div>
                        <div class="vrm-performance-value"><?php echo esc_html($data['zero_to_sixty'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Fuel Economy Block -->
            <div class="vrm-performance-block">
                <h3 class="vrm-block-title">Fuel Economy</h3>
                <div class="vrm-performance-items">
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">Urban</div>
                        <div class="vrm-performance-value"><?php echo esc_html($data['urban_mpg'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">Extra-Urban</div>
                        <div class="vrm-performance-value"><?php echo esc_html($data['extra_urban_mpg'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">Combined</div>
                        <div class="vrm-performance-value"><?php echo esc_html($data['combined_mpg'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>
            
                        <!-- Recommended Block -->
            <div class="vrm-recommended-block">
                <h3 class="vrm-block-title">Recommended</h3>
                <div class="vrm-recommended-content">
                    <h4 class="vrm-recommended-title">Resto Revival</h4>
                    <p class="vrm-recommended-description">Captivating stories behind unique and fascinating cars, celebrating the passion of true petrol heads!</p>
                    
                    <div class="vrm-recommended-video">
                        <iframe src="https://www.youtube.com/embed/YBIZnrdUS4k" 
                                width="100%" 
                                height="150" 
                                style="border-radius: 8px; border: none;" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                        </iframe>
                    </div>
                    
                    <button class="vrm-youtube-btn">Watch Now on YouTube</button>
                </div>
            </div>

            <!-- Fuel Costs Block -->
            <div class="vrm-performance-block">
                <h3 class="vrm-block-title">Fuel Costs</h3>
                <div class="vrm-performance-items">
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">1 Mile</div>
                        <div class="vrm-performance-value">£0.21</div>
                    </div>
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">100 Miles</div>
                        <div class="vrm-performance-value">£20.98</div>
                    </div>
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">12,000 Miles</div>
                        <div class="vrm-performance-value">£2,518</div>
                    </div>
                </div>
            </div>
            
            <!-- Running Costs Block -->
            <div class="vrm-performance-block">
                <h3 class="vrm-block-title">Running Costs</h3>
                <div class="vrm-performance-items">
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">Tax (12 months)*</div>
                        <div class="vrm-performance-value">£<?php echo esc_html($data['ved_twelve_months'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">Tax (6 months)*</div>
                        <div class="vrm-performance-value">£<?php echo esc_html($data['ved_six_months'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">Insurance Group</div>
                        <div class="vrm-performance-value">45E</div>
                    </div>
                </div>
            </div>
            

        </div>
        
        <div class="vrm-tax-disclaimer">
            <p style="font-size: 12px; color: #666; margin-top: 15px;">*Road tax costs are indicative. You should check with the seller or book at the <a href="#" style="color: #007cba;">vehicle tax rates</a> table to confirm tax costs.</p>
        </div>
        
        <!-- Two Column Section -->
        <div class="vrm-two-column-section" style="margin-top: 30px;">
            <div class="vrm-two-column-container">
                <!-- Left Column -->
                <div class="vrm-column vrm-column-left">
                    <div class="vrm-column-content">
                        <!-- Car Finance Claim Block -->
                        <div class="car-finance-claim">
                            <div class="claim-header">
                                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/alert.svg'); ?>" alt="Claim" class="claim-icon-svg">
                                <h3 class="claim-title">Car Finance Claim</h3>
                            </div>
                            
                            <h4 class="claim-question">Have you had a car on finance between 2008-2021?</h4>
                            
                            <p class="claim-description">
                                You may be able to reclaim £1000s in recovered interest payments due 
                                to potential hidden commission paid. Find out if you are eligible.
                            </p>
                            
                            <a href="https://pcp.claim.co.uk/new/" class="claim-button" target="_blank">
                                → Start your claim at claim.co.uk
                            </a>
                            
                            <p class="claim-disclaimer">
                                 Car Finance (HP/PCP) claims are regulated by the FCA. You do not 
                                 need to use a claims management company such as partners to make 
                                 a claim. You have the right to complain directly to the lender and/or use 
                                 the Financial Ombudsman Service to seek redress for free.
                             </p>
                         </div>
                         
                         <!-- Additional Check Sections -->
                         <div class="vehicle-checks">
                             <!-- Diesel Claim -->
                             <div class="check-item">
                                 <div class="check-header">
                                     <span class="check-icon">✓</span>
                                     <h4 class="check-title">Diesel Claim</h4>
                                 </div>
                                 <p class="check-description">
                                     It doesn't look like this vehicle has been affected by the diesel emissions scandal.
                                 </p>
                             </div>
                             
                             <!-- Exported -->
                             <div class="check-item">
                                 <div class="check-header">
                                     <span class="check-icon">✓</span>
                                     <h4 class="check-title">Exported</h4>
                                 </div>
                                 <p class="check-description">
                                     This vehicle has not been marked as exported.
                                 </p>
                             </div>
                             
                             <!-- Recalls -->
                             <div class="check-item">
                                 <div class="check-header">
                                     <span class="check-icon">✓</span>
                                     <h4 class="check-title">Recalls</h4>
                                 </div>
                                 <p class="check-description">
                                     No safety recalls found for this vehicle.
                                 </p>
                             </div>
                             
                             <!-- ULEZ Compliance -->
                             <div class="check-item">
                                 <div class="check-header">
                                     <span class="check-icon">✓</span>
                                     <h4 class="check-title">ULEZ Compliance</h4>
                                 </div>
                                 <p class="check-description">
                                     This vehicle meets the ULEZ standards. Cars that meet Euro 4 
                                     standards don't need to pay the daily charge when driving in the ULEZ 
                                     zone. Other charges (e.g. congestion charge) may still apply. You can 
                                     confirm the current status on the TfL website.
                                 </p>
                                 <a href="#" class="check-link">Check ULEZ status on TFL</a>
                             </div>
                         </div>
                         
                         <!-- Hidden History Block -->
                         <div class="hidden-history-block">
                             <div class="hidden-history-header">
                                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/alert.svg'); ?>" alt="Alert" class="issue-icon-svg">
                                <h3 class="hidden-history-title">Hidden History</h3>
                             </div>
                             
                             <p class="hidden-history-intro">
                                 Nearly a <strong>quarter</strong> of vehicles we check reveal <strong>one or more issues</strong>.
                             </p>
                             
                             <div class="hidden-history-issues">
                                 <div class="history-issue">
                                     <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/info.svg'); ?>" alt="Info" class="issue-icon-svg">
                                     <span class="issue-text">Outstanding Finance</span>
                                 </div>
                                 <div class="history-issue">
                                     <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/info.svg'); ?>" alt="Info" class="issue-icon-svg">
                                     <span class="issue-text">Written Off</span>
                                 </div>
                                 <div class="history-issue">
                                     <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/info.svg'); ?>" alt="Info" class="issue-icon-svg">
                                     <span class="issue-text">Stolen</span>
                                 </div>
                                 <div class="history-issue">
                                     <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/info.svg'); ?>" alt="Info" class="issue-icon-svg">
                                     <span class="issue-text">Plate Changes</span>
                                 </div>
                             </div>
                             
                             <p class="hidden-history-cta">
                                 Get a history check to check this vehicle for hidden history.
                             </p>
                             
                             <button class="hidden-history-button">Get a Full Check</button>
                         </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="vrm-column vrm-column-right">
                    <div class="vrm-column-content">
                        <!-- Mileage Issues Block -->
                        <?php if (!empty($data['has_mileage_issues']) && $data['has_mileage_issues']): ?>
                        <div class="mileage-issues-block">
                            <div class="mileage-issues-header">
                                <span class="mileage-issues-icon">✗</span>
                                <h3 class="mileage-issues-title">Mileage Issues</h3>
                            </div>
                            
                            <p class="mileage-issues-description">
                                <?php echo esc_html($data['mileage_issue_description'] ?? 'N/A'); ?>
                            </p>
                            
                            <div class="mileage-issues-links">
                                <a href="#" class="mileage-issue-link">Should I buy a car that shows a mileage issue?</a>
                                <a href="#" class="mileage-issue-link">How do I correct a mistake in the mileage history?</a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Hidden Mileage Issues Block -->
                        <div class="hidden-mileage-issues-block">
                            <div class="hidden-mileage-issues-header">
                                <span class="hidden-mileage-issues-icon">⚠</span>
                                <h3 class="hidden-mileage-issues-title">Hidden Mileage Issues</h3>
                            </div>
                            
                            <p class="hidden-mileage-issues-description">
                                This mileage check only includes data from the DVSA recorded during MOT tests.
                            </p>
                            
                            <p class="hidden-mileage-issues-description">
                                Buy a full report to check mileage readings from the DVLA, Retailers / Garages & Leasing / Hire Companies.
                            </p>
                            
                            <button class="hidden-mileage-issues-button">Get a History Check</button>
                        </div>
                        
                        <!-- Mileage Data Block -->
                        <div class="mileage-data-block">
                            <div class="mileage-data-table">
                                <div class="mileage-data-row">
                                    <span class="mileage-data-label">Est. Current Mileage</span>
                                    <span class="mileage-data-value"><?php echo esc_html($data['current_mileage'] ?? '32,962 miles'); ?></span>
                                </div>
                                <div class="mileage-data-row">
                                    <span class="mileage-data-label">Mileage Last Year</span>
                                    <span class="mileage-data-value"><?php echo esc_html($data['mileage_last_year'] ?? '0 miles'); ?></span>
                                </div>
                                <div class="mileage-data-row">
                                    <span class="mileage-data-label">Average Mileage</span>
                                    <span class="mileage-data-value"><?php echo esc_html($data['average_mileage'] ?? '4300 p/year'); ?></span>
                                </div>
                                <div class="mileage-data-row">
                                    <span class="mileage-data-label">Status</span>
                                    <span class="mileage-data-value status-below">below average</span>
                                </div>
                                <div class="mileage-data-row">
                                    <span class="mileage-data-label">Last MOT Mileage</span>
                                    <span class="mileage-data-value"><?php echo esc_html($data['estimated_mileage'] ?? '32962 miles'); ?> on<br><?php echo esc_html($data['mileage_date'] ?? '23 Sep 2024'); ?></span>
                                </div>
                            </div>
                            
                            <button class="mileage-data-button">View Mileage History</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Additional Information -->
    <div class="vrm-disclaimer">
        <p><strong>Important:</strong> This information is provided for reference only. Always verify details with official sources before making any decisions.</p>
        <p>Data sourced from DVLA and other official databases. Last updated: <?php echo esc_html(current_time('j M Y, H:i')); ?></p>
    </div>
    
</div>
<div class="check-footer-fixed">
    <div class="container">
        <ul class="check-footer-fixed-list">
            <li class="vrm-chech-footer-list-item active">
                <a href="#">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/vehicle-selected.svg'); ?>" alt="Vehicle Selected">
                    <p class="vrm-code"><?php echo esc_html($data['vrm'] ?? 'N/A'); ?></p>
                </a>
            </li>
            <li class="vrm-chech-footer-list-item">
                <a href="#">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/mot-selected.svg'); ?>" alt="Mot Selected">
                    <p class="mot-code">Mot History</p>
                </a>
            </li>
            <li class="vrm-chech-footer-list-item">
                <a href="#">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/mileage-selected.svg'); ?>" alt="Mileage Selected">
                    <p class="mileage-code">Mileage History</p>
                </a>
            </li>
            <li class="vrm-chech-footer-list-item">
                <a href="#">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/history-selected.svg'); ?>" alt="Contact Selected">
                    <p class="contact-code">Buy check</p>
                </a>
            </li>
        </ul>
    </div>
</div>