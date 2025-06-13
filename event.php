<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Etkinlik ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$event_id = intval($_GET['id']);
$event = null;

// Etkinlik detaylarını al
$stmt = $conn->prepare("SELECT id, title, description, location, date, capacity, price, latitude, longitude FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$stmt->bind_result($id, $title, $description, $location, $date, $capacity, $price, $latitude, $longitude);
$stmt->fetch();

$event = [
    'id' => $id, 'title' => $title, 'description' => $description, 'location' => $location, 
    'date' => $date, 'capacity' => $capacity, 'price' => $price, 
    'latitude' => $latitude, 'longitude' => $longitude
];
$stmt->close();

if (!$event || !$event['id']) {
    // Etkinlik bulunamadı
    $_SESSION['error'] = "Etkinlik bulunamadı.";
    header("Location: index.php");
    exit;
}

// Sayfa başlığı
$page_title = $event['title'];

// Satılan bilet sayısını kontrol et
$stmt = $conn->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$stmt->bind_result($sold_tickets);
$stmt->fetch();
$stmt->close();

$remaining_tickets = $event['capacity'] - $sold_tickets;
$event_sold_out = ($remaining_tickets <= 0);

// Kullanıcının bilet alması durumu
$ticket_purchased = false;
$user_has_ticket = false;

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    // Kullanıcının bileti var mı kontrol et
    $user_has_ticket = hasTicket($user_id, $event_id);
    
    // Bilet satın alma işlemi
    if (isset($_POST['buy_ticket']) && !$user_has_ticket && !$event_sold_out) {
        // Token oluştur
        $token = generateToken();
        
        // Bileti oluştur
        $stmt = $conn->prepare("INSERT INTO tickets (user_id, event_id, token) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $event_id, $token);
        
        if ($stmt->execute()) {
            $ticket_purchased = true;
            $user_has_ticket = true;
            $_SESSION['success'] = "Biletiniz başarıyla satın alındı!";
        } else {
            $_SESSION['error'] = "Bilet satın alınırken bir hata oluştu.";
        }
        $stmt->close();
    }
}

// Konum bilgisini kontrol et
$has_location = false;
$user_lat = null;
$user_lng = null;

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT latitude, longitude FROM user_locations WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_lat, $user_lng);
    
    if ($stmt->fetch()) {
        $has_location = true;
    }
    $stmt->close();
}
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <img src="https://via.placeholder.com/800x400?text=<?php echo urlencode($event['title']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>">
            <div class="card-body">
                <h1 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h1>
                
                <div class="d-flex mb-3">
                    <div class="me-4">
                        <i class="far fa-calendar-alt text-primary me-2"></i>
                        <strong>Tarih:</strong> <?php echo formatDate($event['date']); ?>
                    </div>
                    <div>
                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                        <strong>Konum:</strong> <?php echo htmlspecialchars($event['location']); ?>
                    </div>
                </div>
                
                <?php if ($has_location && $user_lat && $user_lng && $event['latitude'] && $event['longitude']): ?>
                    <div class="mb-3">
                        <i class="fas fa-route text-primary me-2"></i>
                        <strong>Uzaklık:</strong> 
                        <?php 
                        $distance = calculateDistance($user_lat, $user_lng, $event['latitude'], $event['longitude']);
                        echo round($distance, 1) . ' km';
                        ?>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <h5>Etkinlik Açıklaması</h5>
                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                
                <?php if ($event['latitude'] && $event['longitude']): ?>
                    <div class="mt-4">
                        <h5>Konum</h5>
                        <div class="map-container">
                            <?php
                            $map_url = "https://www.google.com/maps/embed/v1/place?key=GOOGLE_MAPS_KEY_HERE&q={$event['latitude']},{$event['longitude']}";
                            ?>
                            <a href="https://www.google.com/maps?q=<?php echo $event['latitude']; ?>,<?php echo $event['longitude']; ?>" target="_blank" class="btn btn-outline-primary mb-3">
                                <i class="fas fa-map-marked-alt me-2"></i> Google Maps'de Göster
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Bilet Bilgileri</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h4 class="text-primary"><?php echo formatPrice($event['price']); ?></h4>
                </div>
                
                <div class="mb-3">
                    <i class="fas fa-users text-muted me-2"></i>
                    <span>Kapasite: <?php echo $event['capacity']; ?> kişi</span>
                </div>
                
                <div class="mb-3">
                    <i class="fas fa-ticket-alt text-muted me-2"></i>
                    <span>Kalan Bilet: <?php echo $remaining_tickets; ?></span>
                </div>
                
                <?php if (!isLoggedIn()): ?>
                    <div class="alert alert-info">
                        Bilet almak için <a href="login.php">giriş yapmalısınız</a>.
                    </div>
                <?php elseif ($user_has_ticket): ?>
                    <div class="alert alert-success">
                        Bu etkinlik için biletiniz bulunmaktadır.
                        <a href="my_tickets.php" class="btn btn-sm btn-success mt-2">Biletlerimi Görüntüle</a>
                    </div>
                <?php elseif ($event_sold_out): ?>
                    <div class="alert alert-danger">
                        Bu etkinlik için tüm biletler tükenmiştir.
                    </div>
                <?php else: ?>
                    <form method="post" action="">
                        <button type="submit" name="buy_ticket" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-shopping-cart me-2"></i> Bilet Al
                        </button>
                    </form>
                <?php endif; ?>
                
                <!-- Paylaş Butonları -->
                <div class="mt-4">
                    <h5>Bu etkinliği paylaş</h5>
                    <div class="d-flex">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-primary me-2">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($event['title']); ?>" target="_blank" class="btn btn-outline-info me-2">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode($event['title'] . ' - ' . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-outline-success">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Benzer Etkinlikler -->
<?php
// Benzer etkinlikleri getir (aynı lokasyonda veya aynı gün)
$similar_events = [];
if ($event['latitude'] && $event['longitude']) {
    $stmt = $conn->prepare("SELECT id, title, description, location, date, capacity, price, latitude, longitude,
                                ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance 
                           FROM events 
                           WHERE id != ? AND date > NOW()
                           HAVING distance < 100 
                           ORDER BY distance 
                           LIMIT 3");
    $stmt->bind_param("dddi", $event['latitude'], $event['longitude'], $event['latitude'], $event['id']);
    $stmt->execute();
    $stmt->bind_result($s_id, $s_title, $s_description, $s_location, $s_date, $s_capacity, $s_price, $s_latitude, $s_longitude, $s_distance);

    while ($stmt->fetch()) {
        $similar_events[] = [
            'id' => $s_id,
            'title' => $s_title,
            'description' => $s_description,
            'location' => $s_location,
            'date' => $s_date,
            'capacity' => $s_capacity,
            'price' => $s_price,
            'latitude' => $s_latitude,
            'longitude' => $s_longitude,
            'distance' => $s_distance
        ];
    }
    $stmt->close();
}
?>

<div class="mt-5">
    <h3 class="mb-4">Benzer Etkinlikler</h3>
    <div class="row">
        <?php foreach ($similar_events as $similar): ?>
            <div class="col-md-4 mb-4">
                <div class="card event-card">
                    <img src="https://via.placeholder.com/350x180?text=<?php echo urlencode($similar['title']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($similar['title']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($similar['title']); ?></h5>
                        <div class="event-date">
                            <i class="far fa-calendar-alt"></i>
                            <span><?php echo formatDate($similar['date']); ?></span>
                        </div>
                        <div class="event-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($similar['location']); ?></span>
                        </div>
                        <div class="event-price mt-2">
                            <?php echo formatPrice($similar['price']); ?>
                        </div>
                        <a href="event.php?id=<?php echo $similar['id']; ?>" class="btn btn-sm btn-primary mt-2">Detaylar</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 