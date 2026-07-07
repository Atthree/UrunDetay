<?php require_once __DIR__ . '/includes/header.php'; ?>

<?php
// Kategoriye göre filtreleme
$seciliKategori = $_GET['kategori'] ?? null;
$filtreAktif = !empty($seciliKategori);
?>

<!-- Hero Section (yalnızca ana sayfada göster) -->
<?php if (!$filtreAktif): ?>
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <span class="hero-etiket">Yeni Sezon</span>
            <h1>Tarzını Yansıtan<br>Ürünler Burada</h1>
            <p>Kaliteli ürünleri en uygun fiyatlarla keşfet, hemen alışverişe başla.</p>
            <a href="#urunler" class="btn btn-light btn-lg rounded-pill px-4" style="font-weight:600;">
                Ürünleri İncele <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Kategorileri çek (hem grid hem filtre sidebar için)
$kategoriler = $pdo->query("
    SELECT kategori, COUNT(*) AS urun_sayisi, MIN(ana_resim) AS ornek_resim
    FROM urunler
    WHERE kategori IS NOT NULL AND kategori != '' AND durum = 1
    GROUP BY kategori
    ORDER BY urun_sayisi DESC
")->fetchAll();
?>

<!-- Kategori Kartları (yalnızca ana sayfada göster) -->
<?php if (!$filtreAktif && count($kategoriler) > 0): ?>
<section class="container py-5">
    <h2 class="section-baslik">Kategoriler</h2>
    <div class="row g-3">
        <?php foreach ($kategoriler as $kat): ?>
            <div class="col-6 col-md-3">
                <a href="index.php?kategori=<?php echo urlencode($kat['kategori']); ?>#urunler" class="kategori-kart">
                    <div class="kategori-resim" style="background-image:url('<?php echo htmlspecialchars(resim_url($kat['ornek_resim'])); ?>')"></div>
                    <div class="kategori-bilgi">
                        <span class="kategori-ad"><?php echo htmlspecialchars(ucfirst($kat['kategori'])); ?></span>
                        <span class="kategori-sayi"><?php echo (int)$kat['urun_sayisi']; ?> ürün</span>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php
// Ürünleri çek
// Fiyat aralığı filtreleri
$fiyatMin = isset($_GET['fiyat_min']) ? (float)$_GET['fiyat_min'] : null;
$fiyatMax = isset($_GET['fiyat_max']) ? (float)$_GET['fiyat_max'] : null;
$siralama = $_GET['siralama'] ?? 'yeni';

$sql = "SELECT * FROM urunler WHERE durum = 1";
$params = [];

if ($seciliKategori) {
    $sql .= " AND kategori = :kategori";
    $params[':kategori'] = $seciliKategori;
}

if ($fiyatMin !== null && $fiyatMin > 0) {
    $sql .= " AND fiyat_tl >= :fiyat_min";
    $params[':fiyat_min'] = $fiyatMin;
}

if ($fiyatMax !== null && $fiyatMax > 0) {
    $sql .= " AND fiyat_tl <= :fiyat_max";
    $params[':fiyat_max'] = $fiyatMax;
}

// Sıralama
switch ($siralama) {
    case 'fiyat_artan':
        $sql .= " ORDER BY fiyat_tl ASC";
        break;
    case 'fiyat_azalan':
        $sql .= " ORDER BY fiyat_tl DESC";
        break;
    case 'ad_az':
        $sql .= " ORDER BY baslik_tr ASC";
        break;
    default: // 'yeni'
        $sql .= " ORDER BY id DESC";
}

if (!$filtreAktif) {
    $sql .= " LIMIT 24";
}

$stmtUrunler = $pdo->prepare($sql);
$stmtUrunler->execute($params);
$urunler = $stmtUrunler->fetchAll();

// Toplam ürün sayısı (filtre sonucu)
$toplamUrun = count($urunler);
?>

<main class="container py-4" id="urunler">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="section-baslik mb-0">
            <?php echo $seciliKategori ? htmlspecialchars(ucfirst($seciliKategori)) : 'Tüm Ürünler'; ?>
            <span style="font-size:0.85rem;font-weight:400;color:var(--renk-metin-acik);margin-left:8px;">
                (<?php echo $toplamUrun; ?> ürün)
            </span>
        </h2>
        <?php if ($filtreAktif): ?>
            <div class="d-flex gap-2 align-items-center">
                <button class="filtre-mobil-btn" id="filtreMobilBtn">
                    <i class="bi bi-funnel"></i> Filtrele
                </button>
                <a href="index.php#urunler" class="btn btn-outline-secondary btn-sm" style="border-radius:var(--yuvarlatma-kucuk);">
                    <i class="bi bi-x"></i> Filtreyi Temizle
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($filtreAktif): ?>
    <!-- Kategoriye girildiğinde: Sol Filtre + Sağ Ürünler -->
    <div class="magaza-layout">
        <!-- Sol Filtre Sidebar -->
        <aside class="filtre-sidebar" id="filtreSidebar">
            <!-- Kategoriler -->
            <div class="filtre-kart">
                <div class="filtre-baslik"><i class="bi bi-grid"></i> Kategoriler</div>
                <ul class="filtre-liste">
                    <li>
                        <a href="index.php#urunler" class="<?php echo !$seciliKategori ? 'aktif' : ''; ?>">
                            Tümü
                        </a>
                    </li>
                    <?php foreach ($kategoriler as $kat): ?>
                        <li>
                            <a href="index.php?kategori=<?php echo urlencode($kat['kategori']); ?>#urunler"
                               class="<?php echo ($seciliKategori === $kat['kategori']) ? 'aktif' : ''; ?>">
                                <?php echo htmlspecialchars(ucfirst($kat['kategori'])); ?>
                                <span class="sayi"><?php echo (int)$kat['urun_sayisi']; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Fiyat Aralığı -->
            <div class="filtre-kart">
                <div class="filtre-baslik"><i class="bi bi-currency-exchange"></i> Fiyat Aralığı</div>
                <form method="get" action="index.php">
                    <?php if ($seciliKategori): ?>
                        <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($seciliKategori); ?>">
                    <?php endif; ?>
                    <input type="hidden" name="siralama" value="<?php echo htmlspecialchars($siralama); ?>">
                    <div class="filtre-fiyat-wrap">
                        <input type="number" name="fiyat_min" placeholder="Min" min="0" step="1"
                               value="<?php echo $fiyatMin !== null ? (int)$fiyatMin : ''; ?>">
                        <span>—</span>
                        <input type="number" name="fiyat_max" placeholder="Max" min="0" step="1"
                               value="<?php echo $fiyatMax !== null ? (int)$fiyatMax : ''; ?>">
                    </div>
                    <button type="submit" class="filtre-uygula-btn">Uygula</button>
                </form>
            </div>

            <!-- Sıralama -->
            <div class="filtre-kart">
                <div class="filtre-baslik"><i class="bi bi-sort-down"></i> Sıralama</div>
                <form method="get" action="index.php" id="siralamaForm">
                    <?php if ($seciliKategori): ?>
                        <input type="hidden" name="kategori" value="<?php echo htmlspecialchars($seciliKategori); ?>">
                    <?php endif; ?>
                    <?php if ($fiyatMin !== null): ?>
                        <input type="hidden" name="fiyat_min" value="<?php echo (int)$fiyatMin; ?>">
                    <?php endif; ?>
                    <?php if ($fiyatMax !== null): ?>
                        <input type="hidden" name="fiyat_max" value="<?php echo (int)$fiyatMax; ?>">
                    <?php endif; ?>
                    <select name="siralama" class="filtre-siralama" onchange="document.getElementById('siralamaForm').submit();">
                        <option value="yeni" <?php echo $siralama === 'yeni' ? 'selected' : ''; ?>>Yeni Eklenen</option>
                        <option value="fiyat_artan" <?php echo $siralama === 'fiyat_artan' ? 'selected' : ''; ?>>Fiyat: Düşük → Yüksek</option>
                        <option value="fiyat_azalan" <?php echo $siralama === 'fiyat_azalan' ? 'selected' : ''; ?>>Fiyat: Yüksek → Düşük</option>
                        <option value="ad_az" <?php echo $siralama === 'ad_az' ? 'selected' : ''; ?>>İsim: A → Z</option>
                    </select>
                </form>
            </div>
        </aside>

        <!-- Sağ: Ürün Grid -->
        <div class="urun-grid-alani">
            <?php if ($toplamUrun === 0): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size:3rem;color:#ccc;"></i>
                    <p class="text-muted mt-3">Bu filtrelere uygun ürün bulunamadı.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($urunler as $urun): ?>
                        <div class="col-6 col-md-4">
                            <a href="urun.php?id=<?php echo $urun['id']; ?>" class="urun-kart">
                                <button class="favori-btn" data-id="<?php echo $urun['id']; ?>" 
                                        onclick="event.preventDefault();event.stopPropagation();toggleFavori(<?php echo $urun['id']; ?>, this);"
                                        title="Favorilere Ekle">
                                    <i class="bi bi-heart"></i>
                                </button>
                                <div class="urun-resim-wrap">
                                    <div class="urun-resim" style="background-image:url('<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>')"></div>
                                </div>
                                <div class="urun-bilgi">
                                    <span class="urun-baslik"><?php echo htmlspecialchars($urun['baslik_tr']); ?></span>
                                    <span class="urun-fiyat">
                                        <?php if ((float)$urun['fiyat_tl'] > 0): ?>
                                            <?php echo number_format($urun['fiyat_tl'], 2, ',', '.'); ?> TL
                                        <?php else: ?>
                                            $<?php echo number_format($urun['fiyat_usd'], 2); ?>
                                        <?php endif; ?>
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
        </div>
    </div>

    <?php else: ?>
    <!-- Ana sayfa: Normal ürün grid -->
    <?php if ($toplamUrun === 0): ?>
        <p class="text-muted">Henüz ürün eklenmemiş.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($urunler as $urun): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="urun.php?id=<?php echo $urun['id']; ?>" class="urun-kart">
                        <button class="favori-btn" data-id="<?php echo $urun['id']; ?>"
                                onclick="event.preventDefault();event.stopPropagation();toggleFavori(<?php echo $urun['id']; ?>, this);"
                                title="Favorilere Ekle">
                            <i class="bi bi-heart"></i>
                        </button>
                        <div class="urun-resim-wrap">
                            <div class="urun-resim" style="background-image:url('<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>')"></div>
                        </div>
                        <div class="urun-bilgi">
                            <span class="urun-baslik"><?php echo htmlspecialchars($urun['baslik_tr']); ?></span>
                            <span class="urun-fiyat">
                                <?php if ((float)$urun['fiyat_tl'] > 0): ?>
                                    <?php echo number_format($urun['fiyat_tl'], 2, ',', '.'); ?> TL
                                <?php else: ?>
                                    $<?php echo number_format($urun['fiyat_usd'], 2); ?>
                                <?php endif; ?>
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
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>