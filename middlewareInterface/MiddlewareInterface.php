<?php
namespace Middleware;

interface MiddlewareInterface
{
    public function handle(array $params): bool;
}

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(array $params): bool
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_token'] ?? '';
            $sessionToken = $_SESSION['_token'] ?? '';
            
            if (!hash_equals($sessionToken, $token)) {
                http_response_code(419);
                echo json_encode(['error' => 'CSRF token mismatch']);
                return false;
            }
        }
        
        return true;
    }
}

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(array $params): bool
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return false;
        }
        
        return true;
    }
}

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(array $params): bool
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        if (isset($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        return true;
    }
}