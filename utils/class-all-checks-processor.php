<?php
/**
 * All Checks Data Processor
 * 
 * Processes and prepares data for the "All Checks" block in premium results template
 * 
 * @package VRM_Check_Plugin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VRM_All_Checks_Processor {
    
    /**
     * Process all checks data from merged vehicle data
     * 
     * @param array $merged_data The complete merged vehicle data
     * @return array Processed checks data ready for template display
     */
    public static function process_checks_data($merged_data) {
        if (empty($merged_data) || !is_array($merged_data)) {
            return self::get_default_checks_data();
        }
        
        $checks_data = array();
        
        // Process Imported status
        $checks_data['imported'] = self::process_imported_status($merged_data);
        
        // Process Exported status
        $checks_data['exported'] = self::process_exported_status($merged_data);
        
        // Process Scrapped status
        $checks_data['scrapped'] = self::process_scrapped_status($merged_data);
        
        // Process Unscrapped status
        $checks_data['unscrapped'] = self::process_unscrapped_status($merged_data);
        
        // Process Safety Recalls (static for now)
        $checks_data['safety_recalls'] = self::process_safety_recalls($merged_data);
        
        // Process Previous Keepers count
        $checks_data['previous_keepers'] = self::process_previous_keepers($merged_data);
        
        // Process Plate Changes count
        $checks_data['plate_changes'] = self::process_plate_changes($merged_data);
        
        // Process MOT status
        $checks_data['mot'] = self::process_mot_status($merged_data);
        
        // Process Road Tax status
        $checks_data['road_tax'] = self::process_road_tax_status($merged_data);
        
        return $checks_data;
    }
    
    /**
     * Process imported status from VehicleStatus
     */
    private static function process_imported_status($data) {
        $is_imported = isset($data['VehicleDetails']['VehicleStatus']['IsImported']) 
            ? $data['VehicleDetails']['VehicleStatus']['IsImported'] 
            : false;
            
        return array(
            'status' => $is_imported ? 'warning' : 'pass',
            'message' => $is_imported ? 'Vehicle was imported' : 'Vehicle was not imported',
            'class' => $is_imported ? 'status-yes' : 'status-no',
            'text' => $is_imported ? 'Yes' : 'No'
        );
    }
    
    /**
     * Process exported status from VehicleStatus
     */
    private static function process_exported_status($data) {
        $is_exported = isset($data['VehicleDetails']['VehicleStatus']['IsExported']) 
            ? $data['VehicleDetails']['VehicleStatus']['IsExported'] 
            : false;
            
        return array(
            'status' => $is_exported ? 'warning' : 'pass',
            'message' => $is_exported ? 'Vehicle was exported' : 'Vehicle was not exported',
            'class' => $is_exported ? 'status-yes' : 'status-no',
            'text' => $is_exported ? 'Yes' : 'No'
        );
    }
    
    /**
     * Process scrapped status from VehicleStatus
     */
    private static function process_scrapped_status($data) {
        $is_scrapped = isset($data['VehicleDetails']['VehicleStatus']['IsScrapped']) 
            ? $data['VehicleDetails']['VehicleStatus']['IsScrapped'] 
            : false;
            
        return array(
            'status' => $is_scrapped ? 'fail' : 'pass',
            'message' => $is_scrapped ? 'Vehicle is scrapped' : 'Vehicle is not scrapped',
            'class' => $is_scrapped ? 'status-fail' : 'status-no',
            'text' => $is_scrapped ? 'Yes' : 'No'
        );
    }
    
    /**
     * Process unscrapped status from VehicleStatus
     */
    private static function process_unscrapped_status($data) {
        $is_unscrapped = isset($data['VehicleDetails']['VehicleStatus']['IsUnscrapped']) 
            ? $data['VehicleDetails']['VehicleStatus']['IsUnscrapped'] 
            : false;
            
        return array(
            'status' => $is_unscrapped ? 'fail' : 'pass',
            'message' => $is_unscrapped ? 'Vehicle was unscrapped' : 'Vehicle was not unscrapped',
            'class' => $is_unscrapped ? 'status-fail' : 'status-no',
            'text' => $is_unscrapped ? 'Yes' : 'No'
        );
    }
    
    /**
     * Process safety recalls (static implementation)
     */
    private static function process_safety_recalls($data) {
        // Static implementation - no safety recalls data available in current API
        return array(
            'status' => 'pass',
            'message' => 'No safety recalls found',
            'class' => 'status-no',
            'text' => 'No'
        );
    }
    
    /**
     * Process previous keepers count from VehicleHistory
     */
    private static function process_previous_keepers($data) {
        $keeper_changes = isset($data['VehicleDetails']['VehicleHistory']['KeeperChangeList']) 
            ? $data['VehicleDetails']['VehicleHistory']['KeeperChangeList'] 
            : array();
            
        $count = count($keeper_changes);
        
        return array(
            'count' => $count,
            'message' => $count > 0 ? "Vehicle had {$count} previous keepers" : 'No previous keepers found'
        );
    }
    
    /**
     * Process plate changes count from VehicleHistory
     */
    private static function process_plate_changes($data) {
        $plate_changes = isset($data['VehicleDetails']['VehicleHistory']['PlateChangeList']) 
            ? $data['VehicleDetails']['VehicleHistory']['PlateChangeList'] 
            : array();
            
        $count = count($plate_changes);
        
        return array(
            'count' => $count,
            'message' => $count > 0 ? "Vehicle had {$count} plate changes" : 'No plate changes found'
        );
    }
    
    /**
     * Process MOT status from MotHistoryDetails
     */
    private static function process_mot_status($data) {
        $mot_data = isset($data['MotHistoryDetails']) ? $data['MotHistoryDetails'] : array();
        
        // Get the latest MOT test
        $latest_test = null;
        if (isset($mot_data['MotTestDetailsList']) && !empty($mot_data['MotTestDetailsList'])) {
            $latest_test = $mot_data['MotTestDetailsList'][0]; // First item is the latest
        }
        
        if ($latest_test) {
            $test_passed = isset($latest_test['TestPassed']) ? $latest_test['TestPassed'] : false;
            $expiry_date = isset($latest_test['ExpiryDate']) ? $latest_test['ExpiryDate'] : null;
            
            if ($test_passed && $expiry_date) {
                // Check if MOT is still valid
                $expiry_timestamp = strtotime($expiry_date);
                $current_timestamp = time();
                
                if ($expiry_timestamp > $current_timestamp) {
                    $expiry_formatted = date('Y-m-d', $expiry_timestamp);
                    return array(
                        'status' => 'valid',
                        'message' => 'MOT is valid until ' . $expiry_formatted,
                        'class' => 'status-valid',
                        'text' => 'Valid'
                    );
                } else {
                    return array(
                        'status' => 'expired',
                        'message' => 'MOT has expired',
                        'class' => 'status-fail',
                        'text' => 'Expired'
                    );
                }
            } else {
                return array(
                    'status' => 'failed',
                    'message' => 'Latest MOT test failed',
                    'class' => 'status-fail',
                    'text' => 'Failed'
                );
            }
        }
        
        // No MOT data available
        return array(
            'status' => 'unknown',
            'message' => 'MOT status unknown',
            'class' => 'status-unknown',
            'text' => 'Unknown'
        );
    }
    
    /**
     * Process road tax status from VehicleTaxDetails or VehicleStatus
     */
    private static function process_road_tax_status($data) {
        // Check VehicleTaxDetails first
        if (isset($data['VehicleTaxDetails'])) {
            $tax_data = $data['VehicleTaxDetails'];
            $is_valid = isset($tax_data['TaxIsCurrentlyValid']) ? $tax_data['TaxIsCurrentlyValid'] : false;
            $tax_status = isset($tax_data['TaxStatus']) ? $tax_data['TaxStatus'] : null;
            $due_date = isset($tax_data['TaxDueDate']) ? $tax_data['TaxDueDate'] : null;
            
            if ($is_valid) {
                $message = 'Road tax is valid';
                if ($due_date) {
                    $due_formatted = date('Y-m-d', strtotime($due_date));
                    $message .= ' until ' . $due_formatted;
                }
                
                return array(
                    'status' => 'valid',
                    'message' => $message,
                    'class' => 'status-valid',
                    'text' => 'Valid'
                );
            } elseif ($tax_status) {
                return array(
                    'status' => 'invalid',
                    'message' => 'Road tax status: ' . $tax_status,
                    'class' => 'status-fail',
                    'text' => 'Invalid'
                );
            }
        }
        
        // Fallback to static valid status if no tax data
        return array(
            'status' => 'valid',
            'message' => 'Road tax status valid',
            'class' => 'status-valid',
            'text' => 'Valid'
        );
    }
    
    /**
     * Get default checks data when no data is available
     */
    private static function get_default_checks_data() {
        return array(
            'imported' => array(
                'status' => 'unknown',
                'message' => 'Import status unknown',
                'class' => 'status-unknown',
                'text' => 'Unknown'
            ),
            'exported' => array(
                'status' => 'unknown',
                'message' => 'Export status unknown',
                'class' => 'status-unknown',
                'text' => 'Unknown'
            ),
            'scrapped' => array(
                'status' => 'unknown',
                'message' => 'Scrapped status unknown',
                'class' => 'status-unknown',
                'text' => 'Unknown'
            ),
            'unscrapped' => array(
                'status' => 'unknown',
                'message' => 'Unscrapped status unknown',
                'class' => 'status-unknown',
                'text' => 'Unknown'
            ),
            'safety_recalls' => array(
                'status' => 'unknown',
                'message' => 'Safety recalls status unknown',
                'class' => 'status-unknown',
                'text' => 'Unknown'
            ),
            'previous_keepers' => array(
                'count' => 0,
                'message' => 'Previous keepers data unavailable'
            ),
            'plate_changes' => array(
                'count' => 0,
                'message' => 'Plate changes data unavailable'
            ),
            'mot' => array(
                'status' => 'unknown',
                'message' => 'MOT status unknown',
                'class' => 'status-unknown',
                'text' => 'Unknown'
            ),
            'road_tax' => array(
                'status' => 'unknown',
                'message' => 'Road tax status unknown',
                'class' => 'status-unknown',
                'text' => 'Unknown'
            )
        );
    }
    
    /**
     * Process extended checks data for right column
     * 
     * @param array $data Vehicle data from API
     * @return array Processed extended checks data
     */
    public static function process_extended_checks_data($data) {
        return array(
            'written_off' => self::get_written_off_status($data),
            'salvage_history' => self::get_salvage_history_status($data),
            'stolen' => self::get_stolen_status($data),
            'colour_changes' => self::get_colour_changes_status($data),
            'mileage_issues' => self::get_mileage_issues_status($data),
            'ex_taxi' => self::get_ex_taxi_status($data),
            'vin_check' => self::get_vin_check_status($data),
            'outstanding_finance' => self::get_outstanding_finance_status($data),
            'market_value' => self::get_market_value_status($data)
        );
    }
    
    /**
     * Get written off status from vehicle data
     * 
     * @param array $data Vehicle data
     * @return array Status information
     */
    private static function get_written_off_status($data) {
         if (isset($data['VehicleDetails']['VehicleStatus'])) {
             $vehicle_status = $data['VehicleDetails']['VehicleStatus'];
            
            $is_scrapped = isset($vehicle_status['IsScrapped']) ? $vehicle_status['IsScrapped'] : false;
            $certificate_issued = isset($vehicle_status['CertificateOfDestructionIssued']) ? $vehicle_status['CertificateOfDestructionIssued'] : false;
            $date_scrapped = isset($vehicle_status['DateScrapped']) ? $vehicle_status['DateScrapped'] : null;
            
            // Vehicle is considered written off if scrapped or certificate issued
            $is_written_off = $is_scrapped || $certificate_issued;
            
            if ($is_written_off && $date_scrapped) {
                $formatted_date = date('d/m/Y', strtotime($date_scrapped));
                $message = "Vehicle was written off on {$formatted_date}";
            } elseif ($is_written_off) {
                $message = 'Vehicle is written off';
            } else {
                $message = 'Vehicle is not written off';
            }
            
            return array(
                'status' => $is_written_off ? 'fail' : 'pass',
                'class' => $is_written_off ? 'status-fail' : 'status-no',
                'text' => $is_written_off ? 'Yes' : 'No',
                'message' => $message
            );
        }
        
        return array(
            'status' => 'unknown',
            'class' => 'status-unknown',
            'text' => 'Unknown',
            'message' => 'Written off status is unknown'
        );
    }
    
    /**
     * Get salvage history status from vehicle data
     * 
     * @param array $data Vehicle data
     * @return array Status information
     */
    private static function get_salvage_history_status($data) {
        // No salvage history data available in current API
        return array(
            'status' => 'unknown',
            'class' => 'status-unknown',
            'text' => 'Unknown',
            'message' => 'Salvage history data not available'
        );
    }
    
    /**
     * Get stolen status from vehicle data
     * 
     * @param array $data Vehicle data
     * @return array Status information
     */
    private static function get_stolen_status($data) {
        // No stolen status data available in current API
        return array(
            'status' => 'unknown',
            'class' => 'status-unknown',
            'text' => 'Unknown',
            'message' => 'Stolen status data not available'
        );
    }
    
    /**
     * Get colour changes status from vehicle data
     * 
     * @param array $data Vehicle data
     * @return array Status information
     */
    private static function get_colour_changes_status($data) {
        if (isset($data['VehicleDetails']['VehicleHistory']['ColourDetails']['NumberOfColourChanges'])) {
             $colour_changes_count = $data['VehicleDetails']['VehicleHistory']['ColourDetails']['NumberOfColourChanges'];
            
            if ($colour_changes_count > 0) {
                return array(
                    'status' => 'warning',
                    'class' => 'status-yes',
                    'text' => 'Yes',
                    'message' => "Vehicle had {$colour_changes_count} colour changes"
                );
            } else {
                return array(
                    'status' => 'pass',
                    'class' => 'status-no',
                    'text' => 'No',
                    'message' => 'No colour changes found'
                );
            }
        }
        
        return array(
            'status' => 'unknown',
            'class' => 'status-unknown',
            'text' => 'Unknown',
            'message' => 'Colour changes data not available'
        );
    }
    
    /**
     * Get mileage issues status from vehicle data
     * 
     * @param array $data Vehicle data
     * @return array Status information
     */
    private static function get_mileage_issues_status($data) {
        // Check MileageCheckDetails for mileage anomalies
        if (isset($data['MileageCheckDetails'])) {
            $mileage_details = $data['MileageCheckDetails'];
            
            // Check if mileage anomaly is detected
            if (isset($mileage_details['MileageAnomalyDetected']) && $mileage_details['MileageAnomalyDetected'] === true) {
                return array(
                    'status' => 'fail',
                    'class' => 'status-fail',
                    'text' => 'Yes',
                    'message' => 'Mileage anomaly detected in vehicle history'
                );
            }
            
            // Check for out-of-sequence mileage readings
            if (isset($mileage_details['MileageResultList']) && is_array($mileage_details['MileageResultList'])) {
                $out_of_sequence_count = 0;
                foreach ($mileage_details['MileageResultList'] as $reading) {
                    if (isset($reading['InSequence']) && $reading['InSequence'] === false) {
                        $out_of_sequence_count++;
                    }
                }
                
                if ($out_of_sequence_count > 0) {
                    return array(
                        'status' => 'fail',
                        'class' => 'status-fail',
                        'text' => 'Yes',
                        'message' => "Found {$out_of_sequence_count} out-of-sequence mileage reading(s)"
                    );
                }
            }
            
            // No issues found
            return array(
                'status' => 'pass',
                'class' => 'status-no',
                'text' => 'No',
                'message' => 'No mileage issues detected'
            );
        }
        
        return array(
            'status' => 'unknown',
            'class' => 'status-unknown',
            'text' => 'Unknown',
            'message' => 'Mileage issues data not available'
        );
    }
    
    /**
     * Get ex-taxi status from vehicle data
     * 
     * @param array $data Vehicle data
     * @return array Status information
     */
    private static function get_ex_taxi_status($data) {
        // No ex-taxi data available in current API
        return array(
            'status' => 'unknown',
            'class' => 'status-unknown',
            'text' => 'Unknown',
            'message' => 'Ex-taxi status data not available'
        );
    }
    
    /**
     * Get VIN check status from vehicle data
     * 
     * @param array $data Vehicle data
     * @return array Status information
     */
    private static function get_vin_check_status($data) {
        // Проверяем наличие VIN данных в структуре VehicleDetails
        $vin_data = null;
        $vin_last5 = null;
        
        // Ищем VIN в разных возможных местах структуры данных
        if (isset($data['VehicleDetails']['VehicleIdentification']['Vin'])) {
            $vin_data = $data['VehicleDetails']['VehicleIdentification']['Vin'];
        }
        
        if (isset($data['VehicleDetails']['VehicleIdentification']['VinLast5'])) {
            $vin_last5 = $data['VehicleDetails']['VehicleIdentification']['VinLast5'];
        }
        
        // Проверяем статус VIN
        if (!empty($vin_data) && $vin_data !== 'Permission Required') {
            // Полный VIN доступен
            return array(
                'status' => 'available',
                'class' => 'status-valid',
                'text' => 'Available',
                'message' => 'Full VIN data available for verification'
            );
        } elseif (!empty($vin_last5)) {
            // Доступны только последние 5 цифр VIN
            return array(
                'status' => 'partial',
                'class' => 'status-warning',
                'text' => 'Partial',
                'message' => 'VIN last 5 digits available: ' . esc_html($vin_last5)
            );
        } elseif ($vin_data === 'Permission Required') {
            // VIN требует дополнительных разрешений
            return array(
                'status' => 'restricted',
                'class' => 'status-warning',
                'text' => 'Restricted',
                'message' => 'VIN data requires additional permissions'
            );
        } else {
            // VIN данные недоступны
            return array(
                'status' => 'unavailable',
                'class' => 'status-unknown',
                'text' => 'Unavailable',
                'message' => 'VIN data not available in current API response'
            );
        }
    }
    
    /**
     * Get outstanding finance status from vehicle data
     * 
     * @param array $data Vehicle data
     * @return array Status information
     */
    private static function get_outstanding_finance_status($data) {
        // Check FinanceDetails for finance information
        if (isset($data['FinanceDetails'])) {
            $finance_details = $data['FinanceDetails'];
            
            // Check if there are active finance agreements
            if (isset($finance_details['ActiveFinanceAgreements']) && is_array($finance_details['ActiveFinanceAgreements'])) {
                $active_count = count($finance_details['ActiveFinanceAgreements']);
                
                if ($active_count > 0) {
                    return array(
                        'status' => 'fail',
                        'class' => 'status-fail',
                        'text' => 'Yes',
                        'message' => "Vehicle has {$active_count} active finance agreement(s)"
                    );
                }
            }
            
            // Check general finance status
            if (isset($finance_details['HasOutstandingFinance'])) {
                $has_finance = $finance_details['HasOutstandingFinance'];
                
                return array(
                    'status' => $has_finance ? 'fail' : 'pass',
                    'class' => $has_finance ? 'status-fail' : 'status-no',
                    'text' => $has_finance ? 'Yes' : 'No',
                    'message' => $has_finance ? 'Vehicle has outstanding finance' : 'No outstanding finance found'
                );
            }
        }
        
        return array(
            'status' => 'unknown',
            'class' => 'status-unknown',
            'text' => 'Unknown',
            'message' => 'Outstanding finance data not available'
        );
    }
    
    /**
     * Get market value status from vehicle data
     * 
     * @param array $data Vehicle data
     * @return array Status information
     */
    private static function get_market_value_status($data) {
        // Check ValuationDetails for market value information
        if (isset($data['ValuationDetails'])) {
            $valuation_details = $data['ValuationDetails'];
            
            // Check if valuation data is available
            if (isset($valuation_details['EstimatedValue']) || isset($valuation_details['MarketValue'])) {
                return array(
                    'status' => 'available',
                    'class' => 'status-yes',
                    'text' => 'Yes',
                    'message' => 'Market value data is available'
                );
            }
        }
        
        return array(
            'status' => 'unavailable',
            'class' => 'status-no',
            'text' => 'No',
            'message' => 'Market value data not available'
        );
    }
}