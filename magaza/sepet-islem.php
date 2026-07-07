<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

header('Content-Type: application/json');

if (!girisYapmisMi()) {
    http_response_code(401);
    echo json_encode(['basarili' => false, 'hata' => 'giris_gerekli']);
    exit;
}

$kullaniciId = $_SESSION['kullanici_id'];
$islem = $_POST['islem'] ?? '';
$urunId = (int)($_POST['urun_id'] ?? 0);

if ($urunId <= 0) {
    http_response_code(400);
    echo json_encode(['basarili' => false, 'hata' => 'gecersiz_urun']);
    exit;
}

if ($islem === 'ekle') {
    $adet = max(1, (int)($_POST['adet'] ?? 1));

    $kontrol = $pdo->prepare("SELECT id, adet FROM sepet WHERE kullanici_id = :kid AND urun_id = :uid");
    $kontrol->execute([':kid' => $kullaniciId, ':uid' => $urunId]);
    $mevcut = $kontrol->fetch();

    if ($mevcut) {
        $guncelle = $pdo->prepare("UPDATE sepet SET adet = :adet WHERE id = :id");
        $guncelle->execute([':adet' => $mevcut['adet'] + $adet, ':id' => $mevcut['id']]);
    } else {
        $ekle = $pdo->prepare("INSERT INTO sepet (kullanici_id, urun_id, adet) VALUES (:kid, :uid, :adet)");
        $ekle->execute([':kid' => $kullaniciId, ':uid' => $urunId, ':adet' => $adet]);
    }
} elseif ($islem === 'adet_guncelle') {
    $delta = (int)($_POST['delta'] ?? 0);

    $kontrol = $pdo->prepare("SELECT id, adet FROM sepet WHERE kullanici_id = :kid AND urun_id = :uid");
    $kontrol->execute([':kid' => $kullaniciId, ':uid' => $urunId]);
    $mevcut = $kontrol->fetch();

    if ($mevcut) {
        $yeniAdet = $mevcut['adet'] + $delta;
        if ($yeniAdet < 1) {
            $pdo->prepare("DELETE FROM sepet WHERE id = :id")->execute([':id' => $mevcut['id']]);
        } else {
            $pdo->prepare("UPDATE sepet SET adet = :adet WHERE id = :id")->execute([':adet' => $yeniAdet, ':id' => $mevcut['id']]);
        }
    }
} elseif ($islem === 'sil') {
    $pdo->prepare("DELETE FROM sepet WHERE kullanici_id = :kid AND urun_id = :uid")->execute([':kid' => $kullaniciId, ':uid' => $urunId]);
} else {
    http_response_code(400);
    echo json_encode(['basarili' => false, 'hata' => 'gecersiz_islem']);
    exit;
}

// Güncel sepeti döndür
$stmt = $pdo->prepare("
    SELECT s.urun_id AS id, s.adet, u.baslik_tr AS baslik, u.fiyat_usd AS fiyat, u.ana_resim AS resim
    FROM sepet s
    INNER JOIN urunler u ON u.id = s.urun_id
    WHERE s.kullanici_id = :kid
    ORDER BY s.eklenme_tarihi DESC
");
$stmt->execute([':kid' => $kullaniciId]);
$sepet = $stmt->fetchAll();

foreach ($sepet as &$oge) {
    $oge['resim'] = resim_url($oge['resim']);
    $oge['adet'] = (int)$oge['adet'];
    $oge['fiyat'] = (float)$oge['fiyat'];
}
unset($oge);

echo json_encode(['basarili' => true, 'sepet' => $sepet]);