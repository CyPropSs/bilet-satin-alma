<?php 
include 'includes/header.php'; 
include 'includes/config.php';

$sefer_id = $_GET['id'] ?? 0;

try {
    $stmt = $db->prepare("
        SELECT t.*, c.name as company_name 
        FROM trips t 
        LEFT JOIN bus_company c ON t.company_id = c.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$sefer_id]);
    $sefer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sefer) {
        die("
            <div class='container mt-4'>
                <div class='alert alert-danger text-center'>
                    <i class='fas fa-exclamation-triangle fa-2x mb-3'></i>
                    <h4>Sefer bulunamadı!</h4>
                    <p>İstediğiniz sefer sistemde bulunmamaktadır.</p>
                    <a href='seferler.php' class='btn btn-primary'>Tüm Seferlere Dön</a>
                </div>
            </div>
        ");
    }
    
    $departure_time = strtotime($sefer['departure_time']);
    $is_past = $departure_time < time();
    
} catch (PDOException $e) {
    die("
        <div class='container mt-4'>
            <div class='alert alert-danger text-center'>
                <i class='fas fa-exclamation-triangle fa-2x mb-3'></i>
                <h4>Hata oluştu!</h4>
                <p>Sefer bilgileri yüklenirken bir hata oluştu.</p>
                <a href='seferler.php' class='btn btn-primary'>Tüm Seferlere Dön</a>
            </div>
        </div>
    ");
}

$user_balance = $_SESSION['user_balance'] ?? 0;

try {
    $stmt = $db->prepare("
        SELECT COUNT(*) as occupied_seats 
        FROM booked_seats bs 
        JOIN tickets t ON bs.ticket_id = t.id 
        WHERE t.trip_id = ? AND t.status = 'active'
    ");
    $stmt->execute([$sefer_id]);
    $occupied_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $occupied_seats = $occupied_result['occupied_seats'];
    $available_seats = $sefer['capacity'] - $occupied_seats;
} catch (PDOException $e) {
    $occupied_seats = 0;
    $available_seats = $sefer['capacity'];
}

$departure = strtotime($sefer['departure_time']);
$arrival = strtotime($sefer['arrival_time']);
$duration = $arrival - $departure;
$hours = floor($duration / 3600);
$minutes = floor(($duration % 3600) / 60);
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
            <li class="breadcrumb-item"><a href="seferler.php">Seferler</a></li>
            <li class="breadcrumb-item active">
                <?php echo htmlspecialchars($sefer['departure_city']); ?> → <?php echo htmlspecialchars($sefer['destination_city']); ?>
            </li>
        </ol>
    </nav>

    <div class="row">
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-info-circle"></i> Sefer Detayları
                        <?php if ($is_past): ?>
                            <span class="badge bg-warning float-end">Geçmiş Sefer</span>
                        <?php else: ?>
                            <span class="badge bg-success float-end">Aktif Sefer</span>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-route"></i> Güzergah Bilgileri
                            </h5>
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-success text-white rounded-circle p-2 me-3">
                                        <i class="fas fa-play"></i>
                                    </div>
                                    <div>
                                        <strong>Kalkış</strong><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($sefer['departure_city']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-danger text-white rounded-circle p-2 me-3">
                                        <i class="fas fa-stop"></i>
                                    </div>
                                    <div>
                                        <strong>Varış</strong><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($sefer['destination_city']); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-clock"></i>
                                <strong>Yolculuk Süresi:</strong> 
                                <?php echo $hours; ?> saat <?php echo $minutes; ?> dakika
                            </div>
                        </div>
                        
                        
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-calendar-alt"></i> Zaman Bilgileri
                            </h5>
                            <div class="mb-3">
                                <p class="mb-2">
                                    <i class="fas fa-calendar text-muted"></i>
                                    <strong>Tarih:</strong> 
                                    <?php echo date('d.m.Y', strtotime($sefer['departure_time'])); ?>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-clock text-muted"></i>
                                    <strong>Kalkış:</strong> 
                                    <?php echo date('H:i', strtotime($sefer['departure_time'])); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-clock text-muted"></i>
                                    <strong>Varış:</strong> 
                                    <?php echo date('H:i', strtotime($sefer['arrival_time'])); ?>
                                </p>
                            </div>
                            
                            <?php if (!$is_past): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <small>Terminale en az 30 dakika önce geliniz.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-bus"></i> Otobüs Bilgileri
                            </h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-2">
                                        <i class="fas fa-building text-muted"></i>
                                        <strong>Firma:</strong> 
                                        <?php echo htmlspecialchars($sefer['company_name']); ?>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-2">
                                        <i class="fas fa-chair text-muted"></i>
                                        <strong>Boş Koltuk:</strong> 
                                        <span class="badge bg-<?php echo $available_seats > 10 ? 'success' : ($available_seats > 0 ? 'warning' : 'danger'); ?>">
                                            <?php echo $available_seats; ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-2">
                                        <i class="fas fa-users text-muted"></i>
                                        <strong>Toplam Koltuk:</strong> 
                                        <span class="badge bg-info"><?php echo $sefer['capacity']; ?></span>
                                    </p>
                                </div>
                            </div>
                            
                            
                            <div class="mt-3">
                                <label class="form-label"><strong>Koltuk Doluluk Durumu:</strong></label>
                                <div class="progress" style="height: 20px;">
                                    <?php
                                    $doluluk_orani = $sefer['capacity'] > 0 ? ($occupied_seats / $sefer['capacity']) * 100 : 0;
                                    $progress_class = $doluluk_orani < 50 ? 'bg-success' : ($doluluk_orani < 80 ? 'bg-warning' : 'bg-danger');
                                    ?>
                                    <div class="progress-bar <?php echo $progress_class; ?>" 
                                         style="width: <?php echo $doluluk_orani; ?>%">
                                        %<?php echo round($doluluk_orani); ?> dolu
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-muted"><?php echo $occupied_seats; ?> dolu koltuk</small>
                                    <small class="text-muted"><?php echo $available_seats; ?> boş koltuk</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($sefer['company_name']); ?> Hakkında
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p class="mb-2">
                                <i class="fas fa-star text-warning"></i>
                                <strong>4.8</strong> (256 Değerlendirme)
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-shield-alt text-success"></i>
                                Güvenli ve konforlu yolculuk
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-wifi text-info"></i>
                                Ücretsiz WiFi
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-tv text-primary"></i>
                                Kişisel Eğlence Sistemi
                            </p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="bg-light p-3 rounded">
                                <i class="fas fa-headset fa-2x text-primary mb-2"></i>
                                <h6>Müşteri Hizmetleri</h6>
                                <p class="mb-0">
                                    <i class="fas fa-phone"></i> 
                                    0850 123 45 67
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-ticket-alt"></i> Bilet Bilgileri
                    </h4>
                </div>
                <div class="card-body text-center">
                    
                    <h2 class="text-success mb-3">₺<?php echo number_format($sefer['price'], 2); ?></h2>
                    <p class="text-muted">Kişi Başı</p>
                    
                    
                    <div class="mb-4">
                        <i class="fas fa-chair fa-2x text-<?php echo $available_seats > 10 ? 'success' : ($available_seats > 0 ? 'warning' : 'danger'); ?>"></i>
                        <h5 class="mt-2"><?php echo $available_seats; ?> Boş Koltuk</h5>
                    </div>
                    
                    
                    <?php if ($is_past): ?>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-clock fa-2x mb-3"></i>
                            <h5>Geçmiş Sefer</h5>
                            <p class="mb-2">Bu seferin kalkış zamanı geçmiştir.</p>
                            <small>
                                Kalkış: <?php echo date('d.m.Y H:i', strtotime($sefer['departure_time'])); ?>
                            </small>
                        </div>
                        <a href="seferler.php" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Yeni Sefer Ara
                        </a>
                        
                    <?php else: ?>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['user_role'] == 'user'): ?>
                                
                                <?php if ($available_seats > 0): ?>
                                    <?php if ($user_balance >= $sefer['price']): ?>
                                        
                                        <a href="bilet-satin-al.php?sefer_id=<?php echo $sefer['id']; ?>" class="btn btn-success btn-lg w-100 mb-3">
                                            <i class="fas fa-ticket-alt"></i> Bilet Al
                                        </a>
                                        <div class="alert alert-info">
                                            <small>
                                                <i class="fas fa-wallet"></i>
                                                <strong>Mevcut Bakiyeniz:</strong> 
                                                ₺<?php echo number_format($user_balance, 2); ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <h6>Bakiyeniz Yetersiz!</h6>
                                            <p class="mb-2">
                                                <small>
                                                    Gerekli: <strong>₺<?php echo number_format($sefer['price'], 2); ?></strong><br>
                                                    Mevcut: <strong>₺<?php echo number_format($user_balance, 2); ?></strong>
                                                </small>
                                            </p>
                                            <a href="hesabim.php" class="btn btn-sm btn-outline-primary w-100">
                                                Bakiye Yükle
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    
                                    <div class="alert alert-danger">
                                        <i class="fas fa-times-circle"></i>
                                        <h6>Boş Koltuk Yok!</h6>
                                        <p class="mb-0">Bu sefer için boş koltuk kalmamıştır.</p>
                                    </div>
                                    <a href="seferler.php" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Yeni Sefer Ara
                                    </a>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <h6>Bilet Satın Alma</h6>
                                    <p class="mb-2">Bilet satın alma işlemi sadece yolcu kullanıcılar içindir.</p>
                                    <small>
                                        Rolünüz: <strong>
                                        <?php 
                                        switch($_SESSION['user_role']) {
                                            case 'admin': echo 'ADMİN'; break;
                                            case 'company': echo 'FİRMA'; break;
                                            default: echo 'KULLANICI';
                                        }
                                        ?>
                                        </strong>
                                    </small>
                                </div>
                                <a href="seferler.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-search"></i> Seferleri İncele
                                </a>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-user-clock"></i>
                                <h6>Giriş Yapmalısınız!</h6>
                                <p class="mb-3">Bilet almak için giriş yapın veya hesap oluşturun.</p>
                                <div class="d-grid gap-2">
                                    <a href="login.php" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> Giriş Yap
                                    </a>
                                    <a href="register.php" class="btn btn-outline-primary">
                                        <i class="fas fa-user-plus"></i> Kayıt Ol
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    
                    <hr class="my-4">
                    <div class="text-start">
                        <h6><i class="fas fa-info-circle text-primary"></i> Önemli Bilgiler:</h6>
                        <ul class="small text-muted ps-3">
                            <li>Bilet iptali için son saat: <?php echo date('H:i', strtotime($sefer['departure_time']) - 3600); ?></li>
                            <li>Yanınızda kimlik bulundurunuz</li>
                            <li>Terminale 30 dakika önce geliniz</li>
                            <li>18 yaş altı yolcular için veli izni gereklidir</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            
            <div class="card mt-3">
                <div class="card-body text-center">
                    <h6 class="mb-3">Hızlı İşlemler</h6>
                    <div class="d-grid gap-2">
                        <a href="seferler.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-search"></i> Yeni Sefer Ara
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-home"></i> Ana Sayfa
                        </a>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'user'): ?>
                            <a href="biletlerim.php" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-ticket-alt"></i> Biletlerim
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sticky-top {
    position: -webkit-sticky;
    position: sticky;
    z-index: 1000;
}

.card-header.bg-primary {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

.card-header.bg-success {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}

.progress {
    border-radius: 10px;
}

.bg-success.text-white {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bg-danger.text-white {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.alert {
    border-left: 4px solid;
}

.alert-warning {
    border-left-color: #ffc107;
}

.alert-danger {
    border-left-color: #dc3545;
}

.alert-info {
    border-left-color: #0dcaf0;
}

.alert-success {
    border-left-color: #198754;
}
</style>

<?php include 'includes/footer.php'; ?>