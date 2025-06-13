<?php
$page_title = "Kullanıcı Yönetimi";
require_once dirname(__DIR__) . '/includes/config.php';
require_once ROOT_PATH . '/includes/functions.php';

// Admin kontrolü
requireAdmin();

// İşlemler (Silme vb.) buraya eklenebilir.

// Kullanıcıları getir
$users = [];
$stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$stmt->bind_result($id, $name, $email, $role, $created_at);

while ($stmt->fetch()) {
    $users[] = [
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'role' => $role,
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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <?php include '../includes/admin_sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="admin-content">
                    <h2 class="mb-4">Kullanıcı Yönetimi</h2>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Ad Soyad</th>
                                            <th>Email</th>
                                            <th>Rol</th>
                                            <th>Kayıt Tarihi</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($users) > 0): ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-success' : 'bg-secondary'; ?>">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                                    <td>
                                                        <a href="#" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                                        <a href="#" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Kullanıcı bulunamadı.</td>
                                            </tr>
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