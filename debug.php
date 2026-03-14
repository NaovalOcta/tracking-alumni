<?php
// Tampilkan semua error PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 PHP Environment Debug</h1>";

// 1. Cek Versi PHP
echo "<b>PHP Version:</b> " . PHP_VERSION . "<br>";

// 2. Cek Folder Penting
$folders = [
    'vendor' => 'vendor/autoload.php',
    'storage' => 'storage/logs',
    'bootstrap' => 'bootstrap/cache',
    'env' => '.env'
];

foreach ($folders as $name => $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        echo "✅ Folder/File <b>$name</b> terdeteksi.<br>";
    } else {
        echo "❌ Folder/File <b>$name</b> TIDAK DITEMUKAN di: " . __DIR__ . '/' . $path . "<br>";
    }
}

// 3. Cek Permission Storage
if (is_writable(__DIR__ . '/storage')) {
    echo "✅ Folder <b>storage</b> bisa ditulis (Writable).<br>";
} else {
    echo "❌ Folder <b>storage</b> TIDAK BISA DITULIS!<br>";
}

echo "<h2>⚙️ PHP Extensions</h2>";
$required = ['bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'mbstring', 'openssl', 'pdo_mysql', 'tokenizer', 'xml'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext<br>";
    } else {
        echo "❌ $ext (Dibutuhkan Laravel)<br>";
    }
}
