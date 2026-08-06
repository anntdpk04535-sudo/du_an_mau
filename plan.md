# Kế hoạch nâng cấp nền tảng du lịch AI Đắk Lắk – Phú Yên

## Tóm tắt

Triển khai theo 4 giai đoạn độc lập:

1. Chuẩn hóa và mở rộng dữ liệu.
2. Xây RAG, chatbot có ảnh và kết quả có căn cứ.
3. Nâng cấp giao diện, gallery và review ảnh.
4. Cho phép chỉnh sửa, lưu và tối ưu lịch trình.

Giữ PHP/MySQL hiện tại, không thêm framework hoặc dependency. Dữ liệu cũ vẫn hoạt động trong suốt quá trình migration.

## Thay đổi chính

### 1. Nền tảng dữ liệu và trang quản trị

- Tạo migration có đánh số và bảng `schema_migrations`; cập nhật đồng thời file dump chuẩn [daklak_travel.sql](/Applications/XAMPP/xamppfiles/htdocs/du_an_mau/database/daklak_travel.sql:355).
- Mở rộng `destinations` với tỉnh/thành, giờ mở cửa dạng JSON, giá vé tối thiểu/tối đa, thông tin liên hệ, nguồn dữ liệu, ngày xác minh và `updated_at`; giữ `image_url` để tương thích.
- Thêm các bảng:
  - `destination_images`: gallery, caption, alt text, ảnh đại diện, thứ tự.
  - `foods`: loại `dish|restaurant|cafe`, liên kết `destination_id`, mô tả song ngữ, địa chỉ, GPS, giá, giờ mở cửa và nguồn.
  - `food_images`: nhiều ảnh cho món/quán.
  - `accommodations`: loại `homestay|hotel|resort`, GPS, giá, tiện nghi, liên hệ, nguồn.
  - `accommodation_images`: gallery lưu trú.
  - `review_images`: nhiều ảnh cho mỗi review; không lưu danh sách đường dẫn trong một cột JSON.
- Backfill `destinations.image_url` vào `destination_images` dưới dạng ảnh đại diện; migration chỉ thêm bảng/cột nullable, không xóa dữ liệu cũ.
- Mở rộng admin destination hiện tại [destinations.php](/Applications/XAMPP/xamppfiles/htdocs/du_an_mau/admin/destinations.php:15) và thêm CRUD cho ẩm thực/lưu trú/gallery.
- Chuẩn hóa upload vào `assets/images/uploads/{destinations,foods,accommodations,reviews}`:
  - JPEG, PNG, WebP; kiểm tra MIME bằng `finfo`.
  - Tối đa 5 MB/ảnh, 5 ảnh/review.
  - Tên file ngẫu nhiên, chặn executable/double extension và xóa file nếu transaction DB thất bại.
- Seed tối thiểu 40 điểm đến, 60 bản ghi ẩm thực/quán, 25 nơi lưu trú; mỗi điểm đến có ít nhất 3 ảnh và mỗi bản ghi nổi bật có ít nhất 2 ảnh.
- Mỗi dữ liệu seed phải có `source_url`, `last_verified_at`; ảnh phải được lưu local và có quyền sử dụng, không tiếp tục phụ thuộc hotlink không ổn định.

### 2. RAG và chatbot có rich media

