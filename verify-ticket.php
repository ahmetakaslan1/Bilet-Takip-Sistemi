<?php
$page_title = "Bilet Doğrulama";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'assets/libs/qrcode/qrcode.php';

$verification_status = null;
$ticket_data = null;
$user_data = null;
$event_data = null;

// Veri geldiyse
if (isset($_GET['data']) && !empty($_GET['data'])) {
    // QR kod verisini decode et
    $decoded_data = QRCode::decodeTicketQR($_GET['data']);
    
    if ($decoded_data) {
        $ticket_id = $decoded_data->ticket_id;
        $user_id = $decoded_data->user_id;
        $event_id = $decoded_data->event_id;
        $token = $decoded_data->token;
        
        // Bilet bilgilerini getir
        $stmt = $conn->prepare("SELECT t.id, t.purchase_date, t.status, e.title, e.date, u.name, u.email
                               FROM tickets t
                               JOIN events e ON t.event_id = e.id
                               JOIN users u ON t.user_id = u.id
                               WHERE t.id = ? AND t.user_id = ? AND t.event_id = ? AND t.token = ?");
        $stmt->bind_param("iiis", $ticket_id, $user_id, $event_id, $token);
        $stmt->execute();
        $stmt->bind_result($t_id, $purchase_date, $status, $event_title, $event_date, $user_name, $user_email);
        
        if ($stmt->fetch()) {
            $ticket_data = [
                'id' => $t_id,
                'purchase_date' => $purchase_date,
                'status' => $status,
                'event_title' => $event_title,
                'event_date' => $event_date,
                'user_name' => $user_name,
                'user_email' => $user_email
            ];
            
            // Etkinlik tarihi geçmiş mi kontrol et
            $event_date = strtotime($ticket_data['event_date']);
            $current_date = time();
            
            // Bilet durumuna göre doğrulama durumunu belirle
            if ($ticket_data['status'] == 'used') {
                $verification_status = 'used';
            } elseif ($event_date < $current_date) {
                $verification_status = 'expired';
            } else {
                $verification_status = 'valid';
                
                // Eğer admin tarafından doğrulanıyorsa bileti kullanıldı olarak işaretle
                if (isAdmin() && isset($_POST['mark_as_used'])) {
                    $update_stmt = $conn->prepare("UPDATE tickets SET status = 'used' WHERE id = ?");
                    $update_stmt->bind_param("i", $ticket_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    $verification_status = 'marked_used';
                }
            }
        } else {
            $verification_status = 'invalid';
        }
        $stmt->close();
    } else {
        $verification_status = 'invalid_data';
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="ticket-validation">
    <h2 class="mb-4 text-center">Bilet Doğrulama</h2>
    
    <?php if (!$verification_status): ?>
        <!-- Doğrulama formu -->
        <div class="card mb-4">
            <div class="card-body">
                <p class="mb-4">QR kodu okutun veya doğrulama kodunu girin.</p>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="verification_code" class="form-label">Doğrulama Kodu</label>
                        <input type="text" class="form-control" id="verification_code" name="verification_code" placeholder="Bilet doğrulama kodunu girin">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Doğrula</button>
                </form>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Doğrulama sonucu -->
        <div class="card mb-4 <?php echo ($verification_status == 'valid' || $verification_status == 'marked_used') ? 'valid-ticket' : 'invalid-ticket'; ?>">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php if ($verification_status == 'valid'): ?>
                        <i class="fas fa-check-circle text-success me-2"></i> Geçerli Bilet
                    <?php elseif ($verification_status == 'marked_used'): ?>
                        <i class="fas fa-check-circle text-success me-2"></i> Bilet Kullanıldı Olarak İşaretlendi
                    <?php elseif ($verification_status == 'used'): ?>
                        <i class="fas fa-times-circle text-danger me-2"></i> Bilet Zaten Kullanılmış
                    <?php elseif ($verification_status == 'expired'): ?>
                        <i class="fas fa-calendar-times text-danger me-2"></i> Bilet Süresi Dolmuş
                    <?php else: ?>
                        <i class="fas fa-times-circle text-danger me-2"></i> Geçersiz Bilet
                    <?php endif; ?>
                </h5>
            </div>
            
            <?php if ($ticket_data && ($verification_status == 'valid' || $verification_status == 'marked_used' || $verification_status == 'used' || $verification_status == 'expired')): ?>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="card-title"><?php echo htmlspecialchars($ticket_data['event_title']); ?></h5>
                            
                            <div class="mb-3">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                <strong>Tarih:</strong> <?php echo formatDate($ticket_data['event_date']); ?>
                            </div>
                            
                            <div class="mb-3">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                <strong>Konum:</strong> <?php echo htmlspecialchars($ticket_data['event_location']); ?>
                            </div>
                            
                            <div class="mb-3">
                                <i class="fas fa-user text-primary me-2"></i>
                                <strong>Bilet Sahibi:</strong> <?php echo htmlspecialchars($ticket_data['user_name']); ?>
                            </div>
                            
                            <div class="mb-3">
                                <i class="fas fa-envelope text-primary me-2"></i>
                                <strong>E-posta:</strong> <?php echo htmlspecialchars($ticket_data['user_email']); ?>
                            </div>
                            
                            <div class="mb-3">
                                <i class="fas fa-ticket-alt text-primary me-2"></i>
                                <strong>Bilet No:</strong> #<?php echo $ticket_data['id']; ?>
                            </div>
                            
                            <div class="mb-3">
                                <i class="fas fa-clock text-primary me-2"></i>
                                <strong>Satın Alma Tarihi:</strong> <?php echo formatDate($ticket_data['purchase_date']); ?>
                            </div>
                            
                            <?php if ($verification_status == 'valid' && isAdmin()): ?>
                                <form method="post" action="?data=<?php echo htmlspecialchars($_GET['data']); ?>">
                                    <button type="submit" name="mark_as_used" class="btn btn-success mt-3">
                                        <i class="fas fa-check-circle me-2"></i> Kullanıldı Olarak İşaretle
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 text-center">
                            <?php if ($verification_status == 'valid'): ?>
                                <div class="verification-result verification-success p-4 rounded">
                                    <i class="fas fa-check-circle fa-5x mb-3"></i>
                                    <h5>Bilet Geçerli</h5>
                                    <p class="mb-0">Bu bilet aktif ve kullanılabilir durumdadır.</p>
                                </div>
                            <?php elseif ($verification_status == 'marked_used'): ?>
                                <div class="verification-result verification-success p-4 rounded">
                                    <i class="fas fa-check-circle fa-5x mb-3"></i>
                                    <h5>İşlem Başarılı</h5>
                                    <p class="mb-0">Bilet kullanıldı olarak işaretlendi.</p>
                                </div>
                            <?php elseif ($verification_status == 'used'): ?>
                                <div class="verification-result verification-danger p-4 rounded">
                                    <i class="fas fa-times-circle fa-5x mb-3"></i>
                                    <h5>Bilet Zaten Kullanılmış</h5>
                                    <p class="mb-0">Bu bilet daha önce kullanılmıştır.</p>
                                </div>
                            <?php elseif ($verification_status == 'expired'): ?>
                                <div class="verification-result verification-danger p-4 rounded">
                                    <i class="fas fa-calendar-times fa-5x mb-3"></i>
                                    <h5>Bilet Süresi Dolmuş</h5>
                                    <p class="mb-0">Bu bilet için etkinlik tarihi geçmiştir.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-body">
                    <div class="verification-result verification-danger p-4 rounded">
                        <div class="text-center">
                            <i class="fas fa-times-circle fa-5x mb-3"></i>
                            <h4>Geçersiz Bilet</h4>
                            <p>Bu bilet geçerli değil veya veritabanında bulunamadı.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card-footer text-center">
                <a href="verify-ticket.php" class="btn btn-primary">Başka Bir Bilet Doğrula</a>
                
                <?php if (isAdmin()): ?>
                    <a href="admin/tickets.php" class="btn btn-outline-secondary ms-2">Tüm Biletlere Dön</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?> 