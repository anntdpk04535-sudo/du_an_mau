# Tích hợp Dashboard, Quản lý User, Bộ lọc & Cẩm nang du lịch

## Tổng quan
Kế hoạch này sẽ bổ sung các tính năng quản trị (Admin Dashboard & User Management), Bộ lọc nâng cao cho điểm đến và trang Cẩm nang (Blog) theo yêu cầu của bạn. 
Lưu ý: Tính năng Tích hợp bản đồ (Map) và Xem lại lịch trình đã lưu (Itinerary View) đã có sẵn trên hệ thống nên chúng ta sẽ tập trung hoàn thiện 3 tính năng còn lại.

## User Review Required
> [!IMPORTANT]
> - Cột `status` (active, banned) sẽ được thêm vào bảng `users`.
> - Bảng `articles` sẽ được tạo mới để lưu các bài viết cẩm nang du lịch.
> - Các thay đổi này yêu cầu cập nhật cơ sở dữ liệu (`database/daklak_travel.sql` và chạy script update). Xin xác nhận để tôi tiến hành.

## Proposed Changes

### Database Changes
#### [NEW] `database/migration_update.php`
- Script thực thi ALTER TABLE `users` thêm cột `status`.
- Script tạo bảng `articles` (id, title, slug, summary, content, image_url, status, created_at).
#### [MODIFY] `database/daklak_travel.sql`
- Cập nhật cấu trúc db gốc cho các lệnh CREATE TABLE mới.

---

### Phase 1: Admin Dashboard & User Management

#### [NEW] `admin/index.php`
- Tạo trang tổng quan (Dashboard) hiển thị: tổng người dùng, tổng lịch trình tạo bởi AI, tổng số tin nhắn chat, và các điểm đến đánh giá cao.
- Tích hợp biểu đồ trực quan (sử dụng Chart.js).
#### [NEW] `admin/users.php`
- Giao diện danh sách người dùng.
- Thêm chức năng Khóa/Mở khóa tài khoản (ban) và Cấp/Hủy quyền Admin.
#### [MODIFY] `public/login.php`
- Cập nhật hàm xử lý đăng nhập để chặn người dùng có trạng thái (status = banned).
#### [MODIFY] `admin/includes/header.php` (hoặc nơi chứa menu admin)
- Bổ sung link điều hướng tới "Dashboard" và "Quản lý Người dùng".

---

### Phase 2: Advanced Filter (Tìm kiếm & Lọc Điểm đến)

#### [MODIFY] `includes/functions.php`
- Cập nhật hàm `getAllDestinations()` (hoặc tạo hàm mới) để nhận tham số lọc:
  - `keyword`: Tìm kiếm Text.
  - `price_level`: Mức giá (free, low, medium, high).
  - `min_rating`: Đánh giá tối thiểu (>= 4 sao).

#### [MODIFY] `public/destinations.php`
- Thêm Form bộ lọc (Filter Form) trên danh sách hiển thị.
- Giữ nguyên giao diện bản đồ, tự động cập nhật bản đồ dựa theo kết quả lọc mới.

---

### Phase 3: Cẩm nang Du lịch (Blog)

#### [NEW] `public/articles.php`
- Trang danh sách hiển thị tất cả các bài viết (Kinh nghiệm du lịch, Top list, v.v.).
#### [NEW] `public/article.php`
- Trang hiển thị chi tiết bài viết (nội dung chi tiết).
#### [NEW] `admin/articles.php`
- Trang quản lý bài viết cho admin (Thêm, Sửa, Xóa).
#### [MODIFY] `includes/header.php`
- Bổ sung menu điều hướng "Cẩm nang" trên thanh menu công cộng.

## Verification Plan
### Automated & Manual Verification
- Chạy script cập nhật DB thành công.
- Truy cập Admin: Xem biểu đồ trên Dashboard, tiến hành cấp/thu hồi quyền admin và khóa tài khoản thử nghiệm.
- Kiểm tra lại Login bằng tài khoản bị khóa.
- Truy cập `destinations.php` thử tìm kiếm "Dray Nur" và lọc giá "free" xem kết quả trả về đúng không.
- Truy cập `articles.php` xem hiển thị giao diện Blog, và tạo thử bài viết từ Admin.
