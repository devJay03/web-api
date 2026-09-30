<?php

namespace App\Controllers\Api;

use App\Model\PersonalAccessToken;
use App\Model\User;
use App\Request;
use PDOException;

class AuthApiController extends BaseApiController
{
  protected User $userModel;

  public function __construct()
  {
    $this->userModel = new User();
  }

  /**
   * POST /api/auth/login
   * Authenticate user credentials and return personal access token.
   *
   * Response: {"success": true, "token": "<plainTextToken>", "token_type": "Bearer"}
   */
  public function login(): void
  {
    $method = Request::method();
    if ($method !== 'POST') {
      $this->badRequest('Method not supported.', ["{$method} method not supported for login. Use POST."]);
    }

    $data = Request::all();
    $errors = [];

    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'A valid email address is required.';
    }
    if (empty($data['password'])) {
      $errors[] = 'Password is required.';
    }

    if (!empty($errors)) {
      $this->validationError($errors, 'Validation failed.');
    }

    try {
      $user = $this->userModel->findByEmail($data['email']);

      // Verify credentials via password_verify
      if (!$user || !password_verify($data['password'], $user['password'])) {
        $this->unauthorized('Invalid email or password.', ['Authentication failed. Invalid credentials.']);
      }

      // Generate a secure random string token
      $plainTextToken = bin2hex(random_bytes(40));

      $tokenName = $data['token_name'] ?? ($data['name'] ?? 'mobile_app');
      $expiryDays = isset($data['expiry_days']) ? $data['expiry_days'] : null;

      // Save hashed token into personal_access_tokens
      $tokenRecord = PersonalAccessToken::createToken($user['id'], $tokenName, $plainTextToken, $expiryDays);
      if (!$tokenRecord) {
        $this->serverError('Failed to generate personal access token.', ['Could not create token record.']);
      }

      // Return plain-text token to the client
      $this->json([
        'success'    => true,
        'token'      => $plainTextToken,
        'token_type' => 'Bearer',
      ], 200);
    } catch (PDOException $e) {
      $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
    }
  }

  /**
   * POST /api/auth/logout
   * Invalidate / delete the current personal access token.
   */
  public function logout(): void
  {
    $token = Request::bearerToken();

    if (empty($token)) {
      $this->unauthorized('Unauthorized: Missing Authorization header.', [
        'A Bearer token must be provided in the Authorization header.'
      ]);
    }

    // Revoke the token using revokeToken()
    PersonalAccessToken::revokeToken($token);

    $this->json([
      'success' => true,
      'message' => 'Logged out successfully. Token has been revoked.',
    ], 200);
  }

  /**
   * POST /api/register
   * Register a new user account and return personal access token.
   */
  public function register(): void
  {
    $method = Request::method();
    if ($method !== 'POST') {
      $this->badRequest('Method not supported.', ["{$method} method not supported for registration. Use POST."]);
    }

    $rawInput = file_get_contents('php://input');
    $data = !empty($rawInput) ? json_decode($rawInput, true) : null;
    if (!is_array($data)) {
      $data = Request::all();
    }
    if (!is_array($data)) {
      $data = [];
    }

    // Required fields check: name, email, password, password_confirmation
    $requiredFields = ['name', 'email', 'password', 'password_confirmation'];
    foreach ($requiredFields as $field) {
      if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
        $this->json([
          'success' => false,
          'message' => "The {$field} field is required.",
        ], 422);
      }
    }

    $name = trim((string)$data['name']);
    $email = trim((string)$data['email']);
    $password = (string)$data['password'];
    $passwordConfirmation = (string)$data['password_confirmation'];

    // Email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $this->json([
        'success' => false,
        'message' => 'The email must be a valid email address.',
      ], 422);
    }

    // Duplicate email check
    try {
      $existing = $this->userModel->findByEmail($email);
      if ($existing) {
        $this->json([
          'success' => false,
          'message' => 'The email has already been taken.',
        ], 422);
      }
    } catch (PDOException $e) {
      $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
    }

    // Password length & confirmation validation
    if (strlen($password) < 6) {
      $this->json([
        'success' => false,
        'message' => 'The password must be at least 6 characters.',
      ], 422);
    }

    if ($password !== $passwordConfirmation) {
      $this->json([
        'success' => false,
        'message' => 'The password confirmation does not match.',
      ], 422);
    }

    try {
      // User Creation: hash password with BCRYPT
      $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

      $newId = $this->userModel->create([
        'name'     => $name,
        'email'    => $email,
        'password' => $hashedPassword,
      ]);

      if (!$newId) {
        $this->serverError('Failed to register user.', ['Could not create user record.']);
      }

      $userId = (int)$newId;
      $userRecord = $this->userModel->findById($userId);

      // Token Generation
      $plainTextToken = bin2hex(random_bytes(40));
      $tokenName = $data['token_name'] ?? 'auth_token';

      // Save hashed token into personal_access_tokens
      $tokenRecord = PersonalAccessToken::createToken($userId, $tokenName, $plainTextToken);
      if (!$tokenRecord) {
        $this->serverError('Failed to generate personal access token.', ['Could not create token record.']);
      }

      // Return HTTP 201 response
      $this->json([
        'success'    => true,
        'message'    => 'User registered successfully',
        'token'      => $plainTextToken,
        'token_type' => 'Bearer',
        'user'       => [
          'id'    => $userId,
          'name'  => $userRecord['name'] ?? $name,
          'email' => $userRecord['email'] ?? $email,
        ],
      ], 201);
    } catch (PDOException $e) {
      if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate entry')) {
        $this->json([
          'success' => false,
          'message' => 'The email has already been taken.',
        ], 422);
      }
      $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
    }
  }

  /**
   * GET /api/auth/profile
   * Retrieve the authenticated user profile using Bearer token guard.
   */
  public function profile(): void
  {
    $user = $this->authenticate();
    $this->success($user, 'Authenticated user profile retrieved.', 200);
  }

  /**
   * Alias for profile.
   */
  public function check(): void
  {
    $this->profile();
  }

  /**
   * Alias for profile.
   */
  public function me(): void
  {
    $this->profile();
  }
}
