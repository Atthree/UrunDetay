<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

if (!girisYapmisMi()) {
    header('Location: /UrunDetay/magaza/giris.php');
    exit;
}

$urunId = (int)($_POST['urun_id'] ?? 0);
$puan = (int)($_POST['puan'] ?? 0);
$yorum = trim($_POST['yorum'] ?? '');
$kullaniciId = $_SESSION['kullanici_id'];

if ($urunId <= 0 || $puan < 1 || $puan > 5 || $yorum === '') {
    header('Location: /UrunDetay/magaza/urun.php?id=' . $urunId . '&hata=gecersiz');
    exit;
}

$kontrol = $pdo->prepare("SELECT id FROM urun_yorumlari WHERE urun_id = :uid AND kullanici_id = :kid");
$kontrol->execute([':uid' => $urunId, ':kid' => $kullaniciId]);

if ($kontrol->fetch()) {
    header('Location: /UrunDetay/magaza/urun.php?id=' . $urunId . '&hata=zaten_yorum_var');
    exit;
}

$ekle = $pdo->prepare("
    INSERT INTO urun_yorumlari (urun_id, kullanici_id, puan, yorum)
    VALUES (:uid, :kid, :puan, :yorum)
");
$ekle->execute([
    ':uid'   => $urunId,
    ':kid'   => $kullaniciId,
    ':puan'  => $puan,
    ':yorum' => $yorum,
]);

header('Location: /UrunDetay/magaza/urun.php?id=' . $urunId . '#yorumlar');
exit;