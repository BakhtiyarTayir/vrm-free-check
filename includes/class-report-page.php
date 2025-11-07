<?php
/**
 * Report Page Handler
 * 
 * Отображает полный отчёт VRM на отдельной странице
 *
 * @package VRM_Check_Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

use VrmCheckPlugin\HistoryManager;

class VRM_Report_Page {
    
    /**
     * Инициализация
     */
    public static function init() {
        // Добавить rewrite rules
        add_action('init', array(__CLASS__, 'add_rewrite_rules'));
        
        // Добавить query vars
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        
        // Обработать запрос
        add_action('template_redirect', array(__CLASS__, 'handle_report_page'));
    }
    
    /**
     * Добавить rewrite rules
     */
    public static function add_rewrite_rules() {
        add_rewrite_rule(
            '^vrm-report/([0-9]+)/?$',
            'index.php?vrm_report_id=$matches[1]',
            'top'
        );
        
        // Сбросить rewrite rules при первой активации
        if (get_option('vrm_report_flush_rewrite_rules') !== 'done') {
            flush_rewrite_rules();
            update_option('vrm_report_flush_rewrite_rules', 'done');
        }
    }
    
    /**
     * Добавить query vars
     */
    public static function add_query_vars($vars) {
        $vars[] = 'vrm_report_id';
        return $vars;
    }
    
    /**
     * Обработать запрос страницы отчёта
     */
    public static function handle_report_page() {
        $report_id = get_query_var('vrm_report_id');
        
        if (!$report_id) {
            return;
        }
        
        // Проверка авторизации
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
            exit;
        }
        
        $user_id = get_current_user_id();
        
        // Получаем данные проверки
        $check = HistoryManager::get_check_by_id($report_id, $user_id);
        
        if (!$check) {
            wp_die(__('Report not found or you do not have permission to view it.', 'vrm-check-plugin'));
        }
        
        // Декодируем данные API
        $data = json_decode($check->api_data, true);
        
        if (!$data) {
            wp_die(__('Report data is corrupted.', 'vrm-check-plugin'));
        }
        
        // Выводим страницу
        self::render_report_page($check, $data);
        exit;
    }
    
    /**
     * Добавить стили для страницы отчёта
     */
    public static function add_report_styles() {
        ?>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                background: #f5f5f5;
                padding: 20px;
            }
            .report-container {
                max-width: 1200px;
                margin: 0 auto;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 2px 20px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .report-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .report-header h1 {
                font-size: 32px;
                font-weight: 700;
            }
            .report-vrm {
                background: rgba(255,255,255,0.2);
                padding: 10px 20px;
                border-radius: 8px;
                font-size: 24px;
                font-weight: 700;
                letter-spacing: 2px;
            }
            .report-actions {
                padding: 20px 30px;
                background: #f9fafb;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                gap: 12px;
            }
            .btn {
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                border: none;
                font-size: 14px;
                transition: all 0.2s;
            }
            .btn-primary {
                background: #3b82f6;
                color: white;
            }
            .btn-primary:hover {
                background: #2563eb;
            }
            .btn-secondary {
                background: #6b7280;
                color: white;
            }
            .btn-secondary:hover {
                background: #4b5563;
            }
            .btn-success {
                background: #10b981;
                color: white;
            }
            .btn-success:hover {
                background: #059669;
            }
            .report-content {
                padding: 30px;
            }
            @media print {
                body {
                    padding: 0;
                    background: white;
                }
                .report-actions {
                    display: none;
                }
                .report-container {
                    box-shadow: none;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Отрендерить страницу отчёта
     */
    private static function render_report_page($check, $data) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($check->vrm); ?> - Vehicle Report | <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
        
        <link rel="stylesheet" href="<?php echo VRM_CHECK_PLUGIN_URL; ?>assets/css/premium-style.css?v=<?php echo VRM_CHECK_PLUGIN_VERSION; ?>">
        
        <style>
            /* Reset стили */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            html, body {
                height: 100%;
                margin: 0;
                padding: 0;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                background: #f5f5f5;
                line-height: 1.6;
            }
            
            /* Скрываем все элементы темы */
            .site-header,
            .site-footer,
            #masthead,
            #colophon,
            .header,
            .footer,
            nav,
            .navigation,
            .site-branding,
            .site-info,
            #site-navigation {
                display: none !important;
            }
            
            /* Основной контейнер */
            #page,
            #content,
            .site-content {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            
            /* Wrapper для всей страницы */
            .report-page-wrapper {
                min-height: 100vh;
                padding: 40px 20px;
                background: #f5f5f5;
            }
            
            .report-container {
                max-width: 1200px;
                margin: 0 auto;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 2px 20px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            
            .report-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 20px;
            }
            
            .report-header h1 {
                font-size: 32px;
                font-weight: 700;
                margin: 0;
            }
            
            .report-header p {
                opacity: 0.9;
                margin: 8px 0 0 0;
            }
            
            .report-vrm {
                background: rgba(255,255,255,0.2);
                padding: 10px 20px;
                border-radius: 8px;
                font-size: 24px;
                font-weight: 700;
                letter-spacing: 2px;
            }
            
            .report-actions {
                padding: 20px 30px;
                background: #f9fafb;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
            }
            
            .btn {
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                border: none;
                font-size: 14px;
                transition: all 0.2s;
            }
            
            .btn-primary {
                background: #3b82f6;
                color: white;
            }
            
            .btn-primary:hover {
                background: #2563eb;
            }
            
            .btn-secondary {
                background: #6b7280;
                color: white;
            }
            
            .btn-secondary:hover {
                background: #4b5563;
            }
            
            .btn-success {
                background: #10b981;
                color: white;
            }
            
            .btn-success:hover {
                background: #059669;
            }
            
            .report-content {
                padding: 30px;
            }
            
            @media print {
                body {
                    padding: 0 !important;
                    background: white !important;
                }
                .report-actions {
                    display: none !important;
                }
                .report-container {
                    box-shadow: none !important;
                }
            }
            
            @media (max-width: 768px) {
                .report-header {
                    flex-direction: column;
                    align-items: flex-start;
                }
                
                .report-header h1 {
                    font-size: 24px;
                }
                
                .report-vrm {
                    font-size: 20px;
                }
                
                .report-actions {
                    flex-direction: column;
                }
                
                .btn {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
        </head>
        <body <?php body_class('vrm-report-page'); ?>>
        
        <div class="report-page-wrapper">
            <div class="report-container">
                <!-- Header -->
                <div class="report-header">
                    <div>
                        <h1>Vehicle Check Report</h1>
                        <p style="opacity: 0.9; margin-top: 8px;">
                            Generated on <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($check->created_at)); ?>
                        </p>
                    </div>
                    <div class="report-vrm">
                        <?php echo esc_html($check->vrm); ?>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="report-actions">
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('vrm-reports')); ?>" class="btn btn-secondary">
                        ← Back to Reports
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        🖨️ Print Report
                    </button>
                    <button onclick="downloadPDF(<?php echo $check->id; ?>)" class="btn btn-success">
                        ⬇️ Download PDF
                    </button>
                </div>
                
                <!-- Report Content -->
                <div class="report-content">
                    <?php
                    // Используем существующий template
                    include VRM_CHECK_PLUGIN_PATH . 'templates/premium-results-template.php';
                    ?>
                </div>
            </div>
            
        </div>
        
        <script>
            function downloadPDF(checkId) {
                alert('PDF download functionality will be implemented soon. Check ID: ' + checkId);
                // TODO: Implement PDF generation
            }
        </script>
        
        <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
}
