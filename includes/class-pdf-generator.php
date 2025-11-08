<?php
/**
 * PDF Generator
 * 
 * Генерация PDF отчётов для VRM проверок
 *
 * @package VRM_Check_Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

use VrmCheckPlugin\HistoryManager;

class VRM_PDF_Generator {
    
    /**
     * Инициализация
     */
    public static function init() {
        // AJAX обработчик для скачивания PDF
        add_action('wp_ajax_vrm_download_report', array(__CLASS__, 'handle_download_request'));
    }
    
    /**
     * Обработать запрос на скачивание PDF
     */
    public static function handle_download_request() {
        // Проверка nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vrm_history_nonce')) {
            wp_die('Security check failed');
        }
        
        // Проверка авторизации
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        // Получаем ID проверки
        $check_id = isset($_POST['check_id']) ? intval($_POST['check_id']) : 0;
        
        if (!$check_id) {
            wp_die('Invalid check ID');
        }
        
        $user_id = get_current_user_id();
        
        // Получаем данные проверки
        $check = HistoryManager::get_check_by_id($check_id, $user_id);
        
        if (!$check) {
            wp_die('Report not found or you do not have permission to view it.');
        }
        
        // Декодируем данные API
        $data = json_decode($check->api_data, true);
        
        if (!$data) {
            wp_die('Report data is corrupted.');
        }
        
        // Генерируем PDF
        self::generate_pdf($check, $data);
    }
    
    /**
     * Генерировать PDF
     */
    private static function generate_pdf($check, $data) {
        // Проверяем наличие mPDF
        if (file_exists(VRM_CHECK_PLUGIN_PATH . 'vendor/autoload.php')) {
            require_once VRM_CHECK_PLUGIN_PATH . 'vendor/autoload.php';
            
            try {
                self::generate_mpdf($check, $data);
                return;
            } catch (Exception $e) {
                // Если mPDF не работает, используем HTML метод
                error_log('mPDF Error: ' . $e->getMessage());
            }
        }
        
        // Fallback: используем HTML метод для генерации PDF
        // Браузер сможет сохранить как PDF через Print dialog
        self::generate_html_pdf($check, $data);
    }
    
    /**
     * Генерировать PDF с помощью mPDF
     */
    private static function generate_mpdf($check, $data) {
        // Очищаем любой предыдущий вывод
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Создаем экземпляр mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir' => sys_get_temp_dir()
        ]);
        
        // Метаданные документа
        $mpdf->SetTitle('Vehicle Report - ' . $check->vrm);
        $mpdf->SetAuthor('MOT Check');
        $mpdf->SetCreator('MOT Check - Vehicle Check System');
        $mpdf->SetSubject('Vehicle Check Report');
        
        // Генерируем HTML контент
        $html = self::generate_pdf_html($check, $data);
        
        // Записываем HTML в PDF
        $mpdf->WriteHTML($html);
        
        // Выводим PDF для скачивания
        $filename = 'vehicle-report-' . strtoupper($check->vrm) . '-' . date('Y-m-d') . '.pdf';
        
        // Устанавливаем заголовки
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Выводим PDF
        echo $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
        
        exit;
    }
    
    /**
     * Генерировать HTML для PDF (mPDF)
     */
    private static function generate_pdf_html($check, $data) {
        $vrm = $check->vrm;
        $check_type = $check->check_type;
        
        // Безопасное получение даты
        if (is_object($check->created_at)) {
            $date = $check->created_at->format('j F Y, H:i');
        } else {
            $date = date('j F Y, H:i', strtotime($check->created_at));
        }
        
        // Начинаем буферизацию для захвата HTML из шаблона
        ob_start();
        
        // Подключаем соответствующий шаблон
        if ($check_type === 'basic') {
            include VRM_CHECK_PLUGIN_PATH . 'templates/results-template.php';
        } else {
            include VRM_CHECK_PLUGIN_PATH . 'templates/premium-results-template.php';
        }
        
        $template_html = ob_get_clean();
        
        // Начинаем новую буферизацию для финального HTML
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                /* Базовые стили */
                body {
                    font-family: Arial, sans-serif;
                    font-size: 11px;
                    line-height: 1.5;
                    color: #333;
                }
                
                /* PDF Header */
                .pdf-header {
                    background: #667eea;
                    color: white;
                    padding: 25px;
                    text-align: center;
                    margin-bottom: 20px;
                }
                
                .pdf-header h1 {
                    font-size: 24px;
                    margin: 0 0 10px 0;
                }
                
                .pdf-vrm {
                    font-size: 28px;
                    font-weight: bold;
                    letter-spacing: 2px;
                    padding: 12px;
                    background: rgba(255,255,255,0.2);
                    border-radius: 6px;
                    display: inline-block;
                    margin: 8px 0;
                }
                
                .pdf-badge {
                    display: inline-block;
                    padding: 5px 14px;
                    border-radius: 15px;
                    font-size: 10px;
                    font-weight: bold;
                    background: rgba(255,255,255,0.3);
                    margin-top: 8px;
                }
                
                /* Стили из premium-style.css */
                h2, h3 {
                    color: #667eea;
                    margin-top: 20px;
                    margin-bottom: 10px;
                }
                
                h2 {
                    font-size: 16px;
                    border-bottom: 2px solid #667eea;
                    padding-bottom: 5px;
                }
                
                h3 {
                    font-size: 14px;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 15px;
                }
                
                table td, table th {
                    padding: 8px;
                    border-bottom: 1px solid #e5e7eb;
                    text-align: left;
                }
                
                table td:first-child, table th:first-child {
                    font-weight: bold;
                    width: 35%;
                    color: #6b7280;
                }
                
                /* Секции */
                .vrm-section {
                    margin-bottom: 20px;
                    page-break-inside: avoid;
                }
                
                .vrm-section-title {
                    font-size: 16px;
                    color: #667eea;
                    border-bottom: 2px solid #667eea;
                    padding-bottom: 5px;
                    margin-bottom: 12px;
                }
                
                /* Info tables */
                .vrm-info-table, .vrm-specs-grid {
                    margin-bottom: 15px;
                }
                
                .vrm-info-row {
                    display: table;
                    width: 100%;
                    border-bottom: 1px solid #e5e7eb;
                }
                
                .vrm-info-label {
                    display: table-cell;
                    padding: 8px;
                    font-weight: bold;
                    width: 35%;
                    color: #6b7280;
                }
                
                .vrm-info-value {
                    display: table-cell;
                    padding: 8px;
                }
                
                /* Checks */
                .vrm-check-item {
                    margin-bottom: 12px;
                    padding: 10px;
                    border: 1px solid #e5e7eb;
                    border-radius: 6px;
                }
                
                .vrm-check-title {
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                
                .vrm-check-status {
                    color: #10b981;
                    font-weight: bold;
                }
                
                /* MOT History */
                .mot-history-table-container {
                    margin-top: 15px;
                }
                
                .mot-test-section {
                    margin-bottom: 15px;
                    page-break-inside: avoid;
                }
                
                .mot-section-header {
                    background: #f3f4f6;
                    padding: 10px;
                    font-weight: bold;
                    margin-bottom: 8px;
                }
                
                .mot-details-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                
                .mot-details-table td {
                    padding: 6px 10px;
                    border-bottom: 1px solid #e5e7eb;
                }
                
                .mot-table-label {
                    font-weight: bold;
                    width: 30%;
                    color: #6b7280;
                }
                
                /* Скрываем кнопки и интерактивные элементы */
                button,
                .vrm-check-now-btn,
                .hidden-history-button,
                .hidden-mileage-issues-button,
                .mileage-data-button,
                form,
                .report-actions {
                    display: none !important;
                }
                
                /* Footer */
                .pdf-footer {
                    margin-top: 30px;
                    padding-top: 15px;
                    border-top: 2px solid #e5e7eb;
                    text-align: center;
                    color: #6b7280;
                    font-size: 9px;
                }
                
                /* Page breaks */
                @page {
                    margin: 15mm;
                    size: A4;
                }
            </style>
        </head>
        <body>
            <!-- PDF Header -->
            <div class="pdf-header">
                <h1>🚗 Vehicle Check Report</h1>
                <div class="pdf-vrm"><?php echo esc_html($vrm); ?></div>
                <div style="opacity: 0.9; font-size: 11px;">Generated: <?php echo $date; ?></div>
                <div class="pdf-badge"><?php echo strtoupper($check_type); ?> CHECK</div>
            </div>
            
            <!-- Template Content -->
            <?php echo $template_html; ?>
            
            <!-- PDF Footer -->
            <div class="pdf-footer">
                <p><strong>MOT Check - Vehicle Report</strong></p>
                <p>Report ID: <?php echo $check->id; ?> | Type: <?php echo strtoupper($check_type); ?> | Generated: <?php echo $date; ?></p>
                <p style="margin-top: 8px;">This report is for informational purposes only. Always verify details with official sources.</p>
                <p>© <?php echo date('Y'); ?> MOT Check. All rights reserved.</p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Генерировать HTML контент для PDF
     */
    private static function generate_html_content($check, $data) {
        $vrm = $check->vrm;
        $check_type = $check->check_type;
        
        // Безопасное получение даты
        if (is_object($check->created_at)) {
            $date = $check->created_at->format('j F Y, H:i');
        } else {
            $date = date('j F Y, H:i', strtotime($check->created_at));
        }
        
        $html = '
        <style>
            body { font-family: Arial, sans-serif; }
            h1 { color: #667eea; font-size: 24px; margin-bottom: 10px; }
            h2 { color: #333; font-size: 18px; margin-top: 20px; margin-bottom: 10px; border-bottom: 2px solid #667eea; padding-bottom: 5px; }
            .header { background: #667eea; color: white; padding: 20px; margin-bottom: 20px; }
            .vrm { font-size: 28px; font-weight: bold; letter-spacing: 2px; }
            .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .info-table td { padding: 8px; border-bottom: 1px solid #ddd; }
            .info-table td:first-child { font-weight: bold; width: 30%; color: #666; }
            .badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; }
            .badge-basic { background: #3b82f6; color: white; }
            .badge-premium { background: #10b981; color: white; }
        </style>
        
        <div class="header">
            <h1>Vehicle Check Report</h1>
            <div class="vrm">' . esc_html($vrm) . '</div>
            <p>Generated: ' . $date . '</p>
            <span class="badge badge-' . $check_type . '">' . strtoupper($check_type) . '</span>
        </div>
        
        <h2>Vehicle Identity</h2>
        <table class="info-table">
            <tr>
                <td>Registration</td>
                <td>' . esc_html($data['registration'] ?? $vrm) . '</td>
            </tr>
            <tr>
                <td>Make</td>
                <td>' . esc_html($data['make'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td>Model</td>
                <td>' . esc_html($data['model'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td>Year</td>
                <td>' . esc_html($data['year'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td>Colour</td>
                <td>' . esc_html($data['colour'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td>Fuel Type</td>
                <td>' . esc_html($data['fuel_type'] ?? 'N/A') . '</td>
            </tr>
        </table>
        
        <h2>Legal Checks</h2>
        <table class="info-table">
            <tr>
                <td>Tax Status</td>
                <td>' . esc_html($data['tax_status'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td>Tax Due Date</td>
                <td>' . esc_html($data['tax_due_date'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td>MOT Status</td>
                <td>' . esc_html($data['mot_status'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td>MOT Due Date</td>
                <td>' . esc_html($data['mot_due_date'] ?? 'N/A') . '</td>
            </tr>
        </table>
        
        <h2>Vehicle Specification</h2>
        <table class="info-table">
            <tr>
                <td>Engine Capacity</td>
                <td>' . esc_html($data['engine_capacity'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td>Vehicle Type</td>
                <td>' . esc_html($data['vehicle_type'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td>CO2 Emissions</td>
                <td>' . esc_html($data['co2_emissions'] ?? 'N/A') . '</td>
            </tr>
            <tr>
                <td>Euro Status</td>
                <td>' . esc_html($data['euro_status'] ?? 'N/A') . '</td>
            </tr>
        </table>
        
        <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
            This report was generated by MOT Check on ' . $date . '.<br>
            Report ID: ' . $check->id . ' | Type: ' . strtoupper($check_type) . '
        </p>
        ';
        
        return $html;
    }
    
    /**
     * Генерировать HTML PDF (альтернативный метод)
     */
    private static function generate_html_pdf($check, $data) {
        // Генерируем полный HTML документ для печати
        $vrm = $check->vrm;
        
        // Безопасное получение даты
        if (is_object($check->created_at)) {
            $date = $check->created_at->format('j F Y, H:i');
        } else {
            $date = date('j F Y, H:i', strtotime($check->created_at));
        }
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Vehicle Report - <?php echo esc_html($vrm); ?></title>
            <link rel="stylesheet" href="<?php echo VRM_CHECK_PLUGIN_URL; ?>assets/css/premium-style.css?v=<?php echo VRM_CHECK_PLUGIN_VERSION; ?>">
            <style>
                * { 
                    margin: 0; 
                    padding: 0; 
                    box-sizing: border-box; 
                }
                
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
                    padding: 40px;
                    background: white;
                    line-height: 1.6;
                }
                
                .pdf-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    border-radius: 12px;
                    margin-bottom: 30px;
                    text-align: center;
                }
                
                .pdf-header h1 {
                    font-size: 32px;
                    margin-bottom: 10px;
                }
                
                .pdf-vrm {
                    font-size: 36px;
                    font-weight: bold;
                    letter-spacing: 3px;
                    margin: 20px 0;
                    padding: 15px;
                    background: rgba(255,255,255,0.2);
                    border-radius: 8px;
                    display: inline-block;
                }
                
                .pdf-date {
                    opacity: 0.9;
                    font-size: 14px;
                }
                
                .pdf-badge {
                    display: inline-block;
                    padding: 6px 16px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: bold;
                    margin-top: 10px;
                    background: rgba(255,255,255,0.3);
                }
                
                .pdf-footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 2px solid #e5e7eb;
                    text-align: center;
                    color: #6b7280;
                    font-size: 12px;
                }
                
                /* Стили для печати */
                @media print {
                    body { 
                        padding: 20px;
                    }
                    
                    .pdf-header {
                        background: #667eea !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    /* Скрываем кнопки и интерактивные элементы */
                    button,
                    .vrm-check-now-btn,
                    .hidden-history-button,
                    .hidden-mileage-issues-button,
                    .mileage-data-button {
                        display: none !important;
                    }
                    
                    /* Разрывы страниц */
                    .vrm-section {
                        page-break-inside: avoid;
                    }
                }
                
                @page {
                    margin: 1.5cm;
                    size: A4;
                }
            </style>
        </head>
        <body>
            <!-- PDF Header -->
            <div class="pdf-header">
                <h1>🚗 Vehicle Check Report</h1>
                <div class="pdf-vrm"><?php echo esc_html($vrm); ?></div>
                <div class="pdf-date">Generated: <?php echo $date; ?></div>
                <div class="pdf-badge"><?php echo strtoupper($check->check_type); ?> CHECK</div>
            </div>
            
            <!-- Report Content -->
            <?php
            // Выбираем шаблон в зависимости от типа проверки
            if ($check->check_type === 'basic') {
                include VRM_CHECK_PLUGIN_PATH . 'templates/results-template.php';
            } else {
                include VRM_CHECK_PLUGIN_PATH . 'templates/premium-results-template.php';
            }
            ?>
            
            <!-- PDF Footer -->
            <div class="pdf-footer">
                <p><strong>MOT Check - Vehicle Report</strong></p>
                <p>Report ID: <?php echo $check->id; ?> | Type: <?php echo strtoupper($check->check_type); ?> | Generated: <?php echo $date; ?></p>
                <p style="margin-top: 10px;">This report is for informational purposes only. Always verify details with official sources.</p>
                <p>© <?php echo date('Y'); ?> MOT Check. All rights reserved.</p>
            </div>
            
            <script>
                // Автоматически открываем диалог печати
                window.onload = function() {
                    // Небольшая задержка для загрузки стилей
                    setTimeout(function() {
                        window.print();
                        
                        // После печати можно закрыть окно (опционально)
                        // window.onafterprint = function() {
                        //     window.close();
                        // };
                    }, 1000);
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}
