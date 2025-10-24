<?php
include 'config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiletSat - Otobüs Bileti Satın Alma Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-bus"></i> BiletSat
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Ana Sayfa</a>
                    </li>

                    
                    <li class="nav-item">
                        <a class="nav-link" href="seferler.php">Seferler</a>
                    </li>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        
                        
                        <?php if ($_SESSION['user_role'] == 'user'): ?>
                            
                            <li class="nav-item">
                                <a class="nav-link" href="biletlerim.php">
                                    <i class="fas fa-ticket-alt"></i> Biletlerim
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="hesabim.php">
                                    <i class="fas fa-user"></i> Hesabım
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($_SESSION['user_role'] == 'company'): ?>
                            
                            <li class="nav-item">
                                <a class="nav-link" href="firma-paneli.php">
                                    <i class="fas fa-building"></i> Firma Paneli
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="firma-kupon.php">
                                    <i class="fas fa-tag"></i> Kupon Yönetimi
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($_SESSION['user_role'] == 'admin'): ?>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog"></i> Admin İşlemleri
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="admin-paneli.php">
                                        <i class="fas fa-tachometer-alt"></i> Admin Paneli
                                    </a></li>
                                    <li><a class="dropdown-item" href="admin-firma.php">
                                        <i class="fas fa-bus"></i> Firma Yönetimi
                                    </a></li>
                                    <li><a class="dropdown-item" href="admin-firma-admin.php">
                                        <i class="fas fa-user-tie"></i> Firma Admin Atama
                                    </a></li>
                                    <li><a class="dropdown-item" href="admin-kupon.php">
                                        <i class="fas fa-tags"></i> Kupon Yönetimi
                                    </a></li>
                                    <li><a class="dropdown-item" href="admin-bakiye.php">
                                        <i class="fas fa-wallet"></i> Bakiye Yönetimi
                                    </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>

                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Giriş Yap
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus"></i> Kayıt Ol
                            </a>
                        </li>
                    <?php else: ?>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> 
                                <?php 
                                echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Kullanıcı';
                                ?>
                                
                                
                                <span class="badge 
                                    <?php 
                                    switch($_SESSION['user_role']) {
                                        case 'admin': echo 'bg-danger'; break;
                                        case 'company': echo 'bg-warning'; break;
                                        case 'user': echo 'bg-success'; break;
                                        default: echo 'bg-secondary';
                                    }
                                    ?> ms-1">
                                    <?php 
                                    switch($_SESSION['user_role']) {
                                        case 'admin': echo 'ADMİN'; break;
                                        case 'company': echo 'FİRMA'; break;
                                        case 'user': echo 'YOLCU'; break;
                                        default: echo 'KULLANICI';
                                    }
                                    ?>
                                </span>

                                
                                <?php if ($_SESSION['user_role'] == 'user'): ?>
                                    <span class="badge bg-success ms-1">
                                        <i class="fas fa-wallet"></i> 
                                        ₺<?php echo isset($_SESSION['user_balance']) ? number_format($_SESSION['user_balance'], 2) : '0.00'; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu">
                                
                                <li><a class="dropdown-item" href="hesabim.php">
                                    <i class="fas fa-user-circle"></i> Hesabım
                                </a></li>

                                
                                <?php if ($_SESSION['user_role'] == 'user' || $_SESSION['user_role'] == 'company'): ?>
                                    <li><a class="dropdown-item" href="biletlerim.php">
                                        <i class="fas fa-ticket-alt"></i> Biletlerim
                                    </a></li>
                                <?php endif; ?>

                                
                                <?php if ($_SESSION['user_role'] == 'user'): ?>
                                    <li><a class="dropdown-item" href="seferler.php">
                                        <i class="fas fa-bus"></i> Bilet Satın Al
                                    </a></li>
                                <?php endif; ?>

                                
                                <?php if ($_SESSION['user_role'] == 'company'): ?>
                                    <li><a class="dropdown-item" href="firma-paneli.php">
                                        <i class="fas fa-building"></i> Firma Paneli
                                    </a></li>
                                <?php endif; ?>

                                
                                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                    <li><a class="dropdown-item" href="admin-paneli.php">
                                        <i class="fas fa-cog"></i> Admin Paneli
                                    </a></li>
                                <?php endif; ?>

                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                                </a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>