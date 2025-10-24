<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_coupon'])) {
    $code = trim($_POST['code']);
    $discount = (float)$_POST['discount'];
    $usage_limit = (int)$_POST['usage_limit'];
    $expire_date = $_POST['expire_date'];
    $company_id = $_POST['company_id'] ?: null;
    
    $errors = [];
    
    if (empty($code)) {
        $errors[] = "Kupon kodu boş olamaz!";
    }
    
    if ($discount <= 0 || $discount > 100) {
        $errors[] = "İndirim oranı 1-100 arasında olmalıdır!";
    }
    
    if ($usage_limit < 0) {
        $errors[] = "Kullanım limiti negatif olamaz!";
    }
    
    if (empty($expire_date)) {
        $errors[] = "Son kullanma tarihi boş olamaz!";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM coupons WHERE code = ?");
            $stmt->execute([$code]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($exists > 0) {
                $_SESSION['error'] = "Bu kupon kodu zaten kullanılıyor!";
            } else {
                $coupon_id = generateUUID();
                $stmt = $db->prepare("
                    INSERT INTO coupons (id, code, discount, company_id, usage_limit, expire_date) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$coupon_id, $code, $discount, $company_id, $usage_limit, $expire_date]);
                
                $_SESSION['success'] = "Kupon başarıyla eklendi!";
                header('Location: admin-kupon.php');
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Kupon eklenirken hata oluştu: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

if (isset($_GET['delete'])) {
    $coupon_id = $_GET['delete'];
    
    try {
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$coupon_id]);
        
        $_SESSION['success'] = "Kupon başarıyla silindi!";
        header('Location: admin-kupon.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Kupon silinirken hata oluştu: " . $e->getMessage();
        header('Location: admin-kupon.php');
        exit();
    }
}

try {
    $stmt = $db->prepare("SELECT id, name FROM bus_company ORDER BY name");
    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT c.*, bc.name as company_name,
               (SELECT COUNT(*) FROM user_coupons uc WHERE uc.coupon_id = c.id) as used_count
        FROM coupons c
        LEFT JOIN bus_company bc ON c.company_id = bc.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
            <li class="breadcrumb-item active">Kupon Yönetimi</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">
                <i class="fas fa-tags"></i> Kupon Yönetimi
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

    <div class="row">
        
        <div class="col-lg-4">
            <div class="card shadow admin-card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-plus-circle"></i> Yeni Kupon Ekle
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="code" class="form-label">Kupon Kodu *</label>
                            <input type="text" class="form-control" id="code" name="code" 
                                   placeholder="KUPON10" required maxlength="20">
                        </div>
                        
                        <div class="mb-3">
                            <label for="discount" class="form-label">İndirim Oranı (%) *</label>
                            <input type="number" class="form-control" id="discount" name="discount" 
                                   min="1" max="100" step="1" placeholder="10" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="company_id" class="form-label">Firma (Opsiyonel)</label>
                            <select class="form-control" id="company_id" name="company_id">
                                <option value="">-- Tüm Firmalar İçin --</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>">
                                        <?php echo htmlspecialchars($company['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Boş bırakılırsa tüm firmalar için geçerli olur.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="usage_limit" class="form-label">Kullanım Limiti</label>
                            <input type="number" class="form-control" id="usage_limit" name="usage_limit" 
                                   min="0" value="0" placeholder="0">
                            <div class="form-text">
                                0 = limitsiz kullanım
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="expire_date" class="form-label">Son Kullanma Tarihi *</label>
                            <input type="datetime-local" class="form-control" id="expire_date" name="expire_date" required>
                        </div>
                        
                        <button type="submit" name="add_coupon" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Kupon Ekle
                        </button>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="col-lg-8">
            <div class="card shadow admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-list"></i> Kuponlar
                    </h6>
                    <span class="badge bg-primary admin-badge"><?php echo count($coupons); ?> Kupon</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover admin-table">
                            <thead>
                                <tr>
                                    <th>Kupon Kodu</th>
                                    <th>İndirim</th>
                                    <th>Firma</th>
                                    <th>Kullanım</th>
                                    <th>Son Kullanma</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $coupon): ?>
                                    <?php
                                    $is_expired = strtotime($coupon['expire_date']) < time();
                                    $is_limited = $coupon['usage_limit'] > 0;
                                    $is_usage_exceeded = $is_limited && $coupon['used_count'] >= $coupon['usage_limit'];
                                    $status_class = $is_expired || $is_usage_exceeded ? 'text-danger' : 'text-success';
                                    $status_text = $is_expired ? 'Süresi Dolmuş' : ($is_usage_exceeded ? 'Limit Dolmuş' : 'Aktif');
                                    ?>
                                    <tr class="<?php echo $status_class; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($coupon['code']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">%<?php echo $coupon['discount']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($coupon['company_name']): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($coupon['company_name']); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Tüm Firmalar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($coupon['usage_limit'] == 0): ?>
                                                <span class="badge bg-warning">Sınırsız</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary"><?php echo $coupon['used_count']; ?>/<?php echo $coupon['usage_limit']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo date('d.m.Y H:i', strtotime($coupon['expire_date'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $is_expired || $is_usage_exceeded ? 'bg-danger' : 'bg-success'; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <div class="btn-group btn-group-sm">
                                                <a href="admin-kupon.php?delete=<?php echo $coupon['id']; ?>" 
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Bu kuponu silmek istediğinizden emin misiniz?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($coupons)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-tags fa-3x mb-3"></i>
                            <p>Henüz kupon eklenmemiş.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const expireDateInput = document.getElementById('expire_date');
    const now = new Date();
    const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    expireDateInput.min = localDateTime;
    
    const defaultExpireDate = new Date(now);
    defaultExpireDate.setDate(now.getDate() + 30);
    const defaultLocalDateTime = new Date(defaultExpireDate.getTime() - defaultExpireDate.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    expireDateInput.value = defaultLocalDateTime;
});
</script>

<?php include 'includes/footer.php'; ?>