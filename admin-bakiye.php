<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_balance'])) {
    $user_id = $_POST['user_id'];
    $amount = (float)$_POST['amount'];
    
    $errors = [];
    
    if (empty($user_id)) {
        $errors[] = "Kullanıcı seçilmedi!";
    }
    
    if ($amount <= 0) {
        $errors[] = "Geçerli bir tutar girin!";
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("SELECT balance, full_name, email FROM user WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception("Kullanıcı bulunamadı!");
            }
            
            $new_balance = $user['balance'] + $amount;
            
            $stmt = $db->prepare("UPDATE user SET balance = ? WHERE id = ?");
            $stmt->execute([$new_balance, $user_id]);
            
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                $_SESSION['user_balance'] = $new_balance;
            }
            
            error_log("ADMIN_BAKIYE_YUKLEME: Kullanıcı: {$user['full_name']}, Tutar: {$amount}, Yeni Bakiye: {$new_balance}");
            
            $db->commit();
            
            $_SESSION['success'] = "✅ Bakiye başarıyla yüklendi!<br><strong>" . htmlspecialchars($user['full_name']) . "</strong> kullanıcısının yeni bakiyesi: <strong>₺" . number_format($new_balance, 2) . "</strong>";
            
            header('Location: admin-bakiye.php?success=1');
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "❌ Bakiye yükleme sırasında hata oluştu: " . $e->getMessage();
            header('Location: admin-bakiye.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "❌ " . implode("<br>❌ ", $errors);
        header('Location: admin-bakiye.php');
        exit();
    }
}

try {
    $stmt = $db->prepare("
        SELECT id, full_name, email, balance, role, created_at 
        FROM user 
        WHERE role = 'user'
        ORDER BY full_name
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_users,
            SUM(balance) as total_balance,
            AVG(balance) as avg_balance,
            MAX(balance) as max_balance,
            MIN(balance) as min_balance
        FROM user 
        WHERE role = 'user'
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
            <li class="breadcrumb-item active">Bakiye Yönetimi</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">
                <i class="fas fa-wallet"></i> Bakiye Yönetimi
            </h1>
        </div>
    </div>

    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show admin-alert" role="alert">
            <i class="fas fa-exclamation-triangle"></i> 
            <div><?php echo $_SESSION['error']; ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show admin-alert" role="alert">
            <i class="fas fa-check-circle"></i> 
            <div><?php echo $_SESSION['success']; ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam Kullanıcı</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Toplam Bakiye</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₺<?php echo number_format($stats['total_balance'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Ortalama Bakiye</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₺<?php echo number_format($stats['avg_balance'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                En Yüksek Bakiye</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₺<?php echo number_format($stats['max_balance'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                En Düşük Bakiye</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₺<?php echo number_format($stats['min_balance'], 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-4">
            <div class="card shadow admin-card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-plus-circle"></i> Bakiye Yükle
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="balanceForm">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Kullanıcı Seçin *</label>
                            <select class="form-control" id="user_id" name="user_id" required>
                                <option value="">-- Kullanıcı Seçin --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" data-balance="<?php echo $user['balance']; ?>">
                                        <?php echo htmlspecialchars($user['full_name']); ?> 
                                        - <?php echo htmlspecialchars($user['email']); ?>
                                        (₺<?php echo number_format($user['balance'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Yüklenecek Tutar (₺) *</label>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   min="1" step="0.01" placeholder="100.00" required>
                            <div class="form-text">
                                Minimum yükleme tutarı: ₺1.00
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-calculator"></i>
                                <strong>Bakiye Hesaplama:</strong><br>
                                <strong>Mevcut:</strong> <span id="current-balance">₺0.00</span><br>
                                <strong>+ Yüklenecek:</strong> <span id="add-amount">₺0.00</span><br>
                                <strong>Yeni Bakiye:</strong> <span id="new-balance" class="text-success fw-bold">₺0.00</span>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_balance" class="btn btn-success btn-lg btn-block">
                            <i class="fas fa-credit-card"></i> Bakiye Yükle
                        </button>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="col-lg-8">
            <div class="card shadow admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-list"></i> Kullanıcılar ve Bakiyeleri
                    </h6>
                    <span class="badge bg-primary admin-badge"><?php echo count($users); ?> Kullanıcı</span>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <p>Henüz kullanıcı bulunmuyor.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover admin-table">
                                <thead>
                                    <tr>
                                        <th>Kullanıcı</th>
                                        <th>Email</th>
                                        <th>Bakiye</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <?php
                                        $balance_class = $user['balance'] == 0 ? 'text-danger' : ($user['balance'] < 50 ? 'text-warning' : 'text-success');
                                        $status_text = $user['balance'] == 0 ? 'Bakiye Yok' : ($user['balance'] < 50 ? 'Düşük Bakiye' : 'Yeterli Bakiye');
                                        $status_class = $user['balance'] == 0 ? 'bg-danger' : ($user['balance'] < 50 ? 'bg-warning' : 'bg-success');
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="<?php echo $balance_class; ?>">
                                                <strong>₺<?php echo number_format($user['balance'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <small><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.getElementById('user_id');
    const amountInput = document.getElementById('amount');
    const currentBalanceSpan = document.getElementById('current-balance');
    const addAmountSpan = document.getElementById('add-amount');
    const newBalanceSpan = document.getElementById('new-balance');
    const balanceForm = document.getElementById('balanceForm');
    
    function updateBalancePreview() {
        const selectedOption = userSelect.options[userSelect.selectedIndex];
        const currentBalance = selectedOption ? parseFloat(selectedOption.getAttribute('data-balance')) || 0 : 0;
        const amount = parseFloat(amountInput.value) || 0;
        const newBalance = currentBalance + amount;
        
        currentBalanceSpan.textContent = '₺' + currentBalance.toFixed(2);
        addAmountSpan.textContent = '₺' + amount.toFixed(2);
        newBalanceSpan.textContent = '₺' + newBalance.toFixed(2);
        
        if (newBalance < 0) {
            newBalanceSpan.className = 'text-danger fw-bold';
        } else if (newBalance < 50) {
            newBalanceSpan.className = 'text-warning fw-bold';
        } else {
            newBalanceSpan.className = 'text-success fw-bold';
        }
    }
    
    userSelect.addEventListener('change', updateBalancePreview);
    amountInput.addEventListener('input', updateBalancePreview);
    
    balanceForm.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor...';
    });
    
    updateBalancePreview();
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        userSelect.selectedIndex = 0;
        amountInput.value = '';
        updateBalancePreview();
        
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});
</script>

<?php include 'includes/footer.php'; ?>