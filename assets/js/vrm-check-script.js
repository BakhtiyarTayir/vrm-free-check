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

/**
 * Показать модальное окно покупки проверки
 */
function showPurchaseCheckModal(message, shopUrl) {
    showModal({
        type: 'warning',
        icon: '🛒',
        title: 'No Checks Available',
        subtitle: 'Purchase a VRM check to continue',
        message: message || 'You need to purchase a VRM check to view the full vehicle report.',
        buttons: [
            {
                text: 'Buy VRM Check (£9.99)',
                class: 'vrm-modal-btn-primary',
                icon: '💳',
                action: function() {
                    window.location.href = shopUrl || '/shop/';
                }
            },
            {
                text: 'Close',
                class: 'vrm-modal-btn-secondary',
                action: closeModal
            }
        ]
    });
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

function updateCreditsDisplay(credits) {
    // Обновить отображение баланса кредитов
    var creditsElement = jQuery('.user-credits-balance');
    if (creditsElement.length) {
        creditsElement.text(credits);
        console.log('Credits updated:', credits);
    }
    
    // Показать уведомление
    if (credits > 0) {
        console.log('Remaining credits:', credits);
    } else {
        console.log('No credits remaining');
    }
}

// Функции для модального окна
function showModal(options) {
    var modal = jQuery('#vrm-modal');
    
    // Если модальное окно не существует, создаём его
    if (modal.length === 0) {
        jQuery('body').append(
            '<div id="vrm-modal-overlay" class="vrm-modal-overlay">' +
                '<div id="vrm-modal" class="vrm-modal">' +
                    '<div class="vrm-modal-header">' +
                        '<div class="vrm-modal-icon"></div>' +
                        '<div class="vrm-modal-header-text">' +
                            '<h3 class="vrm-modal-title"></h3>' +
                            '<p class="vrm-modal-subtitle"></p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="vrm-modal-body">' +
                        '<p class="vrm-modal-message"></p>' +
                    '</div>' +
                    '<div class="vrm-modal-footer"></div>' +
                '</div>' +
            '</div>'
        );
        modal = jQuery('#vrm-modal');
    }
    
    var overlay = jQuery('#vrm-modal-overlay');
    
    // Установить иконку
    var icon = modal.find('.vrm-modal-icon');
    icon.removeClass('warning error info');
    icon.addClass(options.type || 'info');
    icon.html(options.icon || '⚠️');
    
    // Установить заголовок и подзаголовок
    modal.find('.vrm-modal-title').text(options.title || '');
    modal.find('.vrm-modal-subtitle').text(options.subtitle || '');
    
    // Установить сообщение
    modal.find('.vrm-modal-message').html(options.message || '');
    
    // Создать кнопки
    var footer = modal.find('.vrm-modal-footer');
    footer.empty();
    
    if (options.buttons && options.buttons.length > 0) {
        options.buttons.forEach(function(button) {
            var btnClass = 'vrm-modal-btn ' + (button.class || 'vrm-modal-btn-secondary');
            var btn = jQuery('<button>')
                .addClass(btnClass)
                .attr('type', 'button')
                .html(button.icon ? button.icon + ' ' + button.text : button.text)
                .on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Modal button clicked:', button.text);
                    closeModal();
                    if (button.action) {
                        console.log('Executing button action');
                        button.action();
                    }
                });
            footer.append(btn);
        });
    }
    
    // Показать модальное окно
    overlay.addClass('active');
    
    // Закрытие по клику на overlay
    overlay.off('click').on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // Закрытие по ESC
    jQuery(document).off('keydown.modal').on('keydown.modal', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

function closeModal() {
    jQuery('#vrm-modal-overlay').removeClass('active');
    jQuery(document).off('keydown.modal');
}

function showLoginModal(message, loginUrl, registerUrl) {
    console.log('showLoginModal called', {
        message: message,
        loginUrl: loginUrl,
        registerUrl: registerUrl
    });
    
    var finalRegisterUrl = registerUrl || (loginUrl.replace('wp-login.php', 'my-account/'));
    var finalLoginUrl = loginUrl.indexOf('my-account') > -1 ? loginUrl : loginUrl.replace('wp-login.php', 'my-account/');
    
    console.log('Final URLs:', {
        register: finalRegisterUrl,
        login: finalLoginUrl
    });
    
    showModal({
        type: 'warning',
        icon: '🔐',
        title: 'Login Required',
        subtitle: 'Premium features require authentication',
        message: message,
        buttons: [
            {
                text: 'Register',
                class: 'vrm-modal-btn-secondary',
                icon: '📝',
                action: function() {
                    console.log('Register button action, redirecting to:', finalRegisterUrl);
                    window.location.href = finalRegisterUrl;
                }
            },
            {
                text: 'Log In',
                class: 'vrm-modal-btn-primary',
                icon: '→',
                action: function() {
                    console.log('Login button action, redirecting to:', finalLoginUrl);
                    window.location.href = finalLoginUrl;
                }
            }
        ]
    });
}

function showCreditsModal(message, shopUrl, currentCredits) {
    var creditsInfo = currentCredits !== undefined 
        ? '<div class="vrm-credits-info"><p><strong>Current Balance:</strong> ' + currentCredits + ' credits</p></div>'
        : '';
    
    showModal({
        type: 'warning',
        icon: '💳',
        title: 'Credits Required',
        subtitle: 'Purchase credits to continue',
        message: message + creditsInfo,
        buttons: [
            {
                text: 'Maybe Later',
                class: 'vrm-modal-btn-secondary',
                action: function() {
                    // Просто закрыть
                }
            },
            {
                text: 'Buy Credits',
                class: 'vrm-modal-btn-success',
                icon: '🛒',
                action: function() {
                    window.location.href = shopUrl;
                }
            }
        ]
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
                
                // Обновить баланс кредитов если есть
                if (response.data.credits_remaining !== undefined) {
                    updateCreditsDisplay(response.data.credits_remaining);
                }
                
                if (response.data.cached) {
                    console.log('Results from cache');
                }
            } else {
                // Проверка требования авторизации
                if (response.data.login_required) {
                    console.log('Login required, showing login modal');
                    showLoginModal(response.data.message, response.data.login_url, response.data.register_url);
                    return;
                }
                
                // Проверка требования кредитов (старая система)
                if (response.data.credits_required) {
                    console.log('Credits required, showing credits modal');
                    showCreditsModal(response.data.message, response.data.shop_url, 0);
                    return;
                }
                
                // Проверка требования проверок (новая система)
                if (response.data.checks_required) {
                    console.log('Checks required, showing purchase modal');
                    showPurchaseCheckModal(response.data.message, response.data.shop_url);
                    return;
                }
                
                // Проверка уже существующей проверки
                if (response.data.already_checked) {
                    console.log('VRM already checked, redirecting to reports');
                    showModal({
                        type: 'info',
                        icon: '✅',
                        title: 'Already Checked',
                        subtitle: 'This vehicle is in your reports',
                        message: response.data.message,
                        buttons: [
                            {
                                text: 'View Reports',
                                class: 'vrm-modal-btn-primary',
                                icon: '📊',
                                action: function() {
                                    window.location.href = response.data.redirect_url;
                                }
                            },
                            {
                                text: 'Close',
                                class: 'vrm-modal-btn-secondary',
                                action: closeModal
                            }
                        ]
                    });
                    return;
                }
                
                // Обычная ошибка
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