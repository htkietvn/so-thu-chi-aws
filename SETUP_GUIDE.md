# 🚀 HƯỚNG DẪN SETUP - SỔ THU CHI (XAMPP ĐÃ CÓ)

## ✅ Bạn đã có: MySQL và Apache đang chạy!

---

## BƯỚC 1: TÌM ĐƯỜNG DẪN PHP

Mở Command Prompt (CMD) và chạy:

```cmd
where php
```

Nếu không thấy, thử:
```cmd
C:\xampp\php\php.exe --version
```

Hoặc:
```cmd
dir C:\xampp\php\php.exe
```

**→ Ghi nhớ đường dẫn PHP, ví dụ: `C:\xampp\php\php.exe`**

---

## BƯỚC 2: SETUP DATABASE

### 2.1. Mở MySQL Command Line

**Cách 1:** Dùng XAMPP Control Panel
- Mở XAMPP Control Panel
- Click "Shell" button (góc phải)
- Trong shell, gõ:
```bash
mysql -u root -p
```
- Password: (thường để trống, nhấn Enter)

**Cách 2:** Command Prompt
```cmd
C:\xampp\mysql\bin\mysql.exe -u root -p
```

### 2.2. Tạo Database

Trong MySQL prompt (`mysql>`), chạy từng lệnh:

```sql
CREATE DATABASE expense_book CHARACTER SET utf8mb4;
```

```sql
USE expense_book;
```

```sql
SOURCE C:/Users/htkie/Downloads/Manguon_So_thu_chi/Manguon_So_thu_chi/database/schema.sql;
```

**⚠️ Lưu ý:** Dùng dấu `/` (forward slash), không phải `\` (backslash)

### 2.3. Tạo User và Cấp Quyền

```sql
CREATE USER 'expense_app'@'localhost' IDENTIFIED BY 'TestPassword123';
```

```sql
GRANT SELECT, INSERT ON expense_book.users TO 'expense_app'@'localhost';
```

```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON expense_book.entries TO 'expense_app'@'localhost';
```

```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON expense_book.app_sessions TO 'expense_app'@'localhost';
```

```sql
FLUSH PRIVILEGES;
```

```sql
EXIT;
```

---

## BƯỚC 3: CẤU HÌNH ỨNG DỤNG

### 3.1. Mở thư mục project
```cmd
cd C:\Users\htkie\Downloads\Manguon_So_thu_chi\Manguon_So_thu_chi
```

### 3.2. Copy file config
```cmd
copy config\database.example.json config\database.json
```

### 3.3. Sửa file config

Mở file `config\database.json` bằng Notepad:

```json
{
  "host": "127.0.0.1",
  "database": "expense_book",
  "username": "expense_app",
  "password": "TestPassword123",
  "ssl_ca": ""
}
```

**Lưu lại và đóng Notepad.**

---

## BƯỚC 4: TẠO TÀI KHOẢN TEST

### 4.1. Mở Command Prompt tại thư mục project
```cmd
cd C:\Users\htkie\Downloads\Manguon_So_thu_chi\Manguon_So_thu_chi
```

### 4.2. Chạy lệnh tạo user

**Cách 1:** PowerShell
```powershell
"DemoPassword123" | C:\xampp\php\php.exe scripts\create-user.php demo@test.com
```

**Cách 2:** CMD (2 lệnh)
```cmd
echo DemoPassword123 > temp_password.txt
type temp_password.txt | C:\xampp\php\php.exe scripts\create-user.php demo@test.com
del temp_password.txt
```

**Kết quả mong đợi:**
```
✓ User created successfully.
  User ID: 1
  Email: demo@test.com

