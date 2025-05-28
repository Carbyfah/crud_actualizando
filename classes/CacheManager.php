<?php

namespace Classes;

class CacheManager
{
    private static array $cache = [];
    private static string $cacheDir = __DIR__ . '/../storage/cache/';

    public static function get(string $key, $default = null)
    {
        // Memoria primero
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // Archivo después
        $filePath = self::$cacheDir . md5($key) . '.cache';

        if (file_exists($filePath)) {
            $data = unserialize(file_get_contents($filePath));

            // Verificar expiración
            if ($data['expires'] > time()) {
                self::$cache[$key] = $data['value'];
                return $data['value'];
            } else {
                unlink($filePath);
            }
        }

        return $default;
    }

    public static function put(string $key, $value, int $minutes = 60): bool
    {
        // Memoria
        self::$cache[$key] = $value;

        // Archivo
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }

        $data = [
            'value' => $value,
            'expires' => time() + ($minutes * 60)
        ];

        $filePath = self::$cacheDir . md5($key) . '.cache';
        return file_put_contents($filePath, serialize($data)) !== false;
    }

    public static function remember(string $key, int $minutes, callable $callback)
    {
        $value = self::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        self::put($key, $value, $minutes);

        return $value;
    }

    public static function forget(string $key): bool
    {
        unset(self::$cache[$key]);

        $filePath = self::$cacheDir . md5($key) . '.cache';
        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    public static function flush(): bool
    {
        self::$cache = [];

        if (is_dir(self::$cacheDir)) {
            $files = glob(self::$cacheDir . '*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
        }

        return true;
    }
}
