<?php

namespace App\Controllers;

use App\Model\Category;
use App\Validator\CategoryValidator;
use App\Request;
use PDOException;

class CategoryController
{
  protected Category $model;

  public function __construct()
  {
    $this->model = new Category();
  }

  /**
   * Display listing of categories.
   */
  public function index()
  {
    $categories = $this->model->getAll();
    require_once view . 'category/index.php';
  }

  /**
   * Store a newly created category.
   */
  public function store()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $data = Request::all();
      $errors = CategoryValidator::validate($data);

      if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $data;
        header('Location: ?url=category');
        exit;
      }

      try {
        $this->model->create($data);
        $_SESSION['success'] = 'Category created successfully!';
        header('Location: ?url=category');
        exit;
      } catch (PDOException $e) {
        $_SESSION['errors'] = ['Database error: ' . $e->getMessage()];
        $_SESSION['old'] = $data;
        header('Location: ?url=category');
        exit;
      }
    } else {
      $_SESSION['errors'] = ['GET method is not supported for category/store'];
      header('Location: ?url=category');
      exit;
    }
  }

  /**
   * Update an existing category.
   */
  public function update($id = null)
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $data = Request::all();
      $data['id'] = $data['id'] ?? $id ?? $_GET['id'] ?? null;
      $errors = CategoryValidator::validate($data, true);

      if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $data;
        header('Location: ?url=category');
        exit;
      }

      try {
        $this->model->update($data);
        $_SESSION['success'] = 'Category updated successfully!';
        header('Location: ?url=category');
        exit;
      } catch (PDOException $e) {
        $_SESSION['errors'] = ['Database error: ' . $e->getMessage()];
        $_SESSION['old'] = $data;
        header('Location: ?url=category');
        exit;
      }
    } else {
      $_SESSION['errors'] = ['GET method is not supported for category/update'];
      header('Location: ?url=category');
      exit;
    }
  }

  /**
   * Delete a category by ID.
   */
  public function delete($id = null)
  {
    $id = $id ?? $_GET['id'] ?? $_POST['id'] ?? null;

    if (empty($id) || !is_numeric($id)) {
      $_SESSION['errors'] = ['Invalid category ID.'];
      header('Location: ?url=category');
      exit;
    }

    try {
      $count = $this->model->delete($id);
      if ($count) {
        $_SESSION['success'] = 'Category deleted successfully!';
      } else {
        $_SESSION['errors'] = ['Unable to delete the category. Category not found.'];
      }
      header('Location: ?url=category');
      exit;
    } catch (PDOException $e) {
      if ($e->getCode() == '23000') {
        $_SESSION['errors'] = ['Cannot delete this category because products are currently assigned to it.'];
      } else {
        $_SESSION['errors'] = ['Database error: ' . $e->getMessage()];
      }
      header('Location: ?url=category');
      exit;
    }
  }
}
