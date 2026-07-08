<?php
/**
 * urun.php için veri hazırlama (controller) katmanı.
 * Bu dosya çağrılmadan önce $pdo ve bulunmuş bir $urun dizisi hazır olmalıdır.
 *
 * @var PDO   $pdo
 * @var array $urun
 */

// Ek resimler
$stmt2 = $pdo->prepare("SELECT * FROM urun_resimler WHERE urun_id = :id ORDER BY sira");
$stmt2->execute([':id' => $urun['id']]);
$ekResimler = $stmt2->fetchAll();

$eskiFiyat = (float)($urun['fiyat2_tl'] ?? 0);
$stokVar = (int)$urun['miktar'] > 0;

// Bu ürün, giriş yapan kullanıcının favorisinde mi?
$favoride = false;
if (girisYapmisMi()) {
    $favKontrol = $pdo->prepare("SELECT id FROM favoriler WHERE kullanici_id = :kid AND urun_id = :uid");
    $favKontrol->execute([':kid' => $_SESSION['kullanici_id'], ':uid' => $urun['id']]);
    $favoride = (bool)$favKontrol->fetch();
}

// Benzer ürünler (aynı kategori)
$benzerUrunler = [];
if (!empty($urun['kategori'])) {
    $stmtBenzer = $pdo->prepare("
        SELECT * FROM urunler
        WHERE kategori = :kategori AND id != :id AND durum = 1
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmtBenzer->execute([':kategori' => $urun['kategori'], ':id' => $urun['id']]);
    $benzerUrunler = $stmtBenzer->fetchAll();
}

// Ürün yorumları
$yorumStmt = $pdo->prepare("
    SELECT uy.*, COALESCE(k.ad_soyad, uy.reviewer_adi) AS yazan_ad
    FROM urun_yorumlari uy
    LEFT JOIN kullanicilar k ON k.id = uy.kullanici_id
    WHERE uy.urun_id = :id
    ORDER BY uy.olusturma_tarihi DESC
");
$yorumStmt->execute([':id' => $urun['id']]);
$urunYorumlari = $yorumStmt->fetchAll();

// Puan dağılımı (5..1 yıldız adetleri) ve ortalama
$ozetStmt = $pdo->prepare("
    SELECT puan, COUNT(*) AS adet
    FROM urun_yorumlari
    WHERE urun_id = :id
    GROUP BY puan
");
$ozetStmt->execute([':id' => $urun['id']]);
$ozetSonuc = $ozetStmt->fetchAll();

$dagilim = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$yorumSayisi = 0;
$toplamPuan = 0;

foreach ($ozetSonuc as $satir) {
    $p = (int)$satir['puan'];
    $adet = (int)$satir['adet'];
    if (isset($dagilim[$p])) {
        $dagilim[$p] = $adet;
        $yorumSayisi += $adet;
        $toplamPuan += $p * $adet;
    }
}

$ortalamaPuan = $yorumSayisi > 0 ? round($toplamPuan / $yorumSayisi, 1) : 0;

// Giriş yapan kullanıcı bu ürüne daha önce yorum yapmış mı?
$dahaOnceYorumYapmis = false;
if (girisYapmisMi()) {
    $kontrolStmt = $pdo->prepare("SELECT id FROM urun_yorumlari WHERE urun_id = :uid AND kullanici_id = :kid");
    $kontrolStmt->execute([':uid' => $urun['id'], ':kid' => $_SESSION['kullanici_id']]);
    $dahaOnceYorumYapmis = (bool)$kontrolStmt->fetch();
}
