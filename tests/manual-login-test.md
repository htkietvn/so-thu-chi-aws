# Manual Login Testing Guide
## Kiểm thử đăng nhập thủ công cho Sổ Thu Chi

### Prerequisites
1. ✅ PHP 8.3+ và MySQL 8.4 đã cài đặt
2. ✅ Database `expense_book` đã được tạo với schema
3. ✅ Ứng dụng đang chạy (php -S 127.0.0.1:8080 -t public)
4. ✅ Có ít nhất 1 user test đã được tạo

---

## Test Case 1: Đăng nhập thành công ✅

**Mục tiêu:** Verify user có thể đăng nhập với credentials hợp lệ

**Bước thực hiện:**
1. Tạo user test:
```bash
echo 'TestPassword123' | php scripts/create-user.php test@example.com
```

2. Mở trình duyệt: http://127.0.0.1:8080

3. Nhập thông tin:
   - Email: `test@example.com`
   - Password: `TestPassword123`

4. Click "Vào sổ thu chi"

**Kết quả mong đợi:**
- ✅ Redirect sang trang chính
- ✅ Không còn form đăng nhập
- ✅ Hiển thị nút "Đăng xuất"
- ✅ Hiển thị form ghi giao dịch
- ✅ Session cookie được set (kiểm tra DevTools → Application → Cookies)

---

## Test Case 2: Đăng nhập thất bại - Sai password ❌

**Bước thực hiện:**
1. Mở http://127.0.0.1:8080
2. Nhập:
   - Email: `test@example.com`
   - Password: `WrongPassword123`
3. Click "Vào sổ thu chi"

**Kết quả mong đợi:**
- ❌ Không redirect
- ⚠️ Hiển thị message: "Email hoặc mật khẩu không đúng."
- ❌ Session không được tạo
- ⚠️ Form đăng nhập vẫn hiển thị

---

## Test Case 3: Đăng nhập thất bại - Email không tồn tại ❌

**Bước thực hiện:**
1. Mở http://127.0.0.1:8080
2. Nhập:
   - Email: `notexist@example.com`
   - Password: `AnyPassword123`
3. Click "Vào sổ thu chi"

**Kết quả mong đợi:**
- ❌ Không redirect
- ⚠️ Hiển thị message: "Email hoặc mật khẩu không đúng."
- ❌ Session không được tạo
- ℹ️ Message giống với sai password (security best practice)

---

## Test Case 4: Rate Limiting ⏱️

**Bước thực hiện:**
1. Thử đăng nhập với wrong password
2. Immediately thử lại ngay lập tức
3. Đợi 3 giây
4. Thử lại lần nữa

**Kết quả mong đợi:**
- ⚠️ Lần 2: HTTP 429 "Vui lòng đợi trước khi thử lại."
- ✅ Sau 3 giây: Cho phép thử lại

**Kiểm tra bằng code:**
```php
// Trong bootstrap hoặc index.php
if (time() < (int)($_SESSION['retry_at']??0)) {
    stop(429,'Vui lòng đợi trước khi thử lại.');
}
```

---

## Test Case 5: CSRF Protection 🔒

**Bước thực hiện:**
1. Mở http://127.0.0.1:8080
2. Mở DevTools → Network
3. Thử đăng nhập và capture request
4. Copy cURL command
5. Modify: xóa hoặc thay đổi csrf token
6. Gửi lại request

**Kết quả mong đợi:**
- ❌ HTTP 403 "Phiên biểu mẫu không hợp lệ."
- ❌ Không tạo session

**Test bằng curl:**
```bash
curl -X POST http://127.0.0.1:8080 \
  -d "action=login" \
  -d "email=test@example.com" \
  -d "password=TestPassword123" \
  -d "csrf=invalid-token"
```

---

## Test Case 6: Session Regeneration 🔄

**Bước thực hiện:**
1. Mở http://127.0.0.1:8080
2. DevTools → Application → Cookies
3. Note session ID (EXPENSESESSID)
4. Đăng nhập thành công
5. Check session ID again

