<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

cikisYap();
header('Location: /UrunDetay/magaza/index.php');
exit;