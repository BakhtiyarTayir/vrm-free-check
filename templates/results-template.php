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
                        <div class="vrm-performance-value">£265</div>
                    </div>
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">Tax (6 months)*</div>
                        <div class="vrm-performance-value">£145.75</div>
                    </div>
                    <div class="vrm-performance-item">
                        <div class="vrm-performance-label">Insurance Group</div>
                        <div class="vrm-performance-value">45E</div>
                    </div>
                </div>
            </div>
            
            <!-- Recommended Block -->
            <div class="vrm-recommended-block">
                <h3 class="vrm-block-title">Recommended</h3>
                <div class="vrm-recommended-content">
                    <h4 class="vrm-recommended-title">Resto Revival</h4>
                    <p class="vrm-recommended-description">Captivating stories behind unique and fascinating cars, celebrating the passion of true petrol heads!</p>
                    
                    <div class="vrm-recommended-image">
                        <img src="https://images.unsplash.com/photo-1494976688153-ca3ce29d8df4?w=400&h=200&fit=crop&auto=format" alt="Classic Car" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px;">
                        <div class="vrm-play-button">▶</div>
                    </div>
                    
                    <button class="vrm-youtube-btn">Watch Now on YouTube</button>
                </div>
            </div>
        </div>
        
        <div class="vrm-tax-disclaimer">
            <p style="font-size: 12px; color: #666; margin-top: 15px;">*Road tax costs are indicative. You should check with the seller or book at the <a href="#" style="color: #007cba;">vehicle tax rates</a> table to confirm tax costs.</p>
        </div>
    </div>
    
    <!-- Additional Information -->
    <div class="vrm-disclaimer">
        <p><strong>Important:</strong> This information is provided for reference only. Always verify details with official sources before making any decisions.</p>
        <p>Data sourced from DVLA and other official databases. Last updated: <?php echo esc_html(current_time('j M Y, H:i')); ?></p>
    </div>
    
</div>