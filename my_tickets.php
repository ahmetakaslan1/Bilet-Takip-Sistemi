<?php
$page_title = "Biletlerim";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'assets/libs/qrcode/qrcode.php';

// Giriş yapılmış mı kontrol et
requireLogin();

// Admin ise admin paneline yönlendir
if (isAdmin()) {
    header("Location: admin/index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$tickets = [];

// Kullanıcının biletlerini getir
$stmt = $conn->prepare("SELECT t.id as ticket_id, t.token, t.purchase_date, t.status, 
                               e.title, e.location, e.date as event_date
                        FROM tickets t
                        JOIN events e ON t.event_id = e.id
                        WHERE t.user_id = ?
                        ORDER BY e.date DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($ticket_id, $token, $purchase_date, $status, $title, $location, $event_date);

while ($stmt->fetch()) {
    $tickets[] = [
        'ticket_id' => $ticket_id,
        'token' => $token,
        'purchase_date' => $purchase_date,
        'status' => $status,
        'title' => $title,
        'location' => $location,
        'event_date' => $event_date
    ];
}
$stmt->close();
?>

<?php include 'includes/header.php'; ?>

<h2 class="mb-4">Biletlerim</h2>

<?php if (count($tickets) > 0): ?>
    <!-- Aktif Biletler -->
    <h4 class="mb-3">Aktif Biletler</h4>
    
    <?php
    $active_tickets = array_filter($tickets, function($ticket) {
        return strtotime($ticket['event_date']) > time() && $ticket['status'] == 'active';
    });
    ?>
    
    <?php if (count($active_tickets) > 0): ?>
        <div class="row">
            <?php foreach ($active_tickets as $ticket): ?>
                <div class="col-12 mb-4">
                    <div class="card ticket-card" id="ticket-<?php echo $ticket['ticket_id']; ?>">
                        <div class="row g-0">
                            <div class="col-md-8">
                                <div class="ticket-info">
                                    <h5 class="event-title"><?php echo htmlspecialchars($ticket['title']); ?></h5>
                                    
                                    <div class="mb-2">
                                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                                        <span><?php echo formatDate($ticket['event_date']); ?></span>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        <span><?php echo htmlspecialchars($ticket['location']); ?></span>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <i class="fas fa-ticket-alt text-primary me-2"></i>
                                        <span>Bilet No: #<?php echo $ticket['ticket_id']; ?></span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <i class="fas fa-shopping-cart text-primary me-2"></i>
                                        <span>Satın Alma: <?php echo formatDate($ticket['purchase_date']); ?></span>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <a href="event.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-outline-primary me-2">
                                            <i class="fas fa-info-circle"></i> Etkinlik Detayları
                                        </a>
                                        <button class="btn btn-outline-secondary btn-print-ticket" data-ticket-id="<?php echo $ticket['ticket_id']; ?>">
                                            <i class="fas fa-print"></i> Yazdır
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="ticket-qr">
                                    <?php 
                                    echo QRCode::generateTicketQR(
                                        $ticket['ticket_id'],
                                        $user_id,
                                        $ticket['ticket_id'],
                                        $ticket['token']
                                    );
                                    ?>
                                    <div class="text-center mt-2">
                                        <small class="text-muted">Bu QR kodu etkinliğe girişte gösteriniz</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Aktif biletiniz bulunmamaktadır.</div>
    <?php endif; ?>
    
    <!-- Geçmiş Biletler -->
    <h4 class="mb-3 mt-5">Geçmiş Biletler</h4>
    
    <?php
    $past_tickets = array_filter($tickets, function($ticket) {
        return strtotime($ticket['event_date']) <= time() || $ticket['status'] == 'used';
    });
    ?>
    
    <?php if (count($past_tickets) > 0): ?>
        <div class="row">
            <?php foreach ($past_tickets as $ticket): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($ticket['title']); ?></h5>
                            
                            <div class="mb-2">
                                <i class="fas fa-calendar-alt text-muted me-2"></i>
                                <span><?php echo formatDate($ticket['event_date']); ?></span>
                            </div>
                            
                            <div class="mb-2">
                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                <span><?php echo htmlspecialchars($ticket['location']); ?></span>
                            </div>
                            
                            <div class="mb-2">
                                <i class="fas fa-ticket-alt text-muted me-2"></i>
                                <span>Bilet No: #<?php echo $ticket['ticket_id']; ?></span>
                            </div>
                            
                            <?php if ($ticket['status'] == 'used'): ?>
                                <span class="badge bg-secondary">Kullanılmış</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Süresi Geçmiş</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Geçmiş biletiniz bulunmamaktadır.</div>
    <?php endif; ?>
    
<?php else: ?>
    <div class="alert alert-info">
        <p>Henüz biletiniz bulunmamaktadır.</p>
        <a href="index.php" class="btn btn-primary mt-2">Etkinliklere Göz At</a>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?> 