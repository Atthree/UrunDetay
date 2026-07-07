<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

girisGerekli();

$kullaniciId = $_SESSION['kullanici_id'];

$stmt = $pdo->prepare("
    SELECT u.*
    FROM urunler u
    INNER JOIN favoriler f ON f.urun_id = u.id
    WHERE f.kullanici_id = :kid AND u.durum = 1
    ORDER BY f.eklenme_tarihi DESC
");
$stmt->execute([':kid' => $kullaniciId]);
$favoriUrunler = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<main class="container py-4">
    <h2 class="section-baslik mb-4">
        Favorilerim
        <span style="font-size:0.85rem;font-weight:400;color:var(--renk-metin-acik);margin-left:8px;">
            (<?php echo count($favoriUrunler); ?> ürün)
        </span>
    </h2>

    <?php if (count($favoriUrunler) === 0): ?>
        <div class="text-center py-5">
            <i class="bi bi-heart" style="font-size:3rem;color:#ccc;"></i>
            <p class="text-muted mt-3">Henüz favori ürününüz yok.</p>
            <a href="/UrunDetay/magaza/index.php" class="btn btn-outline-dark btn-sm mt-2">Ürünlere Göz At</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($favoriUrunler as $urun): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="urun.php?id=<?php echo $urun['id']; ?>" class="urun-kart">
                        <button class="favori-btn aktif" data-id="<?php echo $urun['id']; ?>"
                                onclick="event.preventDefault();event.stopPropagation();toggleFavori(<?php echo $urun['id']; ?>, this);"
                                title="Favorilerden Çıkar">
                            <i class="bi bi-heart-fill"></i>
                        </button>
                        <div class="urun-resim-wrap">
                            <div class="urun-resim" style="background-image:url('<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>')"></div>
                        </div>
                        <div class="urun-bilgi">
                            <span class="urun-baslik"><?php echo htmlspecialchars($urun['baslik_tr']); ?></span>
                            <span class="urun-fiyat">
                                $<?php echo number_format($urun['fiyat_usd'], 2); ?>
                            </span>
                            <?php if ((int)$urun['miktar'] === 0): ?>
                                <span class="urun-stok-yok">Stokta Yok</span>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>