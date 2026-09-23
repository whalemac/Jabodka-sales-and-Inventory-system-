<?php

try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );
    $pdo->exec('CREATE DATABASE IF NOT EXISTS jabodka_sims CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "DB OK\n";
} catch (Throwable $e) {
    echo 'ERR: '.$e->getMessage()."\n";
    exit(1);
}
