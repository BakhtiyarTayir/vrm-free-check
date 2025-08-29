<?php
/**
 * Веб-интерфейс для тестирования Premium API Client
 * Доступ через: http://motcheck.local/wp-content/plugins/vrm-check-plugin/premium-test.php
 */

// Определяем ABSPATH для WordPress
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
}

// Подключаем WordPress
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-includes/wp-db.php');
require_once(ABSPATH . 'wp-includes/functions.php');
require_once(ABSPATH . 'wp-includes/http.php');
require_once(ABSPATH . 'wp-includes/option.php');

// Подключаем классы плагина
require_once(__DIR__ . '/includes/class-logger.php');
require_once(__DIR__ . '/includes/class-api-client.php');
require_once(__DIR__ . '/includes/class-premium-api-client.php');

use VrmCheckPlugin\PremiumApiClient;
use VrmCheckPlugin\Logger;

// Получаем VRM из параметра
$vrm = isset($_GET['vrm']) ? strtoupper(trim($_GET['vrm'])) : 'KA57DPO';
$action = isset($_GET['action']) ? $_GET['action'] : 'form';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium API Test - VRM Check Plugin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 200px; }
        button { padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .error { color: #d63638; background: #fcf0f1; padding: 10px; border: 1px solid #d63638; border-radius: 4px; margin: 10px 0; }
        .success { color: #00a32a; background: #f0f6fc; padding: 10px; border: 1px solid #00a32a; border-radius: 4px; margin: 10px 0; }
        .debug { background: #f0f0f0; padding: 15px; border: 1px solid #ccc; border-radius: 4px; margin: 15px 0; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 15px; text-decoration: none; color: #0073aa; }
        .nav a:hover { text-decoration: underline; }
        .template-output { border: 2px solid #0073aa; border-radius: 8px; padding: 20px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Premium API Client - Тестирование</h1>
        
        <div class="nav">
            <a href="?action=form">Форма тестирования</a>
            <a href="?action=test&vrm=KA57DPO">Тест KA57DPO</a>
            <a href="?action=test&vrm=AB12CDE">Тест AB12CDE</a>
            <a href="?action=urls">Показать URL</a>
        </div>
        
        <?php if ($action === 'form'): ?>
            <h2>Введите VRM для тестирования</h2>
            <form method="get">
                <input type="hidden" name="action" value="test">
                <div class="form-group">
                    <label for="vrm">VRM номер:</label>
                    <input type="text" id="vrm" name="vrm" value="<?php echo esc_attr($vrm); ?>" placeholder="Например: KA57DPO">
                </div>
                <button type="submit">Тестировать API</button>
            </form>
            
        <?php elseif ($action === 'urls'): ?>
            <h2>URL адреса из api.txt</h2>
            <?php
            $api_file = __DIR__ . '/api.txt';
            if (file_exists($api_file)) {
                $urls = file($api_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                echo '<div class="debug">';
                foreach ($urls as $i => $url) {
                    echo ($i + 1) . ". " . esc_html($url) . "\n";
                }
                echo '</div>';
            } else {
                echo '<div class="error">Файл api.txt не найден!</div>';
            }
            ?>
            
        <?php elseif ($action === 'test'): ?>
            <h2>Тестирование VRM: <?php echo esc_html($vrm); ?></h2>
            
            <?php
            try {
                // Создаем экземпляр PremiumApiClient
                $premium_client = new PremiumApiClient();
                
                echo '<div class="success">PremiumApiClient успешно создан</div>';
                
                // Получаем данные
                echo '<h3>Получение данных...</h3>';
                $start_time = microtime(true);
                $data = $premium_client->get_vehicle_report($vrm);
                $end_time = microtime(true);
                $execution_time = round(($end_time - $start_time), 2);
                
                echo '<div class="success">Время выполнения: ' . $execution_time . ' секунд</div>';
                
                // Проверяем на ошибки
                if (isset($data['error'])) {
                    echo '<div class="error"><strong>Ошибка:</strong> ' . esc_html($data['error']) . '</div>';
                } else {
                    echo '<div class="success"><strong>Данные успешно получены!</strong>';
                    if (isset($data['_meta'])) {
                        echo '<br>Успешных запросов: ' . $data['_meta']['successful_requests'] . ' из ' . $data['_meta']['total_requests'];
                        echo '<br>Время получения: ' . $data['_meta']['timestamp'];
                    }
                    echo '</div>';
                    
                    // Показываем отладочную информацию
                    echo '<h3>Отладочная информация (первые 2000 символов):</h3>';
                    echo '<div class="debug">';
                    $debug_output = print_r($data, true);
                    echo esc_html(substr($debug_output, 0, 2000));
                    if (strlen($debug_output) > 2000) {
                        echo "\n\n... (данные обрезаны, всего " . strlen($debug_output) . " символов)";
                    }
                    echo '</div>';
                    
                    // Выводим шаблон
                    echo '<h3>Отображение в шаблоне:</h3>';
                    echo '<div class="template-output">';
                    
                    ob_start();
                    include(__DIR__ . '/templates/premium-results-template.php');
                    $template_output = ob_get_clean();
                    
                    echo $template_output;
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error"><strong>Исключение:</strong> ' . esc_html($e->getMessage()) . '</div>';
                echo '<div class="debug">Трассировка стека:\n' . esc_html($e->getTraceAsString()) . '</div>';
            }
            ?>
            
        <?php endif; ?>
        
        <hr>
        <p><small>Тест выполнен: <?php echo date('Y-m-d H:i:s'); ?></small></p>
    </div>
</body>
</html>