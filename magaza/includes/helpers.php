<?php
/**
 * Resim yolunu oluşturur.
 * Harici URL (https://...) ise aynen döner.
 * Yerel dosya ise /UrunDetay/ prefix'i ekler.
 */
function resim_url($yol) {
    if (empty($yol)) {
        return '/UrunDetay/magaza/assets/img/placeholder.svg';
    }
    // Harici URL kontrolü
    if (strpos($yol, 'http://') === 0 || strpos($yol, 'https://') === 0) {
        return $yol;
    }
    // Yerel dosya
    return '/UrunDetay/' . ltrim($yol, '/');
}
