<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$company_id = $_GET['company_id'] ?? '';

$company = null;
if ($company_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM bus_company WHERE id = ?");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Hata: " . $e->getMessage());
    }
}

try {
    $stmt = $db->prepare("
        SELECT id, full_name, email, role 
        FROM user 
        WHERE role = 'user' 
        AND (company_id IS NULL OR company_id = '')
        ORDER BY full_name
    ");
    $stmt->execute();
    $available_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}

$company_admins = [];
if ($company_id) {
    try {
        $stmt = $db->prepare("
            SELECT id, full_name, email, created_at 
            FROM user 
            WHERE company_id = ? AND role = 'company'
            ORDER BY full_name
        ");
        $stmt->execute([$company_id]);
        $company_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Hata: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_admin'])) {
    $user_id = $_POST['user_id'];
    $password = trim($_POST['password']);
    
    if (!empty($user_id) && !empty($password) && $company_id) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                UPDATE user 
                SET role = 'company', company_id = ?, password = ?
                WHERE id = ?
            ");
            $stmt->execute([$company_id, $hashed_password, $user_id]);
            
            $_SESSION['success'] = "Kullanıcı başarıyla firma admini olarak atandı!";
            header('Location: admin-firma-admin.php?company_id=' . $company_id);
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Admin atama sırasında hata oluştu: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Lütfen tüm alanları doldurun!";
    }
}

if (isset($_GET['remove_admin'])) {
    $admin_id = $_GET['remove_admin'];
    
    try {
        $stmt = $db->prepare("
            UPDATE user 
            SET role = 'user', company_id = NULL 
            WHERE id = ? AND role = 'company'
        ");
        $stmt->execute([$admin_id]);
        
        $_SESSION['success'] = "Firma admini başarıyla kaldırıldı!";
        header('Location: admin-firma-admin.php?company_id=' . $company_id);
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Admin kaldırma sırasında hata oluştu: " . $e->getMessage();
        header('Location: admin-firma-admin.php?company_id=' . $company_id);
        exit();
    }
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
            <li class="breadcrumb-item"><a href="admin-paneli.php">Admin Paneli</a></li>
            <li class="breadcrumb-item"><a href="admin-firma.php">Firma Yönetimi</a></li>
            <li class="breadcrumb-item active">Firma Admin Atama</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">
                <i class="fas fa-user-tie"></i> Firma Admin Atama
                <?php if ($company): ?>
                    - <span class="text-primary"><?php echo htmlspecialchars($company['name']); ?></span>
                <?php endif; ?>
            </h1>
        </div>
    </div>

    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show admin-alert" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show admin-alert" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!$company): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Lütfen önce bir firma seçin.
            <a href="admin-firma.php" class="alert-link">Firma Yönetimi</a> sayfasına giderek bir firma seçebilirsiniz.
        </div>
    <?php else: ?>
        <div class="row">
            
            <div class="col-lg-6">
                <div class="card shadow admin-card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-plus-circle"></i> Yeni Admin Ata
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Kullanıcı Seçin</label>
                                <select class="form-control" id="user_id" name="user_id" required>
                                    <option value="">-- Kullanıcı Seçin --</option>
                                    <?php foreach ($available_users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($available_users)): ?>
                                    <div class="form-text text-warning">
                                        <i class="fas fa-info-circle"></i> Atanabilecek kullanıcı bulunamadı.
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Yeni Şifre</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Admin için yeni şifre belirleyin" required>
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> Kullanıcının mevcut şifresi değiştirilecektir.
                                </div>
                            </div>
                            
                            <button type="submit" name="assign_admin" class="btn btn-primary btn-block" 
                                    <?php echo empty($available_users) ? 'disabled' : ''; ?>>
                                <i class="fas fa-user-tie"></i> Admin Olarak Ata
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-6">
                <div class="card shadow admin-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-list"></i> Mevcut Adminler
                        </h6>
                        <span class="badge bg-primary admin-badge"><?php echo count($company_admins); ?> Admin</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($company_admins)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p>Bu firma için atanmış admin bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered admin-table">
                                    <thead>
                                        <tr>
                                            <th>Ad Soyad</th>
                                            <th>Email</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($company_admins as $admin): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                            <td class="table-actions">
                                                <a href="admin-firma-admin.php?company_id=<?php echo $company_id; ?>&remove_admin=<?php echo $admin['id']; ?>" 
                                                   class="btn btn-outline-danger btn-sm"
                                                   onclick="return confirm('Bu kullanıcıyı firma adminliğinden kaldırmak istediğinizden emin misiniz?')">
                                                    <i class="fas fa-times"></i> Kaldır
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow admin-card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-info-circle"></i> Firma Bilgileri
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Firma Adı:</strong> <?php echo htmlspecialchars($company['name']); ?></p>
                                <p><strong>Oluşturulma Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($company['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Toplam Admin:</strong> <span class="badge bg-primary"><?php echo count($company_admins); ?></span></p>
                                <p><strong>Firma ID:</strong> <code><?php echo $company['id']; ?></code></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>