/**
 * VRM Check Plugin JavaScript
 */

// Глобальные переменные
var isLoading = false;

// Глобальные вспомогательные функции
function showError(message) {
    jQuery('.vrm-error-message').text(message);
    jQuery('#vrm-check-error').slideDown();
}

function hideError() {
    jQuery('#vrm-check-error').slideUp();
}

function hideResults() {
    jQuery('#vrm-check-results').slideUp();
}

function showResults(html) {
    jQuery('#vrm-check-results').html(html).slideDown();
    jQuery('html, body').animate({
        scrollTop: jQuery('#vrm-check-results').offset().top - 50
    }, 500);
    
    // Initialize charts after results are displayed
    if (typeof window.initializeMileageChart === 'function') {
        setTimeout(function() {
            window.initializeMileageChart();
        }, 100); // Small delay to ensure DOM is updated
    }
    
    // Initialize CO2 chart after results are displayed
    if (typeof window.initializeCO2Chart === 'function') {
        setTimeout(function() {
            window.initializeCO2Chart();
        }, 100); // Small delay to ensure DOM is updated
    }
    
    isLoading = false;
    hideLoading();
}

function showLoading() {
    jQuery('.vrm-basic-btn .vrm-btn-text').hide();
    jQuery('.vrm-basic-btn .vrm-btn-loading').show();
    jQuery('.vrm-basic-btn').addClass('loading').prop('disabled', true);
    jQuery('.vrm-premium-btn').prop('disabled', true);
}

function showLoadingPremium() {
    jQuery('.vrm-premium-btn .vrm-btn-text').hide();
    jQuery('.vrm-premium-btn .vrm-btn-loading').show();
    jQuery('.vrm-premium-btn').addClass('loading').prop('disabled', true);
    jQuery('.vrm-basic-btn').prop('disabled', true);
}

function hideLoading() {
    jQuery('.vrm-basic-btn .vrm-btn-text').show();
    jQuery('.vrm-basic-btn .vrm-btn-loading').hide();
    jQuery('.vrm-basic-btn').removeClass('loading').prop('disabled', false);
    jQuery('.vrm-premium-btn').prop('disabled', false);
}

function hideLoadingPremium() {
    jQuery('.vrm-premium-btn .vrm-btn-text').show();
    jQuery('.vrm-premium-btn .vrm-btn-loading').hide();
    jQuery('.vrm-premium-btn').removeClass('loading').prop('disabled', false);
    jQuery('.vrm-basic-btn').prop('disabled', false);
}

function isValidVRM(vrm) {
    // Basic UK VRM validation patterns
    var patterns = [
        /^[A-Z]{2}[0-9]{2}[A-Z]{3}$/, // Current format: AB12CDE
        /^[A-Z][0-9]{1,3}[A-Z]{3}$/, // Prefix format: A123BCD
        /^[A-Z]{3}[0-9]{1,3}[A-Z]$/, // Suffix format: ABC123D
        /^[0-9]{1,4}[A-Z]{1,3}$/,    // Dateless format: 1234AB
        /^[A-Z]{1,3}[0-9]{1,4}$/     // Reversed dateless: AB1234
    ];
    
    return patterns.some(function(pattern) {
        return pattern.test(vrm);
    });
}

