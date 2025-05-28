<?php

namespace Classes;

class JWTAuth
{
    private static string $secret;
    private static int $expiration = 3600; // 1 hora

    public static function init(): void
    {
        self::$secret = $_ENV['JWT_SECRET'] ?? 'default-secret-change-this';
    }

    public static function generateToken(array $payload): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $payload['iat'] = time();
        $payload['exp'] = time() + self::$expiration;
        $payload = json_encode($payload);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    public static function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        // Verificar firma
        $validSignature = hash_hmac('sha256', $header . "." . $payload, self::$secret, true);
        $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));

        if (!hash_equals($signature, $validSignature)) {
            return null;
        }

        // Decodificar payload
        $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);

        // Verificar expiración
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return null;
        }

        return $payloadData;
    }

    public static function getCurrentUser(): ?array
    {
        $token = self::getBearerToken();

        if (!$token) {
            return null;
        }

        return self::validateToken($token);
    }

    private static function getBearerToken(): ?string
    {
        $headers = getallheaders();

        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }

        return $_GET['token'] ?? $_POST['token'] ?? null;
    }
}
