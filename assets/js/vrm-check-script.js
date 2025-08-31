/**
 * VRM Check Plugin JavaScript
 */

jQuery(document).ready(function($) {
    var isLoading = false;
    
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
        
        checkVRM(vrm);
    });
    
    // Handle example button click
    $('.vrm-example-btn').on('click', function() {
        var exampleVRM = $(this).data('vrm');
        $('#vrm-input').val(exampleVRM);
        checkVRM(exampleVRM);
    });
    
    // Format VRM input
    $('#vrm-input').on('input', function() {
        var value = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
        $(this).val(value);
        hideError();
    });
    
    function checkVRM(vrm) {
        isLoading = true;
        showLoading();
        hideError();
        hideResults();
        
        // Логируем начало AJAX запроса
        console.log('VRM Check: Starting AJAX request', {
            vrm: vrm,
            timestamp: new Date().toISOString(),
            timeout: 30000
        });
        
        var startTime = Date.now();
        
        $.ajax({
            url: vrm_check_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vrm_check',
                vrm: vrm,
                nonce: vrm_check_ajax.nonce,
                is_premium: vrm_check_ajax.is_premium
            },
            timeout: 30000, // 30 seconds timeout
            success: function(response) {
                var executionTime = Date.now() - startTime;
                
                // Логируем успешный ответ
                console.log('VRM Check: AJAX request successful', {
                    vrm: vrm,
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
                    // Логируем ошибку от сервера
                    console.error('VRM Check: Server returned error', {
                        vrm: vrm,
                        error: response.data.message,
                        executionTime: executionTime + 'ms'
                    });
                    showError(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                var executionTime = Date.now() - startTime;
                
                // Детальное логирование ошибок
                var errorDetails = {
                    vrm: vrm,
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
                    vrm: vrm,
                    totalTime: executionTime + 'ms'
                });
                
                isLoading = false;
                hideLoading();
            }
        });
    }
    
    function showLoading() {
        $('.vrm-btn-text').hide();
        $('.vrm-btn-loading').show();
        $('.vrm-submit-btn').addClass('loading').prop('disabled', true);
    }
    
    function hideLoading() {
        $('.vrm-btn-text').show();
        $('.vrm-btn-loading').hide();
        $('.vrm-submit-btn').removeClass('loading').prop('disabled', false);
    }
    
    function showResults(html) {
        $('#vrm-check-results').html(html).slideDown();
        $('html, body').animate({
            scrollTop: $('#vrm-check-results').offset().top - 50
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
    }
    
    function hideResults() {
        $('#vrm-check-results').slideUp();
    }
    
    function showError(message) {
        $('.vrm-error-message').text(message);
        $('#vrm-check-error').slideDown();
    }
    
    function hideError() {
        $('#vrm-check-error').slideUp();
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
});