function performAjaxRequest(ajaxData, startTime, isPremium) {
    console.log('AJAX data:', ajaxData);
    
    jQuery.ajax({
        url: vrm_check_ajax.ajax_url,
        type: 'POST',
        data: ajaxData,
        timeout: 30000, // 30 seconds timeout
        success: function(response) {
            var executionTime = Date.now() - startTime;
            
            console.log('VRM Check: AJAX request successful', {
                executionTime: executionTime + 'ms',
                responseSize: JSON.stringify(response).length + ' bytes',
                cached: response.data && response.data.cached
            });
            
            if (response.success) {
                showResults(response.data.html);
                if (response.data.cached) {
                    console.log('Results from cache');
                }
            } else {
                    console.error('VRM Check: Server returned error', {
                        error: response.data.message,
                        executionTime: executionTime + 'ms'
                    });
                    showError(response.data.message || 'An error occurred while checking the vehicle');
                }
        },
        error: function(xhr, status, error) {
            var executionTime = Date.now() - startTime;
            
            var errorDetails = {
                status: status,
                error: error,
                executionTime: executionTime + 'ms',
                responseCode: xhr.status,
                responseText: xhr.responseText ? xhr.responseText.substring(0, 500) : 'No response text',
                timestamp: new Date().toISOString()
            };
            
            console.error('VRM Check: AJAX request failed', errorDetails);
            
            if (status === 'timeout') {
                console.error('VRM Check: Request timed out after ' + executionTime + 'ms');
                showError('Request timed out. Please try again.');
            } else if (status === 'error') {
                console.error('VRM Check: Network error occurred', {
                    responseCode: xhr.status,
                    statusText: xhr.statusText
                });
                showError('An error occurred while checking the vehicle. Please try again.');
            } else if (status === 'abort') {
                console.error('VRM Check: Request was aborted');
                showError('Request was cancelled. Please try again.');
            } else {
                console.error('VRM Check: Unknown error occurred', {
                    status: status,
                    error: error
                });
                showError('An error occurred while checking the vehicle. Please try again.');
            }
        },
        complete: function() {
             var executionTime = Date.now() - startTime;
             console.log('VRM Check: AJAX request completed', {
                 totalTime: executionTime + 'ms'
             });
             
             isLoading = false;
             if (isPremium) {
                 hideLoadingPremium();
             } else {
                 hideLoading();
             }
         }
    });
}

// Глобальные функции для onclick обработчиков
function checkVRMBasic() {
    console.log('=== Basic VRM Check Function Started ===');
    
    var vrm = jQuery('#vrm-input').val().trim().toUpperCase();
    
    if (!vrm) {
        showError('Please enter a vehicle registration number');
        return;
    }
    
    if (!isValidVRM(vrm)) {
        showError('Please enter a valid UK registration number');
        return;
    }
    
    isLoading = true;
    showLoading();
    hideError();
    hideResults();
    
    console.log('Basic VRM check - VRM:', vrm);
    
    var startTime = Date.now();
    
    performAjaxRequest({
        action: 'vrm_check',
        vrm: vrm,
        nonce: vrm_check_ajax.nonce
    }, startTime, false);
}

function checkVRMPremium() {
    console.log('=== Premium VRM Check Function Started ===');
    
    var vrm = jQuery('#vrm-input').val().trim().toUpperCase();
    
    if (!vrm) {
        showError('Please enter a vehicle registration number');
        return;
    }
    
    if (!isValidVRM(vrm)) {
        showError('Please enter a valid UK registration number');
        return;
    }
    
    isLoading = true;
    showLoadingPremium();
    hideError();
    hideResults();
    
    console.log('Premium VRM check - VRM:', vrm);
    
    var startTime = Date.now();
    
    performAjaxRequest({
        action: 'vrm_check_premium',
        vrm: vrm,
        nonce: vrm_check_ajax.nonce
    }, startTime, true);
}

jQuery(document).ready(function($) {
    
    // Handle form submission
    $('#vrm-check-form').on('submit', function(e) {
        e.preventDefault();
        
        if (isLoading) return;
        
        var vrm = $('#vrm-input').val().trim().toUpperCase();
        
        if (!vrm) {
            showError('Please enter a vehicle registration number');
            return;
        }
        
        // Validate VRM format (basic UK format)
        if (!isValidVRM(vrm)) {
            showError('Please enter a valid UK registration number');
            return;
        }
        
        checkVRMBasic(vrm);
    });
    
    // Handle example button click
    $('.vrm-example-btn').on('click', function() {
        var exampleVRM = $(this).data('vrm');
        $('#vrm-input').val(exampleVRM);
        checkVRMBasic(exampleVRM);
    });
    
    // Format VRM input
    $('#vrm-input').on('input', function() {
        var value = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
        $(this).val(value);
        hideError();
    });
});