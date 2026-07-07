<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/guvenlik.php';

// Ürünleri en yeni en üstte olacak şekilde çek
$stmt = $pdo->query("SELECT * FROM urunler ORDER BY id DESC");
$urunler = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<?php if (isset($_GET['silindi'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Ürün silindi.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['ice_aktarildi'])): ?>
    <?php if (!empty($_GET['hata'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            İçe aktarma sırasında hata oluştu: <?php echo htmlspecialchars($_GET['hata']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php else: ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            İçe aktarma tamamlandı: <?php echo (int)$_GET['eklenen']; ?> yeni ürün eklendi,
            <?php echo (int)$_GET['atlanan']; ?> ürün zaten var olduğu için atlandı.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="text-muted mb-0">Ürünler</h5>
    <a href="product.php" class="btn btn-outline-success btn-sm">
        <i class="bi bi-plus"></i> Yeni Ürün Ekle
    </a>
    <a href="urun_ice_aktar.php" class="btn btn-outline-info btn-sm"
        onclick="return confirm('DummyJSON API\'sinden ürünler çekilecek. Devam edilsin mi?');">
        <i class="bi bi-cloud-download"></i> API'den İçe Aktar
    </a>
</div>

<div class="table-responsive bg-white border rounded p-3">
    <table class="table align-middle table-hover mb-0">
        <thead>
            <tr>
                <th style="width:70px;">Resim</th>
                <th>Başlık</th>
                <th>Açıklama</th>
                <th>Ürün Kodu</th>
                <th>Miktar</th>
                <th>Fiyat</th>
                <th>Durum</th>
                <th class="text-end">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($urunler) === 0): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Henüz ürün eklenmemiş.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($urunler as $urun): ?>
                    <tr>
                        <td>
                            <?php if (!empty($urun['ana_resim'])): ?>
                                <img src="<?php echo htmlspecialchars($urun['ana_resim']); ?>" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="width:50px;height:50px;border-radius:4px;">
                                    <i class="bi bi-image text-secondary"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($urun['baslik_tr']); ?></td>
                        <td>
                            <?php $aciklama = trim(guvenli_html($urun['aciklama_tr'] ?? '')); ?>
                            <?php if ($aciklama === ''): ?>
                                <span class="text-muted">—</span>
                            <?php else: ?>
                                <div class="aciklama-onizleme"><?php echo $aciklama; ?></div>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($urun['urun_kodu']); ?></small></td>
                        <td>
                            <?php echo (int)$urun['miktar']; ?>
                            <?php if ((int)$urun['miktar'] === 0): ?>
                                <span class="badge bg-danger">Stokta Yok</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format((float)$urun['fiyat_usd'], 2, ',', '.'); ?> USD</td>
                        <td>
                            <?php if ((int)$urun['durum'] === 1): ?>
                                <span class="badge bg-success">Açık</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Kapalı</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="product.php?id=<?php echo $urun['id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil"></i> Düzenle
                            </a>
                            <a href="urun_sil.php?id=<?php echo $urun['id']; ?>"
                                class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('Bu ürünü silmek istediğine emin misin?');">
                                <i class="bi bi-trash"></i> Sil
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>