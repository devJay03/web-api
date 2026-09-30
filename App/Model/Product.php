<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class Product extends Model
{
  /**
   * Fetch all products joined with their category name.
   */
  public function all(): array
  {
    $sql = "SELECT products.*, categories.name AS category_name
            FROM products
            LEFT JOIN categories ON products.category_id = categories.id
            ORDER BY products.id DESC";
    $stmt = $this->db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Store a new product with category_id foreign key.
   * Returns newly created product ID on success, or false on failure.
   */
  public function store(array $data): string|int|false
  {
    $sql = "INSERT INTO products (name, category_id, quantity, price)
            VALUES (:name, :category_id, :quantity, :price)";
    $stmt = $this->db->prepare($sql);
    $result = $stmt->execute([
      'name' => $data['name'],
      'category_id' => $data['category_id'],
      'quantity' => $data['quantity'],
      'price' => $data['price'],
    ]);

    return $result ? $this->getLastInsertId() : false;
  }

  /**
   * Find a single product by ID joined with category name.
   */
  public function edit($id): array|false
  {
    $sql = "SELECT products.*, categories.name AS category_name
            FROM products
            LEFT JOIN categories ON products.category_id = categories.id
            WHERE products.id = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Static alias to find a product by ID.
   */
  public static function find($id): array|false
  {
    return (new self())->edit($id);
  }

  /**
   * Delete a product by ID.
   */
  public function delete($id): int
  {
    $sql = "DELETE FROM products WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount();
  }

  /**
   * Update an existing product with category_id.
   */
  public function update(array $data): bool
  {
    $sql = "UPDATE products
            SET name = :name, category_id = :category_id, quantity = :quantity, price = :price
            WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
      'id' => $data['id'],
      'name' => $data['name'],
      'category_id' => $data['category_id'],
      'quantity' => $data['quantity'],
      'price' => $data['price'],
    ]);
  }
}
