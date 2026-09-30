<?php

namespace App\Controllers\Api;

use App\Model\Category;
use App\Validator\CategoryValidator;
use App\Request;
use PDOException;

class CategoryController extends BaseApiController
{
  protected Category $model;

  public function __construct()
  {
    $this->model = new Category();
  }

  /**
   * GET /api/categories
   * List all categories.
   */
  public function index(): void
  {
    try {
      $categories = $this->model->getAll();
      $this->success($categories, 'Categories retrieved successfully.', 200);
    } catch (PDOException $e) {
      $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
    }
  }

  /**
   * GET /api/categories/{id}
   * Retrieve a single category by ID.
   */
  public function show(?string $id = null): void
  {
    $id = $id ?? Request::query('id');

    if (empty($id) || !is_numeric($id)) {
      $this->badRequest('Invalid category ID.', ['A valid numeric category ID is required.']);
    }

    try {
      $category = $this->model->getById($id);

      if (!$category) {
        $this->notFound('Category not found.', ["Category with ID {$id} does not exist."]);
      }

      $this->success($category, 'Category retrieved successfully.', 200);
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
   * POST /api/categories
   * Create a new category.
   */
  public function store(): void
  {
    $method = Request::method();
    if ($method !== 'POST') {
      $this->badRequest('Method not supported.', ["{$method} method is not supported for category creation. Use POST."]);
    }

    $data = Request::all();
    $errors = CategoryValidator::validate($data);

    if (!empty($errors)) {
      $this->validationError($errors, 'Validation failed.');
    }

    try {
      $newId = $this->model->create($data);
      if (!$newId) {
        $this->serverError('Failed to create category.', ['Database insert did not return an ID.']);
      }

      $created = $this->model->getById($newId);
      $this->created($created ?: array_merge(['id' => (int)$newId], $data), 'Category created successfully.');
    } catch (PDOException $e) {
      if ($e->getCode() == '23000') {
        $this->validationError(['A category with this name already exists.'], 'Conflict error.');
      } else {
        $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
      }
    }
  }

  /**
   * PUT/POST /api/categories/{id}
   * Update an existing category.
   */
  public function update(?string $id = null): void
  {
    $method = Request::method();
    if (!in_array($method, ['PUT', 'PATCH', 'POST'])) {
      $this->badRequest('Method not supported.', ["{$method} method is not supported for category update. Use PUT or POST."]);
    }

    $data = Request::all();
    $data['id'] = $id ?? $data['id'] ?? Request::query('id');

    if (empty($data['id']) || !is_numeric($data['id'])) {
      $this->badRequest('Invalid category ID.', ['A valid numeric category ID is required.']);
    }

    try {
      $existing = $this->model->getById($data['id']);
      if (!$existing) {
        $this->notFound('Category not found.', ["Cannot update. Category with ID {$data['id']} does not exist."]);
      }

      $errors = CategoryValidator::validate($data, true);
      if (!empty($errors)) {
        $this->validationError($errors, 'Validation failed.');
      }

      $this->model->update($data);
      $updated = $this->model->getById($data['id']);

      $this->success($updated ?: $data, 'Category updated successfully.', 200);
    } catch (PDOException $e) {
      if ($e->getCode() == '23000') {
        $this->validationError(['Category name must be unique.'], 'Conflict error.');
      } else {
        $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
      }
    }
  }

  /**
   * DELETE /api/categories/{id}
   * Delete a category.
   */
  public function delete(?string $id = null): void
  {
    $id = $id ?? Request::all()['id'] ?? Request::query('id');

    if (empty($id) || !is_numeric($id)) {
      $this->badRequest('Invalid category ID.', ['A valid numeric category ID is required.']);
    }

    try {
      $existing = $this->model->getById($id);
      if (!$existing) {
        $this->notFound('Category not found.', ["Cannot delete. Category with ID {$id} does not exist."]);
      }

      $count = $this->model->delete($id);
      if ($count > 0) {
        $this->success(['id' => (int)$id], 'Category deleted successfully.', 200);
      } else {
        $this->notFound('Unable to delete category.', ["Category with ID {$id} could not be deleted."]);
      }
    } catch (PDOException $e) {
      if ($e->getCode() == '23000') {
        $this->badRequest(
          'Cannot delete this category because products are currently assigned to it.',
          ['Foreign key constraint violation: products exist in this category.']
        );
      } else {
        $this->serverError('Database error: ' . $e->getMessage(), [$e->getMessage()]);
      }
    }
  }
}
