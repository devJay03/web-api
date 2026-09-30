<?php

namespace App\Core;

use App\Controllers\Api\AuthApiController;
use App\Request;

class Router
{
  /**
   * Explicit API routes registered: [METHOD => [normalized_path => handler]]
   */
  protected static array $apiRoutes = [
    'POST' => [
      'api/register'      => [AuthApiController::class, 'register'],
      'register'          => [AuthApiController::class, 'register'],
      'api/auth/register' => [AuthApiController::class, 'register'],
      'auth/register'     => [AuthApiController::class, 'register'],
    ],
  ];

  /**
   * Register a route for API requests.
   */
  public static function addApiRoute(string $method, string $path, callable|array|string $handler): void
  {
    $method = strtoupper($method);
    $normalized = trim(str_replace('\\', '/', $path), '/');
    self::$apiRoutes[$method][$normalized] = $handler;

    if (str_starts_with($normalized, 'api/')) {
      self::$apiRoutes[$method][substr($normalized, 4)] = $handler;
    } else {
      self::$apiRoutes[$method]['api/' . $normalized] = $handler;
    }
  }

  public static function post(string $path, callable|array|string $handler): void
  {
    self::addApiRoute('POST', $path, $handler);
  }

  public static function get(string $path, callable|array|string $handler): void
  {
    self::addApiRoute('GET', $path, $handler);
  }

  public static function put(string $path, callable|array|string $handler): void
  {
    self::addApiRoute('PUT', $path, $handler);
  }

  public static function delete(string $path, callable|array|string $handler): void
  {
    self::addApiRoute('DELETE', $path, $handler);
  }

  /**
   * Determine if the incoming request is an API request.
   * True if URI path begins with /api or query contains url=api/...
   */
  public static function isApiRequest(): bool
  {
    // 1. Check query parameter ?url=api or ?url=api/...
    if (!empty($_GET['url'])) {
      $url = trim(str_replace('\\', '/', $_GET['url']), '/');
      if ($url === 'api' || str_starts_with($url, 'api/')) {
        return true;
      }
    }

    // 2. Check URI path
    $rawPath = self::getRawPath();
    if ($rawPath === 'api' || str_starts_with($rawPath, 'api/')) {
      return true;
    }

    // 3. Check registered API routes
    foreach (self::$apiRoutes as $routes) {
      if (isset($routes[$rawPath]) || isset($routes['api/' . $rawPath])) {
        return true;
      }
    }

    return false;
  }

  /**
   * Get raw normalized request path stripped of base folder and script name.
   */
  public static function getRawPath(): string
  {
    if (!empty($_GET['url'])) {
      return trim(str_replace('\\', '/', $_GET['url']), '/');
    }

    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
    $path = str_replace('\\', '/', $path);

    // Determine script directory (e.g. /web-api or /)
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if (!empty($baseDir) && $baseDir !== '/' && $baseDir !== '.') {
      if (str_starts_with($path, $baseDir)) {
        $path = substr($path, strlen($baseDir));
      }
    }

    if (str_starts_with($path, '/index.php')) {
      $path = substr($path, strlen('/index.php'));
    }

    $path = trim($path, '/');

    // Strip project directory name if still leading
    $projectName = basename(root);
    if (!empty($projectName) && (str_starts_with($path, $projectName . '/') || $path === $projectName)) {
      $path = trim(substr($path, strlen($projectName)), '/');
    }

    return $path;
  }

  /**
   * Dispatch the request through the appropriate branch (API or Web).
   */
  public static function dispatch(): void
  {
    if (self::isApiRequest()) {
      self::dispatchApi();
    } else {
      self::dispatchWeb();
    }
  }