**Kết quả mong đợi:**
- ✅ Session ID khác nhau before/after login
- ✅ Old session không còn valid
- 🔒 Prevents session fixation attacks

---

## Test Case 7: Email Case Insensitive 📧

**Bước thực hiện:**
1. Tạo user: `test@example.com`
2. Thử đăng nhập với:
   - `TEST@EXAMPLE.COM`
   - `Test@Example.Com`
   - `test@example.com`

**Kết quả mong đợi:**
- ✅ Tất cả đều đăng nhập thành công
- ✅ Cùng user ID

---

## Test Case 8: Cookie Security Attributes 🍪

**Bước thực hiện:**
1. Đăng nhập thành công
2. DevTools → Application → Cookies → EXPENSESESSID
3. Kiểm tra attributes

**Kết quả mong đợi:**
- ✅ `HttpOnly`: true (không access từ JavaScript)
- ✅ `SameSite`: Lax (CSRF protection)
- ⚠️ `Secure`: false (HTTP), true khi dùng HTTPS
- ✅ `Path`: /

**Test HttpOnly:**
```javascript
// Trong DevTools Console
document.cookie
// Không thấy EXPENSESESSID → HttpOnly working ✅
```

---

## Test Case 9: Session Persistence 💾

**Bước thực hiện:**
1. Đăng nhập thành công
2. Tạo 1 giao dịch
3. Đóng tab
4. Mở lại http://127.0.0.1:8080

**Kết quả mong đợi:**
- ✅ Vẫn logged in (không cần login lại)
- ✅ Giao dịch vừa tạo vẫn còn
- ✅ Session data lưu trong database table `app_sessions`

**Verify trong DB:**
```sql
SELECT id, expires_at FROM app_sessions;
-- Should see session record
```

---

## Test Case 10: Đăng xuất 🚪

**Bước thực hiện:**
1. Đăng nhập thành công
2. Click nút "Đăng xuất"
3. Check cookies
4. Thử access http://127.0.0.1:8080 again

**Kết quả mong đợi:**
- ✅ Redirect về login page
- ✅ Session cookie bị xóa
- ✅ Không còn data trong $_SESSION
- ✅ Database: session record bị xóa hoặc expired

---

## Test Case 11: Password Hash Security 🔐

**Kiểm tra trong database:**
```sql
SELECT email, password_hash FROM users WHERE email = 'test@example.com';
```

**Kết quả mong đợi:**
- ✅ Password hash starts with `$2y$` (bcrypt)
- ✅ Hash length ≈ 60 characters
- ✅ Không lưu plain text password
- ✅ Mỗi user có hash khác nhau (salt khác nhau)

**Test bcrypt:**
```php
$hash = '$2y$10$...'; // từ database
var_dump(password_verify('TestPassword123', $hash)); // true
var_dump(password_verify('WrongPassword', $hash));   // false
```

---

## Test Case 12: SQL Injection Prevention 💉

**Bước thực hiện:**
Thử các payload SQL injection:

```
Email: ' OR '1'='1
Password: anything

Email: admin'--
Password: anything

Email: '; DROP TABLE users; --
Password: anything
```

**Kết quả mong đợi:**
- ✅ Tất cả đều fail login (không bypass)
- ✅ Không có SQL error
- ✅ Database không bị ảnh hưởng

---

## Test Case 13: XSS Prevention trong Error Message 🛡️

**Bước thực hiện:**
1. Thử đăng nhập với:
```
Email: <script>alert('XSS')</script>@test.com
Password: test
```

**Kết quả mong đợi:**
- ✅ Script tags được escape trong error message
- ✅ Không execute JavaScript
- ✅ Hiển thị as text: `&lt;script&gt;alert('XSS')&lt;/script&gt;`

---

## Test Case 14: Long Password Handling 📏

**Bước thực hiện:**
1. Tạo user với password 72 bytes (bcrypt limit)
2. Thử password 73+ bytes

