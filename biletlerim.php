<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    $stmt = $db->prepare("
        SELECT 
            t.*,
            tr.departure_city,
            tr.destination_city,
            tr.departure_time,
            tr.arrival_time,
            tr.price as sefer_fiyati,
            bc.name as company_name,
            bs.seat_number
        FROM tickets t
        LEFT JOIN trips tr ON t.trip_id = tr.id
        LEFT JOIN bus_company bc ON tr.company_id = bc.id
        LEFT JOIN booked_seats bs ON bs.ticket_id = t.id
        WHERE t.user_id = ? 
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $biletler = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $biletler = [];
    $error = "Biletler yüklenirken hata oluştu: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">
        <i class="fas fa-ticket-alt"></i> Biletlerim
        <?php if (!empty($biletler)): ?>
            <span class="badge bg-primary"><?php echo count($biletler); ?> bilet</span>
        <?php endif; ?>
    </h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($biletler)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Henüz biletiniz bulunmuyor</h4>
                <p class="text-muted mb-4">İlk biletinizi almak için seferlere göz atın.</p>
                <a href="seferler.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-bus"></i> Seferleri Görüntüle
                </a>
            </div>
        </div>
    <?php else: ?>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-filter"></i> Biletleri Filtrele
                </h5>
                <div class="btn-group" role="group">
                    <a href="?filter=all" class="btn btn-outline-primary <?php echo (!isset($_GET['filter']) || $_GET['filter'] == 'all') ? 'active' : ''; ?>">
                        Tümü <span class="badge bg-secondary"><?php echo count($biletler); ?></span>
                    </a>
                    <a href="?filter=active" class="btn btn-outline-success <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'active') ? 'active' : ''; ?>">
                        Aktif <span class="badge bg-success"><?php echo count(array_filter($biletler, function($b) { return $b['status'] == 'active'; })); ?></span>
                    </a>
                    <a href="?filter=expired" class="btn btn-outline-secondary <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'expired') ? 'active' : ''; ?>">
                        Süresi Dolan <span class="badge bg-secondary"><?php echo count(array_filter($biletler, function($b) { return $b['status'] == 'expired'; })); ?></span>
                    </a>
                    <a href="?filter=cancelled" class="btn btn-outline-danger <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'cancelled') ? 'active' : ''; ?>">
                        İptal Edilen <span class="badge bg-danger"><?php echo count(array_filter($biletler, function($b) { return $b['status'] == 'cancelled'; })); ?></span>
                    </a>
                </div>
            </div>
        </div>

        <?php
        $filtre = $_GET['filter'] ?? 'all';
        $filtrelenmis_biletler = $biletler;
        
        if ($filtre != 'all') {
            $filtrelenmis_biletler = array_filter($biletler, function($bilet) use ($filtre) {
                return $bilet['status'] == $filtre;
            });
        }
        ?>

        <div class="row">
            <?php foreach ($filtrelenmis_biletler as $bilet): 
                $departure_time = strtotime($bilet['departure_time']);
                $current_time = time();
                $time_diff = $departure_time - $current_time;
                $can_cancel = $time_diff > 3600 && $bilet['status'] == 'active'; // 1 saatten fazla varsa ve aktifse
                $hours_remaining = $can_cancel ? floor($time_diff / 3600) : 0;
                
                $status_config = [
                    'active' => ['success', 'check-circle', 'Aktif'],
                    'expired' => ['secondary', 'clock', 'Süresi Doldu'],
                    'cancelled' => ['danger', 'times-circle', 'İptal Edildi'],
                    'used' => ['info', 'check-double', 'Kullanıldı']
                ];
                $status_info = $status_config[$bilet['status']] ?? $status_config['active'];
            ?>
            <div class="col-md-6 mb-4">
                <div class="card ticket h-100 border-<?php echo $status_info[0]; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center bg-<?php echo $status_info[0]; ?> text-white">
                        <span class="fw-bold">
                            <i class="fas fa-route"></i> 
                            <?php echo htmlspecialchars($bilet['departure_city']); ?> → 
                            <?php echo htmlspecialchars($bilet['destination_city']); ?>
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-<?php echo $status_info[1]; ?>"></i> <?php echo $status_info[2]; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-8">
                                <h5 class="card-title text-primary">
                                    <i class="fas fa-bus"></i> 
                                    <?php echo htmlspecialchars($bilet['company_name']); ?>
                                </h5>
                                <p class="card-text mb-2">
                                    <i class="fas fa-calendar-alt text-muted"></i> 
                                    <strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($bilet['departure_time'])); ?>
                                </p>
                                <p class="card-text mb-2">
                                    <i class="fas fa-clock text-muted"></i> 
                                    <strong>Saat:</strong> <?php echo date('H:i', strtotime($bilet['departure_time'])); ?> - <?php echo date('H:i', strtotime($bilet['arrival_time'])); ?>
                                </p>
                                <p class="card-text mb-2">
                                    <i class="fas fa-chair text-muted"></i> 
                                    <strong>Koltuk:</strong> <?php echo $bilet['seat_number'] ?? 'Belirtilmemiş'; ?>
                                </p>
                                <p class="card-text mb-0">
                                    <i class="fas fa-receipt text-muted"></i> 
                                    <strong>Toplam:</strong> <span class="text-success fw-bold">₺<?php echo number_format($bilet['total_price'], 2); ?></span>
                                </p>
                            </div>
                            <div class="col-4 text-end">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Bilet No:</small>
                                    <small class="text-muted"><?php echo substr($bilet['id'], 0, 8); ?>...</small>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted d-block">Satın Alma:</small>
                                    <small class="text-muted"><?php echo date('d.m.Y', strtotime($bilet['created_at'])); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        
                        <?php if ($bilet['status'] == 'active' || $bilet['status'] == 'expired'): ?>
                        <div class="alert alert-<?php echo $time_diff > 0 ? 'info' : 'warning'; ?> py-2">
                            <small>
                                <i class="fas fa-info-circle"></i> 
                                <strong>Kalkış:</strong> 
                                <?php echo date('d.m.Y H:i', $departure_time); ?> 
                                (<?php 
                                if ($time_diff > 0) {
                                    $hours = floor($time_diff / 3600);
                                    $minutes = floor(($time_diff % 3600) / 60);
                                    echo $hours . " saat " . $minutes . " dakika sonra";
                                } else {
                                    echo 'Kalkış zamanı geçti';
                                }
                                ?>)
                            </small>
                        </div>
                        <?php endif; ?>

                        
                        <?php if (!$can_cancel && $bilet['status'] == 'active' && $time_diff > 0): ?>
                            <div class="alert alert-warning py-2">
                                <small>
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Uyarı:</strong> Kalkış saatine 1 saatten az kaldığı için bilet iptal edilemez.
                                </small>
                            </div>
                        <?php endif; ?>

                        
                        <?php if ($bilet['status'] == 'cancelled'): ?>
                            <div class="alert alert-info py-2">
                                <small>
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Bilet İptal Edildi:</strong> 
                                    ₺<?php echo number_format($bilet['total_price'], 2); ?> bakiyenize iade edildi.
                                </small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-barcode"></i>
                                    ID: <?php echo substr($bilet['id'], 0, 12); ?>...
                                </small>
                            </div>
                            
                            <div class="btn-group">
                                <a href="bilet-detay.php?id=<?php echo $bilet['id']; ?>" 
                                   class="btn btn-primary btn-sm"
                                   title="Bilet Detayları">
                                    <i class="fas fa-eye"></i> Detay
                                </a>
                                
                                <?php if ($can_cancel): ?>
                                    <button class="btn btn-danger btn-sm" 
                                            onclick="cancelTicket('<?php echo $bilet['id']; ?>', '<?php echo $bilet['departure_city']; ?> → <?php echo $bilet['destination_city']; ?>', <?php echo $bilet['total_price']; ?>)"
                                            title="Bilet İptal">
                                        <i class="fas fa-times"></i> İptal Et
                                    </button>
                                <?php elseif ($bilet['status'] == 'active'): ?>
                                    <button class="btn btn-secondary btn-sm" disabled
                                            title="Kalkış saatine 1 saatten az kaldığı için iptal edilemez">
                                        <i class="fas fa-ban"></i> İptal Edilemez
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($bilet['status'] == 'active'): ?>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="printTicket('<?php echo $bilet['id']; ?>')">
                                        <i class="fas fa-print"></i> Yazdır
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-chart-bar"></i> Bilet Özetim
                        </h5>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="border rounded p-3 bg-light">
                                    <h3 class="text-primary mb-0"><?php echo count($biletler); ?></h3>
                                    <small class="text-muted">Toplam Bilet</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 bg-light">
                                    <h3 class="text-success mb-0">
                                        <?php 
                                        $aktif_biletler = array_filter($biletler, function($b) { 
                                            return $b['status'] == 'active'; 
                                        });
                                        echo count($aktif_biletler);
                                        ?>
                                    </h3>
                                    <small class="text-muted">Aktif Biletler</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 bg-light">
                                    <h3 class="text-warning mb-0">
                                        <?php 
                                        $iptal_biletler = array_filter($biletler, function($b) { 
                                            return $b['status'] == 'cancelled'; 
                                        });
                                        echo count($iptal_biletler);
                                        ?>
                                    </h3>
                                    <small class="text-muted">İptal Edilen</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 bg-light">
                                    <h3 class="text-info mb-0">
                                        ₺<?php 
                                        $toplam_harcama = array_sum(array_column($biletler, 'total_price'));
                                        echo number_format($toplam_harcama, 2);
                                        ?>
                                    </h3>
                                    <small class="text-muted">Toplam Harcama</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function printTicket(ticketId) {
    window.open('bilet-detay.php?id=' + ticketId + '&print=1', '_blank');
}

