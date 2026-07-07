<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

girisGerekli();

$kullaniciId = $_SESSION['kullanici_id'];
$hata = '';
$basariMesaji = '';

$stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE id = :id");
$stmt->execute([':id' => $kullaniciId]);
$kullanici = $stmt->fetch();

if (!$kullanici) {
    cikisYap();
    header('Location: /UrunDetay/magaza/giris.php');
    exit;
}

// Bilgi güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bilgi_guncelle'])) {
    $adSoyad = trim($_POST['ad_soyad'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');

    if ($adSoyad === '') {
        $hata = 'Ad soyad boş bırakılamaz.';
    } else {
        $guncelle = $pdo->prepare("UPDATE kullanicilar SET ad_soyad = :ad_soyad, telefon = :telefon WHERE id = :id");
        $guncelle->execute([':ad_soyad' => $adSoyad, ':telefon' => $telefon, ':id' => $kullaniciId]);

        $_SESSION['kullanici_ad'] = $adSoyad;
        $kullanici['ad_soyad'] = $adSoyad;
        $kullanici['telefon'] = $telefon;
        $basariMesaji = 'Bilgileriniz güncellendi.';
    }
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sifre_degistir'])) {
    $mevcutSifre = $_POST['mevcut_sifre'] ?? '';
    $yeniSifre = $_POST['yeni_sifre'] ?? '';
    $yeniSifreTekrar = $_POST['yeni_sifre_tekrar'] ?? '';

    if (!password_verify($mevcutSifre, $kullanici['sifre'])) {
        $hata = 'Mevcut şifreniz hatalı.';
    } elseif (strlen($yeniSifre) < 6) {
        $hata = 'Yeni şifre en az 6 karakter olmalı.';
    } elseif ($yeniSifre !== $yeniSifreTekrar) {
        $hata = 'Yeni şifreler eşleşmiyor.';
    } else {
        $hashli = password_hash($yeniSifre, PASSWORD_DEFAULT);
        $guncelle = $pdo->prepare("UPDATE kullanicilar SET sifre = :sifre WHERE id = :id");
        $guncelle->execute([':sifre' => $hashli, ':id' => $kullaniciId]);
        $basariMesaji = 'Şifreniz başarıyla değiştirildi.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5" style="max-width:600px;">
    <h2 class="section-baslik mb-4">Hesabım</h2>

    <?php if ($hata): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($hata); ?></div>
    <?php endif; ?>
    <?php if ($basariMesaji): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($basariMesaji); ?></div>
    <?php endif; ?>

    <div class="auth-kart mb-4">
        <h5 class="mb-3">Bilgilerim</h5>
        <form method="post">
            <input type="hidden" name="bilgi_guncelle" value="1">
            <div class="mb-3">
                <label class="form-label">Ad Soyad</label>
                <input type="text" name="ad_soyad" class="form-control"
                       value="<?php echo htmlspecialchars($kullanici['ad_soyad']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">E-posta</label>
                <input type="email" class="form-control"
                       value="<?php echo htmlspecialchars($kullanici['email']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Telefon</label>
                <input type="text" name="telefon" class="form-control"
                       value="<?php echo htmlspecialchars($kullanici['telefon'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-dark rounded-pill px-4">Bilgileri Güncelle</button>
        </form>
    </div>

    <div class="auth-kart mb-4">
        <h5 class="mb-3">Şifre Değiştir</h5>
        <form method="post">
            <input type="hidden" name="sifre_degistir" value="1">
            <div class="mb-3">
                <label class="form-label">Mevcut Şifre</label>
                <input type="password" name="mevcut_sifre" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Yeni Şifre</label>
                <input type="password" name="yeni_sifre" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
                <label class="form-label">Yeni Şifre Tekrar</label>
                <input type="password" name="yeni_sifre_tekrar" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-dark rounded-pill px-4">Şifreyi Değiştir</button>
        </form>
    </div>

    <a href="/UrunDetay/magaza/cikis.php" class="btn btn-outline-danger rounded-pill px-4">
        <i class="bi bi-box-arrow-right"></i> Çıkış Yap
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>