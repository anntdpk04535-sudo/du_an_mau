# Thiết kế: Nền tảng media, mặt tiền và RAG

Ngày: 2026-08-06
Trạng thái: DA1 đã chốt thiết kế · DA2, DA3 mới ở mức phác thảo

## 1. Bối cảnh

Website du lịch Đắk Lắk – Phú Yên (PHP/MySQL thuần, không framework). Yêu cầu ban đầu:
bổ sung đầy đủ ảnh cho ẩm thực, điểm đến, lưu trú, bài viết; làm website chuyên nghiệp hơn;
triển khai RAG cho chatbot hiệu quả.

Khảo sát thực tế cho thấy vấn đề không nằm ở CSS mà ở dữ liệu và ở hai lỗi logic cụ thể.

### 1.1 Hiện trạng ảnh

| Nội dung | Bản ghi | Có ảnh | Thực tế |
|---|---|---|---|
| Điểm đến | 108 | 16 | 16 ảnh đều hotlink bên thứ ba (8 là thumbnail Google `encrypted-tbn0`); 92 điểm trắng ảnh |
| Ẩm thực | 501 | 432 | 368 dòng trỏ **cùng một** file `/assets/images/article_1_food.png` — file không tồn tại (thật ra ở `uploads/`); 64 dòng còn lại hotlink. Sau khi dọn rác, **437/501 món/quán trắng ảnh** |
| Lưu trú | 245 | 0 | Không ảnh, không lat/lng, không giá |
| Bài viết | 4 | 4 | Path sai gốc app `/travel_daklak/…` (site chạy ở `/du_an_mau`) → 404 cả 4 |
| Sự kiện | 5 | 5 | Ít nhất 2 file không tồn tại |

Không có `onerror`/placeholder fallback ở bất kỳ đâu → người dùng thấy icon ảnh vỡ.

### 1.2 Hai lỗi gốc đã xác minh

**Lỗi 1 — RAG kẹt vĩnh viễn ở 18%** (`includes/rag.php:55`, `ragUpsertDocuments()`)

`content_hash` được ghi **kể cả khi gọi embedding thất bại**:

```php
$s->execute([..., $hash, $v ? $model : null, $v ? json_encode($v) : null]);
//                  ↑ ghi vô điều kiện    ↑ NULL khi API lỗi
```

trong khi vòng lặp bỏ qua bản ghi dựa trên chính hash đó:

```php
if ($check->fetchColumn() === $hash) { $skipped++; continue; }
```

Hệ quả: **520/638 doc có `content_hash` nhưng `embedding_json` NULL**, và mọi lần reindex
sau đều bỏ qua chúng. Chạy lại script bao nhiêu lần cũng không sửa được.
Đã kiểm chứng API không phải nguyên nhân: `gemini-embedding-2` trả HTTP 200, 768 chiều.

Phân bố hiện tại: destination 101/108, food 17/501, accommodation 0/29 (mà 245 cơ sở
lưu trú mới chỉ sinh được 29 doc).

**Lỗi 2 — migration `20260806_region_split` chưa từng được áp dụng**

`schema_migrations` chỉ chứa `20260806_upgrade`. Hệ quả:
`foods.region` NULL 432/501, `accommodations.region` NULL 245/245,
`destinations.province` NULL 108/108 → không lọc được theo tỉnh ở bất kỳ đâu.

**Lỗi 3 — nguyên nhân cơ chế của path hỏng** (`includes/content_helpers.php:44`)

`uploadLocalImage()` trả về `url(...)`, tức ghi cả `BASE_URL` vào DB. `BASE_URL` lại
được suy ra từ đường dẫn script lúc chạy. Đây là cơ chế sinh ra `/travel_daklak/…`;
không sửa gốc thì đổi thư mục hoặc domain là hỏng lại.

### 1.3 Hiện trạng RAG khác

- `ragSearch()` mỗi truy vấn `SELECT *` toàn bộ `search_documents` rồi `json_decode`
  768 số cho từng dòng trong PHP.
- Chỉ có locale `vi`, không có `en`.
- Không lọc theo ngưỡng similarity — kết quả rác vẫn được trả.
- Fallback keyword chỉ kích hoạt khi kết quả **rỗng**, không khi điểm số đều thấp.
- `ragResultCards()` link ẩm thực/lưu trú về `chatbot.php?ask=…` vì chưa có trang chi tiết.

## 2. Quyết định đã chốt

