<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class Category extends Model
{
  /**
   * Fetch all categories ordered by name ascending.
   */
  public static function getAll(): array
  {
    $instance = new self();
    $stmt = $instance->db->query("SELECT * FROM categories ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Fetch a single category by its ID.
   */
  public function getById($id): array|false
  {
    $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Static alias to find a category by its ID.
   */
  public static function find($id): array|false
  {
    return (new self())->getById($id);
  }

  /**
   * Insert a new category and return newly created ID.
   */
  public function create(array $data): string|int|false
  {
    $sql = "INSERT INTO categories (name, description) VALUES (:name, :description)";
    $stmt = $this->db->prepare($sql);
    $result = $stmt->execute([
      'name' => $data['name'],
      'description' => $data['description'] ?? null,
    ]);

    return $result ? $this->getLastInsertId() : false;
  }

  /**
   * Update an existing category.
   */
  public function update(array $data): bool
  {
    $sql = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
      'id' => $data['id'],
      'name' => $data['name'],
      'description' => $data['description'] ?? null,
    ]);
  }

  /**
   * Delete a category by its ID.
   */
  public function delete($id): int
  {
    $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount();
  }
}
