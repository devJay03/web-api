<?php

namespace App\Controllers;

use App\Model\Product;
use App\Model\Category;
use App\Validator\ProductValidator;
use App\Request;
use PDOException;

class ProductController
{
  protected Product $model;

  public function __construct()
  {
    $this->model = new Product();
  }

  /**
   * Display list of products.
   */
  public function index()
  {
    $products = $this->model->all();
    require_once view . 'product/index.php';
  }

  /**
   * Display form to add a new product.
   */
  public function add()
  {
    $categories = Category::getAll();
    require_once view . 'product/add.php';
  }

  /**
   * Store a newly created product.
   */
  public function store()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $data = Request::all();
      $errors = ProductValidator::validate($data);

      if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $data;
        header('Location: ?url=product/add');
        exit();
      }

      try {
        $this->model->store($data);
        $_SESSION['success'] = 'Product created successfully!';
        header('Location: ?url=product');
        exit();
      } catch (PDOException $e) {
        $_SESSION['errors'] = ['Database error: ' . $e->getMessage()];
        $_SESSION['old'] = $data;
        header('Location: ?url=product/add');
        exit();
      }
    } else {
      $_SESSION['errors'] = ['GET method is not supported for product/store'];
      header('Location: ?url=product');
      exit();
    }
  }

  /**
   * Display form to edit a product.
   */
  public function edit($id = null)
  {
    $id = $id ?? $_GET['id'] ?? null;
    $product = $this->model->edit($id);
    if ($product) {
      $categories = Category::getAll();
      require_once view . 'product/edit.php';
    } else {
      $_SESSION['errors'] = ['Unable to find the product'];
      header('Location: ?url=product');
      exit();
    }
  }

  /**
   * Update an existing product.
   */
  public function update($id = null)
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $data = Request::all();
      $data['id'] = $data['id'] ?? $id ?? $_GET['id'] ?? null;
      $errors = ProductValidator::validate($data, true);

      if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $data;
        header("Location: ?url=product/edit&id=" . ($data['id'] ?? ''));
        exit();
      }

      try {
        $this->model->update($data);
        $_SESSION['success'] = 'Product updated successfully!';
        header('Location: ?url=product');
        exit();
      } catch (PDOException $e) {
        $_SESSION['errors'] = ['Database error: ' . $e->getMessage()];
        $_SESSION['old'] = $data;
        header("Location: ?url=product/edit&id=" . ($data['id'] ?? ''));
        exit();
      }
    } else {
      $_SESSION['errors'] = ['GET method is not supported for product/update'];
      header('Location: ?url=product');
      exit();
    }
  }

  /**
   * Delete a product by ID.
   */
  public function delete($id = null)
  {
    $id = $id ?? $_GET['id'] ?? $_POST['id'] ?? null;
    $count = $this->model->delete($id);
    if ($count) {
      $_SESSION['success'] = 'Product deleted successfully!';
      header('Location: ?url=product');
      exit();
    } else {
      $_SESSION['errors'] = ['Unable to delete the product'];
      header('Location: ?url=product');
      exit();
    }
  }
}