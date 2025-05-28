<?php

namespace Classes;

class ApiResponse
{
    public static function success($data = null, string $message = 'Success', int $code = 200): void
    {
        self::respond([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public static function error(string $message = 'Error', int $code = 400, $errors = null): void
    {
        self::respond([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    public static function paginated(array $data, int $total, int $page = 1, int $perPage = 15): void
    {
        $lastPage = ceil($total / $perPage);

        self::success([
            'items' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
                'has_more' => $page < $lastPage
            ]
        ]);
    }

    public static function created($data = null, string $message = 'Created successfully'): void
    {
        self::success($data, $message, 201);
    }

    public static function updated($data = null, string $message = 'Updated successfully'): void
    {
        self::success($data, $message, 200);
    }

    public static function deleted(string $message = 'Deleted successfully'): void
    {
        self::success(null, $message, 200);
    }

    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    public static function validationError(array $errors, string $message = 'Validation failed'): void
    {
        self::error($message, 422, $errors);
    }

    private static function respond(array $data, int $code): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
