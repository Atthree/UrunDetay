<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

$hata = '';
$basariMesaji = '';
$gelistirmeLinki = '';

if (girisYapmisMi()) {
    header('Location: /UrunDetay/magaza/profil.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hata = 'Geçerli bir e-posta adresi girin.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM kullanicilar WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $kullanici = $stmt->fetch();

        // Güvenlik amacıyla e-posta kayıtlı olmasa bile aynı mesaj gösterilir
        // (böylece kayıtlı e-postalar dışarıdan tahmin edilemez)
        $basariMesaji = 'Eğer bu e-posta adresi sistemde kayıtlıysa, şifre sıfırlama bağlantısı gönderildi.';

        if ($kullanici) {
            $token = bin2hex(random_bytes(32));

            $ekle = $pdo->prepare("INSERT INTO sifre_sifirlama (email, token, son_kullanma) VALUES (:email, :token, NOW() + INTERVAL 1 HOUR)");
            $ekle->execute([':email' => $email, ':token' => $token]);

            $protokol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $sifirlamaLinki = $protokol . $_SERVER['HTTP_HOST'] . "/UrunDetay/magaza/sifre-sifirla.php?token=" . $token;

            // E-posta gönderimi (sunucunuzda mail() fonksiyonu / SMTP yapılandırılmışsa çalışır)
            $konu = 'Şifre Sıfırlama Talebi';
            $mesaj = "Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın (1 saat geçerlidir):\n\n" . $sifirlamaLinki;
            $basliklar = 'From: no-reply@magazam.com';
            @mail($email, $konu, $mesaj, $basliklar);

            // NOT: Çoğu yerel/paylaşımlı sunucuda mail() fonksiyonu SMTP kurulu
            // olmadan çalışmaz. Gerçek ortamda PHPMailer + SMTP (örn. Gmail,
            // SendGrid) kullanmanız önerilir. Test amaçlı, linki geliştirici
            // olarak ekranda da gösteriyoruz — CANLI ORTAMA GEÇERKEN
            // AŞAĞIDAKİ SATIRI MUTLAKA SİLİN.
            $gelistirmeLinki = $sifirlamaLinki;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5" style="max-width:460px;">
    <div class="auth-kart">
        <h2 class="section-baslik mb-4 text-center">Şifremi Unuttum</h2>

        <?php if ($hata): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>
        <?php if ($basariMesaji): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($basariMesaji); ?></div>
        <?php endif; ?>

        <?php if ($gelistirmeLinki): ?>
            <div class="alert alert-warning" style="font-size:0.85rem;word-break:break-all;">
                <strong>Geliştirme modu:</strong> E-posta sunucusu kurulu değilse bağlantıyı buradan kullanabilirsiniz:<br>
                <a href="<?php echo htmlspecialchars($gelistirmeLinki); ?>"><?php echo htmlspecialchars($gelistirmeLinki); ?></a>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">E-posta Adresiniz</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-dark w-100 rounded-pill">Sıfırlama Bağlantısı Gönder</button>
        </form>

        <p class="text-center mt-3" style="font-size:0.9rem;">
            <a href="/UrunDetay/magaza/giris.php">Giriş sayfasına dön</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>