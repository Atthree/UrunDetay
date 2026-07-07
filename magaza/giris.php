<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

$hata = '';

if (girisYapmisMi()) {
    header('Location: /UrunDetay/magaza/profil.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $sifre = $_POST['sifre'] ?? '';

    if ($email === '' || $sifre === '') {
        $hata = 'Lütfen e-posta ve şifrenizi girin.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $kullanici = $stmt->fetch();

        if (!$kullanici || !password_verify($sifre, $kullanici['sifre'])) {
            $hata = 'E-posta veya şifre hatalı.';
        } else {
            girisYap($kullanici);
            header('Location: /UrunDetay/magaza/index.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5" style="max-width:420px;">
    <div class="auth-kart">
        <h2 class="section-baslik mb-4 text-center">Giriş Yap</h2>

        <?php if ($hata): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label">E-posta</label>
                <input type="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="sifre" class="form-control" required>
            </div>
            <div class="d-flex justify-content-end mb-3" style="font-size:0.85rem;">
                <a href="/UrunDetay/magaza/sifremi-unuttum.php">Şifremi unuttum</a>
            </div>
            <button type="submit" class="btn btn-dark w-100 rounded-pill">Giriş Yap</button>
        </form>

        <p class="text-center mt-3" style="font-size:0.9rem;">
            Hesabınız yok mu? <a href="/UrunDetay/magaza/kayit.php">Üye Ol</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>