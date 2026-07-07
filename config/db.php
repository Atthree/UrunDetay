<?php
// Veritabanı bağlantı ayarları
// XAMPP varsayılan olarak: host=localhost, user=root, şifre=boş

$DB_HOST = 'localhost';
$DB_NAME = 'magaza_admin'; // phpMyAdmin'de bu isimde veritabanı oluşturacağız
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Veritabanı bağlantı hatası: ' . $e->getMessage());
}
