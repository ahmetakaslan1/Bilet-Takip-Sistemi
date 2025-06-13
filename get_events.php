<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// JSON yanıtı için header
header('Content-Type: application/json');

// Giriş yapmamışsa hata döndür
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Bu işlem için giriş yapmanız gerekiyor.']);
    exit;
}

// Kullanıcı ID'si
$user_id = $_SESSION['user_id'];

// Kullanıcının konum bilgisini kontrol et
$user_lat = null;
$user_lng = null;
$stmt = $conn->prepare("SELECT latitude, longitude FROM user_locations WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_lat, $user_lng);
    
if (!$stmt->fetch()) {
    // Konum bilgisi yoksa hata döndür
    echo json_encode(['error' => 'Konum bilgisi bulunamadı. Lütfen önce konum izni verin.']);
    exit;
}
$stmt->close();
    
// Etkinlikleri getir
$events = [];
$sql = "SELECT 
            e.id, e.title, e.location, e.date, e.price, e.latitude, e.longitude,
            ( 6371 * acos( cos( radians(?) ) * cos( radians( e.latitude ) ) * cos( radians( e.longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( e.latitude ) ) ) ) AS distance 
        FROM events e 
        WHERE e.date > NOW() AND e.latitude IS NOT NULL AND e.longitude IS NOT NULL
        HAVING distance < 5 
        ORDER BY distance ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ddd", $user_lat, $user_lng, $user_lat);
$stmt->execute();

$stmt->bind_result($id, $title, $location, $date, $price, $latitude, $longitude, $distance);

while ($stmt->fetch()) {
    $events[] = [
        'id' => $id,
        'title' => $title,
        'location' => $location,
        'formatted_date' => formatDate($date),
        'formatted_price' => formatPrice($price),
        'distance' => $distance,
    ];
}
$stmt->close();

echo json_encode(['events' => $events]);
?> 