<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

$seciliKategori = $_GET['kategori'] ?? null;
$tumuAktif = isset($_GET['tumu']);
$fiyatMin = isset($_GET['fiyat_min']) && $_GET['fiyat_min'] !== '' ? (float)$_GET['fiyat_min'] : null;
$fiyatMax = isset($_GET['fiyat_max']) && $_GET['fiyat_max'] !== '' ? (float)$_GET['fiyat_max'] : null;
$siralama = $_GET['siralama'] ?? 'yeni';

$urunler = urunleri_filtrele($pdo, $seciliKategori, $fiyatMin, $fiyatMax, $siralama);
$urunler = urunlere_puan_ekle($pdo, $urunler);

$favoriIdler = [];
if (girisYapmisMi()) {
    $favStmt = $pdo->prepare("SELECT urun_id FROM favoriler WHERE kullanici_id = :kid");
    $favStmt->execute([':kid' => $_SESSION['kullanici_id']]);
    $favoriIdler = array_column($favStmt->fetchAll(), 'urun_id');
}

ob_start();
if (count($urunler) === 0) {
    ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox" style="font-size:3rem;color:#ccc;"></i>
        <p class="text-muted mt-3">Bu filtrelere uygun ürün bulunamadı.</p>
    </div>
    <?php
} else {
    ?>
    <div class="row g-4">
        <?php foreach ($urunler as $urun): ?>
            <?php include __DIR__ . '/includes/urun-karti.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php
}
$html = ob_get_clean();

echo json_encode([
    'basarili'       => true,
    'html'           => $html,
    'toplamUrun'     => count($urunler),
    'baslik'         => $seciliKategori ? ucfirst($seciliKategori) : 'Tüm Ürünler',
    'seciliKategori' => $seciliKategori,
    'tumuAktif'      => $tumuAktif,
    'fiyatMin'       => $fiyatMin,
    'fiyatMax'       => $fiyatMax,
    'siralama'       => $siralama,
], JSON_UNESCAPED_UNICODE);
