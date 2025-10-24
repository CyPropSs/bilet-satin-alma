<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$bilet_id = $_GET['id'] ?? 0;
$print_mode = isset($_GET['print']);

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
        WHERE t.id = ? AND t.user_id = ? 
        AND t.status IN ('active', 'expired', 'cancelled', 'used')
    ");
    $stmt->execute([$bilet_id, $_SESSION['user_id']]);
    $bilet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bilet) {
        die("
            <div class='container mt-4'>
                <div class='alert alert-danger text-center'>
                    <i class='fas fa-exclamation-triangle fa-2x mb-3'></i>
                    <h4>Bilet bulunamadı!</h4>
                    <p>Bu bilete erişim izniniz yok veya bilet sistemden kaldırılmış.</p>
                    <a href='biletlerim.php' class='btn btn-primary'>Biletlerime Dön</a>
                </div>
            </div>
        ");
    }
    
    $status_config = [
        'active' => ['success', 'check-circle', 'Aktif'],
        'expired' => ['secondary', 'clock', 'Süresi Doldu'],
        'cancelled' => ['danger', 'times-circle', 'İptal Edildi'],
        'used' => ['info', 'check-double', 'Kullanıldı']
    ];
    
    $status_info = $status_config[$bilet['status']] ?? $status_config['active'];
    
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}

if (!$print_mode) {
    include 'includes/header.php';
}
?>

