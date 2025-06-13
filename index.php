<?php
// Hata ayıklama: Tüm hataları göster
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = "Ana Sayfa";
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Yaklaşan etkinlikleri getir
$upcoming_events = [];
// Sorguyu daha güvenli hale getirelim. NOW() yerine tarih parametresi geçelim.
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("SELECT id, title, description, location, date, capacity, price, latitude, longitude 
                       FROM events 
                       WHERE date > ?
                       ORDER BY created_at DESC 
                       LIMIT 9");
$stmt->bind_param("s", $now);
$stmt->execute();
$stmt->bind_result($id, $title, $description, $location, $date, $capacity, $price, $latitude, $longitude);

while ($stmt->fetch()) {
    $upcoming_events[] = [
        'id' => $id,
        'title' => $title,
        'description' => $description,
        'location' => $location,
        'date' => $date,
        'capacity' => $capacity,
        'price' => $price,
        'latitude' => $latitude,
        'longitude' => $longitude
    ];
}
$stmt->close();

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

<!-- Hero Bölümü -->
<div class="hero-section">
    <div class="container">
        <h1>Konumunuza En Yakın Etkinlikleri Keşfedin!</h1>
        <p>Çevrenizdeki konserler, tiyatrolar, festivaller ve daha fazlası. Biletinizi hemen alın, eğlenceyi kaçırmayın!</p>
        
        <?php if (!isLoggedIn()): ?>
            <div class="d-flex justify-content-center">
                <a href="register.php" class="btn btn-light btn-lg me-2">Kayıt Ol</a>
                <a href="login.php" class="btn btn-outline-light btn-lg">Giriş Yap</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="container mt-5">
    <!-- Konum İzni ve Filtreleme Bölümü (Sadece normal kullanıcılar için) -->
    <?php if (isLoggedIn() && !isAdmin()): ?>
        <div class="location-permission p-3 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4><i class="fas fa-map-marker-alt text-primary me-2"></i> Konum Bazlı Etkinlik Önerileri</h4>
                    <p class="mb-0">
                        <?php if ($has_location): ?>
                            Konum bilginiz kaydedilmiş durumda. Size en yakın etkinlikleri göstermek için kullanıyoruz.
                        <?php else: ?>
                            Konumunuza göre size özel etkinlik önerileri için konum izni vermelisiniz.
                        <?php endif; ?>
                    </p>
                    <?php if ($has_location && $user_lat && $user_lng): ?>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle"></i> Kayıtlı Konumunuz: Enlem <?php echo round($user_lat, 4); ?>, Boylam <?php echo round($user_lng, 4); ?>
                        </small>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <?php if (!$has_location): ?>
                        <button id="request-location" class="btn btn-primary">
                            <i class="fas fa-location-arrow me-1"></i> Konum İzni Ver
                        </button>
                    <?php else: ?>
                        <button id="update-location" class="btn btn-outline-primary">
                            <i class="fas fa-sync-alt me-1"></i> Konumu Güncelle
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div id="location-status" class="mt-2"></div>
        </div>
        
        <!-- Etkinlik Filtreleme Butonları -->
        <div class="d-flex justify-content-center mb-4">
            <div class="btn-group" role="group" id="event-filters" data-has-location="<?php echo json_encode($has_location); ?>">
                <button id="all-events-btn" type="button" class="btn btn-primary active">Yaklaşan Etkinlikler</button>
                <button id="nearest-events-btn" type="button" class="btn btn-outline-primary">En Yakın Etkinlikler</button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Etkinlikler Başlık -->
    <h2 class="mb-4 text-center section-title">
        <span id="events-title">Yaklaşan Etkinlikler</span>
    </h2>
    
    <!-- Etkinlikler Listesi -->
    <div class="position-relative">
        <!-- Yükleniyor Spinner -->
        <div id="events-loader" class="position-absolute top-50 start-50 translate-middle" style="display: none; z-index: 10;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
        </div>
        
        <!-- Tüm Etkinlikler (PHP ile basılır) -->
        <div class="row" id="all-events-container">
            <?php if (count($upcoming_events) > 0): ?>
                <?php foreach ($upcoming_events as $event): ?>
                    <div class="col-md-4 mb-4 event-card-wrapper">
                        <div class="card event-card">
                            <img src="https://via.placeholder.com/350x180?text=<?php echo urlencode($event['title']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <div class="event-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <span><?php echo formatDate($event['date']); ?></span>
                                </div>
                                <div class="event-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>
                                <div class="event-price mt-2">
                                    <?php echo formatPrice($event['price']); ?>
                                </div>
                                
                                <?php if ($has_location && $user_lat && $user_lng && $event['latitude'] && $event['longitude']): ?>
                                    <div class="event-distance mt-1 mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-route"></i>
                                            <?php 
                                            $distance = calculateDistance($user_lat, $user_lng, $event['latitude'], $event['longitude']);
                                            echo round($distance, 1) . ' km uzaklıkta';
                                            ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary mt-2">Detaylar</a>
                                
                                <?php if (isLoggedIn()): ?>
                                    <?php if (hasTicket($_SESSION['user_id'], $event['id'])): ?>
                                        <span class="badge bg-success ms-2">Biletiniz Var</span>
                                    <?php elseif (!hasCapacity($event['id'])): ?>
                                        <span class="badge bg-danger ms-2">Tükendi</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Şu anda yaklaşan etkinlik bulunmamaktadır. Lütfen daha sonra tekrar kontrol edin.
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- En Yakın Etkinlikler (AJAX ile yüklenecek) -->
        <div class="row d-none" id="nearest-events-container"></div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 