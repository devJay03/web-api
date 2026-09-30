<?php

namespace App\Core;

class Response
{
    /**
     * Send global CORS and response headers.
     */
    public static function sendCorsHeaders(): void
    {
        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Content-Type: application/json; charset=UTF-8');
        }
    }

    /**
     * Intercept OPTIONS preflight requests and terminate immediately with 200 and empty body.
     */
    public static function handlePreflight(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($method) === 'OPTIONS') {
            self::sendCorsHeaders();
            http_response_code(200);
            exit();
        }
    }

    /**
     * Output a standardized JSON response.
     */
    public static function json(array $payload, int $statusCode = 200): void
    {
        self::sendCorsHeaders();
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Output a standardized success response.
     * Format: {"success": true, "data": {...}, "message": "..."}
     */
    public static function success(mixed $data = null, string $message = 'Operation successful', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Output a standardized 201 Created success response.
     */
    public static function created(mixed $data = null, string $message = 'Resource created successfully'): void
    {
        self::success($data, $message, 201);
    }

    /**
     * Output a standardized error response.
     * Format: {"success": false, "errors": [...], "message": "..."}
     */
    public static function error(string $message = 'An error occurred', array $errors = [], int $statusCode = 400): void
    {
        if (empty($errors)) {
            $errors = [$message];
        }

        self::json([
            'success' => false,
            'errors' => array_values($errors),
            'message' => $message,
        ], $statusCode);
    }

    /**
     * 400 Bad Request
     */
    public static function badRequest(string $message = 'Bad Request', array $errors = []): void
    {
        self::error($message, $errors, 400);
    }

    /**
     * 401 Unauthorized
     */
    public static function unauthorized(string $message = 'Unauthorized', array $errors = []): void
    {
        self::error($message, $errors, 401);
    }

    /**
     * 404 Not Found
     */
    public static function notFound(string $message = 'Resource not found', array $errors = []): void
    {
        self::error($message, $errors, 404);
    }

    /**
     * 422 Unprocessable Entity (Validation failed)
     */
    public static function validationError(array $errors = [], string $message = 'Validation failed'): void
    {
        self::error($message, $errors, 422);
    }

    /**
     * 500 Internal Server Error
     */
    public static function serverError(string $message = 'Internal Server Error', array $errors = []): void
    {
        self::error($message, $errors, 500);
    }
}
