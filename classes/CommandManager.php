<?php

namespace Classes;

class CommandManager
{
    private static array $commands = [];

    public static function register(string $name, callable $handler): void
    {
        self::$commands[$name] = $handler;
    }

    public static function run(array $argv): void
    {
        if (count($argv) < 2) {
            self::showHelp();
            return;
        }

        $command = $argv[1];
        $args = array_slice($argv, 2);

        if (!isset(self::$commands[$command])) {
            echo "Comando no encontrado: $command\n";
            self::showHelp();
            return;
        }

        try {
            call_user_func(self::$commands[$command], $args);
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    private static function showHelp(): void
    {
        echo "Comandos disponibles:\n";
        foreach (array_keys(self::$commands) as $command) {
            echo "  php console.php $command\n";
        }
    }
}

// Registrar comandos
CommandManager::register('migrate', function ($args) {
    $direction = $args[0] ?? 'up';

    $migrations = glob(__DIR__ . '/../migrations/*.php');
    sort($migrations);

    foreach ($migrations as $file) {
        require_once $file;
        $class = basename($file, '.php');

        if (class_exists($class)) {
            $migration = new $class();
            $migration->setDB($GLOBALS['db']);

            if ($direction === 'up') {
                echo "Ejecutando migración: $class\n";
                $migration->up();
            } else {
                echo "Revirtiendo migración: $class\n";
                $migration->down();
            }
        }
    }

    echo "Migraciones completadas.\n";
});

CommandManager::register('cache:clear', function ($args) {
    CacheManager::flush();
    echo "Cache limpiado.\n";
});

CommandManager::register('make:controller', function ($args) {
    if (empty($args[0])) {
        echo "Uso: php console.php make:controller NombreController\n";
        return;
    }

    $name = $args[0];
    $template = file_get_contents(__DIR__ . '/templates/controller.stub');
    $content = str_replace('{{ControllerName}}', $name, $template);

    file_put_contents(__DIR__ . "/../controllers/{$name}.php", $content);
    echo "Controlador creado: {$name}.php\n";
});
