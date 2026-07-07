<?php require_once __DIR__ . '/../../config/db.php'; ?>
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
    <div class="container d-flex align-items-center justify-content-between py-3">
        <a href="/UrunDetay/magaza/index.php" class="brand">Mağazam</a>

        <nav class="d-none d-md-flex gap-4">
            <a href="/UrunDetay/magaza/index.php">Ana Sayfa</a>
            <a href="#urunler">Ürünler</a>
            <a href="#">Hakkımızda</a>
            <a href="#">İletişim</a>
        </nav>

        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-search fs-5"></i>
            <i class="bi bi-cart3 fs-5"></i>
        </div>
    </div>
</header>