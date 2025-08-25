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

// Получаем VRM из данных или переменной
if (!isset($vrm) && isset($data['vrm'])) {
    $vrm = $data['vrm'];
} elseif (!isset($vrm)) {
    $vrm = '';
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

<div class="vrm-check-results">
    <!-- Vehicle Details Page -->
    <div id="vehicle-details-page" class="page-content active">
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
            
            <!-- Hidden form for data transfer -->
            <form id="full-check-form" method="POST" action="https://motcheck.local/full-check-page/" style="display: none;">
                <input type="hidden" name="vrm_data" value="<?php echo esc_attr(json_encode($data)); ?>">
                <input type="hidden" name="vrm" value="<?php echo esc_attr($vrm); ?>">
            </form>
            
            <button class="vrm-check-now-btn" onclick="redirectToFullCheck()" style="background-color: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer;">Check Now</button>
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
    
    <!-- MOT History Page -->
    <div id="mot-history-page" class="page-content">
        <div class="mot-history-container">
            <!-- MOT History Details Section -->
            <div class="mot-history-details">
                
                <h2 class="mot-history-title">MOT Summary</h2>
                <?php 
                $mot_details = $data['mot_history_details'] ?? array();
                $test_history = $mot_details['test_history'] ?? array();
                ?>
                
                <?php if (!empty($test_history)): ?>
                    <!-- MOT Statistics Summary -->
                    <div class="mot-summary-layout">
                        <!-- Left Column - MOT Statistics Cards -->
                        <div class="mot-summary-cards">
                            <div class="mot-summary-grid">
                                <!-- Total Tests -->
                                <div class="mot-summary-card">
                                    <div class="mot-summary-icon success">✓</div>
                                    <div class="mot-summary-content">
                                        <div class="mot-summary-label">Total Tests</div>
                                        <div class="mot-summary-value"><?php echo esc_html($mot_details['total_tests'] ?? '0'); ?></div>
                                    </div>
                                </div>
                                
                                <!-- Passed -->
                                <div class="mot-summary-card">
                                    <div class="mot-summary-icon success">✓</div>
                                    <div class="mot-summary-content">
                                        <div class="mot-summary-label">Passed</div>
                                        <div class="mot-summary-value"><?php echo esc_html($mot_details['passed_tests'] ?? '0'); ?></div>
                                    </div>
                                </div>
                                
                                <!-- Failed -->
                                <div class="mot-summary-card">
                                    <div class="mot-summary-icon error">✗</div>
                                    <div class="mot-summary-content">
                                        <div class="mot-summary-label">Failed</div>
                                        <div class="mot-summary-value"><?php echo esc_html($mot_details['failed_tests'] ?? '0'); ?></div>
                                    </div>
                                </div>
                                
                                <!-- Pass Rate -->
                                <div class="mot-summary-card">
                                    <div class="mot-summary-icon warning">⚠</div>
                                    <div class="mot-summary-content">
                                        <div class="mot-summary-label">Pass Rate</div>
                                        <div class="mot-summary-value"><?php echo esc_html($mot_details['pass_rate'] ?? '0%'); ?></div>
                                    </div>
                                </div>
                                
                                <!-- Latest Mileage -->
                                <div class="mot-summary-card">
                                    <div class="mot-summary-icon warning">⚠</div>
                                    <div class="mot-summary-content">
                                        <div class="mot-summary-label">Latest Mileage</div>
                                        <div class="mot-summary-value"><?php echo esc_html($mot_details['latest_mileage'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                
                                <!-- Avg Annual Mileage -->
                                <div class="mot-summary-card">
                                    <div class="mot-summary-icon warning">⚠</div>
                                    <div class="mot-summary-content">
                                        <div class="mot-summary-label">Avg Annual Mileage</div>
                                        <div class="mot-summary-value"><?php echo esc_html($mot_details['average_annual_mileage'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
        
                        <!-- Right Column - Book MOT Section -->
                        <div class="mot-book-section">
                            <div class="mot-book-card">
                                <p class="mot-book-text">Book an MOT or service with free collection and delivery across the UK</p>
                                <button class="mot-book-button">Book an MOT</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vehicle Information -->
                    <div class="mot-vehicle-info">
                        <h3 class="mot-history-details-title">MOT History</h3>
                        <div class="mot-vehicle-grid">
                            <div class="mot-vehicle-item">
                                <span class="mot-vehicle-label">Make:</span>
                                <span class="mot-vehicle-value"><?php echo esc_html($mot_details['make'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="mot-vehicle-item">
                                <span class="mot-vehicle-label">Model:</span>
                                <span class="mot-vehicle-value"><?php echo esc_html($mot_details['model'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="mot-vehicle-item">
                                <span class="mot-vehicle-label">Fuel Type:</span>
                                <span class="mot-vehicle-value"><?php echo esc_html($mot_details['fuel_type'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="mot-vehicle-item">
                                <span class="mot-vehicle-label">Colour:</span>
                                <span class="mot-vehicle-value"><?php echo esc_html($mot_details['colour'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="mot-vehicle-item">
                                <span class="mot-vehicle-label">Vehicle Age:</span>
                                <span class="mot-vehicle-value"><?php echo esc_html($mot_details['vehicle_age_years'] ?? '0'); ?> years</span>
                            </div>
                            <div class="mot-vehicle-item">
                                <span class="mot-vehicle-label">MOT Status:</span>
                                <span class="mot-vehicle-value mot-status-<?php echo strtolower($mot_details['mot_status'] ?? 'unknown'); ?>">
                                    <?php echo esc_html($mot_details['mot_status'] ?? 'Unknown'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- MOT Test History Table Format -->
                    <div class="mot-history-table-container">
                        <?php foreach ($test_history as $test): ?>
                            <div class="mot-test-section">
                                <!-- Section Header -->
                                <div class="mot-section-header">
                                    <div class="mot-header-date">
                                        <span class="mot-check-icon">✓</span>
                                        <?php echo esc_html($test['test_date_formatted'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="mot-header-status <?php echo $test['test_passed'] ? 'pass' : 'fail'; ?>">
                                        <?php echo $test['test_passed'] ? 'Pass' : 'Fail'; ?>
                                    </div>
                                </div>
                                
                                <!-- Details Table -->
                                <table class="mot-details-table">
                                    <tbody>
                                        <tr>
                                            <td class="mot-table-label">Test Date</td>
                                            <td class="mot-table-value mot-date-value"><?php echo esc_html($test['test_date_formatted'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="mot-table-label">Mileage</td>
                                            <td class="mot-table-value mot-mileage-value"><?php echo esc_html($test['odometer_formatted'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="mot-table-label">Result</td>
                                            <td class="mot-table-value">
                                                <?php if ($test['test_passed']): ?>
                                                    <span class="mot-result-pass"><span class="mot-check-icon">✓</span> Passed</span>
                                                <?php else: ?>
                                                    <span class="mot-result-fail"><span class="mot-cross-icon">✗</span> Failed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="mot-table-label">Test Results</td>
                                            <td class="mot-table-value">
                                                <?php if ($test['test_passed']): ?>
                                                    <div class="mot-test-passed">
                                                        <span class="mot-check-icon">✓</span>
                                                        Vehicle passed MOT with no advisory notices.
                                                    </div>
                                                <?php else: ?>
                                                    <?php if (!empty($test['annotations'])): ?>
                                                        <div class="mot-test-annotations">
                                                            <?php foreach ($test['annotations'] as $annotation): ?>
                                                                <div class="mot-annotation <?php echo esc_attr($annotation['type_class']); ?> severity-<?php echo esc_attr($annotation['severity']); ?>">
                                                                    <span class="annotation-type"><?php echo esc_html($annotation['type']); ?>:</span>
                                                                    <span class="annotation-text"><?php echo esc_html($annotation['text']); ?></span>
                                                                    <?php if ($annotation['is_dangerous']): ?>
                                                                        <span class="annotation-dangerous">⚠ DANGEROUS</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mot-test-failed">
                                                            <span class="mot-cross-icon">✗</span>
                                                            Vehicle failed MOT test.
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($test['test_number'])): ?>
                                                    <div class="mot-test-number">Test Number: <?php echo esc_html($test['test_number']); ?></div>
                                                <?php endif; ?>
                                                
                                                <?php if ($test['is_retest']): ?>
                                                    <div class="mot-retest-indicator">🔄 Retest</div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Collapse All Test Results Link -->
                    <div class="mot-collapse-section">
                        <a href="#" class="mot-collapse-link" onclick="toggleAllTestResults(); return false;">
                            Collapse All Test Results
                        </a>
                    </div>
                    
                <?php else: ?>
                    <div class="mot-no-data">
                        <p>No MOT history data available for this vehicle.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <style>
            .mot-history-details {
                margin-top: 30px;
                background: #fff;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .mot-history-details-title {
                color: #1e3a8a;
                font-size: 24px;
                font-weight: 600;
                margin-bottom: 20px;
                border-bottom: 2px solid #e5e7eb;
                padding-bottom: 10px;
            }
            
            .mot-history-table-container {
                margin-bottom: 20px;
            }
            
            .mot-test-section {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                margin-bottom: 15px;
                overflow: hidden;
                border: 1px solid #e5e7eb;
            }
            
            .mot-section-header {
                background: #f8fafc;
                padding: 15px 20px;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .mot-header-date {
                font-weight: 600;
                color: #374151;
                font-size: 16px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .mot-header-status {
                font-weight: 600;
                font-size: 14px;
                padding: 4px 12px;
                border-radius: 20px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .mot-header-status.pass {
                background: #dcfce7;
                color: #166534;
            }
            
            .mot-header-status.fail {
                background: #fee2e2;
                color: #dc2626;
            }
            
            .mot-details-table {
                width: 100%;
                border-collapse: collapse;
                background: #fff;
            }
            
            .mot-details-table td {
                padding: 15px 20px;
                border-bottom: 1px solid #f3f4f6;
                vertical-align: top;
            }
            
            .mot-details-table tr:last-child td {
                border-bottom: none;
            }
            
            .mot-table-label {
                font-weight: 600;
                color: #374151;
                width: 120px;
                min-width: 120px;
            }
            
            .mot-table-value {
                color: #1f2937;
            }
            
            .mot-date-value {
                color: #2563eb;
                font-weight: 500;
            }
            
            .mot-mileage-value {
                color: #2563eb;
                font-weight: 500;
            }
            
            .mot-test-date {
                font-weight: 500;
                color: #1f2937;
                white-space: nowrap;
            }
            
            .mot-test-mileage {
                font-weight: 500;
                color: #2563eb;
            }
            
            .mot-result-pass {
                color: #059669;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            
            .mot-result-fail {
                color: #dc2626;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            
            .mot-test-details {
                width: 100%;
            }
            
            .mot-check-icon {
                color: #059669;
                font-size: 18px;
                margin-right: 5px;
            }
            
            .mot-test-passed {
                color: #059669;
                font-style: italic;
                margin-bottom: 8px;
            }
            
            .mot-test-failed {
                color: #dc2626;
                font-weight: 500;
                margin-bottom: 8px;
            }
            
            .mot-test-annotations {
                margin-bottom: 10px;
            }
            
            .mot-annotation {
                margin-bottom: 8px;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 13px;
                line-height: 1.4;
            }
            
            .mot-annotation.advisory {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
            }
            
            .mot-annotation.dangerous {
                background: #fee2e2;
                border-left: 4px solid #dc2626;
            }
            
            .mot-annotation.minor {
                background: #dbeafe;
                border-left: 4px solid #3b82f6;
            }
            
            .annotation-type {
                font-weight: 600;
                text-transform: uppercase;
                font-size: 11px;
                letter-spacing: 0.5px;
            }
            
            .annotation-text {
                display: block;
                margin-top: 4px;
                color: #374151;
            }
            
            .annotation-dangerous {
                color: #dc2626;
                font-weight: 600;
                font-size: 11px;
                margin-left: 8px;
            }
            
            .mot-test-number {
                font-size: 12px;
                color: #6b7280;
                margin-top: 8px;
            }
            
            .mot-expiry-date {
                font-size: 12px;
                color: #059669;
                margin-top: 4px;
                font-weight: 500;
            }
            
            .mot-collapse-section {
                text-align: center;
                margin-top: 20px;
                padding-top: 15px;
                border-top: 1px solid #e5e7eb;
            }
            
            .mot-collapse-link {
                color: #2563eb;
                text-decoration: none;
                font-weight: 500;
                font-size: 14px;
            }
            
            .mot-collapse-link:hover {
                text-decoration: underline;
            }
            
            .mot-no-data {
                text-align: center;
                padding: 40px 20px;
                color: #6b7280;
                font-style: italic;
            }
            
            @media (max-width: 768px) {
                .mot-section-header {
                    padding: 12px 15px;
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }
                
                .mot-details-table td {
                    padding: 12px 15px;
                }
                
                .mot-table-label {
                    width: 100px;
                    min-width: 100px;
                    font-size: 14px;
                }
                
                .mot-table-value {
                    font-size: 14px;
                }
            }
            </style>
            
            <script>
            function toggleAllTestResults() {
                const annotations = document.querySelectorAll('.mot-test-annotations');
                const link = document.querySelector('.mot-collapse-link');
                
                if (link.textContent.includes('Collapse')) {
                    annotations.forEach(annotation => {
                        annotation.style.display = 'none';
                    });
                    link.textContent = 'Expand All Test Results';
                } else {
                    annotations.forEach(annotation => {
                        annotation.style.display = 'block';
                    });
                    link.textContent = 'Collapse All Test Results';
                }
            }
            </script>
        </div>
    </div>
    
    <!-- Mileage History Page -->
    <div id="mileage-history-page" class="page-content">
        <div class="mileage-history-container">
            <h2 class="mileage-history-title">Mileage History</h2>
            
            <!-- Mileage Chart -->
            <div class="mileage-chart-container">
                <h3 class="mileage-chart-title">Mileage Chart</h3>
                <div class="mileage-chart" id="mileageChart">
                    <?php if (!empty($data['mileage_chart_data']) && is_array($data['mileage_chart_data'])): ?>
                        <?php 
                        $chart_data = $data['mileage_chart_data'];
                        $max_mileage = 0;
                        
                        // Находим максимальное значение для масштабирования
                        foreach ($chart_data as $item) {
                            if ($item['mileage'] > $max_mileage) {
                                $max_mileage = $item['mileage'];
                            }
                        }
                        
                        $current_year = date('Y');
                        ?>
                        
                        <div class="chart-bars">
                            <?php foreach ($chart_data as $item): ?>
                                <?php 
                                $height_percentage = $max_mileage > 0 ? ($item['mileage'] / $max_mileage) * 100 : 0;
                                $is_current_year = ($item['year'] == $current_year);
                                $bar_class = $is_current_year ? 'chart-bar current-year' : 'chart-bar';
                                ?>
                                <div class="chart-bar-container">
                                    <div class="<?php echo $bar_class; ?>" style="height: <?php echo $height_percentage; ?>%;">
                                        <span class="bar-value"><?php echo number_format($item['mileage']); ?></span>
                                    </div>
                                    <span class="bar-year"><?php echo esc_html($item['year']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-chart-data">
                            <p>No mileage data available for chart</p>
                        </div>
                    <?php endif; ?>
                </div>
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
                <!-- Mileage History Table -->
                <div class="mileage-history-table">
                    <h3 class="mileage-history-table-title">Mileage History</h3>
                    <div class="mileage-table-container">
                        <table class="mileage-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Mileage</th>
                                    <th>+/-</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($data['mileage_history']) && is_array($data['mileage_history'])): ?>
                                    <?php foreach ($data['mileage_history'] as $index => $record): ?>
                                        <?php 
                                        $change_class = '';
                                        $change_value = $record['change'] ?? 0;
                                        if ($change_value < 0) {
                                            $change_class = 'negative-change';
                                        } elseif ($change_value > 0) {
                                            $change_class = 'positive-change';
                                        }
                                        ?>
                                        <tr class="<?php echo $change_class; ?>">
                                            <td><?php echo esc_html($record['date'] ?? 'N/A'); ?></td>
                                            <td><?php echo esc_html($record['mileage'] ?? 'N/A'); ?></td>
                                            <td class="change-value"><?php echo esc_html($record['change_display'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Default data from screenshot -->
                                    <tr>
                                        <td>19 Dec 2019</td>
                                        <td>22308 miles</td>
                                        <td class="positive-change">+22308</td>
                                    </tr>
                                    <tr>
                                        <td>19 Dec 2019</td>
                                        <td>22310 miles</td>
                                        <td class="positive-change">+2</td>
                                    </tr>
                                    <tr>
                                        <td>22 Sep 2020</td>
                                        <td>22913 miles</td>
                                        <td class="positive-change">+603</td>
                                    </tr>
                                    <tr>
                                        <td>25 Aug 2021</td>
                                        <td>30745 miles</td>
                                        <td class="positive-change">+7832</td>
                                    </tr>
                                    <tr>
                                        <td>31 Aug 2022</td>
                                        <td>40012 miles</td>
                                        <td class="positive-change">+9267</td>
                                    </tr>
                                    <tr>
                                        <td>13 Sep 2023</td>
                                        <td>52608 miles</td>
                                        <td class="positive-change">+12596</td>
                                    </tr>
                                    <tr class="negative-change">
                                        <td>23 Sep 2024</td>
                                        <td>32962 miles</td>
                                        <td class="change-value">-19646</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Additional Mileage Data Block -->
                <div class="additional-mileage-data">
                    <div class="additional-mileage-header">
                        <span class="info-icon">ℹ</span>
                        <h4 class="additional-mileage-title">Additional Mileage Data</h4>
                    </div>
                    
                    <div class="carly-scanner-info">
                        <h5 class="carly-scanner-title">Detect Mileage Fraud With a Carly Scanner</h5>
                        
                        <p class="carly-scanner-description">
                            A Carly Scanner completes a check on the vehicles ECU by connecting to the OBD2 port.
                        </p>
                        
                        <div class="carly-scanner-features">
                            <div class="feature-item">
                                <span class="feature-icon">ℹ</span>
                                <span class="feature-text">Find out if the vehicles odometer has been manipulated</span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon">ℹ</span>
                                <span class="feature-text">Detect odometer discrepancies on both digital and mechanical odometers</span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon">ℹ</span>
                                <span class="feature-text">Check for fault codes and other potential mechanical issues</span>
                            </div>
                        </div>
                        
                        <p class="carly-scanner-footer">
                            Complete a used car check with a Carly scanner to access additional data that isn't included on a vehicle history check.
                        </p>
                        
                        <button class="carly-scanner-button">Check Now</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
</div>
<div class="check-footer-fixed">
    <div class="container">
        <ul class="check-footer-fixed-list">
            <li class="vrm-chech-footer-list-item active" data-page="vehicle-details">
                <a href="#" onclick="switchPage('vehicle-details-page'); return false;">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/vehicle-selected.svg'); ?>" alt="Vehicle Selected">
                    <p class="vrm-code"><?php echo esc_html($data['registration'] ?? $vrm); ?></p>
                </a>
            </li>
            <li class="vrm-chech-footer-list-item" data-page="mot-history">
                <a href="#" onclick="switchPage('mot-history-page'); return false;">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/mot.svg'); ?>" alt="Mot Selected">
                    <p class="mot-code">Mot History</p>
                </a>
            </li>
            <li class="vrm-chech-footer-list-item" data-page="mileage-history">
                <a href="#" onclick="switchPage('mileage-history-page'); return false;">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/mileage.svg'); ?>" alt="Mileage Selected">
                    <p class="mileage-code">Mileage History</p>
                </a>
            </li>
            <li class="vrm-chech-footer-list-item">
                <a href="#">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../assets/images/history.svg'); ?>" alt="Contact Selected">
                    <p class="contact-code" style="color:red!important">Buy check</p>
                </a>
            </li>
        </ul>
    </div>
</div>


<script>
function updateMenuImages() {
    const menuItems = document.querySelectorAll('.vrm-chech-footer-list-item');
    const pluginUrl = '<?php echo esc_url(plugin_dir_url(__FILE__) . "../assets/images/"); ?>';
    
    menuItems.forEach(item => {
        const img = item.querySelector('img');
        const dataPage = item.getAttribute('data-page');
        
        if (img && dataPage) {
            if (item.classList.contains('active')) {
                // Set selected image for active item
                switch(dataPage) {
                    case 'vehicle-details':
                        img.src = pluginUrl + 'vehicle-selected.svg';
                        break;
                    case 'mot-history':
                        img.src = pluginUrl + 'mot-selected.svg';
                        break;
                    case 'mileage-history':
                        img.src = pluginUrl + 'mileage-selected.svg';
                        break;
                    default:
                        img.src = pluginUrl + 'history-selected.svg';
                        break;
                }
            } else {
                // Set normal image for inactive items
                switch(dataPage) {
                    case 'vehicle-details':
                        img.src = pluginUrl + 'vehicle.svg';
                        break;
                    case 'mot-history':
                        img.src = pluginUrl + 'mot.svg';
                        break;
                    case 'mileage-history':
                        img.src = pluginUrl + 'mileage.svg';
                        break;
                    default:
                        img.src = pluginUrl + 'history.svg';
                        break;
                }
            }
        }
    });
}

function switchPage(pageId) {
    // Hide all pages
    const pages = document.querySelectorAll('.page-content');
    pages.forEach(page => {
        page.classList.remove('active');
    });
    
    // Show selected page
    const targetPage = document.getElementById(pageId);
    if (targetPage) {
        targetPage.classList.add('active');
    }
    
    // Update menu active state
    const menuItems = document.querySelectorAll('.vrm-chech-footer-list-item');
    menuItems.forEach(item => {
        item.classList.remove('active');
    });
    
    // Set active menu item based on page
    if (pageId === 'vehicle-details-page') {
        const vehicleMenuItem = document.querySelector('[data-page="vehicle-details"]');
        if (vehicleMenuItem) {
            vehicleMenuItem.classList.add('active');
        }
    } else if (pageId === 'mot-history-page') {
        const motMenuItem = document.querySelector('[data-page="mot-history"]');
        if (motMenuItem) {
            motMenuItem.classList.add('active');
        }
    } else if (pageId === 'mileage-history-page') {
        const mileageMenuItem = document.querySelector('[data-page="mileage-history"]');
        if (mileageMenuItem) {
            mileageMenuItem.classList.add('active');
        }
    }
    
    // Update images after changing active states
    updateMenuImages();
}

function setActiveMenuItem(clickedItem) {
    // Remove active class from all menu items
    const menuItems = document.querySelectorAll('.vrm-chech-footer-list-item');
    menuItems.forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active class to clicked item
    clickedItem.classList.add('active');
    
    // Update images
    updateMenuImages();
    
    // Switch to corresponding page
    const dataPage = clickedItem.getAttribute('data-page');
    if (dataPage) {
        switchPage(dataPage + '-page');
    }
}

// Initialize page on load
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners to menu items
    const menuItems = document.querySelectorAll('.vrm-chech-footer-list-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            setActiveMenuItem(this);
        });
    });
    
    // Make sure vehicle details page is active by default
    switchPage('vehicle-details-page');
});

// Function to redirect to full check page with data
function redirectToFullCheck() {
    const form = document.getElementById('full-check-form');
    if (form) {
        form.submit();
    }
}
</script>