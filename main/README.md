# Đắk Lắk Travel AI 🌿

Website du lịch Đắk Lắk viết bằng PHP thuần (PDO + MySQL), tích hợp AI (Claude API) cho:
- 🤖 Chatbot tư vấn du lịch
- 🧭 Gợi ý lịch trình tự động theo số ngày & sở thích

## 1. Cấu trúc dự án

```
daklak-travel/
├── config/
│   ├── db.php          # Kết nối MySQL (PDO)
│   └── ai.php          # Gọi Claude API
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── public/              # Các trang người dùng xem
│   ├── index.php
│   ├── destinations.php
│   ├── destination.php
│   ├── itinerary.php    # Form + kết quả lịch trình AI
│   └── chatbot.php
├── api/                  # Endpoint AJAX gọi AI
│   ├── chat.php
│   └── generate_itinerary.php
├── admin/                # Quản trị (CRUD điểm đến)
│   ├── login.php
│   └── destinations.php
├── database/
│   ├── schema.sql        # Tạo DB + dữ liệu mẫu
│   └── create_admin.php  # Script tạo tài khoản admin đầu tiên
└── assets/css/style.css
```

## 2. Yêu cầu

- PHP >= 8.0 (có extension `pdo_mysql`, `curl`)
- MySQL/MariaDB
- 1 API key của Anthropic (https://console.anthropic.com/)

## 3. Cài đặt

### Bước 1: Import database
```bash
mysql -u root -p < database/schema.sql
```
Hoặc mở phpMyAdmin → Import → chọn file `database/schema.sql`.

### Bước 2: Cấu hình kết nối DB
Sửa file `config/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'daklak_travel');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Bước 3: Cấu hình API key AI
Cách 1 (khuyên dùng) — set biến môi trường trước khi chạy PHP:
```bash
export ANTHROPIC_API_KEY="sk-ant-xxxxx"
```
Cách 2 — sửa trực tiếp trong `config/ai.php`:
```php
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: 'sk-ant-xxxxx');
```

### Bước 4: Tạo tài khoản admin
Mở trình duyệt vào:
```
http://localhost/daklak-travel/database/create_admin.php
```
Tài khoản mặc định: `admin@daklaktravel.vn` / `admin123` (đổi mật khẩu ngay sau đó, rồi **xoá file `create_admin.php`**).

### Bước 5: Chạy thử local bằng PHP built-in server
```bash
cd daklak-travel
php -S localhost:8000
```
Truy cập: `http://localhost:8000/public/index.php`

> Nếu dùng XAMPP/cPanel: copy thư mục `daklak-travel` vào `htdocs` (hoặc `public_html`), rồi mở `http://localhost/daklak-travel/public/index.php`.

## 4. Các trang chính

| URL | Mô tả |
|---|---|
| `/public/index.php` | Trang chủ |
| `/public/destinations.php` | Danh sách điểm đến (lọc theo danh mục) |
| `/public/destination.php?slug=ho-lak` | Trang chi tiết điểm đến |
| `/public/itinerary.php` | Form tạo lịch trình bằng AI |
| `/public/chatbot.php` | Chatbot AI tư vấn du lịch |
| `/admin/login.php` | Đăng nhập quản trị |
| `/admin/destinations.php` | CRUD điểm đến (cần đăng nhập admin) |

## 5. Cách AI hoạt động

- **Chatbot** (`api/chat.php`): gửi câu hỏi của người dùng + danh sách điểm đến (từ DB) + lịch sử chat gần nhất tới Claude API, lưu lại lịch sử vào bảng `chat_logs`.
- **Lịch trình** (`api/generate_itinerary.php`): gửi số ngày + sở thích tới Claude API với yêu cầu trả về JSON, parse kết quả, lưu vào bảng `itineraries` + `itinerary_items`, rồi hiển thị ra giao diện.

## 6. Bảo mật / Lưu ý production

- Đổi mật khẩu admin mặc định, xoá `database/create_admin.php` sau khi dùng.
- Không commit API key vào code — luôn dùng biến môi trường.
- Bật HTTPS khi deploy thật.
- Có thể thêm rate-limit cho `api/chat.php` và `api/generate_itinerary.php` để tránh lạm dụng API AI.