| Quyết định | Lựa chọn |
|---|---|
| Nguồn ảnh | **Chỉ ảnh thật, không dùng AI sinh ảnh** |
| Nguồn được phép | Wikimedia Commons · Unsplash/Pexels · Google Places Photos · upload thủ công |
| Thứ tự triển khai | Ảnh & giao diện trước, RAG sau |
| Phạm vi "pro hơn" | Trang chi tiết ẩm thực/lưu trú · visual/UI · SEO & hiệu năng · chuẩn hóa dữ liệu |
| Chi phí Places | Chấp nhận (ước tính lại: ~$20 một lần, xem mục 6.2) |
| Ảnh minh họa cho điểm đến | **Không** — thà placeholder còn hơn gán ảnh địa danh khác |
| Hạn mức Unsplash | Giữ free tier (50 req/giờ), chấp nhận ~10 giờ chạy nền |

## 3. Phân rã dự án

Phạm vi quá lớn cho một spec. Chia theo thứ tự phụ thuộc, mỗi phần có spec → plan → triển khai riêng.

- **DA1 — Nền tảng media & sửa dữ liệu hỏng** (chi tiết trong tài liệu này)
- **DA2 — Nâng cấp mặt tiền** (phác thảo, chốt sau)
- **DA3 — RAG & chatbot** (phác thảo, chốt sau)

---

# DA1 — Nền tảng media & sửa dữ liệu hỏng

## 4. Mô hình dữ liệu

Hai bảng dùng chung, thay cho việc nhân bản cột license ra 6 bảng riêng
(`articles` và `events` hiện còn chưa có bảng ảnh nào).

```sql
CREATE TABLE media_assets (
  id               bigint AUTO_INCREMENT PRIMARY KEY,
  storage_path     varchar(300) NULL,      -- '/assets/images/media/ab/cd/<hash>.webp'
                                           -- KHÔNG chứa BASE_URL
  content_hash     char(64) NULL,          -- sha256 nội dung file
  mime             varchar(40) NULL,
  width            int NULL,
  height           int NULL,
  bytes            int NULL,
  source           enum('wikimedia','unsplash','google_places','upload') NOT NULL,
  source_url       varchar(600) NULL,
  author           varchar(200) NULL,
  license          varchar(80) NULL,       -- 'CC BY-SA 4.0', 'Unsplash License'
  license_url      varchar(300) NULL,
  attribution_text varchar(400) NULL,
  place_photo_ref  varchar(600) NULL,      -- riêng Google Places; không lưu file
  fetched_at       timestamp NULL,
  created_at       timestamp DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_media_hash (content_hash)
);

CREATE TABLE media_links (
  id           bigint AUTO_INCREMENT PRIMARY KEY,
  asset_id     bigint NOT NULL,
  entity_type  varchar(32) NOT NULL,   -- destination|food|accommodation|article|event|review
  entity_id    bigint NOT NULL,
  role         enum('primary','gallery') NOT NULL DEFAULT 'gallery',
  authenticity enum('actual','illustrative') NOT NULL DEFAULT 'illustrative',
  alt_text     varchar(255) NULL,
  caption      varchar(500) NULL,
  sort_order   int NOT NULL DEFAULT 0,
  UNIQUE KEY uq_link (entity_type, entity_id, asset_id),
  KEY idx_entity (entity_type, entity_id, role, sort_order),
  CONSTRAINT fk_media_links_asset FOREIGN KEY (asset_id)
    REFERENCES media_assets(id) ON DELETE CASCADE
);
```

### 4.1 Ba điểm thiết kế cốt lõi

**`storage_path` không chứa `BASE_URL`.** DB chỉ lưu đường dẫn tương đối; `url()` được
ghép ở tầng render. Diệt tận gốc cả lớp bug `/travel_daklak/`. Đổi lại phải sửa
`uploadLocalImage()` và mọi chỗ đang viết `<img src="<?= e($x['image_url']) ?>">`.

**`content_hash` UNIQUE.** Cùng nội dung ⇒ cùng một `media_assets`, chỉ khác `media_links`.
Vụ 368 dòng trùng không thể tái diễn. Cho phép NULL để chứa bản ghi Google Places
(không có file cục bộ — MySQL cho phép nhiều NULL trong unique index).

**`authenticity`** giữ đúng cam kết "chỉ ảnh thật":

- `actual` — Wikimedia đúng địa danh, Google Places đúng venue, admin upload. Không nhãn.
- `illustrative` — ảnh Unsplash generic gắn vào đối tượng cụ thể. UI hiện nhãn
  **"Ảnh minh họa"**.

