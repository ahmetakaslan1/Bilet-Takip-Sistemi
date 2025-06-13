<?php
// Hata ayıklama: Tüm hataları göster
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Sistem Durum Kontrolü</h1>";

// PHP Sürümü
echo "<h2>PHP Bilgileri</h2>";
echo "PHP Sürümü: " . phpversion() . "<br>";
echo "Uzantılar: <pre>" . print_r(get_loaded_extensions(), true) . "</pre>";

// Veritabanı Bağlantı Testi
echo "<h2>Veritabanı Bağlantı Testi</h2>";
require_once 'includes/config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Bağlantı hatası: " . $conn->connect_error);
    }
    
    echo "<div style='color:green;'>Veritabanına başarıyla bağlandı!</div>";
    $conn->close();
} catch (Exception $e) {
    echo "<div style='color:red;'>Veritabanı Hatası: " . $e->getMessage() . "</div>";
}

// Dosya İzinleri
echo "<h2>Dosya İzinleri</h2>";
$folders_to_check = [
    '.', 'includes', 'assets', 'admin'
];

foreach ($folders_to_check as $folder) {
    if (file_exists($folder)) {
        echo "$folder: " . (is_writable($folder) ? "<span style='color:green;'>Yazılabilir</span>" : "<span style='color:red;'>Yazılamaz</span>") . "<br>";
    } else {
        echo "$folder: <span style='color:red;'>Bulunamadı</span><br>";
    }
}

// Sistem Yolu
echo "<h2>Sistem Yolları</h2>";
echo "Çalışma Dizini: " . getcwd() . "<br>";
echo "Tam Dosya Yolu: " . __FILE__ . "<br>";
echo "Include Yolu: <pre>" . print_r(get_include_path(), true) . "</pre>";

// Hata Günlüğü
echo "<h2>Hata Günlüğü</h2>";
$error_log = 'error_log.txt';
if (file_exists($error_log) && is_readable($error_log)) {
    $log_content = file_get_contents($error_log);
    if (empty($log_content)) {
        echo "Hata günlüğü boş.";
    } else {
        echo "<pre>" . htmlspecialchars($log_content) . "</pre>";
    }
} else {
    echo "Hata günlüğü bulunamadı veya okunamıyor.";
}
?> 