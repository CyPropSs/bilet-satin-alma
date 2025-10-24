<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_company'])) {
    $company_name = trim($_POST['company_name']);
    
    if (!empty($company_name)) {
        try {
            $company_id = generateUUID();
            $stmt = $db->prepare("INSERT INTO bus_company (id, name) VALUES (?, ?)");
            $stmt->execute([$company_id, $company_name]);
            
            $_SESSION['success'] = "Firma başarıyla eklendi!";
            header('Location: admin-firma.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Firma eklenirken hata oluştu: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Firma adı boş olamaz!";
    }
}

if (isset($_GET['delete'])) {
    $company_id = $_GET['delete'];
    
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as trip_count FROM trips WHERE company_id = ?");
        $stmt->execute([$company_id]);
        $trip_count = $stmt->fetch(PDO::FETCH_ASSOC)['trip_count'];
        
        if ($trip_count > 0) {
            $_SESSION['error'] = "Bu firmaya ait seferler bulunuyor. Önce seferleri silmelisiniz!";
        } else {
            $stmt = $db->prepare("DELETE FROM bus_company WHERE id = ?");
            $stmt->execute([$company_id]);
            
            $_SESSION['success'] = "Firma başarıyla silindi!";
        }
        
        header('Location: admin-firma.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Firma silinirken hata oluştu: " . $e->getMessage();
        header('Location: admin-firma.php');
        exit();
    }
}

try {
    $stmt = $db->prepare("
        SELECT bc.*, 
               COUNT(t.id) as trip_count,
               COUNT(DISTINCT u.id) as admin_count
        FROM bus_company bc
        LEFT JOIN trips t ON bc.id = t.company_id
        LEFT JOIN user u ON bc.id = u.company_id AND u.role = 'company'
        GROUP BY bc.id
        ORDER BY bc.created_at DESC
    ");
    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
            <li class="breadcrumb-item"><a href="admin-paneli.php">Admin Paneli</a></li>
            <li class="breadcrumb-item active">Firma Yönetimi</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">
                <i class="fas fa-bus"></i> Firma Yönetimi
            </h1>
        </div>
    </div>

    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="row">
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-plus-circle"></i> Yeni Firma Ekle
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Firma Adı</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   placeholder="Firma adını girin" required>
                        </div>
                        <button type="submit" name="add_company" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Firma Ekle
                        </button>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> Firmalar
                    </h6>
                    <span class="badge bg-primary"><?php echo count($companies); ?> Firma</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead class="table-light">
                                <tr>
                                    <th>Firma Adı</th>
                                    <th>Sefer Sayısı</th>
                                    <th>Admin Sayısı</th>
                                    <th>Oluşturulma</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companies as $company): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($company['name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $company['trip_count']; ?> sefer</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?php echo $company['admin_count']; ?> admin</span>
                                    </td>
                                    <td>
                                        <small><?php echo date('d.m.Y', strtotime($company['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="admin-firma-admin.php?company_id=<?php echo $company['id']; ?>" 
                                               class="btn btn-outline-primary" title="Admin Ata">
                                                <i class="fas fa-user-tie"></i>
                                            </a>
                                            <a href="admin-firma-edit.php?id=<?php echo $company['id']; ?>" 
                                               class="btn btn-outline-warning" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="admin-firma.php?delete=<?php echo $company['id']; ?>" 
                                               class="btn btn-outline-danger" 
                                               title="Sil"
                                               onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 10px;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>

<?php include 'includes/footer.php'; ?>