-- create_tables.sql
-- Bilet Satın Alma Platformu Veritabanı Kurulumu

-- 1. Kullanıcılar Tablosu
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

-- 2. Otobüs Firmaları Tablosu
CREATE TABLE IF NOT EXISTS bus_company (
    id TEXT PRIMARY KEY,
    name TEXT UNIQUE NOT NULL,
    logo_path TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Seferler Tablosu
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

-- 4. Biletler Tablosu
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

-- 5. İndirim Kuponları Tablosu
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

-- Kullanıcı-Kupon İlişkisi Tablosu
CREATE TABLE IF NOT EXISTS user_coupons (
    id TEXT PRIMARY KEY,
    coupon_id TEXT NOT NULL,
    user_id TEXT NOT NULL,    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id),
    FOREIGN KEY (user_id) REFERENCES user(id)
);

-- 6. Dolu Koltuklar Tablosu
CREATE TABLE IF NOT EXISTS booked_seats (
    id TEXT PRIMARY KEY,
    ticket_id TEXT NOT NULL,
    seat_number INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);