function cancelTicket(ticketId, route, price) {
    if (confirm('BİLET İPTAL ONAYI\n\n' +
                'Sefer: ' + route + '\n' +
                'İade Edilecek Tutar: ₺' + parseFloat(price).toFixed(2) + '\n\n' +
                'Bu bileti iptal etmek istediğinizden emin misiniz?\n\n' +
                '✓ Bilet ücreti bakiyenize iade edilecek\n' +
                '✓ İşlem geri alınamaz')) {
        
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İptal Ediliyor...';
        btn.disabled = true;
        
        fetch('bilet-iptal.php?id=' + ticketId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage('Bilet başarıyla iptal edildi! ₺' + parseFloat(price).toFixed(2) + ' bakiyenize iade edildi.');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Bilet iptal edilemedi: ' + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                alert('Bilet iptal sırasında hata oluştu: ' + error);
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }
}

function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
}

document.addEventListener('DOMContentLoaded', function() {
    const tickets = document.querySelectorAll('.ticket');
    tickets.forEach(ticket => {
        ticket.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 8px 16px rgba(0,0,0,0.1)';
        });
        
        ticket.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        });
    });
});
</script>

<style>
.ticket {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    border-left: 4px solid #28a745;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ticket:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.ticket.border-secondary {
    border-left-color: #6c757d;
}

.ticket.border-danger {
    border-left-color: #dc3545;
}

.ticket.border-info {
    border-left-color: #17a2b8;
}

.card-header.bg-success {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}

.card-header.bg-secondary {
    background: linear-gradient(135deg, #6c757d, #5a6268) !important;
}

.card-header.bg-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
}

.card-header.bg-info {
    background: linear-gradient(135deg, #17a2b8, #138496) !important;
}

.btn-group .btn {
    margin-left: 5px;
    border-radius: 5px;
}

.card-text {
    font-size: 0.9rem;
}

.badge {
    font-size: 0.7rem;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

.alert-warning {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

.btn-group .btn-sm {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

.position-fixed {
    min-width: 300px;
}

.btn-group .btn-outline-primary.active {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}
</style>

<?php include 'includes/footer.php'; ?>