<?php
require_once 'config.php';

/**
 * Kullanıcının giriş yapmış olup olmadığını kontrol eder
 * @return bool Kullanıcının giriş yapmış olup olmadığı
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Kullanıcının admin olup olmadığını kontrol eder
 * @return bool Kullanıcının admin olup olmadığı
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

/**
 * Admin değilse admin sayfalarına erişimi engeller
 */
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit;
    }
}

/**
 * Giriş yapmamışsa kullanıcıyı giriş sayfasına yönlendirir
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Güvenli input temizleme
 * @param string $data Temizlenecek veri
 * @return string Temizlenmiş veri
 */
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

/**
 * İki konum arasındaki mesafeyi hesaplar (km cinsinden)
 * @param float $lat1 1. konum enlem
 * @param float $lon1 1. konum boylam
 * @param float $lat2 2. konum enlem
 * @param float $lon2 2. konum boylam
 * @return float Mesafe (km)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    return $miles * 1.609344; // Km'ye çevirme
}

/**
 * Rastgele bir token oluşturur
 * @return string Oluşturulan token
 */
function generateToken() {
    return md5(uniqid(rand(), true));
}

/**
 * Tarih formatını değiştirir
 * @param string $date MySQL datetime formatında tarih
 * @return string Gün.Ay.Yıl Saat:Dakika formatında tarih
 */
function formatDate($date) {
    $datetime = new DateTime($date);
    return $datetime->format('d.m.Y H:i');
}

/**
 * Para formatını düzenler
 * @param float $price Fiyat
 * @return string Formatlanmış fiyat (örn: 250,00 ₺)
 */
function formatPrice($price) {
    return number_format($price, 2, ',', '.') . ' ₺';
}

/**
 * Hata mesajı gösterir
 * @param string $message Hata mesajı
 * @return string HTML formatında hata mesajı
 */
function showError($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}

/**
 * Başarı mesajı gösterir
 * @param string $message Başarı mesajı
 * @return string HTML formatında başarı mesajı
 */
function showSuccess($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}

/**
 * Bilgi mesajı gösterir
 * @param string $message Bilgi mesajı
 * @return string HTML formatında bilgi mesajı
 */
function showInfo($message) {
    return '<div class="alert alert-info">' . $message . '</div>';
}

/**
 * Kullanıcının biletini kontrol eder
 * @param int $user_id Kullanıcı ID
 * @param int $event_id Etkinlik ID
 * @return bool Kullanıcının bileti var mı
 */
function hasTicket($user_id, $event_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM tickets WHERE user_id = ? AND event_id = ? LIMIT 1");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

/**
 * Etkinliğin kapasitesini kontrol eder
 * @param int $event_id Etkinlik ID
 * @return bool Etkinlikte yer var mı
 */
function hasCapacity($event_id) {
    global $conn;
    
    // Etkinlik kapasitesini al
    $stmt = $conn->prepare("SELECT capacity FROM events WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->bind_result($capacity);
    $stmt->fetch();
    $stmt->close();
    
    // Satılan bilet sayısını al
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->bind_result($sold);
    $stmt->fetch();
    $stmt->close();
    
    return $sold < $capacity;
}
?> 