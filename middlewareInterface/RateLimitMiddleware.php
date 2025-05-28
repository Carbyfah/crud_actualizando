<?php

namespace Middleware;
use Classes\CacheManager;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxAttempts;
    private int $decayMinutes;
    private string $prefix;

    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1, string $prefix = 'rate_limit')
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->prefix = $prefix;
    }

    public function handle(array $params): bool
    {
        $key = $this->getKey();
        $attempts = $this->getAttempts($key);

        if ($attempts >= $this->maxAttempts) {
            $this->sendTooManyRequestsResponse();
            return false;
        }

        $this->incrementAttempts($key);

        // Agregar headers de rate limit
        header("X-RateLimit-Limit: {$this->maxAttempts}");
        header("X-RateLimit-Remaining: " . max(0, $this->maxAttempts - $attempts - 1));
        header("X-RateLimit-Reset: " . (time() + ($this->decayMinutes * 60)));

        return true;
    }

    private function getKey(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $route = $_SERVER['REQUEST_URI'] ?? 'unknown';

        return $this->prefix . ':' . hash('sha256', $ip . '|' . $route);
    }

    private function getAttempts(string $key): int
    {
        $data = CacheManager::get($key, ['count' => 0, 'reset_time' => time()]);

        // Si ha pasado el tiempo de reset, reiniciar contador
        if (time() >= $data['reset_time']) {
            return 0;
        }

        return $data['count'];
    }

    private function incrementAttempts(string $key): void
    {
        $data = CacheManager::get($key, ['count' => 0, 'reset_time' => time() + ($this->decayMinutes * 60)]);
        $data['count']++;

        CacheManager::put($key, $data, $this->decayMinutes);
    }

    private function sendTooManyRequestsResponse(): void
    {
        http_response_code(429);
        header('Content-Type: application/json');

        echo json_encode([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Try again later.',
            'retry_after' => $this->decayMinutes * 60
        ]);
    }
}
