<?php
echo "<h2>BiletSat Veritabanı Kurulumu</h2>";

try {
    $db = new PDO('sqlite:database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Veritabanı bağlantısı kuruldu<br>";
    echo "✅ database.sqlite dosyası oluşturuldu<br>";
    
    $sql = "
    CREATE TABLE IF NOT EXISTS user (
        id TEXT PRIMARY KEY,
        full_name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        role TEXT NOT NULL CHECK(role IN ('user', 'company', 'admin')),
        password TEXT NOT NULL,
        company_id TEXT,
        balance REAL DEFAULT 800.0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES bus_company(id)
    );

    CREATE TABLE IF NOT EXISTS bus_company (
        id TEXT PRIMARY KEY,
        name TEXT UNIQUE NOT NULL,
        logo_path TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS trips (
        id TEXT PRIMARY KEY,
        company_id TEXT NOT NULL,
        destination_city TEXT NOT NULL,
        arrival_time DATETIME NOT NULL,
        departure_time DATETIME NOT NULL,
        departure_city TEXT NOT NULL,
        price INTEGER NOT NULL,
        capacity INTEGER NOT NULL,
        created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES bus_company(id)
    );

    CREATE TABLE IF NOT EXISTS tickets (
        id TEXT PRIMARY KEY,
        trip_id TEXT NOT NULL,
        user_id TEXT NOT NULL,
        status TEXT DEFAULT 'active' CHECK(status IN ('active', 'cancelled', 'expired')),
        total_price INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (trip_id) REFERENCES trips(id),
        FOREIGN KEY (user_id) REFERENCES user(id)
    );

    CREATE TABLE IF NOT EXISTS coupons (
        id TEXT PRIMARY KEY,
        code TEXT NOT NULL,
        discount REAL NOT NULL,    
        company_id TEXT,
        usage_limit INTEGER NOT NULL,
        expire_date DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES bus_company(id)
    );

    CREATE TABLE IF NOT EXISTS user_coupons (
        id TEXT PRIMARY KEY,
        coupon_id TEXT NOT NULL,
        user_id TEXT NOT NULL,    
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (coupon_id) REFERENCES coupons(id),
        FOREIGN KEY (user_id) REFERENCES user(id)
    );

    CREATE TABLE IF NOT EXISTS booked_seats (
        id TEXT PRIMARY KEY,
        ticket_id TEXT NOT NULL,
        seat_number INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES tickets(id)
    );
    ";
    
    $db->exec($sql);
    echo "✅ Tablolar başarıyla oluşturuldu!<br><br>";
    
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll();
    
    echo "<h3>📊 Oluşturulan Tablolar:</h3>";
    echo "<ul>";
    foreach($tables as $table) {
        echo "<li>✅ " . $table['name'] . "</li>";
    }
    echo "</ul>";
    
    function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    echo "<br><h3>👥 Kullanıcıları Ekliyorum...</h3>";
    
    $firma_stmt = $db->query("SELECT id, name FROM bus_company");
    $firmalar = $firma_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($firmalar)) {
        $sample_firms = [
            ['id' => generateUUID(), 'name' => 'Metro Turizm'],
            ['id' => generateUUID(), 'name' => 'Pamukkale Turizm'],
            ['id' => generateUUID(), 'name' => 'Kamil Koç']
        ];
        
        foreach($sample_firms as $firm) {
            $stmt = $db->prepare("INSERT INTO bus_company (id, name) VALUES (?, ?)");
            $stmt->execute([$firm['id'], $firm['name']]);
        }
        echo "✅ Örnek firmalar eklendi<br>";
        
        $firma_stmt = $db->query("SELECT id, name FROM bus_company");
        $firmalar = $firma_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo "<h3>Mevcut Firmalar:</h3>";
    foreach($firmalar as $firma) {
        echo "✅ " . $firma['name'] . "<br>";
    }
    
    $users = [
        [
            'id' => generateUUID(),
            'full_name' => 'bugi admin',
            'email' => 'admin@biletsat.com',
            'password' => '123123',
            'role' => 'admin',
            'company_id' => null
        ],
        [
            'id' => generateUUID(),
            'full_name' => 'bugi company',
            'email' => 'company@biletsat.com',
            'password' => '123123',
            'role' => 'company',
            'company_id' => $firmalar[0]['id'] // Metro Turizm'e atama
        ],
        [
            'id' => generateUUID(),
            'full_name' => 'bugi user',
            'email' => 'user@biletsat.com',
            'password' => '123123',
            'role' => 'user',
            'company_id' => null
        ]
    ];
    
    $added_users = 0;
    foreach($users as $user) {
        try {
            $check_stmt = $db->prepare("SELECT id FROM user WHERE email = ?");
            $check_stmt->execute([$user['email']]);
            
            if ($check_stmt->fetch()) {
                echo "⚠️ " . $user['email'] . " zaten mevcut<br>";
                continue;
            }
            
            $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO user 
                (id, full_name, email, password, role, company_id, balance) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $user['id'],
                $user['full_name'],
                $user['email'],
                $hashed_password,
                $user['role'],
                $user['company_id'],
                800.0
            ]);
            
            if ($result) {
                echo "✅ " . $user['full_name'] . " (" . $user['email'] . ") - Şifre: " . $user['password'] . "<br>";
                $added_users++;
            }
        } catch (PDOException $e) {
            echo "❌ " . $user['email'] . " eklenirken hata: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h4>🎯 Kullanıcı Giriş Bilgileri:</h4>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@biletsat.com / 123123</li>";
    echo "<li><strong>Firma:</strong> company@biletsat.com / 123123 (Metro Turizm)</li>";
    echo "<li><strong>User:</strong> user@biletsat.com / 123123</li>";
    echo "</ul>";
    
    echo "<br><h3>🚌 Seferleri Ekliyorum...</h3>";
    
    $real_trips = [
        [
            'company_id' => $firmalar[0]['id'],
            'departure_city' => 'İstanbul',
            'destination_city' => 'Ankara',
            'departure_time' => '2025-10-25 08:00:00',
            'arrival_time' => '2025-10-25 14:00:00',
            'price' => 180,
            'capacity' => 45
        ],
        [
            'company_id' => $firmalar[0]['id'],
            'departure_city' => 'İstanbul',
            'destination_city' => 'İzmir',
            'departure_time' => '2025-10-25 10:30:00',
            'arrival_time' => '2025-10-25 18:00:00',
            'price' => 220,
            'capacity' => 45
        ],
        [
            'company_id' => $firmalar[0]['id'],
            'departure_city' => 'İstanbul',
            'destination_city' => 'Antalya',
            'departure_time' => '2025-10-26 09:00:00',
            'arrival_time' => '2025-10-26 17:00:00',
            'price' => 200,
            'capacity' => 45
        ],
        [
            'company_id' => $firmalar[0]['id'],
            'departure_city' => 'İstanbul',
            'destination_city' => 'Bursa',
            'departure_time' => '2025-10-27 07:30:00',
            'arrival_time' => '2025-10-27 10:00:00',
            'price' => 80,
            'capacity' => 45
        ],
        
        [
            'company_id' => $firmalar[1]['id'],
            'departure_city' => 'İzmir',
            'destination_city' => 'Ankara',
            'departure_time' => '2025-10-25 07:00:00',
            'arrival_time' => '2025-10-25 13:30:00',
            'price' => 160,
            'capacity' => 45
        ],
        [
            'company_id' => $firmalar[1]['id'],
            'departure_city' => 'İzmir',
            'destination_city' => 'İstanbul',
            'departure_time' => '2025-10-26 08:00:00',
            'arrival_time' => '2025-10-26 15:00:00',
            'price' => 210,
            'capacity' => 45
        ],
        [
            'company_id' => $firmalar[1]['id'],
            'departure_city' => 'İzmir',
            'destination_city' => 'Bursa',
            'departure_time' => '2025-10-27 06:30:00',
            'arrival_time' => '2025-10-27 11:00:00',
            'price' => 120,
            'capacity' => 45
        ],
        [
            'company_id' => $firmalar[1]['id'],
            'departure_city' => 'İzmir',
            'destination_city' => 'Antalya',
            'departure_time' => '2025-10-28 09:00:00',
            'arrival_time' => '2025-10-28 14:30:00',
            'price' => 130,
            'capacity' => 45
        ],
        
        [
            'company_id' => $firmalar[2]['id'],
            'departure_city' => 'Ankara',
            'destination_city' => 'İstanbul',
            'departure_time' => '2025-10-25 14:00:00',
            'arrival_time' => '2025-10-25 20:00:00',
            'price' => 170,
            'capacity' => 45
        ],
        [
            'company_id' => $firmalar[2]['id'],
            'departure_city' => 'Ankara',
            'destination_city' => 'İzmir',
            'departure_time' => '2025-10-26 16:00:00',
            'arrival_time' => '2025-10-26 22:30:00',
            'price' => 190,
            'capacity' => 45
        ],
        [
            'company_id' => $firmalar[2]['id'],
            'departure_city' => 'Ankara',
            'destination_city' => 'Antalya',
            'departure_time' => '2025-10-27 10:00:00',
            'arrival_time' => '2025-10-27 16:30:00',
            'price' => 150,
            'capacity' => 45
        ],
        [
            'company_id' => $firmalar[2]['id'],
            'departure_city' => 'Ankara',
            'destination_city' => 'Trabzon',
            'departure_time' => '2025-10-28 18:00:00',
            'arrival_time' => '2025-10-29 06:00:00',
            'price' => 280,
            'capacity' => 45
        ],
        [
            'company_id' => $firmalar[2]['id'],
            'departure_city' => 'Ankara',
            'destination_city' => 'Trabzon',
            'departure_time' => '2025-10-23 16:00:00',
            'arrival_time' => '2025-10-23 18:00:00',
            'price' => 280,
            'capacity' => 45
        ],
        [
            'company_id' => $firmalar[2]['id'],
            'departure_city' => 'Ankara',
            'destination_city' => 'Trabzon',
            'departure_time' => '2025-10-23 17:00:00',
            'arrival_time' => '2025-10-23 19:00:00',
            'price' => 280,
            'capacity' => 45
        ]
    ];
    
    $added_trips = 0;
    $trip_errors = [];
    
    foreach($real_trips as $index => $trip) {
        $trip_id = generateUUID();
        
        try {
            $stmt = $db->prepare("
                INSERT INTO trips 
                (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $trip_id,
                $trip['company_id'],
                $trip['departure_city'],
                $trip['destination_city'],
                $trip['departure_time'],
                $trip['arrival_time'],
                $trip['price'],
                $trip['capacity']
            ]);
            
            if ($result) {
                echo "✅ " . $trip['departure_city'] . " → " . $trip['destination_city'] . " - ₺" . $trip['price'] . "<br>";
                $added_trips++;
            }
        } catch (PDOException $e) {
            $trip_errors[] = "Sefer " . ($index + 1) . " hatası: " . $e->getMessage();
            echo "❌ " . $trip['departure_city'] . " → " . $trip['destination_city'] . " - HATA: " . $e->getMessage() . "<br>";
        }
    }
    
    if (!empty($trip_errors)) {
        echo "<h3 style='color: orange;'>⚠️ Bazı sefer hataları oluştu:</h3>";
        foreach($trip_errors as $error) {
            echo "<div style='color: orange;'>" . $error . "</div>";
        }
    }
    
    echo "<h3 style='color: green;'>🎉 İşlem Tamamlandı!</h3>";
    echo "<ul>";
    echo "<li>✅ $added_users kullanıcı eklendi</li>";
    echo "<li>✅ $added_trips sefer eklendi</li>";
    echo "</ul>";
    
    echo "<h4>🔑 Test Giriş Bilgileri:</h4>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Admin Panel:</strong> admin@biletsat.com / 123123<br>";
    echo "<strong>Firma Panel:</strong> company@biletsat.com / 123123<br>";
    echo "<strong>User (Yolcu):</strong> user@biletsat.com / 123123<br>";
    echo "</div>";
    
    echo "<br><div style='margin-top: 20px;'>";
    echo "<a href='../seferler.php' style='margin-right: 10px;' class='btn btn-primary'>Seferleri Görüntüle</a>";
    echo "<a href='../index.php' style='margin-right: 10px;' class='btn btn-secondary'>Ana Sayfa</a>";
    echo "<a href='../login.php' class='btn btn-success'>Giriş Yap</a>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='color: red;'><h3>❌ Hata:</h3>";
    echo "Mesaj: " . $e->getMessage() . "<br>";
    echo "Dosya: " . $e->getFile() . "<br>";
    echo "Satır: " . $e->getLine() . "</div>";
}
?>