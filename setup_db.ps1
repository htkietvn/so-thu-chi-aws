# Setup Database Script - Không cần MySQL client
Write-Host "`n🚀 SETUP DATABASE CHO SỔ THU CHI" -ForegroundColor Cyan
Write-Host "================================`n"

# Check nếu có MySQL.Data.dll (MySQL Connector)
# Nếu không có, dùng cách thủ công

Write-Host "📋 Tạo file SQL commands..." -ForegroundColor Yellow

# Tạo SQL script hoàn chỉnh
$sqlContent = @"
CREATE DATABASE IF NOT EXISTS expense_book CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE expense_book;

-- Tables
CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(254) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS entries (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 kind VARCHAR(7) NOT NULL CHECK (kind IN ('income','expense')),
 amount BIGINT UNSIGNED NOT NULL CHECK (amount BETWEEN 1 AND 1000000000000),
 category VARCHAR(24) NOT NULL,
 occurred_on DATE NOT NULL,
 note VARCHAR(500) NOT NULL DEFAULT '',
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX ix_owner_date (user_id, occurred_on, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS app_sessions (
 id VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 data MEDIUMBLOB NOT NULL, 
 expires_at BIGINT UNSIGNED NOT NULL,
 INDEX ix_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User
CREATE USER IF NOT EXISTS 'expense_app'@'localhost' IDENTIFIED BY 'TestPass123';
GRANT SELECT, INSERT ON expense_book.users TO 'expense_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON expense_book.entries TO 'expense_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON expense_book.app_sessions TO 'expense_app'@'localhost';
FLUSH PRIVILEGES;

SELECT 'Setup complete!' AS Status;
"@

$sqlContent | Out-File -FilePath "setup_complete.sql" -Encoding UTF8

Write-Host "✅ Đã tạo file: setup_complete.sql`n" -ForegroundColor Green

Write-Host "📌 BÂY GIỜ LÀM THẾ NÀY:" -ForegroundColor Yellow
Write-Host "================================`n"
Write-Host "CÁCH 1 - Dùng phpMyAdmin (DỄ NHẤT):" -ForegroundColor Cyan
Write-Host "1. Mở browser: http://localhost/phpmyadmin"
Write-Host "2. Click tab 'SQL' ở trên"
Write-Host "3. Copy nội dung file 'setup_complete.sql' và paste vào"
Write-Host "4. Click 'Go' để chạy`n"

Write-Host "CÁCH 2 - Tìm MySQL client thủ công:" -ForegroundColor Cyan
Write-Host "1. Mở XAMPP Control Panel"
Write-Host "2. Nhìn vào dòng MySQL, xem folder ở đâu"
Write-Host "3. Thường là: C:\xampp\mysql\bin\mysql.exe"
Write-Host "4. Chạy lệnh:`n"
Write-Host "   C:\xampp\mysql\bin\mysql.exe -u root -p < setup_complete.sql`n" -ForegroundColor White

Write-Host "CÁCH 3 - Dùng HeidiSQL (nếu có cài):" -ForegroundColor Cyan
Write-Host "1. Mở HeidiSQL"
Write-Host "2. File > Load SQL file > Chọn setup_complete.sql"
Write-Host "3. Run`n"

Write-Host "✅ Sau khi chạy xong, quay lại và báo tôi nhé!" -ForegroundColor Green
Write-Host "`n================================`n"

# Tạo luôn config file
Write-Host "📝 Đang tạo config file..." -ForegroundColor Yellow

$configContent = @"
{
  "host": "127.0.0.1",
  "database": "expense_book",
  "username": "expense_app",
  "password": "TestPass123",
  "ssl_ca": ""
}
"@

$configPath = "config\database.json"
if (Test-Path "config\database.example.json") {
    $configContent | Out-File -FilePath $configPath -Encoding UTF8
    Write-Host "✅ Đã tạo: $configPath`n" -ForegroundColor Green
}

Write-Host "📦 FILES ĐÃ TẠO:" -ForegroundColor Cyan
Write-Host "- setup_complete.sql (để import vào MySQL)"
Write-Host "- config\database.json (config app)`n"
