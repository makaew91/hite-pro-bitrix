<?php

namespace Makaew\Store\Exchange;

use Bitrix\Main\Application;

/**
 * Логирование операций обмена с 1С.
 *
 * Пишет в файл /local/logs/exchange/YYYY-MM-DD.log.
 * Ротация — по дням. Уровни: info, warning, error.
 */
class Logger
{
    /** @var string Директория логов */
    private string $logDir;

    public function __construct()
    {
        $this->logDir = Application::getDocumentRoot() . EXCHANGE_LOG_DIR;
        $this->ensureDirectory();
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    /**
     * Записать строку в лог-файл.
     */
    private function write(string $level, string $message, array $context): void
    {
        $date = date('Y-m-d');
        $time = date('H:i:s');
        $file = $this->logDir . $date . '.log';

        $line = "[{$time}] [{$level}] {$message}";

        if (!empty($context)) {
            $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        $line .= PHP_EOL;

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Создать директорию логов при необходимости.
     */
    private function ensureDirectory(): void
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
}
