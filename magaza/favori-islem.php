<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (!girisYapmisMi()) {
    http_response_code(401);
    echo json_encode(['basarili' => false, 'hata' => 'giris_gerekli']);
    exit;
}

$urunId = (int)($_POST['urun_id'] ?? 0);
if ($urunId <= 0) {
    http_response_code(400);
    echo json_encode(['basarili' => false, 'hata' => 'gecersiz_urun']);
    exit;
}

$kullaniciId = $_SESSION['kullanici_id'];

$kontrol = $pdo->prepare("SELECT id FROM favoriler WHERE kullanici_id = :kid AND urun_id = :uid");
$kontrol->execute([':kid' => $kullaniciId, ':uid' => $urunId]);
$mevcut = $kontrol->fetch();

if ($mevcut) {
    $sil = $pdo->prepare("DELETE FROM favoriler WHERE id = :id");
    $sil->execute([':id' => $mevcut['id']]);
    $durum = 'silindi';
} else {
    $ekle = $pdo->prepare("INSERT INTO favoriler (kullanici_id, urun_id) VALUES (:kid, :uid)");
    $ekle->execute([':kid' => $kullaniciId, ':uid' => $urunId]);
    $durum = 'eklendi';
}

echo json_encode(['basarili' => true, 'durum' => $durum]);