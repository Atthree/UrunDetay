<?php
// Simulate the header rendering and count mega-menu-link elements
ob_start();
$_SERVER['REQUEST_URI'] = '/UrunDetay/magaza/index.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$kategoriMenusu = $pdo->query("
    SELECT kategori, COUNT(*) AS urun_sayisi
    FROM urunler
    WHERE kategori IS NOT NULL AND kategori != '' AND durum = 1
    GROUP BY kategori
    ORDER BY urun_sayisi DESC
")->fetchAll();

echo "PHP \$kategoriMenusu count: " . count($kategoriMenusu) . PHP_EOL;
echo "Foreach loop would produce " . count($kategoriMenusu) . " mega-menu-link elements + 1 'Tum Urunler' = " . (count($kategoriMenusu) + 1) . " total" . PHP_EOL;

// Now render the actual mega-menu-grid HTML
$html = '<div class="mega-menu-grid">';
foreach ($kategoriMenusu as $km) {
    $html .= '<a href="#" class="mega-menu-link">' . htmlspecialchars(ucfirst($km['kategori'])) . ' (' . $km['urun_sayisi'] . ')</a>';
}
$html .= '<a href="#" class="mega-menu-link">Tum Urunler</a>';
$html .= '</div>';

$count = substr_count($html, 'mega-menu-link');
echo "Actual mega-menu-link count in rendered HTML: $count" . PHP_EOL;
