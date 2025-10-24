<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    header('Location: login.php');
    exit();
}

try {
    $stmt = $db->prepare("
        SELECT * FROM coupons 
        WHERE company_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['company_id']]);
    $kuponlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $kuponlar = [];
    $error = "Kuponlar yüklenirken hata oluştu: " . $e->getMessage();
}

$kupon_istatistik = [];
foreach ($kuponlar as $kupon) {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as kullanım_sayisi 
            FROM user_coupons 
            WHERE coupon_id = ?
        ");
        $stmt->execute([$kupon['id']]);
        $kullanım = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $kupon_istatistik[$kupon['id']] = $kullanım['kullanım_sayisi'];
    } catch (PDOException $e) {
        $kupon_istatistik[$kupon['id']] = 0;
    }
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
$form_errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];

unset($_SESSION['success'], $_SESSION['error'], $_SESSION['form_errors'], $_SESSION['form_data']);

include 'includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
            <li class="breadcrumb-item"><a href="firma-paneli.php">Firma Paneli</a></li>
            <li class="breadcrumb-item active">Kupon Yönetimi</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-tags"></i> Kupon Yönetimi
                    </h4>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#kuponEkleModal">
                        <i class="fas fa-plus"></i> Yeni Kupon
                    </button>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-light">
                                <h3 class="text-primary mb-0"><?php echo count($kuponlar); ?></h3>
                                <small class="text-muted">Toplam Kupon</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-light">
                                <h3 class="text-success mb-0">
                                    <?php
                                    $aktif_kuponlar = 0;
                                    foreach ($kuponlar as $kupon) {
                                        $kullanım_sayisi = $kupon_istatistik[$kupon['id']] ?? 0;
                                        $is_active = strtotime($kupon['expire_date']) > time() && 
                                                   ($kupon['usage_limit'] == 0 || $kullanım_sayisi < $kupon['usage_limit']);
                                        if ($is_active) $aktif_kuponlar++;
                                    }
                                    echo $aktif_kuponlar;
                                    ?>
                                </h3>
                                <small class="text-muted">Aktif Kupon</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-light">
                                <h3 class="text-warning mb-0">
                                    <?php echo array_sum($kupon_istatistik); ?>
                                </h3>
                                <small class="text-muted">Toplam Kullanım</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-light">
                                <h3 class="text-info mb-0">
                                    <?php echo count($kuponlar) - $aktif_kuponlar; ?>
                                </h3>
                                <small class="text-muted">Pasif Kupon</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
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
                    <h6><i class="fas fa-exclamation-triangle"></i> Form Hataları:</h6>
                    <ul class="mb-0">
                        <?php foreach ($form_errors as $err): ?>
                            <li><?php echo $err; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Kupon Listesi
                        <span class="badge bg-primary"><?php echo count($kuponlar); ?> kupon</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($kuponlar)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-tag fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Henüz kupon bulunmuyor</h5>
                            <p class="text-muted mb-4">İlk kuponunuzu oluşturarak başlayın.</p>
                            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#kuponEkleModal">
                                <i class="fas fa-plus"></i> İlk Kuponu Oluştur
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Kupon Kodu</th>
                                        <th>İndirim</th>
                                        <th>Kullanım Limiti</th>
                                        <th>Kullanım</th>
                                        <th>Son Kullanma</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kuponlar as $kupon): 
                                        $kullanım_sayisi = $kupon_istatistik[$kupon['id']] ?? 0;
                                        $is_active = strtotime($kupon['expire_date']) > time() && 
                                                    ($kupon['usage_limit'] == 0 || $kullanım_sayisi < $kupon['usage_limit']);
                                        $is_expired = strtotime($kupon['expire_date']) <= time();
                                        $is_limited = $kupon['usage_limit'] > 0 && $kullanım_sayisi >= $kupon['usage_limit'];
                                    ?>
                                    <tr>
                                        <td>
                                            <strong class="font-monospace"><?php echo htmlspecialchars($kupon['code']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-success fs-6">%<?php echo $kupon['discount']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($kupon['usage_limit'] == 0): ?>
                                                <span class="badge bg-info">Sınırsız</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo $kupon['usage_limit']; ?> kullanım</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="progress mb-1" style="height: 8px;">
                                                <div class="progress-bar <?php echo $is_limited ? 'bg-danger' : ($kullanım_sayisi > 0 ? 'bg-warning' : 'bg-success'); ?>" 
                                                     style="width: <?php echo $kupon['usage_limit'] > 0 ? min(100, ($kullanım_sayisi / $kupon['usage_limit']) * 100) : ($kullanım_sayisi > 0 ? 100 : 0); ?>%">
                                                </div>
                                            </div>
                                            <small><?php echo $kullanım_sayisi; ?> kullanım</small>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y H:i', strtotime($kupon['expire_date'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($is_expired): ?>
                                                <span class="badge bg-danger">Süresi Doldu</span>
                                            <?php elseif ($is_limited): ?>
                                                <span class="badge bg-warning">Limit Doldu</span>
                                            <?php elseif ($is_active): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-primary btn-sm" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#kuponDuzenleModal"
                                                        data-kupon-id="<?php echo $kupon['id']; ?>"
                                                        data-code="<?php echo htmlspecialchars($kupon['code']); ?>"
                                                        data-discount="<?php echo $kupon['discount']; ?>"
                                                        data-usage-limit="<?php echo $kupon['usage_limit']; ?>"
                                                        data-expire-date="<?php echo $kupon['expire_date']; ?>"
                                                        title="Düzenle">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" 
                                                        onclick="deleteKupon('<?php echo $kupon['id']; ?>', '<?php echo htmlspecialchars($kupon['code']); ?>')"
                                                        title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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


<div class="modal fade" id="kuponEkleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Kupon Oluştur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="firma-kupon-ekle.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kupon Kodu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="code" required 
                               value="<?php echo htmlspecialchars($form_data['code'] ?? ''); ?>"
                               placeholder="Örn: YAZ2024" maxlength="20">
                        <div class="form-text">Büyük harf ve rakamlardan oluşmalıdır.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">İndirim Oranı (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="discount" 
                                   value="<?php echo htmlspecialchars($form_data['discount'] ?? ''); ?>"
                                   min="1" max="100" step="1" required placeholder="Örn: 10">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kullanım Limiti</label>
                        <input type="number" class="form-control" name="usage_limit" 
                               value="<?php echo htmlspecialchars($form_data['usage_limit'] ?? '0'); ?>"
                               min="0" placeholder="0 = sınırsız">
                        <div class="form-text">0 girerseniz kupon sınırsız kullanılabilir.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Son Kullanma Tarihi <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" name="expire_date" 
                               value="<?php echo htmlspecialchars($form_data['expire_date'] ?? ''); ?>"
                               min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Kuponu Oluştur</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="kuponDuzenleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kuponu Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="firma-kupon-duzenle.php" method="POST">
                <input type="hidden" name="kupon_id" id="editKuponId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kupon Kodu</label>
                        <input type="text" class="form-control" name="code" id="editCode" required readonly>
                        <div class="form-text">Kupon kodu değiştirilemez.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">İndirim Oranı (%)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="discount" id="editDiscount" 
                                   min="1" max="100" step="1" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kullanım Limiti</label>
                        <input type="number" class="form-control" name="usage_limit" id="editUsageLimit" 
                               min="0" value="0">
                        <div class="form-text">0 = sınırsız</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Son Kullanma Tarihi</label>
                        <input type="datetime-local" class="form-control" name="expire_date" id="editExpireDate" required>
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
    const kuponDuzenleModal = document.getElementById('kuponDuzenleModal');
    
    kuponDuzenleModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        
        document.getElementById('editKuponId').value = button.getAttribute('data-kupon-id');
        document.getElementById('editCode').value = button.getAttribute('data-code');
        document.getElementById('editDiscount').value = button.getAttribute('data-discount');
        document.getElementById('editUsageLimit').value = button.getAttribute('data-usage-limit');
        document.getElementById('editExpireDate').value = formatDateTime(button.getAttribute('data-expire-date'));
    });
    
    function formatDateTime(datetime) {
        const date = new Date(datetime);
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 16);
    }
});

function deleteKupon(kuponId, kuponKodu) {
    if (confirm('"' + kuponKodu + '" kuponunu silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) {
        window.location.href = 'firma-kupon-sil.php?id=' + kuponId;
    }
}
</script>

<style>
.font-monospace {
    font-family: 'Courier New', monospace;
}

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
</style>

<?php include 'includes/footer.php'; ?>