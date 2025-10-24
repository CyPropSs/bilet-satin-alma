<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: login.php');
    exit();
}

try {
    $stmt = $db->prepare("SELECT COUNT(*) as total_users FROM user");
    $stmt->execute();
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    $stmt = $db->prepare("SELECT COUNT(*) as total_companies FROM bus_company");
    $stmt->execute();
    $total_companies = $stmt->fetch(PDO::FETCH_ASSOC)['total_companies'];

    $stmt = $db->prepare("SELECT COUNT(*) as total_trips FROM trips");
    $stmt->execute();
    $total_trips = $stmt->fetch(PDO::FETCH_ASSOC)['total_trips'];

    $stmt = $db->prepare("SELECT COUNT(*) as total_tickets FROM tickets");
    $stmt->execute();
    $total_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['total_tickets'];

    $stmt = $db->prepare("SELECT SUM(total_price) as total_revenue FROM tickets WHERE status = 'active'");
    $stmt->execute();
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

    $stmt = $db->prepare("
        SELECT t.*, u.full_name, tr.departure_city, tr.destination_city, bc.name as company_name
        FROM tickets t
        JOIN user u ON t.user_id = u.id
        JOIN trips tr ON t.trip_id = tr.id
        JOIN bus_company bc ON tr.company_id = bc.id
        ORDER BY t.created_at DESC LIMIT 5
    ");
    $stmt->execute();
    $recent_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
            <li class="breadcrumb-item active">Admin Paneli</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">
                <i class="fas fa-tachometer-alt"></i> Admin Paneli
            </h1>
        </div>
    </div>

    
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam Kullanıcı</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Toplam Firma</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_companies; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Toplam Sefer</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_trips; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-route fa-2x text-gray-300"></i>
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
                                Toplam İşlem Hacmi</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₺<?php echo number_format($total_revenue, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-ticket-alt"></i> Son Satın Alınan Biletler
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Bilet ID</th>
                                    <th>Yolcu</th>
                                    <th>Güzergah</th>
                                    <th>Firma</th>
                                    <th>Tutar</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_tickets as $ticket): ?>
                                <tr>
                                    <td><?php echo substr($ticket['id'], 0, 8); ?>...</td>
                                    <td><?php echo htmlspecialchars($ticket['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['departure_city']); ?> → <?php echo htmlspecialchars($ticket['destination_city']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['company_name']); ?></td>
                                    <td>₺<?php echo number_format($ticket['total_price'], 2); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-rocket"></i> Hızlı Erişim
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="admin-firma.php" class="btn btn-primary btn-block">
                            <i class="fas fa-bus"></i> Firma Yönetimi
                        </a>
                        <a href="admin-firma-admin.php" class="btn btn-success btn-block">
                            <i class="fas fa-user-tie"></i> Firma Admin Atama
                        </a>
                        <a href="admin-kupon.php" class="btn btn-info btn-block">
                            <i class="fas fa-tags"></i> Kupon Yönetimi
                        </a>
                        <a href="admin-bakiye.php" class="btn btn-warning btn-block">
                            <i class="fas fa-wallet"></i> Bakiye Yönetimi
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Sistem Bilgileri
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <img class="img-fluid px-3 px-sm-4 mt-3 mb-4" style="width: 25rem;" 
                             src="assets/img/admin-dashboard.svg" alt="Admin Dashboard">
                    </div>
                    <p>Toplam <strong><?php echo $total_tickets; ?></strong> bilet satılmış.</p>
                    <p>Toplam <strong><?php echo $total_trips; ?></strong> aktif sefer bulunuyor.</p>
                    <p class="mb-0">Sistem sağlıklı çalışıyor.</p>
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

.border-left-primary {
    border-left: 4px solid #4e73df !important;
}

.border-left-success {
    border-left: 4px solid #1cc88a !important;
}

.border-left-info {
    border-left: 4px solid #36b9cc !important;
}

.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}

.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
}

.text-xs {
    font-size: 0.7rem;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.text-gray-300 {
    color: #dddfeb !important;
}
</style>

<?php include 'includes/footer.php'; ?>