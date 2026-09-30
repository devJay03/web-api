<?php

namespace App\Controllers\Api;

use App\Core\Response;
use App\Model\PersonalAccessToken;
use App\Request;

abstract class BaseApiController
{
  /**
   * Authenticated user record for the current request.
   */
  protected ?array $authenticatedUser = null;

  /**
   * Current plain-text Bearer token.
   */
  protected ?string $currentToken = null;

  /**
   * Auth Guard / Authenticate incoming request:
   * - Reads Authorization: Bearer <token> header.
   * - Validates via PersonalAccessToken::findToken($token).
   * - Denies invalid/expired tokens with HTTP 401 JSON.
   *
   * @return array The authenticated user record
   */
  protected function authenticate(): array
  {
    $token = Request::bearerToken();

    if (empty($token)) {
      $this->unauthorized('Unauthorized: Missing Authorization header.', [
        'A Bearer token must be provided in the Authorization header.'
      ]);
    }

    $user = PersonalAccessToken::findToken($token);

    if (!$user) {
      $this->unauthorized('Unauthorized: Invalid or expired access token.', [
        'The provided Bearer token is invalid or has expired.'
      ]);
    }

    $this->authenticatedUser = $user;
    $this->currentToken = $token;

    return $user;
  }

  /**
   * Alias for authenticate()
   */
  protected function authGuard(): array
  {
    return $this->authenticate();
  }

  /**
   * Get the authenticated user record.
   */
  protected function user(): ?array
  {
    return $this->authenticatedUser;
  }

  /**
   * Get the current Bearer token.
   */
  protected function token(): ?string
  {
    return $this->currentToken;
  }

  /**
   * Check if request has been authenticated.
   */
  protected function isAuthenticated(): bool
  {
    return $this->authenticatedUser !== null;
  }

  /**
   * Send JSON response.
   */
  protected function json(mixed $data, int $statusCode = 200): void
  {
    Response::json($data, $statusCode);
  }

  /**
   * Send 200 OK success response.
   */
  protected function success(mixed $data = null, string $message = 'Operation successful', int $statusCode = 200): void
  {
    Response::success($data, $message, $statusCode);
  }

  /**
   * Send 201 Created success response.
   */
  protected function created(mixed $data = null, string $message = 'Resource created successfully'): void
  {
    Response::created($data, $message);
  }

  /**
   * Send generic error response.
   */
  protected function error(string $message = 'An error occurred', array $errors = [], int $statusCode = 400): void
  {
    Response::error($message, $errors, $statusCode);
  }

  /**
   * Send 400 Bad Request error.
   */
  protected function badRequest(string $message = 'Bad Request', array $errors = []): void
  {
    Response::badRequest($message, $errors);
  }

  /**
   * Send 401 Unauthorized error.
   */
  protected function unauthorized(string $message = 'Unauthorized', array $errors = []): void
  {
    Response::unauthorized($message, $errors);
  }

  /**
   * Send 404 Not Found error.
   */
  protected function notFound(string $message = 'Resource not found', array $errors = []): void
  {
    Response::notFound($message, $errors);
  }

  /**
   * Send 422 Unprocessable Entity validation error.
   */
  protected function validationError(array $errors = [], string $message = 'Validation failed'): void
  {
    Response::validationError($errors, $message);
  }

  /**
   * Send 500 Internal Server Error.
   */
  protected function serverError(string $message = 'Internal Server Error', array $errors = []): void
  {
    Response::serverError($message, $errors);
  }
}
