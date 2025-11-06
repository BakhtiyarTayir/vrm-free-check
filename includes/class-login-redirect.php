<?php
/**
 * Login Redirect Handler
 * 
 * Обрабатывает перенаправление после логина/регистрации
 * Если в сессии сохранён VRM, перенаправляет на страницу проверки
 *
 * @package VRM_Check_Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class VRM_Login_Redirect {
    
    /**
     * Инициализация хуков
     */
    public static function init() {
        // Хук после логина
        add_filter('woocommerce_login_redirect', array(__CLASS__, 'redirect_after_login'), 10, 2);
        
        // Хук после регистрации
        add_filter('woocommerce_registration_redirect', array(__CLASS__, 'redirect_after_registration'), 10, 1);
        
        // Добавляем скрипт для автоматического запуска проверки
        add_action('wp_footer', array(__CLASS__, 'add_auto_check_script'));
    }
    
    /**
     * Перенаправление после логина
     */
    public static function redirect_after_login($redirect, $user) {
        if (!session_id()) {
            session_start();
        }
        
        // Проверяем, есть ли сохранённый VRM
        if (isset($_SESSION['vrm_check_pending']) && !empty($_SESSION['vrm_check_pending'])) {
            $redirect_url = isset($_SESSION['vrm_check_redirect']) 
                ? $_SESSION['vrm_check_redirect'] 
                : home_url('/full-check-page/');
            
            // Добавляем VRM как параметр URL
            $vrm = $_SESSION['vrm_check_pending'];
            $redirect_url = add_query_arg('vrm', urlencode($vrm), $redirect_url);
            $redirect_url = add_query_arg('auto_check', '1', $redirect_url);
            
            // Логируем
            if (class_exists('VRM_Logger')) {
                $logger = new VRM_Logger();
                $logger->log('info', 'Redirecting after login', array(
                    'user_id' => $user->ID,
                    'vrm' => $vrm,
                    'redirect_url' => $redirect_url
                ));
            }
            
            // Очищаем сессию
            unset($_SESSION['vrm_check_pending']);
            unset($_SESSION['vrm_check_redirect']);
            
            return $redirect_url;
        }
        
        return $redirect;
    }
    
    /**
     * Перенаправление после регистрации
     */
    public static function redirect_after_registration($redirect) {
        if (!session_id()) {
            session_start();
        }
        
        // Проверяем, есть ли сохранённый VRM
        if (isset($_SESSION['vrm_check_pending']) && !empty($_SESSION['vrm_check_pending'])) {
            $redirect_url = isset($_SESSION['vrm_check_redirect']) 
                ? $_SESSION['vrm_check_redirect'] 
                : home_url('/full-check-page/');
            
            // Добавляем VRM как параметр URL
            $vrm = $_SESSION['vrm_check_pending'];
            $redirect_url = add_query_arg('vrm', urlencode($vrm), $redirect_url);
            $redirect_url = add_query_arg('auto_check', '1', $redirect_url);
            
            // Логируем
            if (class_exists('VRM_Logger')) {
                $logger = new VRM_Logger();
                $logger->log('info', 'Redirecting after registration', array(
                    'vrm' => $vrm,
                    'redirect_url' => $redirect_url
                ));
            }
            
            // Очищаем сессию
            unset($_SESSION['vrm_check_pending']);
            unset($_SESSION['vrm_check_redirect']);
            
            return $redirect_url;
        }
        
        return $redirect;
    }
    
    /**
     * Добавляет JavaScript для автоматического запуска проверки
     */
    public static function add_auto_check_script() {
        // Проверяем, есть ли параметр auto_check в URL
        if (!isset($_GET['auto_check']) || $_GET['auto_check'] != '1') {
            return;
        }
        
        // Проверяем, есть ли VRM в URL
        if (!isset($_GET['vrm']) || empty($_GET['vrm'])) {
            return;
        }
        
        $vrm = sanitize_text_field($_GET['vrm']);
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Auto-check triggered for VRM: <?php echo esc_js($vrm); ?>');
            
            // Ждём загрузки страницы
            setTimeout(function() {
                // Находим поле ввода VRM (пробуем разные селекторы)
                var vrmInput = $('#vrm-input-premium, #vrm-input, input[name="vrm"], input.vrm-input').first();
                
                if (vrmInput.length) {
                    console.log('VRM input found, filling with: <?php echo esc_js($vrm); ?>');
                    
                    // Заполняем поле
                    vrmInput.val('<?php echo esc_js($vrm); ?>');
                    
                    // Находим кнопку проверки (добавлены новые селекторы)
                    var checkButton = $(
                        '#vrm-check-premium-btn, ' +
                        '#vrm-check-btn, ' +
                        '.vrm-premium-btn, ' +
                        '.vrm-submit-btn, ' +
                        'button[onclick*="checkVRMPremium"], ' +
                        'button[type="submit"]'
                    ).first();
                    
                    if (checkButton.length) {
                        console.log('Check button found:', checkButton.attr('class'));
                        
                        // Показываем уведомление
                        if (typeof showNotification === 'function') {
                            showNotification('Continuing your vehicle check...', 'info');
                        }
                        
                        // Запускаем проверку через 500ms
                        setTimeout(function() {
                            // Проверяем, есть ли onclick атрибут
                            var onclickAttr = checkButton.attr('onclick');
                            if (onclickAttr) {
                                console.log('Executing onclick function');
                                // Выполняем функцию из onclick
                                eval(onclickAttr);
                            } else {
                                console.log('Triggering click event');
                                checkButton.trigger('click');
                            }
                            
                            // Удаляем параметры из URL
                            if (window.history && window.history.replaceState) {
                                var cleanUrl = window.location.pathname;
                                window.history.replaceState({}, document.title, cleanUrl);
                            }
                        }, 500);
                    } else {
                        console.warn('Check button not found');
                        console.log('Available buttons:', $('button').length);
                        $('button').each(function(i, btn) {
                            console.log('Button ' + i + ':', $(btn).attr('class'), $(btn).attr('onclick'));
                        });
                    }
                } else {
                    console.warn('VRM input not found');
                    console.log('Available inputs:', $('input').length);
                    $('input').each(function(i, inp) {
                        console.log('Input ' + i + ':', $(inp).attr('name'), $(inp).attr('id'), $(inp).attr('class'));
                    });
                }
            }, 1000);
        });
        </script>
        <?php
    }
}

// Инициализация
VRM_Login_Redirect::init();
