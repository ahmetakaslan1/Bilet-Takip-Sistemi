<?php
// Hata ayıklama: Tüm hataları göster
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Proje Kök Dizini ve Ana URL'si
// Bu iki sabiti projenin temelini oluşturur ve tüm dosya yolları (require, include)
// ve linkler (URL) için kullanılır.
define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', 'https://biletsistemi.ahmetakaslan.com/');

// Veritabanı bağlantı ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'ahmetak1_etkinlik');
define('DB_PASS', 'etkinlik_sifre');
define('DB_NAME', 'ahmetak1_etkinlik_bilet');

// Oturum başlatma
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Bağlantı oluştur
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

// Bağlantı kontrolü
if ($conn->connect_error) {
    // Gerçek bir uygulamada hata detayları loglanır, kullanıcıya gösterilmez.
    error_log("Veritabanı bağlantı hatası: " . $conn->connect_error);
    die("Sistemde bir sorun oluştu. Lütfen daha sonra tekrar deneyin.");
}

// Zaman dilimi ayarı
date_default_timezone_set('Europe/Istanbul');
?> 