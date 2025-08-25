<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Класс для логирования ошибок и отладочной информации
 */
class Logger {
    
    private static $instance = null;
    private $log_file;
    private $max_log_size = 10485760; // 10MB
    
    private function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/vrm-check-plugin-errors.log';
    }
    
    /**
     * Получить экземпляр логгера (Singleton)
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Логирует сообщение с указанным уровнем
     */
    public function log($level, $message, $context = array()) {
        // Проверяем размер лог файла
        $this->rotate_log_if_needed();
        
        // Добавляем временную метку
        $timestamp = current_time('Y-m-d H:i:s');
        
        // Формируем сообщение лога
        $log_message = sprintf(
            "[%s] [%s] VRM Check Plugin: %s\n",
            $timestamp,
            strtoupper($level),
            $message
        );
        
        // Добавляем контекст если есть
        if (!empty($context)) {
            $log_message .= "Context: " . $this->format_context($context) . "\n";
        }
        
        // Добавляем разделитель
        $log_message .= str_repeat('-', 80) . "\n";
        
        // Записываем в лог файл WordPress
        error_log($log_message);
        
        // Записываем в отдельный файл плагина
        $this->write_to_file($log_message);
    }
    
    /**
     * Логирует ошибку
     */
    public function error($message, $context = array()) {
        $this->log('error', $message, $context);
    }
    
    /**
     * Логирует предупреждение
     */
    public function warning($message, $context = array()) {
        $this->log('warning', $message, $context);
    }
    
    /**
     * Логирует информационное сообщение
     */
    public function info($message, $context = array()) {
        $this->log('info', $message, $context);
    }
    
    /**
     * Логирует отладочную информацию
     */
    public function debug($message, $context = array()) {
        // Отладочные сообщения записываем только если включен WP_DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log('debug', $message, $context);
        }
    }
    
    /**
     * Форматирует контекст для записи в лог
     */
    private function format_context($context) {
        if (is_array($context) || is_object($context)) {
            return json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        return (string) $context;
    }
    
    /**
     * Записывает сообщение в файл
     */
    private function write_to_file($message) {
        // Проверяем права на запись
        if (!is_writable(dirname($this->log_file))) {
            return false;
        }
        
        // Записываем в файл
        return file_put_contents($this->log_file, $message, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Ротация лог файла при превышении размера
     */
    private function rotate_log_if_needed() {
        if (!file_exists($this->log_file)) {
            return;
        }
        
        $file_size = filesize($this->log_file);
        if ($file_size > $this->max_log_size) {
            // Создаём архивную копию
            $archive_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.old';
            rename($this->log_file, $archive_file);
            
            // Удаляем старые архивы (оставляем только 5 последних)
            $this->cleanup_old_logs();
        }
    }
    
    /**
     * Удаляет старые лог файлы
     */
    private function cleanup_old_logs() {
        $log_dir = dirname($this->log_file);
        $log_pattern = basename($this->log_file) . '.*.old';
        
        $old_logs = glob($log_dir . '/' . $log_pattern);
        if (count($old_logs) > 5) {
            // Сортируем по времени создания
            usort($old_logs, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Удаляем самые старые файлы
            $files_to_delete = array_slice($old_logs, 0, count($old_logs) - 5);
            foreach ($files_to_delete as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Получить содержимое лог файла
     */
    public function get_log_contents($lines = 100) {
        if (!file_exists($this->log_file)) {
            return 'Лог файл не найден.';
        }
        
        $content = file_get_contents($this->log_file);
        $lines_array = explode("\n", $content);
        
        // Возвращаем последние N строк
        $last_lines = array_slice($lines_array, -$lines);
        return implode("\n", $last_lines);
    }
    
    /**
     * Очистить лог файл
     */
    public function clear_log() {
        if (file_exists($this->log_file)) {
            return unlink($this->log_file);
        }
        return true;
    }
    
    /**
     * Получить путь к лог файлу
     */
    public function get_log_file_path() {
        return $this->log_file;
    }
}