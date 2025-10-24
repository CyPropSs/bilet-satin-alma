<?php
include 'includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$sefer_id = $_GET['sefer_id'] ?? 0;

try {
    $stmt = $db->prepare("
        SELECT t.*, c.name as company_name, c.id as company_id
        FROM trips t 
        LEFT JOIN bus_company c ON t.company_id = c.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$sefer_id]);
    $sefer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sefer) {
        die("Sefer bulunamadı!");
    }
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}

$dolu_koltuklar = [];
try {
    $stmt = $db->prepare("
        SELECT bs.seat_number 
        FROM booked_seats bs 
        JOIN tickets t ON bs.ticket_id = t.id 
        WHERE t.trip_id = ? AND t.status = 'active'
    ");
    $stmt->execute([$sefer_id]);
    $dolu_koltuklar = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
}

$kullanici_koltuklari = [];
try {
    $stmt = $db->prepare("
        SELECT bs.seat_number 
        FROM booked_seats bs 
        JOIN tickets t ON bs.ticket_id = t.id 
        WHERE t.trip_id = ? AND t.user_id = ? AND t.status = 'active'
    ");
    $stmt->execute([$sefer_id, $_SESSION['user_id']]);
    $kullanici_koltuklari = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_coupon'])) {
    $kupon_kodu = trim($_POST['kupon_kodu'] ?? '');
    
    if (!empty($kupon_kodu)) {
        try {
            $stmt = $db->prepare("
                SELECT * FROM coupons 
                WHERE code = ? AND expire_date > datetime('now')
                AND (company_id IS NULL OR company_id = ?)
                AND (usage_limit = 0 OR usage_limit > (
                    SELECT COUNT(*) FROM user_coupons WHERE coupon_id = coupons.id
                ))
            ");
            $stmt->execute([$kupon_kodu, $sefer['company_id']]);
            $kupon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($kupon) {
                $indirim_tutari = $sefer['price'] * ($kupon['discount'] / 100);
                $indirimli_fiyat = $sefer['price'] - $indirim_tutari;
                
                $_SESSION['coupon_applied'] = [
                    'kupon_kodu' => $kupon_kodu,
                    'kupon_id' => $kupon['id'],
                    'indirim_orani' => $kupon['discount'],
                    'indirim_tutari' => $indirim_tutari,
                    'indirimli_fiyat' => $indirimli_fiyat
                ];
                
                $_SESSION['success'] = "Kupon uygulandı! %" . $kupon['discount'] . " indirim (" . number_format($indirim_tutari, 2) . " TL)";
            } else {
                unset($_SESSION['coupon_applied']);
                $_SESSION['error'] = "Geçersiz kupon kodu!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Kupon kontrolü sırasında hata oluştu!";
        }
    } else {
        unset($_SESSION['coupon_applied']);
        $_SESSION['error'] = "Lütfen kupon kodunu girin!";
    }
    
    header('Location: bilet-satin-al.php?sefer_id=' . $sefer_id);
    exit();
}

if (isset($_GET['remove_coupon'])) {
    unset($_SESSION['coupon_applied']);
    $_SESSION['success'] = "Kupon kaldırıldı!";
    header('Location: bilet-satin-al.php?sefer_id=' . $sefer_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['koltuk_numarasi'])) {
    $koltuk_numarasi = (int)$_POST['koltuk_numarasi'];
    
    if (in_array($koltuk_numarasi, $dolu_koltuklar) && !in_array($koltuk_numarasi, $kullanici_koltuklari)) {
        $error = "Bu koltuk zaten dolu!";
    } elseif ($koltuk_numarasi < 1 || $koltuk_numarasi > $sefer['capacity']) {
        $error = "Geçersiz koltuk numarası!";
    } else {
        $indirim = 0;
        $kupon_bilgisi = null;
        $kupon_kodu = '';
        
        if (isset($_SESSION['coupon_applied'])) {
            $indirim = $_SESSION['coupon_applied']['indirim_tutari'];
            $kupon_kodu = $_SESSION['coupon_applied']['kupon_kodu'];
            $kupon_id = $_SESSION['coupon_applied']['kupon_id'];
        }
        
        try {
            $db->beginTransaction();
            
            $toplam_fiyat = $sefer['price'] - $indirim;
            
            if ($indirim < 0) {
                throw new Exception("Geçersiz indirim tutarı!");
            }
            
            if ($toplam_fiyat < 0) {
                $toplam_fiyat = 0; // Minimum 0 TL
            }
            
            if ($_SESSION['user_balance'] < $toplam_fiyat) {
                throw new Exception("Bakiyeniz yetersiz! Gerekli: ₺" . number_format($toplam_fiyat, 2) . ", Mevcut: ₺" . number_format($_SESSION['user_balance'], 2));
            }
            
            $bilet_id = generateUUID();
            $stmt = $db->prepare("
                INSERT INTO tickets (id, trip_id, user_id, total_price, status) 
                VALUES (?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$bilet_id, $sefer_id, $_SESSION['user_id'], $toplam_fiyat]);
            
            $koltuk_id = generateUUID();
            $stmt = $db->prepare("
                INSERT INTO booked_seats (id, ticket_id, seat_number) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$koltuk_id, $bilet_id, $koltuk_numarasi]);
            
            if (isset($_SESSION['coupon_applied'])) {
                $user_kupon_id = generateUUID();
                $stmt = $db->prepare("
                    INSERT INTO user_coupons (id, coupon_id, user_id) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user_kupon_id, $kupon_id, $_SESSION['user_id']]);
                
                unset($_SESSION['coupon_applied']);
            }
            
            $yeni_bakiye = $_SESSION['user_balance'] - $toplam_fiyat;
            $stmt = $db->prepare("UPDATE user SET balance = ? WHERE id = ?");
            $stmt->execute([$yeni_bakiye, $_SESSION['user_id']]);
            
            $_SESSION['user_balance'] = $yeni_bakiye;
            
            $db->commit();
            
            $_SESSION['bilet_basarili'] = [
                'bilet_id' => $bilet_id,
                'sefer' => $sefer,
                'koltuk' => $koltuk_numarasi,
                'fiyat' => $toplam_fiyat,
                'indirim' => $indirim,
                'kupon_kodu' => $kupon_kodu
            ];
            
            header('Location: bilet-basarili.php');
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
            <li class="breadcrumb-item"><a href="seferler.php">Seferler</a></li>
            <li class="breadcrumb-item"><a href="sefer-detay.php?id=<?php echo $sefer_id; ?>">Sefer Detayı</a></li>
            <li class="breadcrumb-item active">Bilet Satın Al</li>
        </ol>
    </nav>

    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Koltuk Seçimi</h4>
                    <?php if (!empty($kullanici_koltuklari)): ?>
                        <div class="alert alert-info mt-2">
                            <small>
                                <i class="fas fa-info-circle"></i> 
                                Bu seferde zaten <strong><?php echo count($kullanici_koltuklari); ?> biletiniz</strong> bulunuyor: 
                                Koltuk <?php echo implode(', ', $kullanici_koltuklari); ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="bus-layout text-center">
                        <div class="driver-seat mb-4">
                            <div class="seat driver"><i class="fas fa-user"></i> SÜRÜCÜ</div>
                        </div>
                        
                        <form method="POST" id="biletForm">
                            <div class="seats-container mb-4">
                                <?php for ($i = 1; $i <= $sefer['capacity']; $i++): ?>
                                    <?php 
                                    $is_occupied = in_array($i, $dolu_koltuklar) && !in_array($i, $kullanici_koltuklari);
                                    $is_my_seat = in_array($i, $kullanici_koltuklari);
                                    $is_available = !$is_occupied && !$is_my_seat;
                                    ?>
                                    <div class="seat-item">
                                        <input class="form-check-input seat-radio" type="radio" name="koltuk_numarasi" 
                                               id="koltuk<?php echo $i; ?>" value="<?php echo $i; ?>" 
                                               <?php echo $is_occupied ? 'disabled' : ''; ?> 
                                               required>
                                        <label class="seat 
                                            <?php echo $is_occupied ? 'occupied' : ''; ?>
                                            <?php echo $is_my_seat ? 'my-seat' : ''; ?>
                                            <?php echo $is_available ? 'available' : ''; ?>" 
                                               for="koltuk<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                            <?php if ($is_my_seat): ?>
                                                <br><small class="my-seat-text">Sizin</small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                    
                                    <?php if ($i % 4 == 0 && $i < $sefer['capacity']): ?>
                                        <div class="w-100"></div>
                                        <div class="seat-space"></div>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="kupon_kodu" class="form-label">Kupon Kodu</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="kupon_kodu" name="kupon_kodu" 
                                                   placeholder="Kupon kodunuzu girin"
                                                   value="<?php echo isset($_SESSION['coupon_applied']) ? $_SESSION['coupon_applied']['kupon_kodu'] : ''; ?>"
                                                   <?php echo isset($_SESSION['coupon_applied']) ? 'readonly' : ''; ?>>
                                            <?php if (isset($_SESSION['coupon_applied'])): ?>
                                                <a href="bilet-satin-al.php?sefer_id=<?php echo $sefer_id; ?>&remove_coupon=1" 
                                                   class="btn btn-outline-danger" type="button">
                                                    <i class="fas fa-times"></i> Kaldır
                                                </a>
                                                <button class="btn btn-success" type="button" disabled>
                                                    <i class="fas fa-check"></i> Uygulandı
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="check_coupon" class="btn btn-outline-primary">
                                                    <i class="fas fa-check"></i> Kuponu Kontrol Et
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <div id="kupon-mesaji" class="form-text">
                                            <?php if (isset($_SESSION['coupon_applied'])): ?>
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle"></i> 
                                                    %<?php echo $_SESSION['coupon_applied']['indirim_orani']; ?> indirim uygulandı. 
                                                    İndirim: ₺<?php echo number_format($_SESSION['coupon_applied']['indirim_tutari'], 2); ?>
                                                </span>
                                            <?php else: ?>
                                                <small>Kupon kodunuzu girip "Kuponu Kontrol Et" butonuna tıklayın.</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-shopping-cart"></i> Biletimi Satın Al
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="seat-legend mt-4">
                        <div class="d-flex justify-content-center gap-4 flex-wrap">
                            <div class="d-flex align-items-center">
                                <div class="seat available me-2"></div>
                                <span>Boş Koltuk</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="seat occupied me-2"></div>
                                <span>Dolu Koltuk</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="seat my-seat me-2"></div>
                                <span>Sizin Koltuğunuz</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="seat selected me-2"></div>
                                <span>Seçili Koltuk</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4>Ödeme Özeti</h4>
                </div>
                <div class="card-body">
                    <h5>Sefer Bilgileri</h5>
                    <p>
                        <strong><?php echo htmlspecialchars($sefer['departure_city']); ?> → <?php echo htmlspecialchars($sefer['destination_city']); ?></strong><br>
                        <?php echo date('d.m.Y', strtotime($sefer['departure_time'])); ?> - <?php echo date('H:i', strtotime($sefer['departure_time'])); ?><br>
                        <?php echo htmlspecialchars($sefer['company_name']); ?>
                    </p>
                    
                    <hr>
                    
                    <h5>Ücret Bilgisi</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Bilet Ücreti:</span>
                        <span>₺<?php echo number_format($sefer['price'], 2); ?></span>
                    </div>
                    
                    <?php if (isset($_SESSION['coupon_applied'])): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>İndirim (%<?php echo $_SESSION['coupon_applied']['indirim_orani']; ?>):</span>
                            <span>-₺<?php echo number_format($_SESSION['coupon_applied']['indirim_tutari'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2">
                        <span>Toplam:</span>
                        <span class="text-primary">
                            ₺<?php 
                            if (isset($_SESSION['coupon_applied'])) {
                                echo number_format($_SESSION['coupon_applied']['indirimli_fiyat'], 2);
                            } else {
                                echo number_format($sefer['price'], 2);
                            }
                            ?>
                        </span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Mevcut Bakiyeniz:</span>
                        <span class="text-success">₺<?php echo number_format($_SESSION['user_balance'], 2); ?></span>
                    </div>
                    
                    <div class="alert alert-warning">
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            Bilet iptali için son saat: <?php echo date('H:i', strtotime($sefer['departure_time']) - 3600); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const seatLabels = document.querySelectorAll('.seat');
    
    seatLabels.forEach(label => {
        label.addEventListener('click', function(e) {
            const radio = document.getElementById(this.htmlFor);
            
            if (!this.classList.contains('occupied') && radio) {
                seatLabels.forEach(seat => {
                    seat.classList.remove('selected');
                });
                
                this.classList.add('selected');
                radio.checked = true;
            }
        });
    });

    document.getElementById('biletForm').addEventListener('submit', function(e) {
        const submitButton = e.submitter;
        
        if (submitButton && submitButton.name === 'check_coupon') {
            return true;
        }
        
        const selectedSeat = document.querySelector('input[name="koltuk_numarasi"]:checked');
        
        if (!selectedSeat) {
            e.preventDefault();
            alert('Lütfen bir koltuk seçin!');
            return false;
        }
        
        const selectedLabel = document.querySelector('label[for="' + selectedSeat.id + '"]');
        if (selectedLabel.classList.contains('occupied')) {
            e.preventDefault();
            alert('Bu koltuk dolu! Lütfen başka bir koltuk seçin.');
            return false;
        }
    });
});
</script>

<style>

.seat {
    display: inline-block;
    width: 50px;
    height: 50px;
    margin: 5px;
    text-align: center;
    line-height: 20px;
    border-radius: 8px;
    cursor: pointer;
    border: 2px solid #ddd;
    background-color: #f8f9fa;
    font-size: 0.9rem;
    font-weight: bold;
    transition: all 0.3s ease;
    padding-top: 5px;
}

.seat:hover {
    transform: scale(1.05);
}

.seat.available {
    background-color: #d1edff;
    border-color: #4da8ff;
    color: #0066cc;
}

.seat.available:hover {
    background-color: #b3d9ff;
}

.seat.occupied {
    background-color: #ffcccc;
    border-color: #ff6666;
    color: #cc0000;
    cursor: not-allowed;
    opacity: 0.6;
}

.seat.my-seat {
    background-color: #fff0cc;
    border-color: #ffcc00;
    color: #996600;
}

.seat.selected {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
    transform: scale(1.1);
}

.seat.driver {
    background-color: #6c757d;
    color: white;
    border-color: #6c757d;
    cursor: default;
    width: 80px;
}

.my-seat-text {
    font-size: 0.6rem;
    font-weight: bold;
    display: block;
    margin-top: -2px;
}

.seat-radio {
    display: none !important;
}

.seat-item {
    display: inline-block;
    position: relative;
}

.seats-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    justify-items: center;
    max-width: 300px;
    margin: 0 auto;
}

.seat-space {
    grid-column: 1 / -1;
    height: 30px;
}

.driver-seat {
    margin-bottom: 30px;
}

.seat-legend .seat {
    width: 35px;
    height: 35px;
    line-height: 15px;
    font-size: 0.7rem;
    margin: 2px;
}


@media (max-width: 768px) {
    .seat {
        width: 45px;
        height: 45px;
        font-size: 0.8rem;
    }
    
    .seats-container {
        grid-template-columns: repeat(3, 1fr);
        max-width: 200px;
    }
}
</style>

<?php include 'includes/footer.php';?>