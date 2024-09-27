<?php

namespace SceneApi\Services;

use SceneApi\SceneManagerException;
use Exception;

class Logger
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
        // Check if file exists, create it if not
        if (!file_exists($logFile)) {
            error_log($logFile);
            touch($logFile);
        }
    }

    public function info(string $message): void
    {
        $this->log('INFO', $message);
    }

    public function error(string $message, Exception $e = null): void
    {
        if ($e) {
            // Log the exception details with a full stack trace
            $this->log('ERROR', $message . "\n" . $this->formatException($e));
        } else {
            $this->log('ERROR', $message);
        }

        // Throw a custom exception
//        throw new SceneManagerException($message, 0, $e);
    }

    private function log(string $type, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s'); // Get current timestamp
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1];
        $logMessage = sprintf("[%s] %s %s:%d - %s\n", $timestamp, $type, $caller['file'], $caller['line'], $message);
        // Write to log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        // Display in command line
        echo $logMessage;

        return;
    }

    private function formatException(Exception $e): string
    {
        return sprintf(
            "Exception: %s in %s:%d\nStack trace:\n%s",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }
}
