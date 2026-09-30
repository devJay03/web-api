<?php

namespace App\Core;

use App\Model\PersonalAccessToken;
use App\Request;

class AuthMiddleware
{
    private static ?array $user = null;
    private static ?string $token = null;

    /**
     * Authenticate request using personal access Bearer token.
     * Reads Authorization: Bearer <token> header,
     * validates via PersonalAccessToken::findToken($token),
     * and denies invalid/expired tokens with HTTP 401 JSON.
     *
     * @return array Authenticated user record
     */
    public static function authenticate(): array
    {
        $token = Request::bearerToken();

        if (empty($token)) {
            Response::unauthorized('Unauthorized: Missing Authorization header.', [
                'A Bearer token must be provided in the Authorization header.'
            ]);
        }

        $user = PersonalAccessToken::findToken($token);

        if (!$user) {
            Response::unauthorized('Unauthorized: Invalid or expired access token.', [
                'The provided Bearer token is invalid or has expired.'
            ]);
        }

        self::$user = $user;
        self::$token = $token;

        return $user;
    }

    /**
     * Alias for authenticate().
     */
    public static function handle(): array
    {
        return self::authenticate();
    }

    /**
     * Get the authenticated user record for the current request.
     */
    public static function user(): ?array
    {
        return self::$user;
    }

    /**
     * Get the plain-text token used for the current request.
     */
    public static function token(): ?string
    {
        return self::$token;
    }

    /**
     * Determine if a user is authenticated.
     */
    public static function check(): bool
    {
        return self::$user !== null;
    }
}