Người dùng luôn phân biệt được ảnh thật và ảnh gợi ý. Đây là lý do loại AI sinh ảnh,
nên phải nhất quán với ảnh stock generic.

### 4.2 Tương thích ngược

Các bảng `destination_images`, `food_images`, `accommodation_images`, `review_images`
**giữ nguyên, không xóa**. Migration chỉ backfill sang bảng mới; phần đọc chuyển dần
sang `includes/media.php`. Cột `destinations.image_url` giữ lại cho code cũ trong
suốt DA1.

## 5. Giai đoạn 0 — Sửa dữ liệu hỏng

Chạy trước mọi thứ khác. Mọi thao tác xóa đã được người dùng xác nhận.

| # | Việc | Số lượng |
|---|---|---|
| 1 | Xóa dòng `food_images` trỏ file không tồn tại (rác trùng lặp, không cứu được) | 368 |
| 2 | Bỏ tiền tố `/travel_daklak` ở `articles.image_url`; `article_4_guide.png` nằm ở `assets/images/` chứ không phải `uploads/` → sửa path | 4 |
| 3 | `events`: `article_1_lake.png` không tồn tại → gỡ tham chiếu, chờ backfill | 2 |
| 4 | Xóa hotlink `encrypted-tbn0.gstatic.com` (thumbnail Google Images, không giấy phép, URL không ổn định) — 32 ở `food_images` + 8 ở `destination_images` | 40 |
| 5 | Đánh dấu hotlink báo/blog còn lại để thay bằng Wikimedia/Places — không hợp thức hóa ảnh có bản quyền bên thứ ba — 32 ở `food_images` + 8 ở `destination_images` | 40 |
| 6 | Áp dụng migration `20260806_region_split` và ghi vào `schema_migrations` | 1 |
| 7 | Sửa `uploadLocalImage()` trả path tương đối thay vì `url(...)` | 1 |

## 6. Connector nguồn ảnh

Tổ chức thành module nhỏ, mỗi nguồn cùng một giao diện trả danh sách ứng viên kèm license:

```
includes/media.php                    -- API công khai: render, resolve, attribution
includes/media/store.php              -- tải, dedupe theo hash, sinh derivative
includes/media/placeholder.php        -- sinh SVG placeholder xác định
includes/media/sources/wikimedia.php
includes/media/sources/unsplash.php
includes/media/sources/google_places.php
```

### 6.1 Chuỗi ưu tiên

| Entity | Ưu tiên 1 | Ưu tiên 2 | Cuối |
|---|---|---|---|
| Điểm đến | Wikimedia (tên + tỉnh) · `actual` | Google Places · `actual` | placeholder |
| Món ăn | Wikimedia (tên món Việt) · `actual` | Unsplash generic · `illustrative` | placeholder |
| Quán ăn / cà phê | Google Places · `actual` | Unsplash không gian quán · `illustrative` | placeholder |
| Lưu trú | Google Places · `actual` | Unsplash phòng theo loại hình · `illustrative` | placeholder |
| Bài viết / sự kiện | Wikimedia · Unsplash theo tag · `illustrative` | — | placeholder |

Điểm đến **cố ý không có nhánh Unsplash**: gắn ảnh một thác nước bất kỳ cho Dray Nur,
dù có nhãn minh họa, vẫn gây hiểu sai về địa danh có thật. Món ăn thì khác — "bún đỏ"
ở đâu cũng là bún đỏ.

### 6.2 Google Places

Một lượt Text Search trả về `place_id` (được phép lưu vĩnh viễn) kèm lat/lng, giờ mở cửa,
mức giá, rating — đúng những trường mục 8 cần. Bước resolve Places phục vụ cả hai mục đích.

Ảnh Places: `storage_path = NULL`, chỉ lưu `place_photo_ref`, phục vụ qua
`api/place_photo.php?asset=<id>&w=800`. Server giữ key, fetch, stream về, cache đĩa
TTL 30 ngày rồi lấy lại. Key không lộ ra client. Cách này tôn trọng ràng buộc
không cache dài hạn trong ToS của Places.

Khối lượng gọi Places bị giới hạn bởi chuỗi ưu tiên ở mục 6.1 — 243 món ăn đi Wikimedia
nên **không tốn lượt Places**:

