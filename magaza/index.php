<?php require_once __DIR__ . '/includes/header.php'; ?>

<?php
// Kategoriye göre filtreleme
$seciliKategori = $_GET['kategori'] ?? null;
$filtreAktif = !empty($seciliKategori);
?>

<!-- Hero Section (yalnızca ana sayfada göster) -->
<?php if (!$filtreAktif): ?>
<section class="hero-video-wrap">
    <video class="hero-video" autoplay muted loop playsinline
           poster="/UrunDetay/magaza/assets/img/hero-poster.jpg">
        <source src="/UrunDetay/magaza/assets/video/hero.mp4" type="video/mp4">
    </video>
    <div class="hero-video-katman"></div>

    <div class="hero-video-icerik">
        <span class="hero-etiket">Yeni Sezon</span>
        <h1>Tarzını Yansıtan<br>Ürünler Burada</h1>
        <p>Kaliteli ürünleri en uygun fiyatlarla keşfet, hemen alışverişe başla.</p>
        <a href="#urunler" class="btn btn-light btn-lg rounded-pill px-4" style="font-weight:600;">
            Ürünleri İncele <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="hero-marquee">
        <div class="hero-marquee-track">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <span>Ücretsiz Kargo</span><span class="ayrac">✦</span>
                <span>14 Gün İade</span><span class="ayrac">✦</span>
                <span>Güvenli Ödeme</span><span class="ayrac">✦</span>
                <span>Kapıda Ödeme</span><span class="ayrac">✦</span>
                <span>%100 Orijinal Ürün</span><span class="ayrac">✦</span>
                <span>7/24 Müşteri Desteği</span><span class="ayrac">✦</span>
                <span>Yeni Sezon Ürünleri</span><span class="ayrac">✦</span>
            <?php endfor; ?>
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

<?php
// "Customer Say!" bölümü için DummyJSON'dan yorumları çek
function yorumlariGetir($adet = 9) {
    $ch = curl_init("https://dummyjson.com/products?limit=15&select=title,reviews,thumbnail,price");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $yanit = curl_exec($ch);
    curl_close($ch);

    if (!$yanit) return [];

    $veri = json_decode($yanit, true);
    if (empty($veri['products'])) return [];

    $tumYorumlar = [];
    foreach ($veri['products'] as $urun) {
        if (empty($urun['reviews'])) continue;
        foreach ($urun['reviews'] as $yorum) {
            $tumYorumlar[] = [
                'reviewerName' => $yorum['reviewerName'],
                'comment'      => $yorum['comment'],
                'rating'       => (int)round($yorum['rating']),
                'urunAdi'      => $urun['title'],
                'urunResim'    => $urun['thumbnail'],
                'urunFiyat'    => $urun['price'],
            ];
        }
    }

    shuffle($tumYorumlar);
    return array_slice($tumYorumlar, 0, $adet);
}

$musteriYorumlari = yorumlariGetir(9);
?>

<?php
// Öne çıkan kategoriler sekmesi — yalnızca İLK kategori için ürünleri PHP'de önceden çekiyoruz.
// Diğer kategoriler, sekmesine tıklandığında AJAX (POST) ile yüklenecek.
$oneCikanKategoriler = array_slice($kategoriler, 0, 6);
$ilkKategoriUrunleri = [];

if (count($oneCikanKategoriler) > 0) {
    $ilkKategoriAdi = $oneCikanKategoriler[0]['kategori'];
    $ornekStmt = $pdo->prepare("SELECT * FROM urunler WHERE kategori = :kategori AND durum = 1 ORDER BY id DESC LIMIT 4");
    $ornekStmt->execute([':kategori' => $ilkKategoriAdi]);
    $ilkKategoriUrunleri = $ornekStmt->fetchAll();
}

