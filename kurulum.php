<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/storage.php';

$dataDir = dirname(DATA_FILE);

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$appsOk = is_file(DATA_FILE) || (file_put_contents(DATA_FILE, '[]') !== false);
$presOk = is_file(PRESENCE_FILE) || (file_put_contents(PRESENCE_FILE, '[]') !== false);

if ($appsOk && $presOk) {
    echo "<h1>TEBRIKLER!</h1>";
    echo "<p>JSON dosyalari basariyla olusturuldu. Sistem hazir.</p>";
    echo "<a href='index.php'>Ana Sayfaya Git</a>";
} else {
    echo "<h1>HATA OLUSTU:</h1>";
    echo "<p>Veri dosyalari olusturulamadi. Klasor izinlerini kontrol edin.</p>";
}
