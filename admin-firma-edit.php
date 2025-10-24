<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$company_id = $_GET['id'] ?? '';

if (!$company_id) {
    header('Location: admin-firma.php');
    exit();
}

try {
    $stmt = $db->prepare("SELECT * FROM bus_company WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company) {
        $_SESSION['error'] = "Firma bulunamadı!";
        header('Location: admin-firma.php');
        exit();
    }
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_company'])) {
    $company_name = trim($_POST['company_name']);
    
    if (!empty($company_name)) {
        try {
            $stmt = $db->prepare("UPDATE bus_company SET name = ? WHERE id = ?");
            $stmt->execute([$company_name, $company_id]);
            
            $_SESSION['success'] = "Firma başarıyla güncellendi!";
            header('Location: admin-firma.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Firma güncellenirken hata oluştu: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Firma adı boş olamaz!";
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
            <li class="breadcrumb-item active">Firma Düzenle</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">
                <i class="fas fa-edit"></i> Firma Düzenle
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

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow admin-card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-bus"></i> Firma Bilgileri
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Firma Adı</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   value="<?php echo htmlspecialchars($company['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Firma ID</label>
                            <input type="text" class="form-control" value="<?php echo $company['id']; ?>" readonly>
                            <div class="form-text">Firma ID değiştirilemez.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Oluşturulma Tarihi</label>
                            <input type="text" class="form-control" value="<?php echo date('d.m.Y H:i', strtotime($company['created_at'])); ?>" readonly>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="update_company" class="btn btn-primary">
                                <i class="fas fa-save"></i> Değişiklikleri Kaydet
                            </button>
                            <a href="admin-firma.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Listeye Dön
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>