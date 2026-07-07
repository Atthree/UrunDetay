<?php
// Oturum (session) yönetimi yardımcı fonksiyonları

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kullanıcı giriş yapmış mı?
 */
function girisYapmisMi(): bool
{
    return isset($_SESSION['kullanici_id']);
}

/**
 * Kullanıcıyı oturuma alır (login veya kayıt sonrası çağrılır)
 * @param array $kullanici kullanicilar tablosundan gelen satır (id, ad_soyad, email içermeli)
 */
function girisYap(array $kullanici): void
{
    session_regenerate_id(true);
    $_SESSION['kullanici_id'] = $kullanici['id'];
    $_SESSION['kullanici_ad'] = $kullanici['ad_soyad'];
    $_SESSION['kullanici_email'] = $kullanici['email'];
}

/**
 * Oturumu tamamen kapatır
 */
function cikisYap(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Giriş yapılmamışsa giriş sayfasına yönlendirir ve sayfayı durdurur.
 * Korumalı sayfaların (örn. profil.php) en başında çağrılmalı.
 */
function girisGerekli(): void
{
    if (!girisYapmisMi()) {
        header('Location: /UrunDetay/magaza/giris.php');
        exit;
    }
}