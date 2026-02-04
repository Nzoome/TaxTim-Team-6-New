<?php

namespace CryptoTax\Services;

use DateTime;

/**
 * Simple Logger
 * Logs processing events and errors
 */
class Logger
{
    private string $logDir;
    private bool $enabled;

    public function __construct(string $logDir, bool $enabled = true)
    {
        $this->logDir = $logDir;
        $this->enabled = $enabled;

        if ($enabled && !is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public function info(string $message): void
    {
        $this->log('INFO', $message);
    }

    public function error(string $message): void
    {
        $this->log('ERROR', $message);
    }

    public function warning(string $message): void
    {
        $this->log('WARNING', $message);
    }

    private function log(string $level, string $message): void
    {
        if (!$this->enabled) {
            return;
        }

        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        $logFile = $this->logDir . '/app_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
