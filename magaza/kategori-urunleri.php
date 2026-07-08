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

// Puan bilgisi ekle
if (count($urunler) > 0) {
    $idler = array_column($urunler, 'id');
    $yerTutucular = implode(',', array_fill(0, count($idler), '?'));
    $puanStmt = $pdo->prepare("
        SELECT urun_id, AVG(puan) AS ortalama
        FROM urun_yorumlari
        WHERE urun_id IN ($yerTutucular)
        GROUP BY urun_id
    ");
    $puanStmt->execute($idler);

    $puanMap = [];
    foreach ($puanStmt->fetchAll() as $satir) {
        $puanMap[$satir['urun_id']] = round((float)$satir['ortalama'], 1);
    }
} else {
    $puanMap = [];
}

foreach ($urunler as &$u) {
    $u['ana_resim'] = resim_url($u['ana_resim']);
    $u['fiyat_usd'] = (float)$u['fiyat_usd'];
    $u['miktar'] = (int)$u['miktar'];
    $u['yildiz_ortalama'] = $puanMap[$u['id']] ?? null;
}
unset($u);

echo json_encode(['basarili' => true, 'urunler' => $urunler]);