- Thay `getDestinationsSummaryForAI()` đang nối toàn bộ dữ liệu thành text [functions.php](/Applications/XAMPP/xamppfiles/htdocs/du_an_mau/includes/functions.php:283) bằng dịch vụ truy xuất dùng chung.
- Tạo `search_documents` gồm `entity_type`, `entity_id`, `locale`, nội dung chuẩn hóa, content hash, model, vector JSON và thời điểm cập nhật.
- Sinh embedding bằng REST/cURL với `gemini-embedding-2`; dùng vector 768 chiều và cosine similarity trong PHP. Quy mô hiện tại chưa cần vector database riêng. Model này hỗ trợ semantic và multimodal retrieval theo [tài liệu Gemini Embeddings](https://ai.google.dev/gemini-api/docs/embeddings).
- Thêm script reindex toàn bộ dữ liệu và cơ chế upsert embedding sau khi admin chỉnh nội dung; không gọi lại embedding nếu content hash không đổi.
- Luồng chat:
  1. Embed câu hỏi.
  2. Lấy top 8 kết quả vượt ngưỡng similarity cấu hình.
  3. Đưa nội dung truy xuất vào prompt cùng ID nguồn.
  4. Sinh câu trả lời chỉ dựa trên context.
  5. Trả ảnh từ các entity đã truy xuất, không để AI tự tạo URL ảnh.
- Xóa các mảng món ăn, quán cà phê và khách sạn hard-code trong [chat.php](/Applications/XAMPP/xamppfiles/htdocs/du_an_mau/api/chat.php:45).
- Mở rộng phản hồi `POST /api/chat.php`, giữ tương thích với `reply` và `images`:

```json
{
  "reply": "Nội dung tư vấn",
  "images": [
    {
      "url": "/assets/images/uploads/foods/...",
      "alt": "Bún đỏ Buôn Ma Thuột",
      "entity_type": "food",
      "entity_id": 12
    }
  ],
  "results": [
    {
      "type": "food",
      "id": 12,
      "title": "Bún đỏ cô Thu",
      "image_url": "...",
      "address": "...",
      "url": "..."
    }
  ],
  "sources": [
    {"type": "food", "id": 12, "score": 0.84}
  ]
}
```

- Nếu embedding API lỗi, fallback sang tìm kiếm từ khóa có trọng số; chatbot vẫn hoạt động nhưng gắn cờ nội bộ `retrieval_mode=keyword`.

### 3. Giao diện, gallery và review ảnh

- Homepage mới gồm hero toàn màn hình, tìm kiếm nhanh, điểm đến nổi bật, trải nghiệm ẩm thực, lưu trú và CTA tạo lịch trình.
- Card điểm đến hiển thị ảnh đại diện, món/quán gần đó, mức giá, rating và giờ mở cửa.
- Trang chi tiết [destination.php](/Applications/XAMPP/xamppfiles/htdocs/du_an_mau/public/destination.php:1) có gallery responsive/lightbox, thông tin thực hành, ẩm thực gần điểm đến, lưu trú và review kèm ảnh.
- Chatbot [chatbot.php](/Applications/XAMPP/xamppfiles/htdocs/du_an_mau/public/chatbot.php:1) được bố trí lại:
  - Desktop có sidebar gợi ý và lịch sử; mobile chuyển thành drawer.
  - Bong bóng trả lời chứa carousel ảnh và card kết quả.
  - Typing indicator, skeleton loading, empty/error state và chế độ tập trung “lights-out”.
  - Render dữ liệu bằng DOM APIs/escaping, không đưa text AI trực tiếp vào `innerHTML`.
- Form review chuyển sang multipart với preview, xóa ảnh trước khi gửi và thông báo lỗi theo từng file.
- `review_submit.php`, `review_get.php`, `review_edit.php` trả trường `images[]`; chủ review có thể thêm/xóa ảnh khi sửa. Xóa review phải xóa cả bản ghi ảnh và file local.
- Bổ sung khóa dịch Việt/Anh cho toàn bộ UI mới; không để chuỗi giao diện hard-code trong JavaScript/PHP.
- Chụp screenshot desktop và mobile cho home, chatbot, destination và review để thực hiện visual QA trước khi hoàn tất giai đoạn.

### 4. Lịch trình có thể chỉnh sửa và tối ưu tuyến

- Mở rộng `itinerary_items` với `food_id`, `accommodation_id`, `reason`, `suggestion`, `community_impact`, `price_min`, `price_max`; hiện tại các trường này bị mất khi lưu [generate_itinerary.php](/Applications/XAMPP/xamppfiles/htdocs/du_an_mau/api/generate_itinerary.php:245).
- Thêm `updated_at` và `version` vào `itineraries` để tránh ghi đè khi mở nhiều tab.
- Prompt sinh lịch trình chỉ được tham chiếu các entity lấy từ RAG; mỗi item phải chứa loại và ID thực thay vì tên tự do.
- Trên trang xem lịch trình [itinerary_view.php](/Applications/XAMPP/xamppfiles/htdocs/du_an_mau/public/itinerary_view.php:31), người sở hữu có thể:
  - Kéo-thả item trong ngày hoặc sang ngày khác.
  - Thêm điểm đến, món/quán hoặc lưu trú từ catalog.
  - Đổi một item bằng lựa chọn tương đương.
  - Sửa giờ, hoạt động, địa chỉ, phương tiện và chi phí.
  - Xóa item, hoàn tác thay đổi chưa lưu và lưu phiên bản tùy chỉnh.
- API mới:
  - `GET /api/itinerary_options.php?q=&type=&destination_id=`: catalog thêm/đổi item.
  - `PATCH /api/itinerary_update.php`: cập nhật metadata và toàn bộ thứ tự item trong một transaction; bắt buộc đúng owner và version.
  - `POST /api/itinerary_optimize.php`: tối ưu lại từng ngày rồi lưu khi người dùng xác nhận.
- Bộ tối ưu:
  - Loại điểm đóng cửa hoặc không phù hợp khung giờ.
  - Lấy ma trận thời gian/quãng đường từ OSRM Table API.
  - Dùng nearest-neighbor tạo tuyến ban đầu và 2-opt cải thiện tuyến.
  - Giữ cố định các item người dùng đã khóa.
  - Fallback Haversine khi OSRM timeout; trả cảnh báo thay vì làm hỏng lịch trình.
- Route sau tối ưu phải có tổng km/thời gian không cao hơn route trước, trừ khi cần đáp ứng giờ mở cửa hoặc item bị khóa.

## Kiểm thử và tiêu chí nghiệm thu

- Migration chạy được trên database cũ, chạy lại không nhân đôi dữ liệu, toàn bộ 16 destination và itinerary cũ vẫn đọc được.
- CRUD admin kiểm tra thêm/sửa/xóa destination, food, accommodation và gallery; file lỗi MIME/kích thước bị từ chối.
- Seed đạt đủ số lượng mục tiêu, không trùng slug và không có entity công khai thiếu ảnh đại diện.
- RAG kiểm thử tối thiểu 30 truy vấn Việt/Anh về điểm đến, món ăn, cà phê và lưu trú; top 5 phải chứa entity đúng trong ít nhất 85% bộ câu hỏi chuẩn.
- Chat không còn mảng dữ liệu ẩm thực/lưu trú hard-code; mọi card/ảnh trả về đều ánh xạ tới bản ghi DB tồn tại.
- Review kiểm thử upload 1–5 ảnh, MIME giả, file quá lớn, sửa/xóa ảnh, phân quyền và dọn file orphan.
- Lịch trình kiểm thử kéo-thả, thêm/bớt/đổi item, conflict version, truy cập sai owner, rollback transaction và reload vẫn giữ đúng thứ tự.
- Bộ tối ưu kiểm thử 2–10 điểm, item khóa, thiếu GPS, OSRM timeout và điểm đóng cửa.
- Chạy `php -l` toàn bộ file PHP; thêm test runner PHP thuần cho service RAG, upload validation và route optimizer vì repository chưa có PHPUnit/Composer.
- Smoke test đầy đủ trên Chrome desktop/mobile: home → destination → chatbot → review → tạo/chỉnh/lưu lịch trình.
- Không hoàn thành một giai đoạn nếu còn PHP warning/error, migration chưa xác minh hoặc API trả dữ liệu không đúng contract.

## Giả định và mặc định

- Phạm vi dữ liệu: Đắk Lắk và Phú Yên.
- Ảnh được lưu local để phù hợp XAMPP; chưa tích hợp cloud storage.
- Giữ Gemini làm nhà cung cấp AI và gọi REST trực tiếp bằng cURL.
- Không thêm dependency; kéo-thả dùng HTML5 Drag and Drop và touch controls riêng.
- Public OSRM chỉ dùng cho development/demo; production cần endpoint OSRM có SLA hoặc self-host.
- MySQL hiện chưa kết nối được trong môi trường khảo sát, nên bước đầu tiên khi triển khai là bật XAMPP MySQL, backup database và chạy preflight đối chiếu schema live với file dump.

---

## Bổ sung 2026-08-06: Lịch trình gắn vị trí xuất phát + thời tiết (đã hoàn thành)

Tính năng: kết hợp RAG + dự báo thời tiết Open-Meteo vào sinh lịch trình, cho phép khai báo
vị trí xuất phát (vị trí hiện tại / khách sạn đang lưu trú / nhập tay có geocode), gợi ý
ẩm thực – lưu trú gần nơi ở, và phân cụm địa lý để các điểm trong một ngày nằm gần nhau.

Trạng thái theo giai đoạn:

- [x] Giai đoạn 0 — Migration `20260808_itinerary_geo.sql` (cột origin_*/radius_km/weather_snapshot,
      bảng weather_cache + geocode_cache), backfill toạ độ & indoor_type, form admin thêm lat/lng.
- [x] Giai đoạn 1 — `includes/geo.php`: haversine, bbox, geoFindNearby (whitelist bảng),
      geoClusterByDays, geoOrderNearestNeighbour, geoResolveOriginInput (làm tròn 3 số lẻ cho
      vị trí hiện tại để bảo vệ riêng tư).
- [x] Giai đoạn 2 — `includes/weather.php`: fetch Open-Meteo + cache DB (TTL 30 phút, fallback
      cache cũ khi mất mạng), phân loại rủi ro good/caution/indoor_preferred/unsafe, khuyến cáo
      tiếng Việt; `api/weather.php` refactor dùng chung, giữ nguyên contract cũ.
- [x] Giai đoạn 3 — `ragSearchGeo` trong `includes/rag.php`: lọc ứng viên RAG theo bán kính quanh
      điểm xuất phát trước khi đưa vào prompt.
- [x] Giai đoạn 4 — API: `generate_itinerary` nhận origin + chèn thời tiết/địa lý vào prompt và
      lưu snapshot; `itinerary_context` (thời tiết + gần đây); `geocode` (Google → Nominatim,
      cache + rate limit); `itinerary_options` thêm chế độ sắp theo khoảng cách (BC giữ nguyên);
      `reroute_itinerary` né điểm ngoài trời ngày mưa.
- [x] Giai đoạn 5 — UI `public/itinerary.php`: khối chọn vị trí xuất phát (hiện tại/nhập tay),
      bán kính, panel thời tiết 7 ngày + khuyến cáo, i18n Việt/Anh đầy đủ.
- [x] Giai đoạn 6 — `itinerary_view.php`: badge nơi xuất phát, marker 🏨 + vòng bán kính trên bản đồ,
      panel thời tiết từ snapshot; `destination.php`: “Ẩm thực & lưu trú gần đây” ưu tiên khoảng
      cách thật (badge 📏 km), fallback logic cũ khi thiếu toạ độ.
- [x] Giai đoạn 7 — Kiểm thử: `scripts/test_geo.php` + `scripts/test_weather.php` (43 assertion PASS),
      `php -l` 18 file sạch, regression API (weather/options/generate giữ contract cũ), sửa bug
      geocode cache âm vĩnh viễn khi key Google hỏng (luôn fallback Nominatim + purge cache độc).