// Yardımcı fonksiyon: bir ürün listesine ortalama puan/yorum sayısı ekler
function urunlereYildizEkle($pdo, $urunler) {
    if (count($urunler) === 0) return $urunler;

    $idler = array_column($urunler, 'id');
    $yerTutucular = implode(',', array_fill(0, count($idler), '?'));
    $puanStmt = $pdo->prepare("
        SELECT urun_id, AVG(puan) AS ortalama, COUNT(*) AS adet
        FROM urun_yorumlari
        WHERE urun_id IN ($yerTutucular)
        GROUP BY urun_id
    ");
    $puanStmt->execute($idler);

    $puanMap = [];
    foreach ($puanStmt->fetchAll() as $satir) {
        $puanMap[$satir['urun_id']] = [
            'ortalama' => round((float)$satir['ortalama'], 1),
            'adet'     => (int)$satir['adet'],
        ];
    }

    foreach ($urunler as &$u) {
        $u['yildiz_ortalama'] = $puanMap[$u['id']]['ortalama'] ?? null;
        $u['yildiz_adet'] = $puanMap[$u['id']]['adet'] ?? 0;
    }
    unset($u);

    return $urunler;
}

$ilkKategoriUrunleri = urunlereYildizEkle($pdo, $ilkKategoriUrunleri);
?>

<!-- Öne Çıkan Kategoriler (sekmeli, ilk sekme dışında AJAX ile yüklenir) -->
<?php if (!$filtreAktif && count($oneCikanKategoriler) > 0): ?>
<section class="container py-5">
    <h2 class="section-baslik text-center">Öne Çıkan Kategoriler</h2>

    <div class="kategori-tab-bar">
        <?php foreach ($oneCikanKategoriler as $i => $kat): ?>
            <button type="button"
                    class="kategori-tab<?php echo $i === 0 ? ' aktif' : ''; ?>"
                    data-kategori="<?php echo htmlspecialchars($kat['kategori']); ?>">
                <?php echo htmlspecialchars(ucfirst($kat['kategori'])); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="kategori-panel-alani">
        <div class="kategori-spinner" id="kategoriSpinner"></div>
        <div class="row g-4" id="kategoriUrunGrid">
            <?php foreach ($ilkKategoriUrunleri as $urun): ?>
                <div class="col-6 col-md-3">
                    <a href="urun.php?id=<?php echo $urun['id']; ?>" class="urun-kart">
                        <div class="urun-resim-wrap">
                            <div class="urun-resim" style="background-image:url('<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>')"></div>
                        </div>
                        <div class="urun-bilgi">
                            <span class="urun-baslik"><?php echo htmlspecialchars($urun['baslik_tr']); ?></span>
                            <?php if ($urun['yildiz_ortalama'] !== null): ?>
                                <span class="urun-yildizlar">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?php echo $i <= round($urun['yildiz_ortalama']) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                    <?php endfor; ?>
                                </span>
                            <?php endif; ?>
                            <span class="urun-fiyat">$<?php echo number_format($urun['fiyat_usd'], 2); ?></span>
                            <?php if ((int)$urun['miktar'] === 0): ?>
                                <span class="urun-stok-yok">Stokta Yok</span>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <a href="index.php?kategori=<?php echo urlencode($oneCikanKategoriler[0]['kategori']); ?>#urunler"
               class="btn btn-outline-dark rounded-pill px-4" id="kategoriTumunuGorBtn">
                Tüm <?php echo htmlspecialchars(ucfirst($oneCikanKategoriler[0]['kategori'])); ?> Ürünlerini Gör
            </a>
        </div>
    </div>
</section>

<script>
    // İlk sekmenin verisini JS önbelleğine baştan koyuyoruz ki
    // kullanıcı ona geri dönünce tekrar sunucuya istek atılmasın.
    window.KATEGORI_ILK_VERI = {
        kategori: <?php echo json_encode($oneCikanKategoriler[0]['kategori'], JSON_UNESCAPED_UNICODE); ?>,
        urunler: <?php echo json_encode(array_map(function ($u) {
            return [
                'id' => $u['id'],
                'baslik_tr' => $u['baslik_tr'],
                'fiyat_usd' => (float)$u['fiyat_usd'],
                'miktar' => (int)$u['miktar'],
                'ana_resim' => resim_url($u['ana_resim']),
                'yildiz_ortalama' => $u['yildiz_ortalama'],
            ];
        }, $ilkKategoriUrunleri), JSON_UNESCAPED_UNICODE); ?>
    };
</script>
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
    $sql .= " AND (CASE WHEN fiyat_tl > 0 THEN fiyat_tl ELSE fiyat_usd END) >= :fiyat_min";
    $params[':fiyat_min'] = $fiyatMin;
}

if ($fiyatMax !== null && $fiyatMax > 0) {
    $sql .= " AND (CASE WHEN fiyat_tl > 0 THEN fiyat_tl ELSE fiyat_usd END) <= :fiyat_max";
    $params[':fiyat_max'] = $fiyatMax;
}

