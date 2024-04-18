<?php

namespace SceneApi\Services;

use SceneApi\SceneManagerException;

class Logger
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
        // Check if file exists, create it if not
        if (!file_exists($logFile)) {
            touch($logFile);
        }
    }

    public function info(string $message): void
    {
        $this->log('INFO', $message);
    }

    public function error(string $message): void
    {
        $this->log('ERROR', $message);

        throw new SceneManagerException($message);
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
}