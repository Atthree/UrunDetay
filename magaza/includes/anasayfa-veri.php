<?php
/**
 * index.php için veri hazırlama (controller) katmanı.
 * Bu dosya yalnızca $pdo üzerinden veri çeker ve view'in
 * kullanacağı değişkenleri hazırlar; hiçbir HTML üretmez.
 *
 * @var PDO $pdo
 */

$seciliKategori = $_GET['kategori'] ?? null;
$filtreAktif = !empty($seciliKategori);

// "Tüm Ürünler" bağlantısıyla (mega menü / sidebar "Tümü") açılan, kategori
// seçilmeden ürün listesinin tamamının gösterildiği mod.
$tumuAktif = isset($_GET['tumu']);

// Ürün listesi + filtre sidebar'ının gösterileceği mod: bir kategori seçili
// olması veya "Tümü" ile açılmış olması yeterli.
$urunlerGoster = $filtreAktif || $tumuAktif;

// Kategorileri çek (hem grid hem filtre sidebar için)
$kategoriler = $pdo->query("
    SELECT kategori, COUNT(*) AS urun_sayisi, MIN(ana_resim) AS ornek_resim
    FROM urunler
    WHERE kategori IS NOT NULL AND kategori != '' AND durum = 1
    GROUP BY kategori
    ORDER BY urun_sayisi DESC
")->fetchAll();

// "Customer Say!" bölümü için dış API'den yorumları çek
$musteriYorumlari = dis_yorumlari_getir(9);

// Öne çıkan kategoriler sekmesi — yalnızca İLK kategori için ürünleri PHP'de
// önceden çekiyoruz. Diğer kategoriler, sekmesine tıklandığında AJAX (POST)
// ile kategori-urunleri.php'den yüklenecek.
$oneCikanKategoriler = array_slice($kategoriler, 0, 6);
$ilkKategoriUrunleri = [];

if (count($oneCikanKategoriler) > 0) {
    $ilkKategoriAdi = $oneCikanKategoriler[0]['kategori'];
    $ornekStmt = $pdo->prepare("SELECT * FROM urunler WHERE kategori = :kategori AND durum = 1 ORDER BY id DESC LIMIT 4");
    $ornekStmt->execute([':kategori' => $ilkKategoriAdi]);
    $ilkKategoriUrunleri = $ornekStmt->fetchAll();
}

$ilkKategoriUrunleri = urunlere_puan_ekle($pdo, $ilkKategoriUrunleri);

// Fiyat aralığı ve sıralama filtreleri
$fiyatMin = isset($_GET['fiyat_min']) ? (float)$_GET['fiyat_min'] : null;
$fiyatMax = isset($_GET['fiyat_max']) ? (float)$_GET['fiyat_max'] : null;
$siralama = $_GET['siralama'] ?? 'yeni';

$urunler = urunleri_filtrele($pdo, $seciliKategori, $fiyatMin, $fiyatMax, $siralama, $urunlerGoster ? null : 24);
$urunler = urunlere_puan_ekle($pdo, $urunler);

// Giriş yapan kullanıcının favori ürün ID'leri
$favoriIdler = [];
if (girisYapmisMi()) {
    $favStmt = $pdo->prepare("SELECT urun_id FROM favoriler WHERE kullanici_id = :kid");
    $favStmt->execute([':kid' => $_SESSION['kullanici_id']]);
    $favoriIdler = array_column($favStmt->fetchAll(), 'urun_id');
}

// "Paket Yap, %30 Kazan" bölümü için ürünler (en yeni 6 ürün) + puanları
$paketUrunleri = $pdo->query("
    SELECT * FROM urunler WHERE durum = 1 ORDER BY id DESC LIMIT 6
")->fetchAll();
$paketPuanlar = urun_ortalama_puanlari($pdo, array_column($paketUrunleri, 'id'));

// Toplam ürün sayısı (filtre sonucu)
$toplamUrun = count($urunler);
