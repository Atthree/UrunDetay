<?php
require_once __DIR__ . '/../config/db.php';

$rows = $pdo->query("
    SELECT kategori, COUNT(*) AS urun_sayisi
    FROM urunler
    WHERE kategori IS NOT NULL AND kategori != '' AND durum = 1
    GROUP BY kategori
    ORDER BY urun_sayisi DESC
")->fetchAll();

echo "Toplam kategori sayisi: " . count($rows) . PHP_EOL;
foreach ($rows as $row) {
    echo "  - " . $row['kategori'] . " (" . $row['urun_sayisi'] . " urun)" . PHP_EOL;
}
