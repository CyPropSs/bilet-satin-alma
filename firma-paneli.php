<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    header('Location: login.php');
    exit();
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
$form_errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];

unset($_SESSION['success'], $_SESSION['error'], $_SESSION['form_errors'], $_SESSION['form_data']);

try {
    $user_stmt = $db->prepare("SELECT company_id FROM user WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_data || empty($user_data['company_id'])) {
        die("<div class='alert alert-danger'>Firma atamanız bulunamadı!</div>");
    }
    
    $_SESSION['company_id'] = $user_data['company_id'];
    
    $firma_stmt = $db->prepare("SELECT * FROM bus_company WHERE id = ?");
    $firma_stmt->execute([$_SESSION['company_id']]);
    $firma = $firma_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$firma) {
        die("<div class='alert alert-danger'>Firma bilgileriniz bulunamadı!</div>");
    }
    
} catch (PDOException $e) {
    die("<div class='alert alert-danger'>Hata: " . $e->getMessage() . "</div>");
}

try {
    $stmt = $db->prepare("
        SELECT t.*, 
               COUNT(tk.id) as satilan_bilet,
               (SELECT COUNT(*) FROM booked_seats bs 
                JOIN tickets tkt ON bs.ticket_id = tkt.id 
                WHERE tkt.trip_id = t.id AND tkt.status = 'active') as dolu_koltuk
        FROM trips t
        LEFT JOIN tickets tk ON t.id = tk.trip_id AND tk.status = 'active'
        WHERE t.company_id = ?
        GROUP BY t.id
        ORDER BY t.departure_time DESC
    ");
    $stmt->execute([$_SESSION['company_id']]);
    $seferler = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $seferler = [];
    $error = "Seferler yüklenirken hata oluştu: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
            <li class="breadcrumb-item active">Firma Paneli</li>
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
        <div class="col-md-12">
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($firma['name']); ?>
                    </h4>
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-chart-line"></i> Firma Paneli
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong><i class="fas fa-user-tie"></i> Yetkili:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                                    <p class="mb-2"><strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong><i class="fas fa-id-card"></i> Firma ID:</strong> <code><?php echo substr($firma['id'], 0, 8); ?>...</code></p>
                                    <p class="mb-0"><strong><i class="fas fa-calendar"></i> Üyelik:</strong> <?php echo date('d.m.Y', strtotime($firma['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="bg-light p-3 rounded">
                                <h6 class="text-muted mb-3">İstatistikler</h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 class="text-primary mb-0"><?php echo count($seferler); ?></h4>
                                        <small class="text-muted">Toplam Sefer</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-success mb-0">
                                            <?php
                                            $aktif_seferler = array_filter($seferler, function($sefer) {
                                                return strtotime($sefer['departure_time']) > time();
                                            });
                                            echo count($aktif_seferler);
                                            ?>
                                        </h4>
                                        <small class="text-muted">Aktif</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-info mb-0">
                                            <?php
                                            $toplam_bilet = array_sum(array_column($seferler, 'satilan_bilet'));
                                            echo $toplam_bilet;
                                            ?>
                                        </h4>
                                        <small class="text-muted">Satılan</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-plus-circle fa-2x mb-2"></i>
                            <h5>Yeni Sefer Ekle</h5>
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#seferEkleModal">
                                Sefer Oluştur
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-tag fa-2x mb-2"></i>
                            <h5>Kupon Yönetimi</h5>
                            <a href="firma-kupon.php" class="btn btn-light btn-sm">Kuponları Yönet</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i>
                            <h5>Raporlar</h5>
                            <button class="btn btn-light btn-sm" disabled>Yakında</button>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-route"></i> Sefer Yönetimi
                    </h4>
                    <div>
                        <span class="badge bg-primary"><?php echo count($seferler); ?> sefer</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($seferler)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-route fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Henüz sefer bulunmuyor</h5>
                            <p class="text-muted mb-4">İlk seferinizi oluşturarak başlayın.</p>
                            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#seferEkleModal">
                                <i class="fas fa-plus"></i> İlk Seferi Ekle
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Güzergah</th>
                                        <th>Tarih & Saat</th>
                                        <th>Fiyat</th>
                                        <th>Koltuk</th>
                                        <th>Satış</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($seferler as $sefer): 
                                        $departure_time = strtotime($sefer['departure_time']);
                                        $is_active = $departure_time > time();
                                        $doluluk_orani = $sefer['capacity'] > 0 ? round(($sefer['dolu_koltuk'] / $sefer['capacity']) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($sefer['departure_city']); ?> → <?php echo htmlspecialchars($sefer['destination_city']); ?></strong>
                                            <br>
                                            <small class="text-muted">ID: <?php echo substr($sefer['id'], 0, 8); ?>...</small>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y', $departure_time); ?><br>
                                            <small class="text-muted"><?php echo date('H:i', $departure_time); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-success fs-6">₺<?php echo number_format($sefer['price'], 2); ?></span>
                                        </td>
                                        <td>
                                            <div class="progress mb-1" style="height: 8px;">
                                                <div class="progress-bar <?php echo $doluluk_orani > 80 ? 'bg-danger' : ($doluluk_orani > 50 ? 'bg-warning' : 'bg-success'); ?>" 
                                                     style="width: <?php echo $doluluk_orani; ?>%">
                                                </div>
                                            </div>
                                            <small><?php echo $sefer['dolu_koltuk']; ?>/<?php echo $sefer['capacity']; ?> (%<?php echo $doluluk_orani; ?>)</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $sefer['satilan_bilet']; ?> bilet</span>
                                        </td>
                                        <td>
                                            <?php if ($is_active): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Tamamlandı</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-primary btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#seferDuzenleModal"
                                                        data-sefer-id="<?php echo $sefer['id']; ?>"
                                                        data-departure-city="<?php echo htmlspecialchars($sefer['departure_city']); ?>"
                                                        data-destination-city="<?php echo htmlspecialchars($sefer['destination_city']); ?>"
                                                        data-departure-time="<?php echo $sefer['departure_time']; ?>"
                                                        data-arrival-time="<?php echo $sefer['arrival_time']; ?>"
                                                        data-price="<?php echo $sefer['price']; ?>"
                                                        data-capacity="<?php echo $sefer['capacity']; ?>"
                                                        title="Düzenle">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <?php if ($sefer['satilan_bilet'] == 0): ?>
                                                    <button class="btn btn-danger btn-sm" 
                                                            onclick="deleteSefer('<?php echo $sefer['id']; ?>', '<?php echo $sefer['departure_city']; ?> → <?php echo $sefer['destination_city']; ?>')"
                                                            title="Sil">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-sm" disabled
                                                            title="Satılan bilet olduğu için silinemez">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
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


<div class="modal fade" id="seferEkleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Sefer Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="firma-sefer-ekle.php" method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kalkış Şehri <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="departure_city" 
                                       value="<?php echo htmlspecialchars($form_data['departure_city'] ?? ''); ?>" 
                                       required placeholder="Örn: İstanbul">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Varış Şehri <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="destination_city" 
                                       value="<?php echo htmlspecialchars($form_data['destination_city'] ?? ''); ?>" 
                                       required placeholder="Örn: Ankara">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kalkış Zamanı <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" name="departure_time" 
                                       value="<?php echo htmlspecialchars($form_data['departure_time'] ?? ''); ?>" 
                                       min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Varış Zamanı <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" name="arrival_time" 
                                       value="<?php echo htmlspecialchars($form_data['arrival_time'] ?? ''); ?>" 
                                       min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fiyat (₺) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₺</span>
                                    <input type="number" class="form-control" name="price" 
                                           value="<?php echo htmlspecialchars($form_data['price'] ?? ''); ?>" 
                                           step="0.01" min="1" required placeholder="Örn: 150.00">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Koltuk Kapasitesi <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="capacity" 
                                       value="<?php echo htmlspecialchars($form_data['capacity'] ?? '45'); ?>" 
                                       min="1" max="60" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Seferi Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="seferDuzenleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seferi Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="firma-sefer-duzenle.php" method="POST">
                <input type="hidden" name="sefer_id" id="editSeferId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kalkış Şehri</label>
                                <input type="text" class="form-control" name="departure_city" id="editDepartureCity" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Varış Şehri</label>
                                <input type="text" class="form-control" name="destination_city" id="editDestinationCity" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kalkış Zamanı</label>
                                <input type="datetime-local" class="form-control" name="departure_time" id="editDepartureTime" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Varış Zamanı</label>
                                <input type="datetime-local" class="form-control" name="arrival_time" id="editArrivalTime" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fiyat (₺)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₺</span>
                                    <input type="number" class="form-control" name="price" id="editPrice" step="0.01" min="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Koltuk Kapasitesi</label>
                                <input type="number" class="form-control" name="capacity" id="editCapacity" min="1" max="60" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const seferDuzenleModal = document.getElementById('seferDuzenleModal');
    
    seferDuzenleModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        
        document.getElementById('editSeferId').value = button.getAttribute('data-sefer-id');
        document.getElementById('editDepartureCity').value = button.getAttribute('data-departure-city');
        document.getElementById('editDestinationCity').value = button.getAttribute('data-destination-city');
        document.getElementById('editDepartureTime').value = formatDateTime(button.getAttribute('data-departure-time'));
        document.getElementById('editArrivalTime').value = formatDateTime(button.getAttribute('data-arrival-time'));
        document.getElementById('editPrice').value = button.getAttribute('data-price');
        document.getElementById('editCapacity').value = button.getAttribute('data-capacity');
    });
    
    function formatDateTime(datetime) {
        const date = new Date(datetime);
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 16);
    }
});

function deleteSefer(seferId, seferBilgisi) {
    if (confirm('"' + seferBilgisi + '" seferini silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) {
        window.location.href = 'firma-sefer-sil.php?id=' + seferId;
    }
}
</script>

<style>
.card-header.bg-primary {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}

.table th {
    background-color: #343a40;
    color: white;
}

.btn-group .btn {
    margin: 1px;
}

.progress {
    background-color: #e9ecef;
}

.badge.fs-6 {
    font-size: 0.9rem !important;
}

.card.bg-success, .card.bg-info, .card.bg-warning {
    border: none;
    transition: transform 0.2s;
}

.card.bg-success:hover, .card.bg-info:hover, .card.bg-warning:hover {
    transform: translateY(-5px);
}
</style>

<?php include 'includes/footer.php'; ?>