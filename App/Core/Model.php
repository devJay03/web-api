<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Model extends Config
{
  protected $db;

  public function __construct()
  {
    $attributes = [
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];

    try {
      $this->db = new PDO(
        Config::type . ':host=' . Config::host . ';dbname=' . Config::name . ';charset=' . Config::charset,
        Config::user,
        Config::pass,
        $attributes
      );
    } catch (PDOException $e) {
      throw new Exception('Database Connection Failed: ' . $e->getMessage(), (int)$e->getCode(), $e);
    }
  }

  /**
   * Retrieve the ID generated for the last INSERT statement.
   */
  public function getLastInsertId(): string|int
  {
    return $this->db->lastInsertId();
  }

  /**
   * Get the active PDO instance.
   */
  public function getDb(): PDO
  {
    return $this->db;
  }
}
