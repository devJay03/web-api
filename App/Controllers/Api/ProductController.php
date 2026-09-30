<?php

namespace App\Controllers\Api;

use App\Model\Product;
use App\Model\Category;
use App\Validator\ProductValidator;
use App\Request;
use PDOException;

class ProductController extends BaseApiController
{
  protected Product $model;

  public function __construct()
  {
    $this->model = new Product();
  }

  /**
   * GET /api/products
   * List all products.
   */
  public function index(): void
  {
    try {
      $products = $this->model->all();
      $this->success($products, 'Products retrieved successfully.', 200);
    } catch (PDOException $e) {
      $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
    }
  }

  /**
   * GET /api/products/{id}
   * Retrieve a single product by ID.
   */
  public function show(?string $id = null): void
  {
    $id = $id ?? Request::query('id');

    if (empty($id) || !is_numeric($id)) {
      $this->badRequest('Invalid product ID.', ['A valid numeric product ID is required.']);
    }

    try {
      $product = $this->model->edit($id);

      if (!$product) {
        $this->notFound('Product not found.', ["Product with ID {$id} does not exist."]);
      }

      $this->success($product, 'Product retrieved successfully.', 200);
    } catch (PDOException $e) {
      $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
    }
  }

  /**
   * Alias for show($id) for backwards-compatibility.
   */
  public function edit(?string $id = null): void
  {
    $this->show($id);
  }

  /**
   * POST /api/products
   * Create a new product.
   */
  public function store(): void
  {
    $method = Request::method();
    if ($method !== 'POST') {
      $this->badRequest('Method not supported.', ["{$method} method is not supported for product creation. Use POST."]);
    }

    $data = Request::all();
    $errors = ProductValidator::validate($data);

    if (!empty($errors)) {
      $this->validationError($errors, 'Validation failed.');
    }

    try {
      $category = Category::find($data['category_id']);
      if (!$category) {
        $this->validationError(['The selected category does not exist.'], 'Validation failed.');
      }

      $newId = $this->model->store($data);
      if (!$newId) {
        $this->serverError('Failed to create product.', ['Database insert did not return an ID.']);
      }

      $createdProduct = $this->model->edit($newId);
      $this->created($createdProduct ?: array_merge(['id' => (int)$newId], $data), 'Product created successfully.');
    } catch (PDOException $e) {
      if ($e->getCode() == '23000') {
        $this->validationError(['A product with this name already exists or foreign key violated.'], 'Conflict error.');
      } else {
        $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
      }
    }
  }

  /**
   * PUT/POST /api/products/{id}
   * Update an existing product.
   */
  public function update(?string $id = null): void
  {
    $method = Request::method();
    if (!in_array($method, ['PUT', 'PATCH', 'POST'])) {
      $this->badRequest('Method not supported.', ["{$method} method is not supported for product update. Use PUT or POST."]);
    }

    $data = Request::all();
    $data['id'] = $id ?? $data['id'] ?? Request::query('id');

    if (empty($data['id']) || !is_numeric($data['id'])) {
      $this->badRequest('Invalid product ID.', ['A valid numeric product ID is required.']);
    }

    try {
      $existing = $this->model->edit($data['id']);
      if (!$existing) {
        $this->notFound('Product not found.', ["Cannot update. Product with ID {$data['id']} does not exist."]);
      }

      $errors = ProductValidator::validate($data, true);
      if (!empty($errors)) {
        $this->validationError($errors, 'Validation failed.');
      }

      $category = Category::find($data['category_id']);
      if (!$category) {
        $this->validationError(['The selected category does not exist.'], 'Validation failed.');
      }

      $this->model->update($data);
      $updated = $this->model->edit($data['id']);

      $this->success($updated ?: $data, 'Product updated successfully.', 200);
    } catch (PDOException $e) {
      if ($e->getCode() == '23000') {
        $this->validationError(['Product name must be unique or foreign key violated.'], 'Conflict error.');
      } else {
        $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
      }
    }
  }

  /**
   * DELETE /api/products/{id}
   * Delete a product.
   */
  public function delete(?string $id = null): void
  {
    $id = $id ?? Request::all()['id'] ?? Request::query('id');

    if (empty($id) || !is_numeric($id)) {
      $this->badRequest('Invalid product ID.', ['A valid numeric product ID is required.']);
    }

    try {
      $existing = $this->model->edit($id);
      if (!$existing) {
        $this->notFound('Product not found.', ["Cannot delete. Product with ID {$id} does not exist."]);
      }

      $count = $this->model->delete($id);
      if ($count > 0) {
        $this->success(['id' => (int)$id], 'Product deleted successfully.', 200);
      } else {
        $this->notFound('Unable to delete product.', ["Product with ID {$id} could not be deleted."]);
      }
    } catch (PDOException $e) {
      $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
    }
  }
}
