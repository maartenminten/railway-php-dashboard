<?php
require_once '/var/www/app/db.php';

try {
    $pdo = db();
    foreach (['/var/www/sql/schema.sql', '/var/www/sql/seed.sql'] as $file) {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException("Could not read {$file}");
        }
        $pdo->exec($sql);
        echo "Applied {$file}\n";
    }
    echo "Database initialization complete.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Database initialization failed: {$e->getMessage()}\n");
    exit(1);
}
