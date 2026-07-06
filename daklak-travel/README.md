# Đắk Lắk Travel AI 🌿

Website du lịch Đắk Lắk viết bằng PHP thuần (PDO + MySQL), tích hợp AI (Google Gemini API) cho:
- 🤖 Chatbot tư vấn du lịch (kèm ảnh minh hoạ từ Unsplash)
- 🧭 Gợi ý lịch trình tự động theo số ngày & sở thích (kèm bản đồ lộ trình Leaflet + OSRM)
- ⭐ Đánh giá điểm đến / website
- 📬 Liên hệ 2 chiều (realtime qua SSE, tự fallback sang polling)

## 1. Cấu trúc dự án (đã được tổ chức lại từ bản gốc)

```
daklak-travel/
├── index.php              # Chuyển hướng gốc domain -> public/index.php
├── .env.example           # Mẫu file cấu hình biến môi trường
├── config/
│   ├── env.php            # Đọc file .env
│   ├── db.php             # Kết nối MySQL (PDO)
│   └── ai.php             # Gọi Google Gemini API
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── functions.php      # Helper chung: url(), e(), currentUser(), requireAdmin()...
├── assets/
│   └── css/style.css
├── public/                 # Các trang người dùng xem trực tiếp
│   ├── index.php           # Trang chủ
│   ├── about.php           # Giới thiệu
│   ├── destinations.php    # Danh sách điểm đến (bản đồ + lọc danh mục)
│   ├── destination.php     # Chi tiết điểm đến (đánh giá, ảnh, bản đồ)
│   ├── itinerary.php       # Form tạo lịch trình AI + bản đồ lộ trình
│   ├── chatbot.php         # Chatbot AI
│   ├── contact.php         # Gửi liên hệ + xem phản hồi realtime (SSE)
│   ├── login.php / register.php / logout.php
├── api/                     # Endpoint JSON gọi từ JS (AJAX) - dùng chung cho user
│   ├── chat.php                  # Chatbot AI (Gemini + Unsplash)
│   ├── generate_itinerary.php    # Sinh lịch trình bằng AI (Gemini)
│   ├── review_submit.php / review_get.php / review_edit.php / review_delete.php
│   └── contact_submit.php / contact_my.php / contact_check_reply.php / contact_sse.php
├── admin/                   # Chỉ admin truy cập được (kiểm tra bằng requireAdmin())
│   ├── contacts.php         # Dashboard quản lý liên hệ (long-polling danh sách mới)
│   ├── contact_get.php / contact_mark_read.php / contact_reply.php / contact_poll.php
│   └── reviews.php          # Dashboard quản lý / xoá đánh giá
├── database/
│   └── schema.sql           # Toàn bộ schema + dữ liệu mẫu (đã gộp các migrate_*.php cũ)
```

> **So với bản gốc:** toàn bộ file trước đây nằm chung 1 thư mục (flat) đã được phân
> loại lại theo đúng vai trò (trang công khai / endpoint API / khu quản trị / cấu hình),
> các file di chuyển tạm (`migrate.php`, `migrate_contacts.php`, `migrate_reviews.php`,
> `debug_review_delete.php`) đã được gộp thẳng vào `database/schema.sql` và loại bỏ khỏi
> mã nguồn chạy thật. File `footer.php` và `config/env.php` (thiếu trong bản gửi) đã được
> bổ sung để chức năng include đầy đủ hoạt động đúng.

## 2. Yêu cầu

- PHP >= 8.0 (extension `pdo_mysql`, `curl`)
- MySQL/MariaDB
- API key Google Gemini (https://aistudio.google.com/app/apikey)
- (Tuỳ chọn) Access key Unsplash để chatbot hiển thị ảnh minh hoạ (https://unsplash.com/developers)

## 3. Cài đặt

### Bước 1 — Import database
```bash
mysql -u root -p < database/schema.sql
```

### Bước 2 — Tạo file `.env` từ mẫu
```bash
cp .env.example .env
```
Sửa các giá trị trong `.env`:
```
DB_HOST=localhost
DB_NAME=daklak_travel
DB_USER=root
DB_PASS=
GEMINI_API_KEY=sk-xxxxx
UNSPLASH_ACCESS_KEY=xxxxx
```

### Bước 3 — Tạo tài khoản admin đầu tiên
Chưa có sẵn tài khoản admin trong dữ liệu mẫu. Tạo bằng 1 trong 2 cách:

- **Đăng ký bình thường** ở `/public/register.php`, sau đó cập nhật role trong DB:
  ```sql
  UPDATE users SET role = 'admin' WHERE email = 'ban@example.com';
  ```
- Hoặc chèn trực tiếp bằng PHP (`password_hash('matkhau', PASSWORD_DEFAULT)`), rồi
  `INSERT INTO users (full_name, email, password_hash, role) VALUES (...)`.

### Bước 4 — Chạy thử local
```bash
php -S localhost:8000
```
Truy cập: `http://localhost:8000/index.php` (tự chuyển vào `public/index.php`),
hoặc trực tiếp `http://localhost:8000/public/index.php`.

> Nếu dùng XAMPP/cPanel: copy cả thư mục `daklak-travel` vào `htdocs`/`public_html`,
> rồi mở `http://localhost/daklak-travel/index.php`.

## 4. Các trang chính

| URL | Mô tả | Quyền |
|---|---|---|
| `/public/index.php` | Trang chủ | Công khai |
| `/public/destinations.php` | Danh sách điểm đến (bản đồ + lọc) | Công khai |
| `/public/destination.php?slug=ho-lak` | Chi tiết điểm đến | Công khai |
| `/public/itinerary.php` | Tạo lịch trình bằng AI | Công khai |
| `/public/chatbot.php` | Chatbot AI | Công khai |
| `/public/contact.php` | Gửi liên hệ, theo dõi phản hồi realtime | Công khai |
| `/public/login.php`, `/public/register.php` | Đăng nhập / đăng ký | Công khai |
| `/admin/contacts.php` | Dashboard quản lý liên hệ | Admin |
| `/admin/reviews.php` | Dashboard quản lý đánh giá | Admin |

## 5. Cách AI hoạt động

- **Chatbot** (`api/chat.php`): gửi câu hỏi người dùng + danh sách điểm đến (DB) +
  10 lượt chat gần nhất tới Gemini, lưu lịch sử vào bảng `chat_logs`, kèm dò từ khoá để
  gợi ý ảnh Unsplash liên quan.
- **Lịch trình** (`api/generate_itinerary.php`): gửi số ngày + sở thích tới Gemini,
  yêu cầu trả JSON, đối chiếu địa điểm với DB để lấy toạ độ, lưu vào `itineraries` +
  `itinerary_items`, rồi vẽ lộ trình thật bằng OSRM trên bản đồ Leaflet.

## 6. Bảo mật / Lưu ý production

- Không commit `.env` — đã có trong `.gitignore`.
- Đổi mật khẩu admin định kỳ; hạn chế số tài khoản admin.
- Bật HTTPS khi deploy thật.
- Nên thêm rate-limit cho `api/chat.php` và `api/generate_itinerary.php` để tránh
  lạm dụng chi phí gọi AI.
