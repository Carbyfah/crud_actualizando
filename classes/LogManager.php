<?php
namespace Classes;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class LogManager
{
    private static ?Logger $logger = null;
    private static array $channels = [];

    public static function getLogger(string $channel = 'app'): Logger
    {
        if (!isset(self::$channels[$channel])) {
            self::$channels[$channel] = self::createLogger($channel);
        }
        
        return self::$channels[$channel];
    }

    private static function createLogger(string $channel): Logger
    {
        $logger = new Logger($channel);
        
        // Desarrollo: Log todo a archivo y consola
        if ($_ENV['DEBUG_MODE'] ?? false) {
            $streamHandler = new StreamHandler('php://stderr', Logger::DEBUG);
            $fileHandler = new StreamHandler(__DIR__ . "/../storage/logs/{$channel}.log", Logger::DEBUG);
        } else {
            // Producción: Solo errores críticos
            $fileHandler = new RotatingFileHandler(
                __DIR__ . "/../storage/logs/{$channel}.log", 
                30, 
                Logger::ERROR
            );
        }
        
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        );
        
        $fileHandler->setFormatter($formatter);
        $logger->pushHandler($fileHandler);
        
        if (isset($streamHandler)) {
            $streamHandler->setFormatter($formatter);
            $logger->pushHandler($streamHandler);
        }
        
        return $logger;
    }

    public static function logQuery(string $query, array $bindings = [], float $time = 0): void
    {
        $logger = self::getLogger('database');
        $logger->info('Query executed', [
            'query' => $query,
            'bindings' => $bindings,
            'time' => $time . 'ms'
        ]);
    }

    public static function logError(\Throwable $e, array $context = []): void
    {
        $logger = self::getLogger('errors');
        $logger->error($e->getMessage(), array_merge($context, [
            'exception' => $e,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]));
    }
}