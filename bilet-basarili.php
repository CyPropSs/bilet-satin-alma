<?php
include 'includes/config.php';

if (!isset($_SESSION['bilet_basarili'])) {
    header('Location: seferler.php');
    exit();
}

$bilet_bilgisi = $_SESSION['bilet_basarili'];
unset($_SESSION['bilet_basarili']);

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white text-center">
                    <h3><i class="fas fa-check-circle"></i> Bilet Satın Alma Başarılı!</h3>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-ticket-alt fa-5x text-success mb-3"></i>
                        <h4>Biletiniz başarıyla oluşturuldu!</h4>
                    </div>
                    
                    <div class="row text-start">
                        <div class="col-md-6">
                            <h5>Bilet Bilgileri</h5>
                            <p><strong>Bilet ID:</strong> <?php echo substr($bilet_bilgisi['bilet_id'], 0, 8); ?>...</p>
                            <p><strong>Güzergah:</strong> <?php echo htmlspecialchars($bilet_bilgisi['sefer']['departure_city']); ?> → <?php echo htmlspecialchars($bilet_bilgisi['sefer']['destination_city']); ?></p>
                            <p><strong>Koltuk No:</strong> <?php echo $bilet_bilgisi['koltuk']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Sefer Bilgileri</h5>
                            <p><strong>Tarih:</strong> <?php echo date('d.m.Y', strtotime($bilet_bilgisi['sefer']['departure_time'])); ?></p>
                            <p><strong>Saat:</strong> <?php echo date('H:i', strtotime($bilet_bilgisi['sefer']['departure_time'])); ?></p>
                            <p><strong>Firma:</strong> <?php echo htmlspecialchars($bilet_bilgisi['sefer']['company_name']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-4 p-3 bg-light rounded">
                        <h5 class="text-success">Ödeme Bilgileri</h5>
                        <p><strong>Toplam Tutar:</strong> ₺<?php echo number_format($bilet_bilgisi['fiyat'], 2); ?></p>
                        <?php if ($bilet_bilgisi['indirim'] > 0): ?>
                            <p><strong>İndirim:</strong> -₺<?php echo number_format($bilet_bilgisi['indirim'], 2); ?></p>
                        <?php endif; ?>
                        <p><strong>Kalan Bakiye:</strong> ₺<?php echo number_format($_SESSION['user_balance'], 2); ?></p>
                    </div>
                    
                    <div class="mt-4">
                        <a href="biletlerim.php" class="btn btn-primary">
                            <i class="fas fa-ticket-alt"></i> Biletlerimi Görüntüle
                        </a>
                        <a href="seferler.php" class="btn btn-outline-secondary">
                            <i class="fas fa-bus"></i> Yeni Sefer Ara
                        </a>
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-home"></i> Ana Sayfa
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>