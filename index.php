<?php include 'includes/header.php'; 
include 'includes/config.php';?>

<div class="hero-section">
    <div class="container text-center">
        <h1 class="display-4 fw-bold">Yolculuğa Hazır Mısınız?</h1>
        <p class="lead">En uygun fiyatlarla, konforlu otobüs seferleri</p>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="search-form">
                <h3 class="mb-4">Sefer Ara</h3>
                <form action="seferler.php" method="GET">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="from" class="form-label">Nereden</label>
                            <select class="form-select" id="from" name="from" required>
                                <option value="">Şehir seçin</option>
                                <option value="İstanbul">İstanbul</option>
                                <option value="Ankara">Ankara</option>
                                <option value="İzmir">İzmir</option>
                                <option value="Antalya">Antalya</option>
                                <option value="Bursa">Bursa</option>
                                <option value="Trabzon">Trabzon</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="to" class="form-label">Nereye</label>
                            <select class="form-select" id="to" name="to" required>
                                <option value="">Şehir seçin</option>
                                <option value="İstanbul">İstanbul</option>
                                <option value="Ankara">Ankara</option>
                                <option value="İzmir">İzmir</option>
                                <option value="Antalya">Antalya</option>
                                <option value="Bursa">Bursa</option>
                                <option value="Trabzon">Trabzon</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date" class="form-label">Tarih</label>
                            <input type="date" class="form-control" id="date" name="date" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Seferleri Bul
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-12">
            <h2 class="mb-4">Yaklaşan Seferler</h2>
        </div>
        
        <?php
        try {
            $current_datetime = date('Y-m-d H:i:s');
            $stmt = $db->prepare("
                SELECT t.*, c.name as company_name 
                FROM trips t 
                LEFT JOIN bus_company c ON t.company_id = c.id 
                WHERE t.departure_time > ?
                ORDER BY t.departure_time ASC 
                LIMIT 6
            ");
            $stmt->execute([$current_datetime]);
            $yaklasan_seferler = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($yaklasan_seferler)) {
                echo '<div class="col-12 text-center">';
                echo '<div class="alert alert-info">';
                echo '<i class="fas fa-info-circle"></i> Henüz yaklaşan sefer bulunmuyor.';
                echo '</div>';
                echo '</div>';
            } else {
                foreach ($yaklasan_seferler as $sefer): 
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
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <small class="float-end">
                        <i class="fas fa-clock"></i> 
                        <?php echo date('d.m', strtotime($sefer['departure_time'])); ?>
                    </small>
                    <strong><?php echo htmlspecialchars($sefer['departure_city']); ?> → <?php echo htmlspecialchars($sefer['destination_city']); ?></strong>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title"><?php echo htmlspecialchars($sefer['company_name']); ?></h5>
                        <span class="badge bg-success">₺<?php echo number_format($sefer['price'], 2); ?></span>
                    </div>
                    <p class="card-text">
                        <i class="fas fa-calendar"></i> <?php echo date('d.m.Y', strtotime($sefer['departure_time'])); ?><br>
                        <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($sefer['departure_time'])); ?><br>
                        <i class="fas fa-chair"></i> 
                        <span class="badge bg-<?php echo $available_seats > 10 ? 'success' : ($available_seats > 0 ? 'warning' : 'danger'); ?>">
                            <?php echo $available_seats; ?> Boş Koltuk
                        </span>
                    </p>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <?php echo htmlspecialchars($sefer['company_name']); ?>
                        </small>
                        <a href="sefer-detay.php?id=<?php echo $sefer['id']; ?>" class="btn btn-sm btn-primary">
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
        <?php 
                endforeach; 
            }
        } catch (PDOException $e) {
            echo '<div class="col-12">';
            echo '<div class="alert alert-danger">Seferler yüklenirken hata oluştu</div>';
            echo '</div>';
        }
        ?>
    </div>

    <?php if (!empty($yaklasan_seferler)): ?>
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="seferler.php" class="btn btn-outline-primary">
                <i class="fas fa-list"></i> Tüm Seferleri Görüntüle
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php';?>