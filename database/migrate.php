<?php

require_once dirname(__DIR__) . '/App/loader.php';

use App\Core\Model;

try {
    $model = new Model();
    $db = $model->getDb();

    // Ensure users.id is compatible with BIGINT UNSIGNED
    $db->exec("ALTER TABLE `users` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");

    $sql = file_get_contents(__DIR__ . '/migrations/create_personal_access_tokens_table.sql');
    $db->exec($sql);

    echo "Migration completed successfully: personal_access_tokens table is ready.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
