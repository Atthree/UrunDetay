<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$hata = '';
$basarili = false;
$gecerliKayit = null;

if ($token !== '') {
    $stmt = $pdo->prepare("SELECT * FROM sifre_sifirlama WHERE token = :token AND kullanildi = 0 AND son_kullanma >= NOW()");
    $stmt->execute([':token' => $token]);
    $gecerliKayit = $stmt->fetch();
}

if ($token === '' || !$gecerliKayit) {
    $hata = 'Bağlantının süresi dolmuş veya geçersiz. Lütfen yeni bir sıfırlama talebinde bulunun.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $yeniSifre = $_POST['yeni_sifre'] ?? '';
    $yeniSifreTekrar = $_POST['yeni_sifre_tekrar'] ?? '';

    if (strlen($yeniSifre) < 6) {
        $hata = 'Şifre en az 6 karakter olmalı.';
    } elseif ($yeniSifre !== $yeniSifreTekrar) {
        $hata = 'Şifreler eşleşmiyor.';
    } else {
        $hashli = password_hash($yeniSifre, PASSWORD_DEFAULT);

        $guncelle = $pdo->prepare("UPDATE kullanicilar SET sifre = :sifre WHERE email = :email");
        $guncelle->execute([':sifre' => $hashli, ':email' => $gecerliKayit['email']]);

        $kullanildiYap = $pdo->prepare("UPDATE sifre_sifirlama SET kullanildi = 1 WHERE id = :id");
        $kullanildiYap->execute([':id' => $gecerliKayit['id']]);

        $basarili = true;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5" style="max-width:420px;">
    <div class="auth-kart">
        <h2 class="section-baslik mb-4 text-center">Şifre Sıfırla</h2>

        <?php if ($hata): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($hata); ?></div>
            <p class="text-center"><a href="/UrunDetay/magaza/sifremi-unuttum.php">Yeni bağlantı talep et</a></p>
        <?php elseif ($basarili): ?>
            <div class="alert alert-success">Şifreniz başarıyla güncellendi.</div>
            <p class="text-center">
                <a href="/UrunDetay/magaza/giris.php" class="btn btn-dark rounded-pill px-4">Giriş Yap</a>
            </p>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="mb-3">
                    <label class="form-label">Yeni Şifre</label>
                    <input type="password" name="yeni_sifre" class="form-control" required minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label">Yeni Şifre Tekrar</label>
                    <input type="password" name="yeni_sifre_tekrar" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn btn-dark w-100 rounded-pill">Şifreyi Güncelle</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>