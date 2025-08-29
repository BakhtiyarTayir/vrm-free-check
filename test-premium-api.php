<?php
/**
 * Тестовый скрипт для проверки работы PremiumApiClient
 * Выполняет запросы по всем URL из api.txt и выводит результат в premium-results-template.php
 */

// Подключаем WordPress
require_once('../../../wp-load.php');

// Подключаем необходимые классы
require_once('includes/class-logger.php');
require_once('includes/class-api-client.php');
require_once('includes/class-premium-api-client.php');

use VrmCheckPlugin\PremiumApiClient;
use VrmCheckPlugin\Logger;

// Создаем экземпляр PremiumApiClient
$premium_client = new PremiumApiClient();

// Получаем VRM из параметра URL или используем значение по умолчанию
$vrm = isset($_GET['vrm']) ? sanitize_text_field($_GET['vrm']) : 'KA57DPO';

echo '<h1>Тест Premium API Client</h1>';
echo '<p>Тестируем VRM: <strong>' . esc_html($vrm) . '</strong></p>';
echo '<p><a href="?vrm=KA57DPO">Тест с KA57DPO</a> | <a href="?vrm=AB12CDE">Тест с AB12CDE</a></p>';
echo '<hr>';

// Получаем данные
echo '<h2>Получение данных...</h2>';
$data = $premium_client->get_vehicle_report($vrm);

// Проверяем на ошибки
if (isset($data['error'])) {
    echo '<div style="color: red; padding: 10px; border: 1px solid red; background: #ffe6e6;">';
    echo '<strong>Ошибка:</strong> ' . esc_html($data['error']);
    echo '</div>';
    exit;
}

echo '<div style="color: green; padding: 10px; border: 1px solid green; background: #e6ffe6;">';
echo '<strong>Данные успешно получены!</strong>';
if (isset($data['_meta'])) {
    echo '<br>Успешных запросов: ' . $data['_meta']['successful_requests'] . ' из ' . $data['_meta']['total_requests'];
    echo '<br>Время получения: ' . $data['_meta']['timestamp'];
}
echo '</div>';

echo '<hr>';
echo '<h2>Отображение в шаблоне:</h2>';

// Подключаем и выводим шаблон
ob_start();
include('templates/premium-results-template.php');
$template_output = ob_get_clean();

echo $template_output;

echo '<hr>';
echo '<h2>Логи:</h2>';

// Выводим последние логи
$logger = Logger::get_instance();
echo '<div style="background: #f5f5f5; padding: 10px; font-family: monospace; white-space: pre-wrap; max-height: 300px; overflow-y: auto;">';
echo 'Логи сохранены в файл лога плагина.';
echo '</div>';

echo '<hr>';
echo '<p><small>Тест завершен в ' . date('Y-m-d H:i:s') . '</small></p>';
?>