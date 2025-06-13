<?php
$page_title = "Destek Talepleri";
require_once dirname(__DIR__) . '/includes/config.php';
require_once ROOT_PATH . '/includes/functions.php';

// Admin kontrolü
requireAdmin();

$tickets = [];
$sql = "SELECT st.id, st.subject, st.status, u.name as user_name, st.created_at 
        FROM support_tickets st
        JOIN users u ON st.user_id = u.id
        ORDER BY st.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$stmt->bind_result($id, $subject, $status, $user_name, $created_at);
while ($stmt->fetch()) {
    $tickets[] = [
        'id' => $id,
        'subject' => $subject,
        'status' => $status,
        'user_name' => $user_name,
        'created_at' => $created_at
    ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Paneli</title>
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
                    <h2 class="mb-4">Destek Talepleri</h2>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Konu</th>
                                            <th>Kullanıcı</th>
                                            <th>Durum</th>
                                            <th>Tarih</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($tickets) > 0): ?>
                                            <?php foreach ($tickets as $ticket): ?>
                                                <tr>
                                                    <td>#<?php echo $ticket['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                                    <td><?php echo htmlspecialchars($ticket['user_name']); ?></td>
                                                    <td><span class="badge <?php echo getStatusBadgeClass($ticket['status']); ?>"><?php echo translateStatus($ticket['status']); ?></span></td>
                                                    <td><?php echo formatDate($ticket['created_at']); ?></td>
                                                    <td>
                                                        <a href="<?php echo BASE_URL; ?>admin/support_ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i> Görüntüle
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center">Destek talebi bulunmamaktadır.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 