<?php if ($print_mode): ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Yazdır - <?php echo htmlspecialchars($bilet['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; }
            .card { border: 2px solid #000 !important; }
            .bg-success { background-color: #28a745 !important; }
            .bg-secondary { background-color: #6c757d !important; }
            .bg-danger { background-color: #dc3545 !important; }
            .bg-info { background-color: #17a2b8 !important; }
        }
        .bilet-border { border: 3px solid #28a745; }
        .bilet-expired { border: 3px solid #6c757d; }
        .bilet-cancelled { border: 3px solid #dc3545; }
        .bilet-used { border: 3px solid #17a2b8; }
    </style>
</head>
<body>
<?php endif; ?>

<div class="container mt-4">
    <?php if (!$print_mode): ?>
    <nav aria-label="breadcrumb" class="no-print">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
            <li class="breadcrumb-item"><a href="biletlerim.php">Biletlerim</a></li>
            <li class="breadcrumb-item active">Bilet Detayı</li>
        </ol>
    </nav>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <?php
            $border_class = [
                'active' => 'bilet-border',
                'expired' => 'bilet-expired',
                'cancelled' => 'bilet-cancelled',
                'used' => 'bilet-used'
            ];
            $bilet_border = $border_class[$bilet['status']] ?? 'bilet-border';
            ?>
            
            <div class="card <?php echo $bilet_border; ?>">
                <div class="card-header bg-<?php echo $status_info[0]; ?> text-white text-center py-3">
                    <h3 class="mb-0">
                        <i class="fas fa-ticket-alt"></i> 
                        OTOBÜS BİLETİ
                        <small class="float-end opacity-75">
                            <i class="fas fa-<?php echo $status_info[1]; ?>"></i>
                            <?php echo $status_info[2]; ?>
                        </small>
                    </h3>
                </div>
                <div class="card-body">
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-primary">
                                <i class="fas fa-user"></i> Yolcu Bilgileri
                            </h5>
                            <p class="mb-1"><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                            <p class="mb-0"><strong>Koltuk No:</strong> <span class="badge bg-primary"><?php echo $bilet['seat_number']; ?></span></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h5 class="text-success">
                                <i class="fas fa-bus"></i> Firma Bilgileri
                            </h5>
                            <p class="mb-1"><strong>Firma:</strong> <?php echo htmlspecialchars($bilet['company_name']); ?></p>
                            <p class="mb-1"><strong>Bilet No:</strong> <?php echo substr($bilet['id'], 0, 12); ?>...</p>
                            <p class="mb-0">
                                <strong>Durum:</strong> 
                                <span class="badge bg-<?php echo $status_info[0]; ?>">
                                    <?php echo $status_info[2]; ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="p-4 bg-light rounded">
                                <div class="row text-center">
                                    <div class="col-md-4 border-end">
                                        <h6 class="text-muted">KALKIŞ</h6>
                                        <h4 class="text-primary"><?php echo htmlspecialchars($bilet['departure_city']); ?></h4>
                                        <p class="mb-1"><strong><?php echo date('d.m.Y', strtotime($bilet['departure_time'])); ?></strong></p>
                                        <p class="mb-0"><strong><?php echo date('H:i', strtotime($bilet['departure_time'])); ?></strong></p>
                                    </div>
                                    <div class="col-md-4 border-end">
                                        <h6 class="text-muted">FİRMA</h6>
                                        <h4 class="text-success"><?php echo htmlspecialchars($bilet['company_name']); ?></h4>
                                        <i class="fas fa-bus fa-2x text-muted mt-2"></i>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-muted">VARIŞ</h6>
                                        <h4 class="text-primary"><?php echo htmlspecialchars($bilet['destination_city']); ?></h4>
                                        <p class="mb-1"><strong><?php echo date('d.m.Y', strtotime($bilet['arrival_time'])); ?></strong></p>
                                        <p class="mb-0"><strong><?php echo date('H:i', strtotime($bilet['arrival_time'])); ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Ödeme Bilgileri</h6>
                            <p class="mb-1"><strong>Sefer Ücreti:</strong> ₺<?php echo number_format($bilet['sefer_fiyati'], 2); ?></p>
                            <p class="mb-1"><strong>Toplam Ödenen:</strong> <span class="text-success fw-bold">₺<?php echo number_format($bilet['total_price'], 2); ?></span></p>
                            <p class="mb-0"><strong>Satın Alma:</strong> <?php echo date('d.m.Y H:i', strtotime($bilet['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <h6 class="text-muted">Yolculuk Bilgileri</h6>
                            <?php
                            $departure = strtotime($bilet['departure_time']);
                            $arrival = strtotime($bilet['arrival_time']);
                            $duration = $arrival - $departure;
                            $hours = floor($duration / 3600);
                            $minutes = floor(($duration % 3600) / 60);
                            ?>
                            <p class="mb-1"><strong>Yolculuk Süresi:</strong> <?php echo $hours; ?> saat <?php echo $minutes; ?> dakika</p>
                            <p class="mb-1"><strong>Koltuk Tipi:</strong> Standart</p>
                            <p class="mb-0"><strong>Bilet Türü:</strong> Elektronik</p>
                        </div>
                    </div>

                    
                    <?php if ($bilet['status'] == 'expired'): ?>
                        <div class="alert alert-warning mt-4 mb-0">
                            <i class="fas fa-clock"></i>
                            <strong>Bu biletin kalkış zamanı geçmiştir.</strong> 
                            Artık kullanılamaz.
                        </div>
                    <?php elseif ($bilet['status'] == 'cancelled'): ?>
                        <div class="alert alert-info mt-4 mb-0">
                            <i class="fas fa-info-circle"></i>
                            <strong>Bu bilet iptal edilmiştir.</strong> 
                            Ücreti bakiyenize iade edilmiştir.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-4 mb-0">
                            <small>
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Önemli:</strong> 
                                Biletin yanında kimlik belgenizi bulundurunuz. 
                                En az 30 dakika önce terminalde hazır bulununuz.
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <?php if (!$print_mode): ?>
            <div class="row mt-4 no-print">
                <div class="col-12 text-center">
                    <?php if ($bilet['status'] == 'active'): ?>
                        <button class="btn btn-success" onclick="window.print()">
                            <i class="fas fa-print"></i> Bileti Yazdır
                        </button>
                    <?php endif; ?>
                    
                    <a href="biletlerim.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Biletlerime Dön
                    </a>
                    
                    <?php if ($bilet['status'] == 'active'): ?>
                        <a href="seferler.php" class="btn btn-primary">
                            <i class="fas fa-bus"></i> Yeni Sefer Ara
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($print_mode): ?>
<script>
window.onload = function() {
    window.print();
}
</script>
</body>
</html>
<?php else: ?>
    <?php include 'includes/footer.php'; ?>
<?php endif; ?>