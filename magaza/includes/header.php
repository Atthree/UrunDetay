<?php require_once __DIR__ . '/../../config/db.php'; ?>
<?php require_once __DIR__ . '/helpers.php'; ?>
<?php require_once __DIR__ . '/auth.php'; ?>
<?php
// Mega menü için kategorileri çek
$kategoriMenusu = $pdo->query("
    SELECT kategori, COUNT(*) AS urun_sayisi
    FROM urunler
    WHERE kategori IS NOT NULL AND kategori != '' AND durum = 1
    GROUP BY kategori
    ORDER BY urun_sayisi DESC
")->fetchAll();

// Header'daki favori rozeti için sayı
$favoriSayisi = 0;
if (girisYapmisMi()) {
    $favSayacStmt = $pdo->prepare("SELECT COUNT(*) FROM favoriler WHERE kullanici_id = :kid");
    $favSayacStmt->execute([':kid' => $_SESSION['kullanici_id']]);
    $favoriSayisi = (int)$favSayacStmt->fetchColumn();
}

// Sayfa açılışında sepeti JS'e aktarmak için çekiyoruz
$sepetOgeleri = [];
if (girisYapmisMi()) {
    $sepetStmt = $pdo->prepare("
        SELECT s.urun_id AS id, s.adet, u.baslik_tr AS baslik, u.fiyat_usd AS fiyat, u.ana_resim AS resim
        FROM sepet s
        INNER JOIN urunler u ON u.id = s.urun_id
        WHERE s.kullanici_id = :kid
        ORDER BY s.eklenme_tarihi DESC
    ");
    $sepetStmt->execute([':kid' => $_SESSION['kullanici_id']]);
    $sepetOgeleri = $sepetStmt->fetchAll();
    foreach ($sepetOgeleri as &$oge) {
        $oge['resim'] = resim_url($oge['resim']);
        $oge['adet'] = (int)$oge['adet'];
        $oge['fiyat'] = (float)$oge['fiyat'];
    }
    unset($oge);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mağazam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/UrunDetay/magaza/assets/css/magaza.css">
</head>
<body>

<header class="site-header">
    <div class="header-genis">
        <div class="header-ust">
            <!-- Logo -->
            <a href="/UrunDetay/magaza/index.php" class="brand">
                <i class="bi bi-bag-heart"></i> Mağazam
            </a>

            <!-- Ana Navigasyon (Mega Menü) -->
            <nav class="ana-nav offcanvas-end offcanvas-md" tabindex="-1" id="anaNav" aria-labelledby="anaNavLabel">
                <div class="offcanvas-header d-md-none">
                    <h5 class="offcanvas-title" id="anaNavLabel">Menü</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#anaNav" aria-label="Kapat"></button>
                </div>
                <div class="offcanvas-body">
                <div class="nav-item">
                    <a href="/UrunDetay/magaza/index.php">Ana Sayfa</a>
                </div>

                <div class="nav-item dropdown">
                    <a href="#urunler" class="mega-trigger dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Ürünler <i class="bi bi-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu mega-menu" data-bs-display="static">
                        <div class="mega-menu-baslik">Kategoriler</div>
                        <div class="mega-menu-grid">
                            <?php foreach ($kategoriMenusu as $km): ?>
                                <a href="/UrunDetay/magaza/index.php?kategori=<?php echo urlencode($km['kategori']); ?>#urunler" class="mega-menu-link">
                                    <span class="mega-ikon"><i class="bi bi-tag"></i></span>
                                    <span>
                                        <?php echo htmlspecialchars(ucfirst($km['kategori'])); ?>
                                        <span class="mega-menu-sayi">(<?php echo (int)$km['urun_sayisi']; ?>)</span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                            <a href="/UrunDetay/magaza/index.php?tumu=1#urunler" class="mega-menu-link">
                                <span class="mega-ikon"><i class="bi bi-grid"></i></span>
                                <span>Tüm Ürünler</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="nav-item">
                    <a href="#">Hakkımızda</a>
                </div>
                <div class="nav-item">
                    <a href="#">İletişim</a>
                </div>
                </div>
            </nav>

            <!-- Sağ Üst İkonlar -->
            <div class="header-ikonlar">
                <!-- Arama -->
                <button class="header-ikon-btn" id="aramaAcBtn" data-bs-toggle="modal" data-bs-target="#aramaModal" title="Ara">
                    <i class="bi bi-search"></i>
                </button>

                <!-- Profil -->
                <?php if (girisYapmisMi()): ?>
                    <a href="/UrunDetay/magaza/profil.php" class="header-ikon-btn" title="Hesabım (<?php echo htmlspecialchars($_SESSION['kullanici_ad']); ?>)">
                        <i class="bi bi-person-check-fill"></i>
                    </a>
                <?php else: ?>
                    <a href="/UrunDetay/magaza/giris.php" class="header-ikon-btn" title="Giriş Yap">
                        <i class="bi bi-person"></i>
                    </a>
                <?php endif; ?>

                <!-- Favoriler -->
                <a href="<?php echo girisYapmisMi() ? '/UrunDetay/magaza/favorilerim.php' : '/UrunDetay/magaza/giris.php'; ?>" class="header-ikon-btn" id="favoriBtn" title="Favorilerim">
                    <i class="bi bi-heart"></i>
                    <span class="badge-sayi" id="favoriBadge"><?php echo $favoriSayisi ?? 0; ?></span>
                    
                </a>
                

                <!-- Sepet -->
                <button class="header-ikon-btn" id="sepetAcBtn" data-bs-toggle="offcanvas" data-bs-target="#sepetSidebar" title="Sepetim">
                    <i class="bi bi-bag"></i>
                    <span class="badge-sayi" id="sepetBadge">0</span>
                </button>

                <!-- Mobil Hamburger -->
                <button class="navbar-toggler hamburger-btn d-md-none" type="button" id="hamburgerBtn" data-bs-toggle="offcanvas" data-bs-target="#anaNav" aria-controls="anaNav" title="Menü">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Arama Modal -->
<div class="modal fade" id="aramaModal" tabindex="-1" aria-labelledby="aramaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered arama-kutu-wrap">
        <div class="modal-content arama-kutu">
            <div class="arama-input-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="aramaInput" placeholder="Ürün, kategori veya marka ara..." autocomplete="off">
                <button class="header-ikon-btn" data-bs-dismiss="modal" style="width:32px;height:32px;font-size:1rem;" aria-label="Kapat">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="arama-sonuclar" id="aramaSonuclar" style="display:none;"></div>
        </div>
    </div>
</div>

<!-- Sepet Sidebar -->
<div class="offcanvas offcanvas-end sepet-sidebar" tabindex="-1" id="sepetSidebar" aria-labelledby="sepetSidebarLabel">
    <div class="offcanvas-header sepet-header">
        <h3 class="offcanvas-title" id="sepetSidebarLabel"><i class="bi bi-bag"></i> Sepetim</h3>
        <button type="button" class="sepet-kapat" data-bs-dismiss="offcanvas" aria-label="Kapat"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <div class="sepet-icerik" id="sepetIcerik">
            <div class="sepet-bos">
                <i class="bi bi-bag-x"></i>
                <p>Sepetiniz boş</p>
            </div>
        </div>
        <div class="sepet-alt" id="sepetAlt" style="display:none;">
            <div class="sepet-toplam">
                <span>Toplam</span>
                <span class="tutar" id="sepetToplam">0,00 TL</span>
            </div>
            <button class="sepet-satin-al-btn">
                <i class="bi bi-lock"></i> Güvenli Ödemeye Geç
            </button>
        </div>
    </div>
</div>

<!-- Bildirim Toast -->
<div class="bildirim-toast" id="bildirimToast">
    <i class="bi bi-check-circle"></i>
    <span id="bildirimMesaj"></span>
</div>