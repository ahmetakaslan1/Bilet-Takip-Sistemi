<?php
$page_title = "Destek Talebi Detayı";
require_once dirname(__DIR__) . '/includes/config.php';
require_once ROOT_PATH . '/includes/functions.php';

requireAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: support_tickets.php");
    exit;
}
$ticket_id = intval($_GET['id']);

// Yanıt gönderme
if (isset($_POST['submit_reply'])) {
    $reply_message = sanitize($_POST['reply_message']);
    if (!empty($reply_message)) {
        $admin_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO support_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $ticket_id, $admin_id, $reply_message);
        $stmt->execute();
        $stmt->close();
        
        $new_status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $new_status, $ticket_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: support_ticket_detail.php?id=$ticket_id");
        exit;
    }
}

// Talep detayları
$ticket = null;
$stmt = $conn->prepare("SELECT st.id, st.subject, st.message, st.status, st.created_at, u.name as user_name, u.email as user_email 
                       FROM support_tickets st 
                       JOIN users u ON st.user_id = u.id 
                       WHERE st.id = ?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$stmt->bind_result($t_id, $t_subject, $t_message, $t_status, $t_created_at, $t_user_name, $t_user_email);
if ($stmt->fetch()) {
    $ticket = [
        'id' => $t_id, 'subject' => $t_subject, 'message' => $t_message,
        'status' => $t_status, 'created_at' => $t_created_at,
        'user_name' => $t_user_name, 'user_email' => $t_user_email
    ];
}
$stmt->close();

if (!$ticket) {
    header("Location: support_tickets.php");
    exit;
}

// Yanıtlar
$replies = [];
$stmt = $conn->prepare("SELECT r.id, r.message, r.created_at, u.name as user_name, u.role 
                       FROM support_replies r 
                       JOIN users u ON r.user_id = u.id 
                       WHERE r.ticket_id = ? ORDER BY r.created_at ASC");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$stmt->bind_result($r_id, $r_message, $r_created_at, $r_user_name, $r_role);
while($stmt->fetch()) {
    $replies[] = [
        'id' => $r_id, 'message' => $r_message, 'created_at' => $r_created_at,
        'user_name' => $r_user_name, 'role' => $r_role
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3 col-lg-2">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        <div class="col-md-9 col-lg-10">
            <div class="admin-content">
                <h2 class="mb-4">Destek Talebi #<?php echo $ticket['id']; ?></h2>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <strong><?php echo htmlspecialchars($ticket['subject']); ?></strong>
                        <span class="badge <?php echo getStatusBadgeClass($ticket['status']); ?>"><?php echo translateStatus($ticket['status']); ?></span>
                    </div>
                    <div class="card-body">
                        <p><strong>Kullanıcı:</strong> <?php echo htmlspecialchars($ticket['user_name']); ?> (<?php echo htmlspecialchars($ticket['user_email']); ?>)</p>
                        <p><strong>Tarih:</strong> <?php echo formatDate($ticket['created_at']); ?></p>
                        <hr>
                        <p><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
                    </div>
                </div>

                <div class="replies mb-4">
                    <h4 class="mb-3">Yanıtlar</h4>
                    <?php foreach ($replies as $reply): ?>
                        <div class="card mb-3 <?php echo ($reply['role'] == 'admin') ? 'border-primary' : ''; ?>">
                            <div class="card-body">
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($reply['message'])); ?></p>
                                <small class="text-muted">
                                    <strong><?php echo htmlspecialchars($reply['user_name']); ?></strong> (<?php echo ucfirst($reply['role']); ?>) - <?php echo formatDate($reply['created_at']); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($replies)): ?>
                        <p>Henüz yanıt eklenmemiş.</p>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header">Yeni Yanıt Ekle</div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="reply_message" class="form-label">Mesajınız</label>
                                <textarea name="reply_message" id="reply_message" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Talebin Yeni Durumu</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="in_progress" <?php echo ($ticket['status'] == 'in_progress') ? 'selected' : ''; ?>>İşlemde</option>
                                    <option value="closed" <?php echo ($ticket['status'] == 'closed') ? 'selected' : ''; ?>>Kapatıldı</option>
                                    <option value="open" <?php echo ($ticket['status'] == 'open') ? 'selected' : ''; ?>>Açık</option>
                                </select>
                            </div>
                            <button type="submit" name="submit_reply" class="btn btn-primary">Yanıtı Gönder</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 