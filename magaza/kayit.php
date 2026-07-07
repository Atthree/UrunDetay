<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

$hata = '';

if (girisYapmisMi()) {
    header('Location: /UrunDetay/magaza/profil.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adSoyad = trim($_POST['ad_soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    $sifreTekrar = $_POST['sifre_tekrar'] ?? '';

    if ($adSoyad === '' || $email === '' || $sifre === '') {
        $hata = 'Lütfen tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hata = 'Geçerli bir e-posta adresi girin.';
    } elseif (strlen($sifre) < 6) {
        $hata = 'Şifre en az 6 karakter olmalı.';
    } elseif ($sifre !== $sifreTekrar) {
        $hata = 'Şifreler eşleşmiyor.';
    } else {
        $kontrol = $pdo->prepare("SELECT id FROM kullanicilar WHERE email = :email");
        $kontrol->execute([':email' => $email]);

        if ($kontrol->fetch()) {
            $hata = 'Bu e-posta adresi zaten kayıtlı.';
        } else {
            $hashli = password_hash($sifre, PASSWORD_DEFAULT);
            $ekle = $pdo->prepare("INSERT INTO kullanicilar (ad_soyad, email, sifre) VALUES (:ad_soyad, :email, :sifre)");
            $ekle->execute([':ad_soyad' => $adSoyad, ':email' => $email, ':sifre' => $hashli]);

            $yeniKullanici = [
                'id' => $pdo->lastInsertId(),
                'ad_soyad' => $adSoyad,
                'email' => $email,
            ];
            girisYap($yeniKullanici);

            header('Location: /UrunDetay/magaza/index.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5" style="max-width:480px;">
    <div class="auth-kart">
        <h2 class="section-baslik mb-4 text-center">Üye Ol</h2>

        <?php if ($hata): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label">Ad Soyad</label>
                <input type="text" name="ad_soyad" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['ad_soyad'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">E-posta</label>
                <input type="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="sifre" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
                <label class="form-label">Şifre Tekrar</label>
                <input type="password" name="sifre_tekrar" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-dark w-100 rounded-pill">Üye Ol</button>
        </form>

        <p class="text-center mt-3" style="font-size:0.9rem;">
            Zaten üye misiniz? <a href="/UrunDetay/magaza/giris.php">Giriş Yap</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>