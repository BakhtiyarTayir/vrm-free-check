<?php
/**
 * Summary Report Generator
 * 
 * Analyzes vehicle data and generates reports with conclusions
 * 
 * @package VRM_Check_Plugin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VRM_Summary_Report_Generator {
    
    /**
     * Analyzes data and generates a report
     * 
     * @param array $merged_data Merged data from API
     * @return array Structured report
     */
    public static function generate_summary_report($merged_data) {
        if (empty($merged_data) || !is_array($merged_data)) {
            return self::get_error_report('Data not found');
        }
        
        // Analyze various aspects of the data
        $vehicle_analysis = self::analyze_vehicle_details($merged_data);
        $mot_analysis = self::analyze_mot_history($merged_data);
        $mileage_analysis = self::analyze_mileage_data($merged_data);
        $finance_analysis = self::analyze_finance_data($merged_data);
        $tax_analysis = self::analyze_tax_data($merged_data);
        
        // Form overall conclusion
        $overall_status = self::determine_overall_status([
            $vehicle_analysis,
            $mot_analysis,
            $mileage_analysis,
            $finance_analysis,
            $tax_analysis
        ]);
        
        return [
            'status' => $overall_status['status'],
            'status_class' => $overall_status['class'],
            'summary_text' => $overall_status['summary'],
            'detailed_analysis' => [
                'vehicle' => $vehicle_analysis,
                'mot' => $mot_analysis,
                'mileage' => $mileage_analysis,
                'finance' => $finance_analysis,
                'tax' => $tax_analysis
            ],
            'recommendations' => self::generate_recommendations($merged_data, $overall_status)
        ];
    }
    
    /**
     * Analyzes vehicle details
     */
    private static function analyze_vehicle_details($data) {
        $vehicle_details = $data['VehicleDetails'] ?? [];
        $vehicle_id = $vehicle_details['VehicleIdentification'] ?? [];
        
        $analysis = [
            'status' => 'good',
            'issues' => [],
            'highlights' => []
        ];
        
        // Check basic data
        if (empty($vehicle_id['DvlaMake']) || empty($vehicle_id['DvlaModel'])) {
            $analysis['issues'][] = 'Incomplete make or model data';
            $analysis['status'] = 'warning';
        } else {
            $analysis['highlights'][] = sprintf('Vehicle: %s %s', 
                $vehicle_id['DvlaMake'], 
                $vehicle_id['DvlaModel']
            );
        }
        
        // Check year of manufacture
        $year = $vehicle_id['YearOfManufacture'] ?? null;
        if ($year) {
            $current_year = date('Y');
            $age = $current_year - $year;
            
            if ($age > 15) {
                $analysis['issues'][] = sprintf('Vehicle is older than 15 years (%d year)', $year);
                $analysis['status'] = 'warning';
            } else {
                $analysis['highlights'][] = sprintf('Year of manufacture: %d (%d years old)', $year, $age);
            }
        }
        
        // Check vehicle status
        $vehicle_status = $vehicle_details['VehicleStatus'] ?? [];
        if (!empty($vehicle_status['TaxStatus']) && $vehicle_status['TaxStatus'] !== 'Taxed') {
            $analysis['issues'][] = 'Tax issues: ' . $vehicle_status['TaxStatus'];
            $analysis['status'] = 'error';
        }
        
        return $analysis;
    }
    
    /**
     * Analyzes MOT history
     */
    private static function analyze_mot_history($data) {
        $mot_history = $data['MotHistoryDetails'] ?? [];
        $mot_tests = $mot_history['MotTests'] ?? [];
        
        $analysis = [
            'status' => 'good',
            'issues' => [],
            'highlights' => []
        ];
        
        if (empty($mot_tests)) {
            $analysis['issues'][] = 'MOT history not found';
            $analysis['status'] = 'warning';
            return $analysis;
        }
        
        // Analyze the latest test
        $latest_test = $mot_tests[0] ?? [];
        $test_result = $latest_test['TestResult'] ?? '';
        $test_date = $latest_test['TestDate'] ?? '';
        
        if ($test_result === 'PASSED') {
            $analysis['highlights'][] = 'Latest MOT: PASSED';
            if ($test_date) {
                $analysis['highlights'][] = 'Latest MOT date: ' . date('d.m.Y', strtotime($test_date));
            }
        } else {
            $analysis['issues'][] = 'Latest MOT: ' . ($test_result ?: 'FAILED');
            $analysis['status'] = 'error';
        }
        
        // Count failed tests
        $failed_tests = 0;
        foreach ($mot_tests as $test) {
            if (($test['TestResult'] ?? '') !== 'PASSED') {
                $failed_tests++;
            }
        }
        
        if ($failed_tests > 2) {
            $analysis['issues'][] = sprintf('Multiple MOT failures (%d out of %d)', $failed_tests, count($mot_tests));
            $analysis['status'] = 'warning';
        }
        
        return $analysis;
    }
    
    /**
     * Analyzes mileage data
     */
    private static function analyze_mileage_data($data) {
        $mileage_details = $data['MileageCheckDetails'] ?? [];
        $mileage_checks = $mileage_details['MileageChecks'] ?? [];
        
        $analysis = [
            'status' => 'good',
            'issues' => [],
            'highlights' => []
        ];
        
        if (empty($mileage_checks)) {
            $analysis['issues'][] = 'Mileage data not found';
            $analysis['status'] = 'warning';
            return $analysis;
        }
        
        // Analyze latest readings
        $latest_mileage = $mileage_checks[0] ?? [];
        $mileage = $latest_mileage['Mileage'] ?? 0;
        $date = $latest_mileage['Date'] ?? '';
        
        if ($mileage > 0) {
            $analysis['highlights'][] = sprintf('Latest mileage: %s miles', number_format($mileage));
            if ($date) {
                $analysis['highlights'][] = 'Record date: ' . date('d.m.Y', strtotime($date));
            }
        }
        
        // Check for suspicious mileage changes
        if (count($mileage_checks) > 1) {
            for ($i = 0; $i < count($mileage_checks) - 1; $i++) {
                $current = $mileage_checks[$i]['Mileage'] ?? 0;
                $previous = $mileage_checks[$i + 1]['Mileage'] ?? 0;
                
                if ($current < $previous) {
                    $analysis['issues'][] = 'Mileage reduction detected (possible adjustment)';
                    $analysis['status'] = 'error';
                    break;
                }
            }
        }
        
        return $analysis;
    }
    
    /**
     * Analyzes finance data
     */
    private static function analyze_finance_data($data) {
        $finance_details = $data['FinanceDetails'] ?? [];
        $finance_checks = $finance_details['FinanceChecks'] ?? [];
        
        $analysis = [
            'status' => 'good',
            'issues' => [],
            'highlights' => []
        ];
        
        if (empty($finance_checks)) {
            $analysis['highlights'][] = 'No finance encumbrances found';
            return $analysis;
        }
        
        $active_finance = 0;
        foreach ($finance_checks as $check) {
            if (($check['Status'] ?? '') === 'Active') {
                $active_finance++;
            }
        }
        
        if ($active_finance > 0) {
            $analysis['issues'][] = sprintf('Active finance encumbrances found: %d', $active_finance);
            $analysis['status'] = 'warning';
        } else {
            $analysis['highlights'][] = 'No active finance encumbrances';
        }
        
        return $analysis;
    }
    
    /**
     * Analyzes tax data
     */
    private static function analyze_tax_data($data) {
        $tax_details = $data['VehicleTaxDetails'] ?? [];
        
        $analysis = [
            'status' => 'good',
            'issues' => [],
            'highlights' => []
        ];
        
        $tax_status = $tax_details['TaxStatus'] ?? '';
        $tax_due_date = $tax_details['TaxDueDate'] ?? '';
        
        if ($tax_status === 'Taxed') {
            $analysis['highlights'][] = 'Tax paid';
            if ($tax_due_date) {
                $due_date = strtotime($tax_due_date);
                $days_until_due = ceil(($due_date - time()) / (24 * 60 * 60));
                
                if ($days_until_due < 30) {
                    $analysis['issues'][] = sprintf('Tax expires in %d days', $days_until_due);
                    $analysis['status'] = 'warning';
                } else {
                    $analysis['highlights'][] = sprintf('Tax valid until %s', date('d.m.Y', $due_date));
                }
            }
        } else {
            $analysis['issues'][] = 'Tax not paid: ' . $tax_status;
            $analysis['status'] = 'error';
        }
        
        return $analysis;
    }
    
    /**
     * Determines overall status based on all analyses
     */
    private static function determine_overall_status($analyses) {
        $error_count = 0;
        $warning_count = 0;
        $good_count = 0;
        
        foreach ($analyses as $analysis) {
            switch ($analysis['status']) {
                case 'error':
                    $error_count++;
                    break;
                case 'warning':
                    $warning_count++;
                    break;
                case 'good':
                    $good_count++;
                    break;
            }
        }
        
        if ($error_count > 0) {
            return [
                'status' => 'Requires Attention',
                'class' => 'status-error',
                'summary' => sprintf('Found %d critical issues and %d warnings', $error_count, $warning_count)
            ];
        } elseif ($warning_count > 0) {
            return [
                'status' => 'Satisfactory',
                'class' => 'status-warning',
                'summary' => sprintf('Found %d warnings requiring attention', $warning_count)
            ];
        } else {
            return [
                'status' => 'Excellent',
                'class' => 'status-good',
                'summary' => 'All checks passed successfully, no serious issues found'
            ];
        }
    }
    
    /**
     * Generates recommendations based on analysis
     */
    private static function generate_recommendations($data, $overall_status) {
        $recommendations = [];
        
        // Recommendations based on status
        switch ($overall_status['status']) {
            case 'Requires Attention':
                $recommendations[] = 'Detailed vehicle inspection by a specialist is recommended';
                $recommendations[] = 'Resolve identified issues before purchase/sale';
                break;
                
            case 'Satisfactory':
                $recommendations[] = 'Pay attention to the indicated warnings';
                $recommendations[] = 'Consider additional inspection';
                break;
                
            case 'Excellent':
                $recommendations[] = 'Vehicle is in good condition according to available data';
                $recommendations[] = 'Regular maintenance is recommended';
                break;
        }
        
        return $recommendations;
    }
    
    /**
     * Returns error report
     */
    private static function get_error_report($message) {
        return [
            'status' => 'Error',
            'status_class' => 'status-error',
            'summary_text' => $message,
            'detailed_analysis' => [],
            'recommendations' => ['Please check the correctness of the entered data']
        ];
    }
}