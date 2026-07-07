<?php

function guvenli_html($ham) {
    $izinVerilenEtiketler = '<p><br><b><strong><i><em><u><ul><ol><li>';

    // Önce encode edilmiş (&lt;script&gt; gibi) gizlenmiş etiketleri açığa çıkar
    $temiz = html_entity_decode($ham ?? '', ENT_QUOTES, 'UTF-8');

    // Sadece izin verilen etiketler kalsın, geri kalanı (script, img, iframe vb.) sil
    $temiz = strip_tags($temiz, $izinVerilenEtiketler);

    // Kalan etiketlerin TÜM attribute'larını temizle (onclick, onerror, style vb.)
    // <b onclick="..."> -> <b>          </b> -> </b>
    $temiz = preg_replace('/<(\/?\w+)[^>]*>/', '<$1>', $temiz);

    return $temiz;
}