User can now log in at the application URL.
```

---

## BƯỚC 5: START WEB SERVER

### 5.1. Mở Command Prompt mới
```cmd
cd C:\Users\htkie\Downloads\Manguon_So_thu_chi\Manguon_So_thu_chi
```

### 5.2. Chạy PHP server
```cmd
C:\xampp\php\php.exe -S 127.0.0.1:8080 -t public
```

**Kết quả:**
```
[Wed Nov 20 08:50:00 2026] PHP 8.3.x Development Server (http://127.0.0.1:8080) started
```

**⚠️ Để cửa sổ này mở! Không tắt.**

---

## BƯỚC 6: TEST TRONG BROWSER

### 6.1. Mở browser và vào:
```
http://127.0.0.1:8080
```

### 6.2. Đăng nhập:
- **Email:** `demo@test.com`
- **Password:** `DemoPassword123`
- Click **"Vào sổ thu chi"**

### 6.3. Test các tính năng:

✅ **Tạo khoản thu:**
- Loại: Khoản thu
- Danh mục: Lương / trợ cấp
- Số tiền: 5000000
- Ngày: 2026-11-20
- Ghi chú: Lương tháng 11
- Click "Lưu giao dịch"

✅ **Tạo khoản chi:**
- Loại: Khoản chi
- Danh mục: Ăn uống
- Số tiền: 150000
- Ngày: 2026-11-20
- Ghi chú: Cơm trưa

✅ **Kiểm tra tính toán:**
- Tổng thu: 5,000,000 ₫
- Tổng chi: 150,000 ₫
- Chênh lệch: 4,850,000 ₫

✅ **Test sửa/xóa:**
- Click "Sửa" trên giao dịch
- Thay đổi số tiền
- Click "Lưu" → Verify tổng thay đổi
- Click "Xóa" → Confirm → Verify giao dịch biến mất

✅ **Test đăng xuất:**
- Click "Đăng xuất"
- Verify quay về trang login

---

## 🎯 TÀI KHOẢN TEST

**URL:** http://127.0.0.1:8080

**Tài khoản 1:**
- Email: `demo@test.com`
- Password: `DemoPassword123`

**Tạo thêm tài khoản 2** (để test isolation):
```cmd
"AnotherPass456" | C:\xampp\php\php.exe scripts\create-user.php user2@test.com
```

**Tài khoản 2:**
- Email: `user2@test.com`
- Password: `AnotherPass456`

**Test:** Đăng nhập user 1, tạo giao dịch. Logout. Đăng nhập user 2 → Không thấy giao dịch của user 1 ✅

---

## ⚠️ TROUBLESHOOTING

### ❌ Lỗi: "php is not recognized"
**Fix:** Dùng đường dẫn đầy đủ:
```cmd
C:\xampp\php\php.exe
```

Hoặc thêm PHP vào PATH:
```cmd
set PATH=%PATH%;C:\xampp\php
```

### ❌ Lỗi: "mysql: command not found"
**Fix:** Dùng đường dẫn đầy đủ:
```cmd
C:\xampp\mysql\bin\mysql.exe -u root -p
```

### ❌ Lỗi: "Can't connect to database"
**Check:**
1. MySQL có đang chạy không?
   - Mở XAMPP Control Panel
   - MySQL phải có status "Running" màu xanh

2. Credentials trong `config\database.json` có đúng không?

3. Test kết nối:
```cmd
C:\xampp\mysql\bin\mysql.exe -u expense_app -p expense_book
```
(Password: `TestPassword123`)

### ❌ Lỗi: "Session unavailable"
**Check:**
1. File `config\database.json` có tồn tại không?
```cmd
dir config\database.json
```

2. Database có table `app_sessions` không?
```sql
USE expense_book;
SHOW TABLES;
```

### ❌ Port 8080 đã được dùng
**Fix:** Dùng port khác:
```cmd
C:\xampp\php\php.exe -S 127.0.0.1:8081 -t public
```
Truy cập: http://127.0.0.1:8081

### ❌ Lỗi: "Could not read password from STDIN"
**Fix:** Dùng PowerShell thay vì CMD:
```powershell
"DemoPassword123" | C:\xampp\php\php.exe scripts\create-user.php demo@test.com
```

---

## 📋 CHECKLIST

- [ ] MySQL đang chạy (XAMPP Control Panel)
- [ ] Database `expense_book` đã tạo
- [ ] User `expense_app` có quyền
- [ ] File `config\database.json` tồn tại và đúng
- [ ] Tài khoản test đã tạo
- [ ] PHP server đang chạy port 8080
- [ ] Browser mở được http://127.0.0.1:8080
- [ ] Đăng nhập thành công
- [ ] Có thể tạo/sửa/xóa giao dịch

---

## 🌐 MUỐN CHIA SẺ LINK CHO NGƯỜI KHÁC?

### Option 1: Ngrok (Miễn phí)
1. Download: https://ngrok.com/download
2. Giải nén và chạy:
```cmd
ngrok.exe http 8080
```
3. Copy link https://xxxx-xxx-xxx.ngrok.io
4. Gửi link cho người khác test

### Option 2: LocalTunnel
```cmd
npm install -g localtunnel
lt --port 8080
```

---

## 📞 CẦN TRỢ GIÚP?

**Khi gặp lỗi, gửi cho tôi:**
1. Message lỗi chính xác
2. Bước nào bạn đang thực hiện
3. Screenshot (nếu có)

**Tôi sẽ giúp bạn fix ngay!** 😊
