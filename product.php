<?php
require_once __DIR__ . '/config/db.php';

$urun = null;
$ekResimler = [];
$indirimler = [];

if (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM urunler WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $urun = $stmt->fetch();

    if ($urun) {
        $stmt2 = $pdo->prepare("SELECT * FROM urun_resimler WHERE urun_id = :id ORDER BY sira");
        $stmt2->execute([':id' => $id]);
        $ekResimler = $stmt2->fetchAll();

        $stmt3 = $pdo->prepare("SELECT * FROM urun_indirimler WHERE urun_id = :id ORDER BY oncelik");
        $stmt3->execute([':id' => $id]);
        $indirimler = $stmt3->fetchAll();
    }
}

// Bir alanın mevcut değerini form input'una basan yardımcı fonksiyon
function v($urun, $alan, $varsayilan = '') {
    if ($urun && isset($urun[$alan])) {
        return htmlspecialchars($urun[$alan]);
    }
    return $varsayilan;
}

// Select kutularında doğru seçeneği "selected" yapan yardımcı fonksiyon
function sel($mevcutDeger, $secenekDegeri) {
    if ($mevcutDeger === null) return '';
    return ((string)$mevcutDeger === (string)$secenekDegeri) ? 'selected' : '';
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<?php if (isset($_GET['basarili'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Ürün başarıyla kaydedildi.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="text-muted mb-0">Ürün <?php echo $urun ? '- Düzenleniyor: ' . v($urun, 'baslik_tr') : ''; ?></h5>
    <div>
        <button type="submit" form="productForm" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark"></i> Kaydet
        </button>
        <a href="urunler.php" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-x"></i> İptal
        </a>
    </div>
</div>

<form id="productForm" action="urun_kaydet.php" method="POST" enctype="multipart/form-data">
    <?php if ($urun): ?>
        <input type="hidden" name="urun_id" value="<?php echo (int)$urun['id']; ?>">
        <input type="hidden" name="ana_resim_mevcut" value="<?php echo v($urun, 'ana_resim'); ?>">
    <?php endif; ?>

    <!-- Sekme başlıkları -->
    <ul class="nav nav-tabs" id="productTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="genel-tab" data-bs-toggle="tab" data-bs-target="#genel" type="button" role="tab">Genel</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="detaylar-tab" data-bs-toggle="tab" data-bs-target="#detaylar" type="button" role="tab">Detaylar</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="resimler-tab" data-bs-toggle="tab" data-bs-target="#resimler" type="button" role="tab">Resimler</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="indirim-tab" data-bs-toggle="tab" data-bs-target="#indirim" type="button" role="tab">İndirim</button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 p-4 bg-white" id="productTabsContent">

        <!-- ================= GENEL ================= -->
        <div class="tab-pane fade show active" id="genel" role="tabpanel">
            <ul class="nav nav-pills mb-3">
                <li class="nav-item">
                    <a class="nav-link active" href="#">🇹🇷 Türkçe</a>
                </li>
            </ul>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"><span class="text-danger">*</span> Türkçe Ürün Başlık</label>
                <div class="col-sm-6">
                    <input type="text" name="baslik_tr" class="form-control" value="<?php echo v($urun, 'baslik_tr'); ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Türkçe Ürün Ek Bilgi Başlığı</label>
                <div class="col-sm-6">
                    <input type="text" name="ek_bilgi_baslik_tr" class="form-control" value="<?php echo v($urun, 'ek_bilgi_baslik_tr'); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Türkçe Ürün Ek Bilgi Açıklaması</label>
                <div class="col-sm-6">
                    <input type="text" name="ek_bilgi_aciklama_tr" class="form-control" value="<?php echo v($urun, 'ek_bilgi_aciklama_tr'); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Türkçe Meta Title</label>
                <div class="col-sm-6">
                    <input type="text" name="meta_title_tr" class="form-control" value="<?php echo v($urun, 'meta_title_tr'); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Türkçe Meta Keywords</label>
                <div class="col-sm-6">
                    <input type="text" name="meta_keywords_tr" class="form-control" value="<?php echo v($urun, 'meta_keywords_tr'); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Türkçe Meta Description</label>
                <div class="col-sm-6">
                    <input type="text" name="meta_description_tr" class="form-control" value="<?php echo v($urun, 'meta_description_tr'); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">
                    Türkçe Seo Adresi
                    <div class="form-text mt-0">Seo adresi girilmesi zorunlu değildir, girilen seo adresi geçerli olur. Girilmez ise otomatik olarak Başlık kısmını referans alarak oluşturulur.</div>
                </label>
                <div class="col-sm-6">
                    <input type="text" name="seo_adresi_tr" class="form-control" value="<?php echo v($urun, 'seo_adresi_tr'); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Türkçe Ürün Açıklama</label>
                <div class="col-sm-9">
                    <textarea name="aciklama_tr" id="aciklama_tr" class="form-control" rows="8"><?php echo v($urun, 'aciklama_tr'); ?></textarea>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">
                    Türkçe Video Embed Kodu
                    <div class="form-text mt-0">Vimeo - Google Video - Youtube tarzı video sitelerinin embed kodu.</div>
                </label>
                <div class="col-sm-6">
                    <textarea name="video_embed_tr" class="form-control" rows="4"><?php echo v($urun, 'video_embed_tr'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- ================= DETAYLAR ================= -->
        <div class="tab-pane fade" id="detaylar" role="tabpanel">

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label">
                    <span class="text-danger">*</span> Ürün Kodu
                    <div class="form-text mt-0">Ürünün kodu.</div>
                </label>
                <div class="col-sm-4">
                    <input type="text" name="urun_kodu" class="form-control" value="<?php echo $urun ? v($urun, 'urun_kodu') : bin2hex(random_bytes(16)); ?>" required>
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label">
                    <span class="text-danger">*</span> Miktar
                    <div class="form-text mt-0">Üründen kaç adet olacağını belirler. Bu miktar 0 olarak girilirse ürün sitede "stokta yok" ibareleriyle listerelenecektir. Eğer üründe seçenek varsa seçeneklerin stoğu ürün stoğundan büyük olamaz.</div>
                </label>
                <div class="col-sm-2">
                    <input type="number" name="miktar" class="form-control" value="<?php echo v($urun, 'miktar', '0'); ?>" min="0" step="1" required>
                </div>
                <div class="col-sm-2">
                    <select name="birim" class="form-select">
                        <option <?php echo sel($urun['birim'] ?? 'Adet', 'Adet'); ?>>Adet</option>
                        <option <?php echo sel($urun['birim'] ?? 'Adet', 'Kg'); ?>>Kg</option>
                        <option <?php echo sel($urun['birim'] ?? 'Adet', 'Litre'); ?>>Litre</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label">
                    <span class="text-danger">*</span> Sepet Ekstra İndirim %
                    <div class="form-text mt-0">Ürün sepet ekstra indirimde seçeneklere fiyat girilmiş ise indirim seçenek fiyatlarına da uygulanmaktadır.</div>
                </label>
                <div class="col-sm-2">
                    <select name="sepet_indirim" class="form-select">
                        <?php $mevcutSepetIndirim = $urun['sepet_indirim'] ?? 0; ?>
                        <?php for ($i = 0; $i <= 100; $i += 5): ?>
                            <option value="<?php echo $i; ?>" <?php echo sel($mevcutSepetIndirim, $i); ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label"><span class="text-danger">*</span> Vergi Oranı %</label>
                <div class="col-sm-2">
                    <select name="vergi_orani" class="form-select">
                        <?php $mevcutVergi = $urun['vergi_orani'] ?? 18; ?>
                        <option value="0" <?php echo sel($mevcutVergi, 0); ?>>0</option>
                        <option value="1" <?php echo sel($mevcutVergi, 1); ?>>1</option>
                        <option value="8" <?php echo sel($mevcutVergi, 8); ?>>8</option>
                        <option value="18" <?php echo sel($mevcutVergi, 18); ?>>18</option>
                        <option value="20" <?php echo sel($mevcutVergi, 20); ?>>20</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label"><span class="text-danger">*</span> Satış Fiyatı</label>
                <div class="col-sm-4">
                    <div class="input-group mb-2">
                        <input type="number" step="0.01" name="fiyat_tl" class="form-control" value="<?php echo v($urun, 'fiyat_tl'); ?>" min="0" required>
                        <span class="input-group-text">TL</span>
                    </div>
                    <div class="input-group mb-2">
                        <input type="number" step="0.01" name="fiyat_usd" class="form-control" value="<?php echo v($urun, 'fiyat_usd'); ?>" min="0">
                        <span class="input-group-text">$</span>
                    </div>
                    <div class="input-group">
                        <input type="number" step="0.01" name="fiyat_eur" class="form-control" value="<?php echo v($urun, 'fiyat_eur'); ?>" min="0">
                        <span class="input-group-text">€</span>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">2. Satış Fiyatı</label>
                <div class="col-sm-4">
                    <div class="input-group">
                        <input type="number" step="0.01" name="fiyat2_tl" class="form-control" value="<?php echo v($urun, 'fiyat2_tl'); ?>" min="0">
                        <span class="input-group-text">TL</span>
                    </div>
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label">
                    <span class="text-danger">*</span> Stoktan Düş
                    <div class="form-text mt-0">Ürün satıldıktan sonra ürün miktarı eksilir.</div>
                </label>
                <div class="col-sm-3">
                    <select name="stoktan_dus" class="form-select">
                        <?php $mevcutStoktanDus = $urun['stoktan_dus'] ?? 1; ?>
                        <option value="1" <?php echo sel($mevcutStoktanDus, 1); ?>>- Evet -</option>
                        <option value="0" <?php echo sel($mevcutStoktanDus, 0); ?>>- Hayır -</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label">
                    <span class="text-danger">*</span> Durum
                    <div class="form-text mt-0">Ürünleri aktif yada pasif edin.</div>
                </label>
                <div class="col-sm-3">
                    <select name="durum" class="form-select">
                        <?php $mevcutDurum = $urun['durum'] ?? 1; ?>
                        <option value="1" <?php echo sel($mevcutDurum, 1); ?>>- Açık -</option>
                        <option value="0" <?php echo sel($mevcutDurum, 0); ?>>- Kapalı -</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label">
                    <span class="text-danger">*</span> Özellik Bölümü
                    <div class="form-text mt-0">Ürünlerin özellik tabını göstermin yada göstermeyin.</div>
                </label>
                <div class="col-sm-3">
                    <select name="ozellik_bolumu" class="form-select">
                        <?php $mevcutOzellik = $urun['ozellik_bolumu'] ?? 1; ?>
                        <option value="1" <?php echo sel($mevcutOzellik, 1); ?>>- Göster -</option>
                        <option value="0" <?php echo sel($mevcutOzellik, 0); ?>>- Gizle -</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label"><span class="text-danger">*</span> Yeni Ürün Geçerlilik Süresi</label>
                <div class="col-sm-3">
                    <input type="date" name="gecerlilik_suresi" class="form-control" value="<?php echo $urun ? v($urun, 'gecerlilik_suresi') : date('Y-m-d', strtotime('+30 days')); ?>">
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label">Sıralama</label>
                <div class="col-sm-3">
                    <input type="number" name="siralama" class="form-control" value="<?php echo v($urun, 'siralama', '0'); ?>" min="0">
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label">
                    <span class="text-danger">*</span> Anasayfada Göster
                    <div class="form-text mt-0">Anasayfa sırasını ayarlamak için sayı girin! 0'dan büyük sayı girerseniz anasayfada gösterir ve o sırayı alır. 0 girerseniz anasayfada gözükmez.</div>
                </label>
                <div class="col-sm-3">
                    <input type="number" name="anasayfada_goster" class="form-control" value="<?php echo v($urun, 'anasayfada_goster', '0'); ?>" min="0">
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label"><span class="text-danger">*</span> Yeni Ürün</label>
                <div class="col-sm-3">
                    <select name="yeni_urun" class="form-select">
                        <?php $mevcutYeniUrun = $urun['yeni_urun'] ?? 1; ?>
                        <option value="1" <?php echo sel($mevcutYeniUrun, 1); ?>>- Evet -</option>
                        <option value="0" <?php echo sel($mevcutYeniUrun, 0); ?>>- Hayır -</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label"><span class="text-danger">*</span> Taksit</label>
                <div class="col-sm-3">
                    <select name="taksit" class="form-select">
                        <?php $mevcutTaksit = $urun['taksit'] ?? 1; ?>
                        <option value="1" <?php echo sel($mevcutTaksit, 1); ?>>- Evet -</option>
                        <option value="0" <?php echo sel($mevcutTaksit, 0); ?>>- Hayır -</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3 align-items-center">
                <label class="col-sm-3 col-form-label">
                    Garanti Süresi
                    <div class="form-text mt-0">Ürün için verilen ay cinsinden garanti süresi</div>
                </label>
                <div class="col-sm-3">
                    <input type="number" name="garanti_suresi" class="form-control" value="<?php echo v($urun, 'garanti_suresi'); ?>" min="0">
                </div>
            </div>
        </div>

        <!-- ================= RESİMLER ================= -->
        <div class="tab-pane fade" id="resimler" role="tabpanel">

            <div class="row mb-4">
                <div class="col-sm-4">
                    <h6>Ürün Ana Resmi</h6>
                    <p class="text-muted small mb-1">Ürüne ana resim eklemek için tıklayın.</p>
                    <p class="text-muted small mb-1">Ürün resim eklerken kare resim girmelisiniz, önerilen boyut 800px genişlik, 800px yükseklik.</p>
                    <p class="text-muted small">Ürün resim eklerken maksimum resim boyutu 1MB ve genişlik 768px, yükseklik 1024px olmalıdır.</p>
                </div>
                <div class="col-sm-3 text-center">
                    <label for="anaResimInput" class="d-block border rounded bg-light" style="width:120px;height:120px;cursor:pointer;" id="anaResimKutu">
                        <?php if ($urun && !empty($urun['ana_resim'])): ?>
                            <img src="<?php echo v($urun, 'ana_resim'); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">
                        <?php else: ?>
                            <i class="bi bi-camera text-secondary" style="font-size:3rem; line-height:120px;"></i>
                        <?php endif; ?>
                    </label>
                    <input type="file" id="anaResimInput" name="ana_resim" accept="image/*" class="d-none">
                    <button type="button" class="btn btn-outline-danger btn-sm mt-2" id="anaResimTemizle">Temizle</button>
                </div>
            </div>

            <hr>

            <h6 class="fw-bold">Resimler</h6>
            <div id="ekResimlerAlani" class="row g-3 mb-3">
                <?php foreach ($ekResimler as $resim): ?>
                    <div class="col-auto">
                        <img src="<?php echo htmlspecialchars($resim['resim_yolu']); ?>" class="ek-resim-onizleme">
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn-outline-success btn-sm" id="resimEkleBtn">
                <i class="bi bi-plus"></i> Resim Ekle
            </button>
            <input type="file" id="ekResimInput" name="ek_resimler[]" accept="image/*" multiple class="d-none">
        </div>

        <!-- ================= İNDİRİM ================= -->
        <div class="tab-pane fade" id="indirim" role="tabpanel">

            <div class="table-responsive">
                <table class="table align-middle" id="indirimTablosu">
                    <thead>
                        <tr>
                            <th>Müşteri Grubu</th>
                            <th>Öncelik</th>
                            <th>Yüzde İndirim Oranı veya İndirimli Fiyatı</th>
                            <th>Başlangıç Tarihi</th>
                            <th>Bitiş Tarihi</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($indirimler as $indirim): ?>
                        <tr>
                            <td>
                                <select name="indirim_musteri_grubu[]" class="form-select">
                                    <option value="<?php echo htmlspecialchars($indirim['musteri_grubu']); ?>" selected><?php echo htmlspecialchars($indirim['musteri_grubu']); ?></option>
                                </select>
                            </td>
                            <td><input type="number" name="indirim_oncelik[]" class="form-control" value="<?php echo (int)$indirim['oncelik']; ?>" min="0"></td>
                            <td>
                                <div class="input-group mb-1">
                                    <input type="number" step="0.01" name="indirim_tl[]" class="form-control" value="<?php echo $indirim['indirim_tl']; ?>" min="0">
                                    <span class="input-group-text">TL</span>
                                    <select name="indirim_tip_tl[]" class="form-select" style="max-width:110px;">
                                        <option <?php echo sel($indirim['indirim_tip_tl'], 'Fiyat'); ?>>Fiyat</option>
                                        <option <?php echo sel($indirim['indirim_tip_tl'], 'Yüzde'); ?>>Yüzde</option>
                                    </select>
                                </div>
                                <div class="input-group mb-1">
                                    <input type="number" step="0.01" name="indirim_usd[]" class="form-control" value="<?php echo $indirim['indirim_usd']; ?>" min="0">
                                    <span class="input-group-text">$</span>
                                    <select name="indirim_tip_usd[]" class="form-select" style="max-width:110px;">
                                        <option <?php echo sel($indirim['indirim_tip_usd'], 'Fiyat'); ?>>Fiyat</option>
                                        <option <?php echo sel($indirim['indirim_tip_usd'], 'Yüzde'); ?>>Yüzde</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="indirim_eur[]" class="form-control" value="<?php echo $indirim['indirim_eur']; ?>" min="0">
                                    <span class="input-group-text">€</span>
                                    <select name="indirim_tip_eur[]" class="form-select" style="max-width:110px;">
                                        <option <?php echo sel($indirim['indirim_tip_eur'], 'Fiyat'); ?>>Fiyat</option>
                                        <option <?php echo sel($indirim['indirim_tip_eur'], 'Yüzde'); ?>>Yüzde</option>
                                    </select>
                                </div>
                            </td>
                            <td><input type="date" name="indirim_baslangic[]" class="form-control" value="<?php echo $indirim['baslangic_tarihi']; ?>"></td>
                            <td><input type="date" name="indirim_bitis[]" class="form-control" value="<?php echo $indirim['bitis_tarihi']; ?>"></td>
                            <td><button type="button" class="btn btn-outline-danger btn-sm indirim-kaldir">Kaldır</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn btn-outline-success btn-sm" id="indirimEkleBtn">
                <i class="bi bi-plus"></i> İndirim Ekle
            </button>
        </div>

    </div>
</form>

<!-- İndirim satırı şablonu (JS bunu klonlayarak tabloya ekleyecek) -->
<template id="indirimSatirSablonu">
    <tr>
        <td>
            <select name="indirim_musteri_grubu[]" class="form-select">
                <option value="">Musteri</option>
            </select>
        </td>
        <td><input type="number" name="indirim_oncelik[]" class="form-control" min="0"></td>
        <td>
            <div class="input-group mb-1">
                <input type="number" step="0.01" name="indirim_tl[]" class="form-control" placeholder="0.00" min="0">
                <span class="input-group-text">TL</span>
                <select name="indirim_tip_tl[]" class="form-select" style="max-width:110px;">
                    <option>Fiyat</option>
                    <option>Yüzde</option>
                </select>
            </div>
            <div class="input-group mb-1">
                <input type="number" step="0.01" name="indirim_usd[]" class="form-control" placeholder="0.00" min="0">
                <span class="input-group-text">$</span>
                <select name="indirim_tip_usd[]" class="form-select" style="max-width:110px;">
                    <option>Fiyat</option>
                    <option>Yüzde</option>
                </select>
            </div>
            <div class="input-group">
                <input type="number" step="0.01" name="indirim_eur[]" class="form-control" placeholder="0.00" min="0">
                <span class="input-group-text">€</span>
                <select name="indirim_tip_eur[]" class="form-select" style="max-width:110px;">
                    <option>Fiyat</option>
                    <option>Yüzde</option>
                </select>
            </div>
        </td>
        <td><input type="date" name="indirim_baslangic[]" class="form-control"></td>
        <td><input type="date" name="indirim_bitis[]" class="form-control"></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm indirim-kaldir">Kaldır</button></td>
    </tr>
</template>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
