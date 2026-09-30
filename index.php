<?php
session_start();

require_once __DIR__ . '/App/loader.php';

use App\Core\Router;
use App\Controllers\Api\AuthApiController;

// Register API Routes
Router::post('/api/register', [AuthApiController::class, 'register']);

// Branch A: Intercept API Requests immediately without rendering HTML
if (Router::isApiRequest()) {
  Router::dispatchApi();
  exit();
}

// Branch B: Render Web Application Layout & Views
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product & Category Management</title>
  <!-- Bootstrap 5 CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="public/style.css">
</head>

<body class="bg-light">
  <?php include 'App/Views/partials/navbar.php'; ?>

  <main class="container my-4">
    <?php
    // Show flash errors (if any)
    if (!empty($_SESSION['errors'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h6 class="alert-heading fw-bold mb-2">There was a problem:</h6>
        <ul class="mb-0">
          <?php foreach ($_SESSION['errors'] as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <?php
    // Show flash success (if any)
    if (!empty($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php Router::dispatchWeb(); ?>
  </main>

  <!-- Bootstrap 5 JS Bundle CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>