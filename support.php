<?php
$page_title = "Destek";
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Giriş yapılmış mı kontrol et
requireLogin();

// Admin ise admin paneline yönlendir
if (isAdmin()) {
    header("Location: admin/support_tickets.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Form gönderildiyse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    // Validasyon
    if (empty($subject) || empty($message)) {
        $error = "Lütfen tüm alanları doldurunuz.";
    } else {
        // Destek talebi oluştur
        $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $subject, $message);
        
        if ($stmt->execute()) {
            $success = "Destek talebiniz başarıyla oluşturuldu. En kısa sürede yanıtlanacaktır.";
        } else {
            $error = "Destek talebi oluşturulurken bir hata oluştu: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Destek taleplerini getir
$tickets = [];
$stmt = $conn->prepare("SELECT id, subject, status, created_at FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($id, $subject, $status, $created_at);

while ($stmt->fetch()) {
    $tickets[] = [
        'id' => $id,
        'subject' => $subject,
        'status' => $status,
        'created_at' => $created_at
    ];
}
$stmt->close();

// Talep detaylarını getir (eğer talep ID'si varsa)
$ticket_details = null;
$ticket_replies = [];
if (isset($_GET['ticket_id'])) {
    $ticket_id = intval($_GET['ticket_id']);
    
    // Talep detayını çek
    $stmt = $conn->prepare("SELECT id, subject, message, status, created_at FROM support_tickets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $ticket_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($td_id, $td_subject, $td_message, $td_status, $td_created_at);
    if ($stmt->fetch()) {
        $ticket_details = [
            'id' => $td_id,
            'subject' => $td_subject,
            'message' => $td_message,
            'status' => $td_status,
            'created_at' => $td_created_at
        ];
    }
    $stmt->close();

    // Talep'e ait yanıtları çek
    if ($ticket_details) {
        $stmt = $conn->prepare("SELECT r.id, r.ticket_id, r.user_id, r.message, r.created_at, u.name as user_name, u.role 
                               FROM support_replies r
                               JOIN users u ON r.user_id = u.id
                               WHERE r.ticket_id = ? ORDER BY r.created_at ASC");
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $stmt->bind_result($r_id, $r_ticket_id, $r_user_id, $r_message, $r_created_at, $r_user_name, $r_role);
        
        while($stmt->fetch()) {
            $ticket_replies[] = [
                'id' => $r_id,
                'ticket_id' => $r_ticket_id,
                'user_id' => $r_user_id,
                'message' => $r_message,
                'created_at' => $r_created_at,
                'user_name' => $r_user_name,
                'role' => $r_role
            ];
        }
        $stmt->close();
    }
}

// Yanıt gönderme
if (isset($_POST['submit_reply']) && isset($_POST['ticket_id']) && isset($_POST['reply_message'])) {
    $ticket_id = intval($_POST['ticket_id']);
    $reply_message = sanitize($_POST['reply_message']);
    
    // Validasyon
    if (empty($reply_message)) {
        $error = "Yanıt mesajı boş olamaz.";
    } else {
        // Talebin bu kullanıcıya ait olup olmadığını kontrol et
        $stmt = $conn->prepare("SELECT id FROM support_tickets WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $ticket_id, $user_id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            $error = "Bu işlem için yetkiniz yok.";
        } else {
            // Yanıtı kaydet
            $stmt->close();
            
            $stmt = $conn->prepare("INSERT INTO support_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $ticket_id, $user_id, $reply_message);
            
            if ($stmt->execute()) {
                // Talebin durumunu güncelle (kullanıcı yanıt verdiyse 'in_progress' yap)
                $stmt->close();
                
                $stmt = $conn->prepare("UPDATE support_tickets SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $ticket_id);
                $stmt->execute();
                
                $success = "Yanıtınız başarıyla gönderildi.";
                
                // Sayfayı yenile (yeni yanıtları görmek için)
                header("Location: support.php");
                exit;
            } else {
                $error = "Yanıt gönderilirken bir hata oluştu: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Yeni Destek Talebi</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Konu</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Mesajınız</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Talebi Gönder</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <h2 class="mb-4">Destek Talepleriniz</h2>
        
        <?php if (count($tickets) > 0): ?>
            <div class="row">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="col-md-6 mb-4">
                        <a href="support.php?ticket_id=<?php echo $ticket['id']; ?>" class="text-decoration-none text-dark">
                            <div class="card support-ticket-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></h5>
                                        <span class="badge <?php echo getStatusBadgeClass($ticket['status']); ?>">
                                            <?php echo translateStatus($ticket['status']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        Talep No: #<?php echo $ticket['id']; ?> | 
                                        Oluşturma: <?php echo formatDate($ticket['created_at']); ?>
                                    </small>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Henüz destek talebiniz bulunmamaktadır.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 