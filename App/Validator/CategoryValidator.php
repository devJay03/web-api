<?php

namespace App\Validator;

class CategoryValidator
{
  /**
   * Validate category input data and sanitize fields.
   */
  public static function validate(array &$data, bool $isUpdate = false): array
  {
    $errors = [];

    if ($isUpdate && (empty($data['id']) || !is_numeric($data['id']))) {
      $errors[] = 'Invalid category ID.';
    }

    if (empty($data['name']) || trim($data['name']) === '') {
      $errors[] = 'Category name is required.';
    } else {
      $data['name'] = trim($data['name']);
      if (strlen($data['name']) > 55) {
        $errors[] = 'Category name must not exceed 55 characters.';
      }
    }

    // Sanitize description
    if (isset($data['description'])) {
      $data['description'] = trim(strip_tags($data['description']));
    } else {
      $data['description'] = '';
    }

    return $errors;
  }
}