  /**
   * Branch A (API Requests):
   * - Confined strictly to App\Controllers\Api\
   * - Emits Content-Type: application/json and CORS headers
   * - Intercepts OPTIONS preflight requests
   * - Never includes HTML layouts, navbars, or .php view files
   */
  public static function dispatchApi(): void
  {
    // Apply global CORS & JSON response headers
    Response::sendCorsHeaders();

    // Intercept OPTIONS preflight requests
    Response::handlePreflight();

    $rawPath = self::getRawPath();

    // Strip 'api/' prefix to get API resource path
    if (str_starts_with($rawPath, 'api/')) {
      $apiPath = trim(substr($rawPath, 4), '/');
    } elseif ($rawPath === 'api') {
      $apiPath = '';
    } else {
      $apiPath = $rawPath;
    }

    $httpMethod = Request::method();

    // API Root status endpoint
    if ($apiPath === '') {
      Response::success([
        'api' => 'Mobile Backend REST API',
        'version' => '1.0.0',
        'status' => 'online',
        'timestamp' => date('c'),
        'endpoints' => [
          'products' => '/api/products',
          'categories' => '/api/categories',
          'auth' => '/api/auth',
          'register' => '/api/register',
        ],
      ], 'Mobile API backend is running successfully.', 200);
    }

    // 1. Check registered explicit API routes
    if (isset(self::$apiRoutes[$httpMethod])) {
      $handler = self::$apiRoutes[$httpMethod][$rawPath]
              ?? self::$apiRoutes[$httpMethod][$apiPath]
              ?? null;
      if ($handler !== null) {
        self::executeHandler($handler);
        exit();
      }
    }

    // 2. Check if route matches registered route with unsupported method
    foreach (self::$apiRoutes as $method => $routes) {
      if (isset($routes[$rawPath]) || isset($routes[$apiPath])) {
        Response::badRequest("HTTP method {$httpMethod} not supported on '/{$rawPath}'.", [
          "Supported methods: {$method}."
        ]);
      }
    }

    $segments = explode('/', $apiPath);
    $rawResource = strtolower($segments[0]);

    // Resource map for API controllers
    $resourceMap = [
      'products'   => 'Product',
      'product'    => 'Product',
      'categories' => 'Category',
      'category'   => 'Category',
      'auth'       => 'AuthApi',
      'auth-api'   => 'AuthApi',
      'authapi'    => 'AuthApi',
      'users'      => 'User',
      'user'       => 'User',
      'register'   => 'AuthApi',
    ];

    $controllerBase = $resourceMap[$rawResource] ?? ucfirst($rawResource);
    $controllerClass = "App\\Controllers\\Api\\{$controllerBase}Controller";
    $controllerFile = app . "Controllers/Api/{$controllerBase}Controller.php";

    if (!file_exists($controllerFile) || !class_exists($controllerClass)) {
      Response::notFound("Resource or endpoint '/api/{$apiPath}' not found.", [
        "API Controller 'App\\Controllers\\Api\\{$controllerBase}Controller' does not exist."
      ]);
    }

    $controller = new $controllerClass();

    $action = null;
    $param = null;
    $segCount = count($segments);

    if ($segCount === 1) {
      if ($httpMethod === 'GET') {
        $action = 'index';
      } elseif ($httpMethod === 'POST') {
        $action = 'store';
      } else {
        Response::badRequest("HTTP method {$httpMethod} not supported on collection '/api/{$segments[0]}'.", [
          'Supported methods: GET (list), POST (create).'
        ]);
      }
    } elseif ($segCount === 2) {
      $seg1 = $segments[1];

      if (is_numeric($seg1)) {
        $param = $seg1;
        if ($httpMethod === 'GET') {
          $action = 'show';
        } elseif (in_array($httpMethod, ['PUT', 'PATCH', 'POST'])) {
          $action = 'update';
        } elseif ($httpMethod === 'DELETE') {
          $action = 'delete';
        } else {
          Response::badRequest("HTTP method {$httpMethod} not supported on single item.", [
            'Supported methods: GET, PUT, PATCH, DELETE, POST.'
          ]);
        }
      } elseif (method_exists($controller, $seg1)) {
        $action = $seg1;
        $param = $_GET['id'] ?? null;
      } else {
        $param = $seg1;
        if ($httpMethod === 'GET') {
          $action = 'show';
        } elseif (in_array($httpMethod, ['PUT', 'PATCH', 'POST'])) {
          $action = 'update';
        } elseif ($httpMethod === 'DELETE') {
          $action = 'delete';
        } else {
          $action = $seg1;
        }
      }
    } elseif ($segCount >= 3) {
      $action = $segments[1];
      $param = $segments[2];
    }

    if ($action === 'show' && !method_exists($controller, 'show') && method_exists($controller, 'edit')) {
      $action = 'edit';
    }

    if (!$action || !method_exists($controller, $action)) {
      Response::notFound("Action not found.", [
        "Method '{$action}' does not exist on '{$controllerBase}Controller'."
      ]);
    }

    $controller->$action($param);
    exit();
  }

