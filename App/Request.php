<?php

namespace App;

use App\Core\Response;

class Request
{
    private static ?array $parsedBody = null;
    private static ?array $cachedHeaders = null;

    /**
     * Get the HTTP request method, respecting method overrides.
     */
    public static function method(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (strtoupper($method) === 'POST') {
            $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
            if (!$override && isset($_POST['_method'])) {
                $override = $_POST['_method'];
            }
            if ($override) {
                return strtoupper($override);
            }
        }

        return strtoupper($method);
    }

    /**
     * Parse and retrieve all input data from JSON, URL-encoded bodies, or multipart form data.
     */
    public static function all(): array
    {
        if (self::$parsedBody !== null) {
            return self::$parsedBody;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $rawInput = file_get_contents('php://input');
        if (empty($rawInput) && php_sapi_name() === 'cli' && stripos($contentType, 'application/json') !== false) {
            $stdin = @fopen('php://stdin', 'r');
            if ($stdin) {
                stream_set_blocking($stdin, false);
                $rawInput = stream_get_contents($stdin) ?: '';
                fclose($stdin);
            }
        }

        $data = [];

        if (stripos($contentType, 'application/json') !== false) {
            if (!empty($rawInput)) {
                $decoded = json_decode($rawInput, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Response::badRequest('Malformed JSON in request body.', [
                        'JSON parse error: ' . json_last_error_msg()
                    ]);
                }
                $data = is_array($decoded) ? $decoded : [];
            }
        } else {
            $method = self::method();
            if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
                if (!empty($_POST)) {
                    $data = $_POST;
                } elseif (!empty($rawInput)) {
                    parse_str($rawInput, $parsed);
                    $data = is_array($parsed) ? $parsed : [];
                }
            } else {
                $data = $_POST ?? [];
            }
        }

        self::$parsedBody = $data;
        return self::$parsedBody;
    }

    /**
     * Get a specific input item by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        if (array_key_exists($key, $all)) {
            return $all[$key];
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Get query string parameter(s).
     */
    public static function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Retrieve all HTTP request headers.
     */
    public static function headers(): array
    {
        if (self::$cachedHeaders !== null) {
            return self::$cachedHeaders;
        }

        $headers = [];

        if (function_exists('getallheaders')) {
            $allHeaders = getallheaders();
            if ($allHeaders !== false) {
                foreach ($allHeaders as $key => $value) {
                    $headers[strtolower($key)] = $value;
                }
            }
        }

        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($name, 5)));
                $headers[$headerName] = $value;
            } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headerName = strtolower(str_replace('_', '-', $name));
                $headers[$headerName] = $value;
            }
        }

        // Special handling for Authorization in Apache
        if (!isset($headers['authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        self::$cachedHeaders = $headers;
        return self::$cachedHeaders;
    }

    /**
     * Get a specific header value.
     */
    public static function header(string $name, mixed $default = null): mixed
    {
        $headers = self::headers();
        $normalized = strtolower($name);
        return $headers[$normalized] ?? $default;
    }

    /**
     * Extract Bearer token from Authorization header.
     */
    public static function bearerToken(): ?string
    {
        $header = self::header('authorization');
        if (!empty($header) && preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
