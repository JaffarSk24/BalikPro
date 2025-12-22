<?php

namespace BalikPro\Utils;

class Logger
{
    private $logPath;

    public function __construct(string $filename = 'app.log')
    {
        $basePath = dirname(__DIR__, 2); // /Users/.../balikpro
        $config = require $basePath . '/config/app.php';

        // Берём путь из конфига
        $logsPath = $config['logs_path'] ?? ($basePath . '/storage/logs/');

        // Если этот путь не существует или не доступен → заменяем на локальный
        if (!is_dir(dirname($logsPath))) {
            $logsPath = $basePath . '/storage/logs/';
        }

        $this->logPath = rtrim($logsPath, '/') . '/' . $filename;

        // Ensure log directory exists
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";

        file_put_contents($this->logPath, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }
}