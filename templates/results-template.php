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
        <div class="vrm-warning-box">
            <p>More than 25% of vehicles checked this month were written off.</p>
            <p class="vrm-example-data">Example Data ⚠</p>
        </div>
    </div>
    
    <!-- Additional Information -->
    <div class="vrm-disclaimer">
        <p><strong>Important:</strong> This information is provided for reference only. Always verify details with official sources before making any decisions.</p>
        <p>Data sourced from DVLA and other official databases. Last updated: <?php echo esc_html(current_time('j M Y, H:i')); ?></p>
    </div>
    
</div>