<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
$form_errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];

unset($_SESSION['success'], $_SESSION['error'], $_SESSION['form_errors'], $_SESSION['form_data']);

try {
    $stmt = $db->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $kullanici = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kullanici) {
        die("Kullanıcı bulunamadı!");
    }
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}

$bilet_istatistik = [];
if ($_SESSION['user_role'] == 'user') {
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as toplam_bilet,
                SUM(total_price) as toplam_harcama,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as iptal_edilen
            FROM tickets 
            WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $bilet_istatistik = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $bilet_istatistik = ['toplam_bilet' => 0, 'toplam_harcama' => 0, 'iptal_edilen' => 0];
    }
}

$son_islemler = [];
if ($_SESSION['user_role'] == 'user') {
    try {
        $stmt = $db->prepare("
            SELECT 
                t.created_at as tarih,
                'Bilet Alış' as islem_adi,
                -t.total_price as tutar,
                tr.departure_city,
                tr.destination_city,
                'bilet' as tip
            FROM tickets t
            LEFT JOIN trips tr ON t.trip_id = tr.id
            WHERE t.user_id = ? AND t.status != 'cancelled'
            
            UNION ALL
            
            SELECT 
                t.created_at as tarih,
                'Bilet İptal İadesi' as islem_adi,
                t.total_price as tutar,
                tr.departure_city,
                tr.destination_city,
                'iade' as tip
            FROM tickets t
            LEFT JOIN trips tr ON t.trip_id = tr.id
            WHERE t.user_id = ? AND t.status = 'cancelled'
            
            ORDER BY tarih DESC 
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        $son_islemler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $son_islemler = [];
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
            <li class="breadcrumb-item active">Hesabım</li>
        </ol>
    </nav>

    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($form_errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <h6><i class="fas fa-exclamation-triangle"></i> Hatalar:</h6>
            <ul class="mb-0">
                <?php foreach ($form_errors as $err): ?>
                    <li><?php echo $err; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-cog"></i> Hesap Ayarları</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#profil" class="list-group-item list-group-item-action active" data-bs-toggle="tab">
                        <i class="fas fa-user"></i> Profil Bilgilerim
                    </a>
                    <a href="#sifre" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-lock"></i> Şifre Değiştir
                    </a>
                    <?php if ($_SESSION['user_role'] == 'user'): ?>
                        <a href="#bakiye" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                            <i class="fas fa-wallet"></i> Bakiye Yönetimi
                        </a>
                    <?php endif; ?>
                    <a href="#gecmis" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-history"></i> İşlem Geçmişi
                    </a>
                </div>
            </div>

            
            <?php if ($_SESSION['user_role'] == 'user'): ?>
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> İstatistiklerim</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <h4 class="text-primary"><?php echo $bilet_istatistik['toplam_bilet'] ?? 0; ?></h4>
                        <small class="text-muted">Toplam Bilet</small>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h4 class="text-success">₺<?php echo number_format($bilet_istatistik['toplam_harcama'] ?? 0, 2); ?></h4>
                        <small class="text-muted">Toplam Harcama</small>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h4 class="text-warning"><?php echo $bilet_istatistik['iptal_edilen'] ?? 0; ?></h4>
                        <small class="text-muted">İptal Edilen</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        
        <div class="col-md-9">
            <div class="tab-content">
                
                <div class="tab-pane fade show active" id="profil">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-user"></i> Profil Bilgilerim</h4>
                        </div>
                        <div class="card-body">
                            <form action="profil-guncelle.php" method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Ad Soyad</label>
                                            <input type="text" class="form-control" name="full_name" 
                                                   value="<?php echo htmlspecialchars($kullanici['full_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($kullanici['email']); ?>" disabled>
                                            <small class="form-text text-muted">Email değiştirmek için destek ile iletişime geçin.</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Kullanıcı Rolü</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php 
                                                   switch($kullanici['role']) {
                                                       case 'admin': echo 'Sistem Admin'; break;
                                                       case 'company': echo 'Firma Yetkilisi'; break;
                                                       case 'user': echo 'Yolcu'; break;
                                                       default: echo 'Kullanıcı';
                                                   }
                                                   ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Üyelik Tarihi</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo date('d.m.Y H:i', strtotime($kullanici['created_at'])); ?>" disabled>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($kullanici['role'] == 'company' && !empty($kullanici['company_id'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Bağlı Firma</label>
                                        <?php
                                        try {
                                            $firma_stmt = $db->prepare("SELECT name FROM bus_company WHERE id = ?");
                                            $firma_stmt->execute([$kullanici['company_id']]);
                                            $firma = $firma_stmt->fetch(PDO::FETCH_ASSOC);
                                            $firma_adi = $firma ? $firma['name'] : 'Firma bulunamadı';
                                        } catch (PDOException $e) {
                                            $firma_adi = 'Firma bilgisi yüklenemedi';
                                        }
                                        ?>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($firma_adi); ?>" disabled>
                                    </div>
                                <?php endif; ?>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Değişiklikleri Kaydet
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                
                <div class="tab-pane fade" id="sifre">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-lock"></i> Şifre Değiştir</h4>
                        </div>
                        <div class="card-body">
                            <form action="sifre-degistir.php" method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Mevcut Şifre</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Yeni Şifre</label>
                                    <input type="password" class="form-control" name="new_password" required minlength="6">
                                    <div class="form-text">Şifre en az 6 karakter olmalıdır.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Yeni Şifre (Tekrar)</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Şifreyi Değiştir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                
                <?php if ($_SESSION['user_role'] == 'user'): ?>
                <div class="tab-pane fade" id="bakiye">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-wallet"></i> Bakiye Yönetimi</h4>
                        </div>
                        <div class="card-body">
                            
                            <div class="alert alert-info">
                                <h5 class="alert-heading">
                                    <i class="fas fa-coins"></i> 
                                    Mevcut Bakiyeniz: <span class="text-success">₺<?php echo number_format($_SESSION['user_balance'], 2); ?></span>
                                </h5>
                            </div>

                            
                            <form action="bakiye-yukle.php" method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Yüklenecek Tutar (₺)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₺</span>
                                                <input type="number" class="form-control" name="amount" 
                                                       min="10" max="1000" step="10" value="50" required>
                                            </div>
                                            <div class="form-text">Minimum ₺10, maksimum ₺1000 yükleyebilirsiniz.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-plus-circle"></i> Bakiye Yükle
                                        </button>
                                    </div>
                                </div>
                            </form>

                            
                            <div class="mb-4">
                                <label class="form-label">Hızlı Yükleme:</label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <form action="bakiye-yukle.php" method="POST" class="d-inline">
                                        <input type="hidden" name="amount" value="50">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">₺50</button>
                                    </form>
                                    <form action="bakiye-yukle.php" method="POST" class="d-inline">
                                        <input type="hidden" name="amount" value="100">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">₺100</button>
                                    </form>
                                    <form action="bakiye-yukle.php" method="POST" class="d-inline">
                                        <input type="hidden" name="amount" value="200">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">₺200</button>
                                    </form>
                                    <form action="bakiye-yukle.php" method="POST" class="d-inline">
                                        <input type="hidden" name="amount" value="500">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">₺500</button>
                                    </form>
                                </div>
                            </div>

                            
                            <?php if (!empty($son_islemler)): ?>
                            <div class="mt-4">
                                <h6><i class="fas fa-history"></i> Son İşlemleriniz</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <th>İşlem</th>
                                                <th>Tutar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($son_islemler as $islem): ?>
                                                <tr>
                                                    <td><?php echo date('d.m.Y H:i', strtotime($islem['tarih'])); ?></td>
                                                    <td>
                                                        <?php echo $islem['islem_adi']; ?>
                                                        <?php if ($islem['tip'] == 'bilet'): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($islem['departure_city']); ?> → 
                                                                <?php echo htmlspecialchars($islem['destination_city']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="<?php echo $islem['tutar'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo $islem['tutar'] > 0 ? '+' : ''; ?>
                                                        ₺<?php echo number_format(abs($islem['tutar']), 2); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>

                            
                            <div class="alert alert-warning">
                                
                                <ul class="mb-0">
                                    <li>Bakiye yükleme işlemi demo amaçlıdır</li>
                                  
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                
                <div class="tab-pane fade" id="gecmis">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-history"></i> İşlem Geçmişi</h4>
                        </div>
                        <div class="card-body">
                            <?php
                            try {
                                $stmt = $db->prepare("
                                    SELECT 
                                        t.created_at as tarih,
                                        'Bilet Alış' as islem_adi,
                                        -t.total_price as tutar,
                                        tr.departure_city,
                                        tr.destination_city,
                                        bc.name as company_name,
                                        'bilet' as tip
                                    FROM tickets t
                                    LEFT JOIN trips tr ON t.trip_id = tr.id
                                    LEFT JOIN bus_company bc ON tr.company_id = bc.id
                                    WHERE t.user_id = ? AND t.status != 'cancelled'
                                    
                                    UNION ALL
                                    
                                    SELECT 
                                        t.created_at as tarih,
                                        'Bilet İptal İadesi' as islem_adi,
                                        t.total_price as tutar,
                                        tr.departure_city,
                                        tr.destination_city,
                                        bc.name as company_name,
                                        'iade' as tip
                                    FROM tickets t
                                    LEFT JOIN trips tr ON t.trip_id = tr.id
                                    LEFT JOIN bus_company bc ON tr.company_id = bc.id
                                    WHERE t.user_id = ? AND t.status = 'cancelled'
                                    
                                    ORDER BY tarih DESC 
                                    LIMIT 20
                                ");
                                $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                                $tum_islemler = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                $tum_islemler = [];
                            }
                            ?>

                            <?php if (empty($tum_islemler)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Henüz işlem geçmişiniz bulunmuyor</h5>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <th>İşlem</th>
                                                <th>Tutar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tum_islemler as $islem): ?>
                                                <tr>
                                                    <td><?php echo date('d.m.Y H:i', strtotime($islem['tarih'])); ?></td>
                                                    <td>
                                                        <?php echo $islem['islem_adi']; ?>
                                                        <?php if ($islem['tip'] == 'bilet'): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($islem['departure_city']); ?> → 
                                                                <?php echo htmlspecialchars($islem['destination_city']); ?>
                                                                (<?php echo htmlspecialchars($islem['company_name']); ?>)
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="<?php echo $islem['tutar'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo $islem['tutar'] > 0 ? '+' : ''; ?>
                                                        ₺<?php echo number_format(abs($islem['tutar']), 2); ?>
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
    </div>
</div>

<style>
.card-header.bg-primary {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

.list-group-item.active {
    background-color: #007bff;
    border-color: #007bff;
}

.tab-pane {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.alert h5.alert-heading {
    margin-bottom: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('a[data-bs-toggle="tab"]');
    
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (e) {
            const target = e.target.getAttribute('href');
            window.location.hash = target;
        });
    });
    
    if (window.location.hash) {
        const triggerEl = document.querySelector(`a[href="${window.location.hash}"]`);
        if (triggerEl) {
            new bootstrap.Tab(triggerEl).show();
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>