  /**
   * Branch B (Web Requests):
   * - Dispatches to standard web controllers in App\Controllers\*
   * - Renders views with HTML layout and Bootstrap
   * - Handles standard form submissions and redirects
   */
  public static function dispatchWeb(): void
  {
    $viewDir = app . 'Views/';
    $controllerDir = app . 'Controllers/';
    $defaultController = 'Product';
    $defaultMethod = 'index';

    // Resolve web controller and action
    if (!empty($_GET['controller'])) {
      $controllerBase = ucfirst($_GET['controller']);
      $method = $_GET['action'] ?? ($_GET['method'] ?? $defaultMethod);
      $param = $_GET['id'] ?? null;
    } elseif (!empty($_GET['url'])) {
      $parts = explode('/', trim($_GET['url'], '/'));
      $controllerBase = ucfirst($parts[0]);
      $method = $parts[1] ?? $defaultMethod;
      $param = $parts[2] ?? ($_GET['id'] ?? null);
    } else {
      $rawPath = self::getRawPath();
      if ($rawPath !== '') {
        $parts = explode('/', $rawPath);
        $controllerBase = ucfirst($parts[0]);
        $method = $parts[1] ?? $defaultMethod;
        $param = $parts[2] ?? ($_GET['id'] ?? null);
      } else {
        $controllerBase = $defaultController;
        $method = $defaultMethod;
        $param = $_GET['id'] ?? null;
      }
    }

    $controllerClass = "App\\Controllers\\{$controllerBase}Controller";
    $controllerFile = "{$controllerDir}{$controllerBase}Controller.php";
    $viewFile = "{$viewDir}{$controllerBase}/index.php";

    // Try to load web controller
    if (file_exists($controllerFile)) {
      require_once $controllerFile;

      if (!class_exists($controllerClass)) {
        echo "<div class=\"alert alert-danger\">Controller class <b>" . htmlspecialchars($controllerClass) . "</b> not found!</div>";
        return;
      }

      $controller = new $controllerClass();

      if (!method_exists($controller, $method)) {
        echo "<div class=\"alert alert-danger\">Method <b>" . htmlspecialchars($method) . "</b> does not exist in <b>" . htmlspecialchars($controllerClass) . "</b>!</div>";
        return;
      }

      $controller->$method($param);
      return;
    }

    // Fallback to controller view folder if exists
    if (file_exists($viewFile)) {
      include_once $viewFile;
      return;
    }

    // Fallback to direct view file (e.g. App/Views/index.php)
    $directViewFile = $viewDir . strtolower($controllerBase) . '.php';
    if (file_exists($directViewFile)) {
      include_once $directViewFile;
      return;
    }

    echo "<div class=\"alert alert-warning\">Page <b>" . htmlspecialchars($controllerBase) . "</b> not found!</div>";
  }

  /**
   * Execute a resolved route handler.
   */
  public static function executeHandler(mixed $handler, array $params = []): void
  {
    if (is_callable($handler)) {
      call_user_func_array($handler, $params);
      return;
    }

    if (is_array($handler) && count($handler) === 2) {
      [$target, $method] = $handler;
      $controller = is_object($target) ? $target : new $target();
      if (!method_exists($controller, $method)) {
        Response::notFound("Method '{$method}' not found.");
      }
      call_user_func_array([$controller, $method], $params);
      return;
    }

    if (is_string($handler)) {
      if (str_contains($handler, '::')) {
        [$class, $method] = explode('::', $handler, 2);
      } elseif (str_contains($handler, '@')) {
        [$class, $method] = explode('@', $handler, 2);
      } else {
        $class = $handler;
        $method = '__invoke';
      }

      if (!class_exists($class)) {
        Response::notFound("Controller '{$class}' not found.");
      }

      $controller = new $class();
      if (!method_exists($controller, $method)) {
        Response::notFound("Method '{$method}' not found on '{$class}'.");
      }

      call_user_func_array([$controller, $method], $params);
      return;
    }

    Response::serverError("Invalid route handler configured.");
  }
}