| Nhóm | Số bản ghi | Dùng Places? |
|---|---|---|
| Món ăn (`dish`) | 243 | Không — Wikimedia |
| Quán ăn (`restaurant`) | 135 | Có |
| Cà phê (`cafe`) | 123 | Có |
| Lưu trú | 245 | Có |
| Điểm đến | 108 | Chỉ khi Wikimedia không có |
| **Tối đa** | **611** | |

Chi phí: Text Search ≈ $32/1000 × ≤611 lượt ≈ **~$20 một lần**; sau đó `place_id`
đã lưu nên không lặp. Places Photo ≈ $7/1000, có cache 30 ngày nên chi phí định kỳ
nhỏ và có chặn trên.

### 6.3 Hàng đợi backfill nối lại được

Thiết kế rút thẳng từ Lỗi 1. Sau dọn dẹp có 437/501 món-quán và 245 cơ sở lưu trú
cần backfill. Số lượt Unsplash không xác định trước được vì chỉ dùng khi Wikimedia và
Places đều trượt; chặn trên là 682 lượt. Với free tier 50 request/giờ, trường hợp xấu
nhất là ~14 giờ chạy nền. Bắt buộc phải nối lại được, và phải log rõ số lượt đã dùng
để biết có cần nâng lên production tier (5000/giờ) hay không.

```sql
CREATE TABLE media_backfill_queue (
  id            bigint AUTO_INCREMENT PRIMARY KEY,
  entity_type   varchar(32) NOT NULL,
  entity_id     bigint NOT NULL,
  status        enum('pending','done','failed') NOT NULL DEFAULT 'pending',
  attempts      int NOT NULL DEFAULT 0,
  source_tried  varchar(120) NULL,
  last_error    varchar(500) NULL,
  next_retry_at timestamp NULL,
  updated_at    timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_queue_entity (entity_type, entity_id)
);
```

Quy tắc bất biến: **`status = 'done'` chỉ được đặt sau khi đã thực sự tạo được
`media_links`.** Thất bại thì tăng `attempts`, giữ `pending`, lùi `next_retry_at`
theo backoff. Không bao giờ đánh dấu hoàn thành cho bản ghi rỗng — đúng lỗi mà
`ragUpsertDocuments()` đang mắc.

## 7. Tầng render & placeholder

Một helper thay cho `<img>` viết tay rải rác:

```php
mediaImg('food', $id, ['size' => 'card', 'alt' => $name]);
```

sinh ra:

```html
<img src="…/800.webp"
     srcset="…/400.webp 400w, …/800.webp 800w, …/1600.webp 1600w"
     sizes="(max-width:768px) 100vw, 400px"
     width="800" height="600" loading="lazy" decoding="async" alt="…">
```

- Derivative 400/800/1600 sinh lúc nạp ảnh, lưu theo
  `/assets/images/media/<hash[0:2]>/<hash[2:4]>/` để không dồn vạn file vào một thư mục.

  **Ràng buộc môi trường:** GD của PHP 8.2.4 trong XAMPP **không có WebP và AVIF**
  (`imagewebp()` không tồn tại), cũng không có ImageMagick. Chỉ JPEG/PNG là chắc chắn.
  Binary `cwebp` có trên máy dev nhưng không đảm bảo có trên server.

  Do đó pipeline hạ cấp có kiểm soát: **luôn** sinh derivative JPEG bằng GD; sinh thêm
  WebP **chỉ khi** dò được encoder (`imagewebp()` hoặc binary `cwebp`). `media_assets`
  ghi lại biến thể nào thực sự tồn tại. `mediaImg()` phát `<picture>` có nguồn WebP khi
  có, ngược lại phát `<img>` JPEG thuần. Không bao giờ trỏ tới file không tồn tại.
- **`width`/`height` luôn có** → CLS về 0. Card hiện tại có `loading="lazy"` mà không
  khai báo kích thước, đó là nguồn layout shift.
- Ảnh hero dùng `loading="eager" fetchpriority="high"` theo ngưỡng LCP < 2.5s.
- **Placeholder SVG sinh xác định** theo `entity_type` + id, màu lấy từ palette site,
  icon riêng từng loại; cache thành file tĩnh. Thay cho emoji `🍜`/`🛏️` hiện tại.
  Không bao giờ còn icon ảnh vỡ.
- `mediaAttribution()` render "Ảnh: <tác giả> / <giấy phép>" dưới gallery —
  **bắt buộc** với cả CC của Wikimedia lẫn Unsplash License.
- Nhãn "Ảnh minh họa" tự hiện khi `authenticity = 'illustrative'`.

