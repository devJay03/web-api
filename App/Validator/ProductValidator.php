<?php

namespace App\Validator;

class ProductValidator
{
  /**
   * Validate product input data.
   */
  public static function validate(array &$data, bool $isUpdate = false): array
  {
    $errors = [];

    if ($isUpdate && (empty($data['id']) || !is_numeric($data['id']))) {
      $errors[] = 'Invalid product ID.';
    }

    if (empty($data['name']) || trim($data['name']) === '') {
      $errors[] = 'Product name is required.';
    } else {
      $data['name'] = trim($data['name']);
    }

    if (empty($data['category_id']) || !is_numeric($data['category_id']) || (int)$data['category_id'] <= 0) {
      $errors[] = 'Please select a valid category.';
    }

    if (!isset($data['quantity']) || !is_numeric($data['quantity']) || (int)$data['quantity'] <= 0) {
      $errors[] = 'Product quantity must be a valid number greater than 0.';
    }

    if (!isset($data['price']) || !is_numeric($data['price']) || (float)$data['price'] <= 0) {
      $errors[] = 'Product price must be a valid number greater than 0.';
    }

    return $errors;
  }
}