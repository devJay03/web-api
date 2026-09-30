<?php

namespace App\Model;

use App\Core\Model;
use PDO;

class User extends Model
{
  /**
   * Find user by email address.
   */
  public function findByEmail(string $email): array|false
  {
    $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Find user by ID without sensitive password field.
   */
  public function findById($id): array|false
  {
    $stmt = $this->db->prepare("SELECT id, name, email, created_at, updated_at FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Register a new user.
   */
  public function create(array $data): string|int|false
  {
    $password = $data['password'] ?? '';
    $info = password_get_info($password);
    if (empty($info['algo'])) {
      $password = password_hash($password, PASSWORD_BCRYPT);
    }

    $sql = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
    $stmt = $this->db->prepare($sql);
    $result = $stmt->execute([
      'name' => $data['name'],
      'email' => $data['email'],
      'password' => $password,
    ]);

    return $result ? $this->getLastInsertId() : false;
  }
}
