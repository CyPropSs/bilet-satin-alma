<?php include 'includes/header.php'; 
include 'includes/config.php';

try {
    $current_datetime = date('Y-m-d H:i:s');
    $sql = "
        SELECT t.*, c.name as company_name 
        FROM trips t 
        LEFT JOIN bus_company c ON t.company_id = c.id 
        WHERE t.departure_time > ?
    ";
    
    $params = [$current_datetime];
    
    if (isset($_GET['from']) && !empty($_GET['from'])) {
        $sql .= " AND t.departure_city = ?";
        $params[] = $_GET['from'];
    }
    
    if (isset($_GET['to']) && !empty($_GET['to'])) {
        $sql .= " AND t.destination_city = ?";
        $params[] = $_GET['to'];
    }
    
    if (isset($_GET['date']) && !empty($_GET['date'])) {
        $sql .= " AND DATE(t.departure_time) = ?";
        $params[] = $_GET['date'];
    }
    
    $sql .= " ORDER BY t.departure_time ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $seferler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $seferler = [];
    $error = "Seferler yüklenirken hata oluştu";
}
?>

<div class="container mt-4">
    <h2 class="mb-4">Mevcut Seferler</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="seferler.php">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Nereden</label>
                        <select class="form-select" name="from">
                            <option value="">Tüm Şehirler</option>
                            <option value="İstanbul" <?php echo (isset($_GET['from']) && $_GET['from'] == 'İstanbul') ? 'selected' : ''; ?>>İstanbul</option>
                            <option value="Ankara" <?php echo (isset($_GET['from']) && $_GET['from'] == 'Ankara') ? 'selected' : ''; ?>>Ankara</option>
                            <option value="İzmir" <?php echo (isset($_GET['from']) && $_GET['from'] == 'İzmir') ? 'selected' : ''; ?>>İzmir</option>
                            <option value="Antalya" <?php echo (isset($_GET['from']) && $_GET['from'] == 'Antalya') ? 'selected' : ''; ?>>Antalya</option>
                            <option value="Bursa" <?php echo (isset($_GET['from']) && $_GET['from'] == 'Bursa') ? 'selected' : ''; ?>>Bursa</option>
                            <option value="Trabzon" <?php echo (isset($_GET['from']) && $_GET['from'] == 'Trabzon') ? 'selected' : ''; ?>>Trabzon</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nereye</label>
                        <select class="form-select" name="to">
                            <option value="">Tüm Şehirler</option>
                            <option value="İstanbul" <?php echo (isset($_GET['to']) && $_GET['to'] == 'İstanbul') ? 'selected' : ''; ?>>İstanbul</option>
                            <option value="Ankara" <?php echo (isset($_GET['to']) && $_GET['to'] == 'Ankara') ? 'selected' : ''; ?>>Ankara</option>
                            <option value="İzmir" <?php echo (isset($_GET['to']) && $_GET['to'] == 'İzmir') ? 'selected' : ''; ?>>İzmir</option>
                            <option value="Antalya" <?php echo (isset($_GET['to']) && $_GET['to'] == 'Antalya') ? 'selected' : ''; ?>>Antalya</option>
                            <option value="Bursa" <?php echo (isset($_GET['to']) && $_GET['to'] == 'Bursa') ? 'selected' : ''; ?>>Bursa</option>
                            <option value="Trabzon" <?php echo (isset($_GET['to']) && $_GET['to'] == 'Trabzon') ? 'selected' : ''; ?>>Trabzon</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tarih</label>
                        <input type="date" class="form-control" name="date" 
                               value="<?php echo $_GET['date'] ?? ''; ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrele</button>
                        <?php if (isset($_GET['from']) || isset($_GET['to']) || isset($_GET['date'])): ?>
                            <a href="seferler.php" class="btn btn-outline-secondary ms-2">Temizle</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php if (empty($seferler)): ?>
            <div class="col-12 text-center">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <?php if (isset($_GET['from']) || isset($_GET['to']) || isset($_GET['date'])): ?>
                        Filtrelere uygun sefer bulunamadı.
                    <?php else: ?>
                        Henüz mevcut sefer bulunmuyor.
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($seferler as $sefer): 
                try {
                    $stmt_occupied = $db->prepare("SELECT COUNT(*) as occupied_seats FROM booked_seats WHERE ticket_id IN (SELECT id FROM tickets WHERE trip_id = ? AND status = 'active')");
                    $stmt_occupied->execute([$sefer['id']]);
                    $occupied_result = $stmt_occupied->fetch(PDO::FETCH_ASSOC);
                    $occupied_seats = $occupied_result['occupied_seats'];
                    $available_seats = $sefer['capacity'] - $occupied_seats;
                } catch (PDOException $e) {
                    $available_seats = $sefer['capacity'];
                }
            ?>
            <div class="col-md-6 mb-4">
                <div class="card sefer-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold">
                            <?php echo htmlspecialchars($sefer['departure_city']); ?> → <?php echo htmlspecialchars($sefer['destination_city']); ?>
                        </span>
                        <span class="badge bg-success">
                            <?php echo date('d.m H:i', strtotime($sefer['departure_time'])); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-8">
                                <h5 class="card-title text-primary"><?php echo htmlspecialchars($sefer['company_name']); ?></h5>
                                <p class="card-text">
                                    <i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($sefer['departure_time'])); ?><br>
                                    <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($sefer['departure_time'])); ?> - <?php echo date('H:i', strtotime($sefer['arrival_time'])); ?><br>
                                    <i class="fas fa-chair"></i> 
                                    <span class="badge bg-<?php echo $available_seats > 10 ? 'success' : ($available_seats > 0 ? 'warning' : 'danger'); ?>">
                                        <?php echo $available_seats; ?> Boş Koltuk
                                    </span>
                                </p>
                            </div>
                            <div class="col-4 text-end">
                                <div class="price mb-3">
                                    <h4 class="text-success">₺<?php echo number_format($sefer['price'], 2); ?></h4>
                                </div>
                                <a href="sefer-detay.php?id=<?php echo $sefer['id']; ?>" class="btn btn-primary">
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'user'): ?>
                                        Bilet Al
                                    <?php else: ?>
                                        Detay
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.sefer-card {
    transition: transform 0.2s;
    border: 1px solid #dee2e6;
}
.sefer-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<?php include 'includes/footer.php';?>