**Test:**
```bash
# 72 characters
echo 'PasswordThatIsExactly72CharactersLongToTestBcryptLimitAndHandlingXXXX' | php scripts/create-user.php long@test.com

# 73 characters - should fail
echo 'PasswordThatIsExactly73CharactersLongToTestBcryptLimitAndHandlingXXXXX' | php scripts/create-user.php toolong@test.com
```

**Kết quả mong đợi:**
- ✅ 72 bytes: Accepted
- ❌ 73+ bytes: Rejected với error message

---

## Test Case 15: Concurrent Login Sessions 👥

**Bước thực hiện:**
1. Browser A: Đăng nhập user test@example.com
2. Browser B (hoặc Incognito): Đăng nhập cùng user
3. Thao tác trên cả 2 browsers

**Kết quả mong đợi:**
- ✅ Cả 2 sessions đều valid
- ✅ Mỗi browser có session riêng
- ✅ Data isolation (giao dịch A không ảnh hưởng session B)

---

## Security Checklist Summary ✅

- [x] Password hashing với bcrypt
- [x] CSRF token validation
- [x] SQL injection prevention (prepared statements)
- [x] XSS prevention (htmlspecialchars)
- [x] Session fixation prevention (regenerate ID)
- [x] HttpOnly cookies
- [x] SameSite cookies
- [x] Rate limiting (basic)
- [x] Email case insensitive
- [x] Constant-time password comparison
- [x] Session data in database
- [x] Proper logout cleanup

---

## Performance Test 🚀

**Đo thời gian login:**
```bash
time curl -X POST http://127.0.0.1:8080 \
  -c cookies.txt \
  -d "action=login" \
  -d "email=test@example.com" \
  -d "password=TestPassword123" \
  -d "csrf=..."
```

**Kết quả mong đợi:**
- ⚡ < 500ms cho successful login
- ⚡ Similar time cho failed login (prevent timing attacks)

---

## Troubleshooting 🔧

### Lỗi "Session unavailable"
- Check MySQL running
- Check config/database.json exists
- Check app_sessions table exists

### Lỗi "Database connection failed"
- Check credentials trong config/database.json
- Check MySQL user permissions

### Cookie không được set
- Check browser không block cookies
- Check PHP session_start() success
- Check session.cookie_params configured

### Rate limiting không hoạt động
- Check $_SESSION['retry_at'] được set
- Check time() comparison logic

---

## Automated Testing với curl 🤖

```bash
#!/bin/bash
# Quick login test script

# Test 1: Valid login
echo "Test 1: Valid credentials"
curl -s -X POST http://127.0.0.1:8080 \
  -c cookies.txt \
  -d "action=login&email=test@example.com&password=TestPassword123&csrf=test" \
  | grep -q "Đăng xuất" && echo "✅ PASS" || echo "❌ FAIL"

# Test 2: Invalid password
echo "Test 2: Invalid password"
curl -s -X POST http://127.0.0.1:8080 \
  -d "action=login&email=test@example.com&password=wrong&csrf=test" \
  | grep -q "không đúng" && echo "✅ PASS" || echo "❌ FAIL"

# Test 3: Non-existent user
echo "Test 3: Non-existent user"
curl -s -X POST http://127.0.0.1:8080 \
  -d "action=login&email=fake@example.com&password=test&csrf=test" \
  | grep -q "không đúng" && echo "✅ PASS" || echo "❌ FAIL"

# Test 4: Missing CSRF
echo "Test 4: CSRF protection"
curl -s -X POST http://127.0.0.1:8080 \
  -d "action=login&email=test@example.com&password=TestPassword123" \
  | grep -q "403" && echo "✅ PASS" || echo "❌ FAIL"

echo "Done!"
```

---

## Kết luận

Sau khi chạy hết test cases trên, bạn sẽ verify được:
- ✅ Authentication mechanism hoạt động đúng
- ✅ Security controls được implement proper
- ✅ Session management an toàn
- ✅ Input validation đầy đủ
- ✅ Error handling appropriate

**Next steps:**
1. Chạy tests trên local environment
2. Document kết quả
3. Test trên AWS deployment
4. Add automated PHPUnit tests
