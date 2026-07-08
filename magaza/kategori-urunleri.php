<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/helpers.php';

header('Content-Type: application/json');

$kategori = trim($_POST['kategori'] ?? '');

if ($kategori === '') {
    http_response_code(400);
    echo json_encode(['basarili' => false, 'hata' => 'gecersiz_kategori']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM urunler WHERE kategori = :kategori AND durum = 1 ORDER BY id DESC LIMIT 4");
$stmt->execute([':kategori' => $kategori]);
$urunler = $stmt->fetchAll();

$puanMap = urun_ortalama_puanlari($pdo, array_column($urunler, 'id'));

foreach ($urunler as &$u) {
    $u['ana_resim'] = resim_url($u['ana_resim']);
    $u['fiyat_usd'] = (float)$u['fiyat_usd'];
    $u['miktar'] = (int)$u['miktar'];
    $u['yildiz_ortalama'] = $puanMap[$u['id']]['ortalama'] ?? null;
}
unset($u);

echo json_encode(['basarili' => true, 'urunler' => $urunler]);