// Sıralama
switch ($siralama) {
    case 'fiyat_artan':
        $sql .= " ORDER BY (CASE WHEN fiyat_tl > 0 THEN fiyat_tl ELSE fiyat_usd END) ASC";
        break;
    case 'fiyat_azalan':
        $sql .= " ORDER BY (CASE WHEN fiyat_tl > 0 THEN fiyat_tl ELSE fiyat_usd END) DESC";
        break;
    case 'ad_az':
        $sql .= " ORDER BY baslik_tr ASC";
        break;
    default:
        $sql .= " ORDER BY id DESC";
}

if (!$filtreAktif) {
    $sql .= " LIMIT 24";
}

$stmtUrunler = $pdo->prepare($sql);
$stmtUrunler->execute($params);
$urunler = $stmtUrunler->fetchAll();

// Giriş yapan kullanıcının favori ürün ID'leri
$favoriIdler = [];
if (girisYapmisMi()) {
    $favStmt = $pdo->prepare("SELECT urun_id FROM favoriler WHERE kullanici_id = :kid");
    $favStmt->execute([':kid' => $_SESSION['kullanici_id']]);
    $favoriIdler = array_column($favStmt->fetchAll(), 'urun_id');
}

// "Paket Yap, %30 Kazan" bölümü için ürünler (en yeni 6 ürün)
$paketUrunleri = $pdo->query("
    SELECT * FROM urunler WHERE durum = 1 ORDER BY id DESC LIMIT 6
")->fetchAll();

// Paket ürünleri için ortalama puan ve yorum sayısını tek sorguda çek
$paketPuanlar = [];
if (count($paketUrunleri) > 0) {
    $idler = array_column($paketUrunleri, 'id');
    $yerTutucular = implode(',', array_fill(0, count($idler), '?'));
    $puanStmt = $pdo->prepare("
        SELECT urun_id, AVG(puan) AS ortalama, COUNT(*) AS adet
        FROM urun_yorumlari
        WHERE urun_id IN ($yerTutucular)
        GROUP BY urun_id
    ");
    $puanStmt->execute($idler);
    foreach ($puanStmt->fetchAll() as $satir) {
        $paketPuanlar[$satir['urun_id']] = [
            'ortalama' => round((float)$satir['ortalama'], 1),
            'adet'     => (int)$satir['adet'],
        ];
    }
}

// Toplam ürün sayısı (filtre sonucu)
$toplamUrun = count($urunler);
?>

<main class="container py-4" id="urunler">
    <?php if ($filtreAktif): ?>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="section-baslik mb-0">
            <?php echo htmlspecialchars(ucfirst($seciliKategori)); ?>
            <span style="font-size:0.85rem;font-weight:400;color:var(--renk-metin-acik);margin-left:8px;">
                (<?php echo $toplamUrun; ?> ürün)
            </span>
        </h2>
        <div class="d-flex gap-2 align-items-center">
            <button class="filtre-mobil-btn" id="filtreMobilBtn">
                <i class="bi bi-funnel"></i> Filtrele
            </button>
            <a href="index.php#urunler" class="btn btn-outline-secondary btn-sm" style="border-radius:var(--yuvarlatma-kucuk);">
                <i class="bi bi-x"></i> Filtreyi Temizle
            </a>
        </div>
    </div>
    <?php endif; ?>

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
                    <?php if ($fiyatMin !== null || $fiyatMax !== null): ?>
                        <a href="index.php?<?php echo http_build_query(array_filter(['kategori' => $seciliKategori, 'siralama' => $siralama])); ?>#urunler" class="filtre-temizle-link">
                            Fiyat filtresini temizle
                        </a>
                    <?php endif; ?>
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
                                <button class="favori-btn<?php echo in_array($urun['id'], $favoriIdler) ? ' aktif' : ''; ?>" data-id="<?php echo $urun['id']; ?>" 
                                        onclick="event.preventDefault();event.stopPropagation();toggleFavori(<?php echo $urun['id']; ?>, this);"
                                        title="Favorilere Ekle">
                                    <i class="bi <?php echo in_array($urun['id'], $favoriIdler) ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
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

    <?php endif; ?>

    <?php if (!$filtreAktif && count($paketUrunleri) > 0): ?>
<section class="container py-5" id="paketBolumu">
    <h2 class="section-baslik text-center">Paket Yap, %30 Kazan</h2>
    <p class="text-center text-muted mb-4">3 ürün seçin, %30 indirim kazanın.</p>

    <div class="paket-alan">
        <div class="paket-urun-satiri">
            <?php foreach ($paketUrunleri as $urun): ?>
                <div class="paket-urun-kart"
                     data-id="<?php echo $urun['id']; ?>"
                     data-baslik="<?php echo htmlspecialchars($urun['baslik_tr']); ?>"
                     data-fiyat="<?php echo (float)$urun['fiyat_usd']; ?>"
                     data-resim="<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>">
                    <div class="urun-resim-wrap">
                        <div class="urun-resim" style="background-image:url('<?php echo htmlspecialchars(resim_url($urun['ana_resim'])); ?>')"></div>
                    </div>
                    <div class="urun-bilgi">
                        <span class="urun-baslik"><?php echo htmlspecialchars($urun['baslik_tr']); ?></span>
                        <?php $puanBilgi = $paketPuanlar[$urun['id']] ?? null; ?>
                        <?php if ($puanBilgi): ?>
                            <span class="paket-urun-yildiz">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi <?php echo $i <= round($puanBilgi['ortalama']) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                <?php endfor; ?>
                            </span>
                        <?php endif; ?>
                        <span class="urun-fiyat">$<?php echo number_format($urun['fiyat_usd'], 2); ?></span>
                    </div>
                    <button type="button" class="paket-ekle-btn">Pakete Ekle</button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="paket-ozet" id="paketOzet">
            <div class="paket-ozet-baslik">Paket İçeriği</div>
            <p class="paket-ozet-aciklama"><strong> 3</strong> ürün ekleyin ve <strong>%30 Kazanın.</strong></p>

            <div class="paket-ozet-liste" id="paketOzetListe">
                <p class="text-muted" id="paketBosMesaj">Henüz ürün eklemediniz.</p>
            </div>

            <div class="paket-ozet-alt">
                <div class="paket-ozet-satir">
                    <span>Ara Toplam</span>
                    <span id="paketAraToplam">$0.00</span>
                </div>
                <div class="paket-ozet-satir paket-indirim-satiri" id="paketIndirimSatiri" style="display:none;">
                    <span>%30 İndirim</span>
                    <span id="paketIndirimTutari">-$0.00</span>
                </div>
                <div class="paket-ozet-satir paket-toplam-satiri">
                    <span>Toplam</span>
                    <span id="paketToplam">$0.00</span>
                </div>
                <button type="button" class="paket-sepete-ekle-btn" id="paketSepeteEkleBtn" disabled>
                    Tümünü Sepete Ekle
                </button>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
</main>

<?php if (count($musteriYorumlari) > 0): ?>
<section class="yorum-bolumu">
    <div class="container">
        <h2 class="section-baslik text-center">Customer Say!</h2>
        <p class="text-center text-muted mb-4">Customers love our products and we always strive to please them all.</p>

        <div class="yorum-slider-wrap">
            <button type="button" class="yorum-ok yorum-ok-sol" id="yorumOkSol">
                <i class="bi bi-chevron-left"></i>
            </button>

            <div class="yorum-slider" id="yorumSlider">
                <?php foreach ($musteriYorumlari as $yorum): ?>
                    <div class="yorum-kart">
                        <div class="yorum-yildizlar">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?php echo $i <= $yorum['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="yorum-isim">
                            <?php echo htmlspecialchars($yorum['reviewerName']); ?>
                            <span class="yorum-onay"><i class="bi bi-patch-check-fill"></i> Verified Buyer</span>
                        </div>
                        <p class="yorum-metin"><?php echo htmlspecialchars($yorum['comment']); ?></p>
                        <div class="yorum-urun">
                            <img src="<?php echo htmlspecialchars($yorum['urunResim']); ?>" alt="">
                            <div>
                                <span class="yorum-urun-ad"><?php echo htmlspecialchars($yorum['urunAdi']); ?></span>
                                <span class="yorum-urun-fiyat">$<?php echo number_format($yorum['urunFiyat'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="yorum-ok yorum-ok-sag" id="yorumOkSag">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const slider = document.getElementById('yorumSlider');
    const solBtn = document.getElementById('yorumOkSol');
    const sagBtn = document.getElementById('yorumOkSag');
    if (!slider || !solBtn || !sagBtn) return;

    function kartGenisligi() {
        const kart = slider.querySelector('.yorum-kart');
        if (!kart) return 300;
        const stil = window.getComputedStyle(kart);
        return kart.offsetWidth + parseInt(stil.marginRight || 0) + 24;
    }

    solBtn.addEventListener('click', function () {
        slider.scrollBy({ left: -kartGenisligi(), behavior: 'smooth' });
    });

    sagBtn.addEventListener('click', function () {
        slider.scrollBy({ left: kartGenisligi(), behavior: 'smooth' });
    });
});
</script>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>