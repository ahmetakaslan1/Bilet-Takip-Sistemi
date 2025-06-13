<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// JSON yanıtı için header
header('Content-Type: application/json');

// Giriş yapmamışsa hata döndür
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Bu işlem için giriş yapmanız gerekiyor.'
    ]);
    exit;
}

// POST verisi boşsa hata döndür
if (!isset($_POST['latitude']) || !isset($_POST['longitude'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Konum bilgisi eksik.'
    ]);
    exit;
}

// Gelen konum verileri
$user_id = $_SESSION['user_id'];
$latitude = floatval($_POST['latitude']);
$longitude = floatval($_POST['longitude']);

// Konum doğruluğunu kontrol et (gerçekçi değerler mi?)
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz konum verileri.'
    ]);
    exit;
}

// İşlemi gerçekleştir
try {
    // Kullanıcının konum kaydı var mı kontrol et
    $stmt = $conn->prepare("SELECT id FROM user_locations WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    $location_exists = $stmt->num_rows > 0;
    $stmt->close();
    
    if ($location_exists) {
        // Konum kaydı varsa güncelle
        $stmt = $conn->prepare("UPDATE user_locations SET latitude = ?, longitude = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("ddi", $latitude, $longitude, $user_id);
    } else {
        // Konum kaydı yoksa oluştur
        $stmt = $conn->prepare("INSERT INTO user_locations (user_id, latitude, longitude, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("idd", $user_id, $latitude, $longitude);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Konum bilgisi güncellendi.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Veritabanı hatası: ' . $stmt->error
        ]);
    }
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Hata: ' . $e->getMessage()
    ]);
}
?> 