## 8. Admin upload

- `admin/media.php`: gắn gallery cho mọi entity — kéo-thả nhiều file, preview trước khi
  gửi, đặt ảnh đại diện, kéo sắp thứ tự, sửa alt text, xóa.
- Dùng lại `uploadLocalImage()` đã siết (`finfo` MIME, tối đa 5MB, chặn ảnh > 10000px,
  tên file ngẫu nhiên), bổ sung dedupe theo hash và sinh derivative.
- Xóa asset: xóa file + mọi derivative trong transaction; DB fail thì rollback,
  không để file mồ côi.
- **CSRF**: hiện **không có token ở bất kỳ trang admin nào** trong cả 15 trang; mọi POST
  chỉ dựa vào `requireAdmin()`. Vì DA1 thêm endpoint upload file, phải bổ sung helper
  dùng chung `csrfToken()` / `csrfCheck()` và áp cho `admin/media.php`. Các trang admin
  còn lại chuyển sang DA2 để không mở rộng phạm vi DA1.

## 9. Backfill dữ liệu thiếu

Đi kèm bước resolve Google Places, không tốn thêm lượt gọi.

| Trường | Hiện trạng | Nguồn |
|---|---|---|
| `accommodations` lat/lng | NULL 245/245 | Places |
| `accommodations` giá, giờ mở cửa, rating | trống | Places |
| `destinations.province` | NULL 108/108 | địa chỉ + Places |
| `foods.region` | NULL 432/501 | mapping huyện có sẵn trong `scripts/backfill_region.php` |
| `accommodations.region` | NULL 245/245 | mapping huyện có sẵn |

## 10. Nghiệm thu

`scripts/verify_media.php` chạy lặp lại được, phải đạt toàn bộ:

- **0** tham chiếu ảnh trỏ file không tồn tại
- **0** hotlink bên thứ ba còn sót
- **0** asset thiếu license/attribution
- **0** thẻ `<img>` thiếu `width`/`height`
- Mọi entity render được: hoặc ảnh thật, hoặc placeholder có thiết kế — không bao giờ ảnh vỡ
- Báo cáo độ phủ theo từng loại: số lượng `actual` / `illustrative` / `placeholder`
- Chạy migration hai lần không nhân đôi dữ liệu
- 108 điểm đến và toàn bộ itinerary cũ vẫn đọc được sau migration

---

# DA2 — Nâng cấp mặt tiền (phác thảo)

Chốt thiết kế chi tiết sau khi DA1 xong.

- Trang chi tiết `public/food.php` và `public/accommodation.php` — hiện 501 món/quán và
  245 cơ sở lưu trú **không có trang chi tiết**, khiến `ragResultCards()` phải link ngược
  về `chatbot.php?ask=…`. Kèm gallery, bản đồ, giá, giờ mở cửa, review, gợi ý liên quan.
- Route đẹp trong `.htaccess`: `/am-thuc/<slug>`, `/luu-tru/<slug>`.
- Visual/UI: hero, card, typography, spacing, hover/focus state, gallery lightbox
  (vanilla JS, tôn trọng `prefers-reduced-motion`), skeleton loading, responsive 320→1920.
- SEO: schema.org cho `TouristAttraction` / `Restaurant` / `Hotel`, OG tags, sitemap.
- Hiệu năng: theo ngưỡng Core Web Vitals trong rule dự án.
- Bổ sung CSRF cho 14 trang admin còn lại.

# DA3 — RAG & chatbot (phác thảo)

Chốt thiết kế chi tiết sau khi DA2 xong.

- **Sửa Lỗi 1**: chỉ ghi `content_hash` khi embedding thành công; bản ghi thất bại giữ
  trạng thái chờ và được thử lại. Thêm retry + rate limit + tiến độ nối lại được.
- Reset 520 bản ghi đang kẹt, index lại đủ 100%.
- Vector lưu dạng BLOB packed float thay JSON — `unpack()` nhanh hơn `json_decode`
  đáng kể; nạp một lần vào cache thay vì decode mỗi truy vấn.
- Thêm ngưỡng similarity; hybrid keyword + vector thay vì fallback chỉ khi rỗng.
- Thêm locale `en` cho người dùng tiếng Anh.
- Chatbot trả ảnh thật lấy từ entity đã truy xuất qua tầng media của DA1;
  không để mô hình tự sinh URL ảnh.
- `ragResultCards()` link tới trang chi tiết thật của DA2.
- Tự động reindex khi admin sửa nội dung.
