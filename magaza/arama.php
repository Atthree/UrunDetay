<?php
/**
 * AJAX Arama API
 * Canlı arama için ürünleri JSON olarak döndürür.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, baslik_tr, fiyat_tl, fiyat_usd, ana_resim, kategori
    FROM urunler
    WHERE durum = 1 AND (baslik_tr LIKE :q1 OR kategori LIKE :q2 OR urun_kodu LIKE :q3)
    ORDER BY baslik_tr ASC
    LIMIT 8
");

$aranan = '%' . $q . '%';
$stmt->execute([':q1' => $aranan, ':q2' => $aranan, ':q3' => $aranan]);
$sonuclar = $stmt->fetchAll();

// Resim URL'lerini düzelt
foreach ($sonuclar as &$s) {
    $s['ana_resim'] = resim_url($s['ana_resim']);
}

echo json_encode($sonuclar, JSON_UNESCAPED_UNICODE);

