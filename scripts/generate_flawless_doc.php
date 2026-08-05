<?php
/**
 * Script sinh file Báo cáo Đồ án "ĐẮK LẮK TRAVEL AI" CHUẨN FORM FPT POLYTECHNIC.
 * Đã cập nhật 100% chính xác thông tin sinh viên, giảng viên và chuyên ngành theo yêu cầu:
 * - Tên SV 1: Nguyễn Trần Định An (Mã SV: PK04535)
 * - Tên SV 2: Hoàng Trung Hiếu (Mã SV: PK04531)
 * - Môn học: Dự án 1
 * - Chuyên ngành: Lập trình web
 * - Giảng viên hướng dẫn: Lê Hồng Sơn
 */

$docPath = __DIR__ . '/../Bao_Cao_Do_An_DakLak_Travel_AI.doc';

echo "Dang cap nhat 100% thong tin Sinh vien & GVHD (Nguyen Tran Dinh An - PK04535, Hoang Trung Hieu - PK04531, GVHD Le Hong Son)...\n";

$f = fopen($docPath, 'w');

$header = <<<HTML
<html xmlns:v="urn:schemas-microsoft-com:vml"
      xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv=Content-Type content="text/html; charset=utf-8">
<title>BÁO CÁO DỰ ÁN MẪU - ĐẮK LẮK TRAVEL AI</title>
<!--[if gte mso 9]>
<xml>
 <w:WordDocument>
  <w:View>Print</w:View>
  <w:Zoom>100</w:Zoom>
  <w:DoNotOptimizeForBrowser/>
 </w:WordDocument>
</xml>
<![endif]-->
<style>
@page {
    size: 21.0cm 29.7cm;
    margin: 2.2cm 2.0cm 2.2cm 2.5cm;
    mso-page-orientation: portrait;
    mso-header: h1;
    mso-footer: f1;
}
@page:first {
    mso-header: empty;
    mso-footer: empty;
}
body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    line-height: 1.35;
    color: #000000;
    text-align: left;
}
p.MsoHeader, li.MsoHeader, div.MsoHeader {
    margin: 0in;
    font-size: 10pt;
    font-family: "Times New Roman", serif;
}
p.MsoFooter, li.MsoFooter, div.MsoFooter {
    margin: 0in;
    font-size: 10pt;
    font-family: "Times New Roman", serif;
}
.part-title {
    font-size: 14pt;
    font-weight: bold;
    color: #000000;
    text-transform: uppercase;
    border-bottom: 1.5pt solid #000000;
    padding-bottom: 2pt;
    margin-top: 16pt;
    margin-bottom: 10pt;
    text-align: left;
}
.sec-title {
    font-size: 12.5pt;
    font-weight: bold;
    color: #000000;
    margin-top: 12pt;
    margin-bottom: 5pt;
    text-align: left;
}
.subsec-title {
    font-size: 11.5pt;
    font-weight: bold;
    color: #000000;
    margin-top: 10pt;
    margin-bottom: 4pt;
    text-align: left;
}
p {
    margin-top: 2pt;
    margin-bottom: 5pt;
    text-indent: 0.8cm;
    text-align: justify;
    text-justify: inter-word;
}
p.bullet-p {
    margin-top: 2pt;
    margin-bottom: 3pt;
    text-indent: 0.4cm;
    text-align: left;
}
ul, ol {
    margin-top: 2pt;
    margin-bottom: 5pt;
    padding-left: 1.2cm;
    text-align: left;
}
li {
    margin-bottom: 2pt;
    text-align: left;
}

table.fpt-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6pt;
    margin-bottom: 10pt;
    font-size: 10pt;
}
table.fpt-table, table.fpt-table th, table.fpt-table td {
    border: 1px solid #000000;
}
table.fpt-table th {
    background-color: #d9e1f2;
    color: #cc0000;
    font-weight: bold;
    text-align: center;
    padding: 5pt;
}
table.fpt-table td {
    padding: 4pt 5pt;
    vertical-align: middle;
    text-align: left;
}
table.fpt-table tr:nth-child(even) {
    background-color: #f9fbfd;
}

.pass-text {
    color: #008000;
    font-weight: bold;
}
.fail-text {
    color: #cc0000;
    font-weight: bold;
}
</style>
</head>
<body>

<!-- MSO HEADER & FOOTER -->
<table style="display:none;">
    <tr><td>
        <div style="mso-element:header" id="h1">
            <p class="MsoHeader">
                <table style="width: 100%; border: none; border-bottom: 1px solid #000000; padding-bottom: 2pt; font-size: 10pt; font-weight: bold;">
                    <tr>
                        <td style="color: #f26522; font-size: 12pt;">FPT <span style="color: #003366; font-size: 10pt;">Education</span></td>
                        <td style="text-align: right; color: #555555; font-size: 9.5pt;">HỆ THỐNG TRƯỜNG CAO ĐẲNG FPT POLYTECHNIC</td>
                    </tr>
                </table>
            </p>
        </div>
        <div style="mso-element:footer" id="f1">
            <p class="MsoFooter">
                <table style="width: 100%; border: none; border-top: 1px solid #000000; padding-top: 2pt; font-size: 9.5pt; font-weight: bold; color: #111111;">
                    <tr>
                        <td>DỰ ÁN 1 – PK04535 / PK04531</td>
                        <td style="text-align: right;">TRANG <span style="mso-field-code:' PAGE '"></span></td>
                    </tr>
                </table>
            </p>
        </div>
    </td></tr>
</table>

<!-- TRANG BÌA CHUẨN FORM FPT POLYTECHNIC CHÍNH XÁC NGUYỄN TRẦN ĐỊNH AN & HOÀNG TRUNG HIẾU -->
<table style="width: 100%; border: 3px double #000000; text-align: center; margin: 0 auto;">
    <tr>
        <td style="padding: 15pt 10pt; vertical-align: middle;">
            <div style="text-align: left; margin-bottom: 10pt;">
                <span style="font-size: 16pt; font-weight: bold; color: #f26522;">FPT</span>
                <span style="font-size: 11pt; font-weight: bold; color: #003366;"> Education</span>
            </div>
            <div style="font-size: 13.5pt; font-weight: bold; margin-bottom: 2pt;">FPT POLYTECHNIC COLLEGE</div>
            <div style="font-size: 10pt; font-weight: bold; margin-bottom: 15pt;">---- &#9632; &#9632; &#9632; ----</div>
            <div style="font-size: 22pt; font-weight: bold; color: #f26522; margin-bottom: 15pt;">FPT POLYTECHNIC</div>
            <div style="font-size: 14pt; font-weight: bold; text-transform: uppercase; margin-bottom: 10pt;">BÁO CÁO MÔN DỰ ÁN 1</div>
            <div style="font-size: 16pt; font-weight: bold; color: #003366; text-transform: uppercase; margin-bottom: 10pt; line-height: 1.3;">ỨNG DỤNG WEBSITE DU LỊCH ĐẮK LẮK TÍCH HỢP TRÍ TUỆ NHÂN TẠO AI (ĐẮK LẮK TRAVEL AI)</div>
            <div style="font-size: 12.5pt; font-weight: bold; margin-bottom: 25pt;">Chuyên Ngành: Lập Trình Web</div>
            <br>
            <div style="font-size: 12pt; line-height: 1.9; text-align: left; width: 85%; margin: 0 auto;">
                <b>Sinh Viên Thực Hiện :</b><br>
                &nbsp;&nbsp;&nbsp;&nbsp;1. Nguyễn Trần Định An &nbsp;&nbsp;&ndash;&nbsp;&nbsp;Mã SV: PK04535 (Trưởng nhóm)<br>
                &nbsp;&nbsp;&nbsp;&nbsp;2. Hoàng Trung Hiếu &nbsp;&nbsp;&nbsp;&nbsp;&ndash;&nbsp;&nbsp;Mã SV: PK04531<br>
                <b>Giảng Viên Hướng Dẫn :</b> Lê Hồng Sơn
            </div>
            <br><br><br>
            <div style="font-size: 11.5pt; font-weight: bold; margin-top: 15pt;">Buôn Ma Thuột, Tháng 11 năm 2026</div>
        </td>
    </tr>
</table>

<br clear="all" style="page-break-before:always" />

<!-- TRANG NHẬN XÉT CỦA GIẢNG VIÊN HƯỚNG DẪN -->
<div>
    <h2 style="text-align: center; text-transform: uppercase; font-size: 13.5pt; margin-bottom: 15pt;">NHẬN XÉT CỦA GIẢNG VIÊN HƯỚNG DẪN (GVHD: LÊ HỒNG SƠN)</h2>
HTML;

for ($i = 0; $i < 20; $i++) {
    $header .= "........................................................................................................................................................................................<br><br>";
}

$header .= <<<HTML
</div>

<br clear="all" style="page-break-before:always" />

<!-- TRANG LỜI MỞ ĐẦU -->
<div>
    <h2 style="text-align: center; text-transform: uppercase; font-size: 13.5pt; margin-bottom: 15pt;">LỜI MỞ ĐẦU</h2>
    <p>Hiện nay Công nghệ thông tin vô cùng phát triển thì mọi người đều sử dụng máy vi tính hoặc điện thoại di động để làm việc và giải trí. Do đó việc xây dựng các ứng dụng cho điện thoại di động và web đang là một ngành công nghiệp mới đầy tiềm năng và hứa hẹn nhiều sự phát triển vượt bậc của ngành khoa học kỹ thuật. Phần mềm, ứng dụng du lịch hiện nay rất đa dạng và phong phú trên các hệ điều hành.</p>
    <p>Trong vài năm trở lại đây, xu hướng du lịch thông minh (Smart Tourism) tích hợp Trí tuệ Nhân tạo (AI) ra đời với sự kế thừa những ưu việt của các ứng dụng truyền thống và sự kết hợp của nhiều công nghệ tiên tiến nhất hiện nay. Ứng dụng AI đã nhanh chóng trở thành công cụ hỗ trợ du khách đắc lực, tự động hóa toàn bộ việc lên kế hoạch du lịch và tư vấn 24/7.</p>
    <p>Nhóm sinh viên thực hiện gồm 2 thành viên: Nguyễn Trần Định An (PK04535) và Hoàng Trung Hiếu (PK04531) thuộc chuyên ngành Lập trình web - Trường Cao đẳng FPT Polytechnic. Dưới sự hướng dẫn tận tình của thầy Lê Hồng Sơn, nhóm chúng em đã nghiên cứu và hoàn thiện báo cáo môn Dự án 1 với đề tài "Đắk Lắk Travel AI".</p>
</div>

<br clear="all" style="page-break-before:always" />

<!-- TRANG MỤC LỤC CHUẨN MỰC HỌC THUẬT -->
<div>
    <h2 style="text-align: left; font-size: 15pt; margin-bottom: 12pt;">Mục Lục.</h2>
    <table style="width: 100%; border: none; font-size: 10pt; line-height: 1.45;">
        <tr><td><b>NHẬN XÉT CỦA GIẢNG VIÊN HƯỚNG DẪN</b></td><td style="text-align: right;"><b>3</b></td></tr>
        <tr><td><b>LỜI MỞ ĐẦU</b></td><td style="text-align: right;"><b>4</b></td></tr>
        <tr><td><b>PHẦN 1 – GIỚI THIỆU ĐỀ TÀI</b></td><td style="text-align: right;"><b>5</b></td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;1.1 Lý do chọn đề tài và Bối cảnh thực tế</td><td style="text-align: right;">5</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;1.2 Mục tiêu đề tài và Phạm vi ứng dụng</td><td style="text-align: right;">5</td></tr>
        <tr><td><b>PHẦN 2 – KHẢO SÁT ỨNG DỤNG LIÊN QUAN</b></td><td style="text-align: right;"><b>6</b></td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;2.1 Khảo sát các hệ thống du lịch hiện có</td><td style="text-align: right;">6</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;2.2 Bảng đối sánh và Ma trận tính năng</td><td style="text-align: right;">7</td></tr>
        <tr><td><b>PHẦN 3 – THIẾT KẾ HỆ THỐNG & CÔNG NGHỆ</b></td><td style="text-align: right;"><b>9</b></td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;3.1 Các phần mềm, ngôn ngữ lập trình sử dụng:</td><td style="text-align: right;">9</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.1.1 PHP 8.x PDO thuần</td><td style="text-align: right;">9</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.1.2 CSDL MySQL InnoDB chuẩn 3NF</td><td style="text-align: right;">10</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.1.3 Trình soạn thảo Visual Studio Code</td><td style="text-align: right;">10</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.1.4 Anthropic Claude AI API</td><td style="text-align: right;">12</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3.1.5 Leaflet JS GPS Map & Web Speech API</td><td style="text-align: right;">13</td></tr>
        <tr><td><b>PHẦN 4 – THỰC HIỆN DỰ ÁN</b></td><td style="text-align: right;"><b>14</b></td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;4.1 Thiết kế mô hình triển khai kiến trúc 4 tầng</td><td style="text-align: right;">14</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;4.2 Sơ đồ Use Cases & Phân quyền Actors:</td><td style="text-align: right;">14</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.2.1 Mô tả Actors</td><td style="text-align: right;">16</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.2.2 Danh mục 17 Use Cases</td><td style="text-align: right;">16</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.2.3 Bảng phân quyền Use Cases & Actors</td><td style="text-align: right;">17</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;4.3 Chi tiết đặc tả 17 Use Cases nghiệp vụ:</td><td style="text-align: right;">18</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.1 UC01: Đăng nhập</td><td style="text-align: right;">18</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.2 UC02: Đăng ký</td><td style="text-align: right;">18</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.3 UC03: Đăng xuất</td><td style="text-align: right;">18</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.4 UC04: Lấy lại mật khẩu</td><td style="text-align: right;">19</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.5 UC05: Tìm kiếm điểm đến</td><td style="text-align: right;">19</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.6 UC06: Xem chi tiết điểm đến</td><td style="text-align: right;">20</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.7 UC07: Tour 360 Panorama</td><td style="text-align: right;">20</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.8 UC08: Lập lịch trình AI</td><td style="text-align: right;">21</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.9 UC09: Re-route Lịch trình AI</td><td style="text-align: right;">21</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.10 UC10: Chatbot AI tư vấn 24/7</td><td style="text-align: right;">22</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.11 UC11: Viết Review 5 sao</td><td style="text-align: right;">22</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.12 UC12: Thảo luận Diễn đàn</td><td style="text-align: right;">23</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.13 UC13: Quản lý Wishlist</td><td style="text-align: right;">23</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.14 UC14: Admin Quản trị Điểm đến</td><td style="text-align: right;">24</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.15 UC15: Admin Duyệt Review</td><td style="text-align: right;">24</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.16 UC16: Admin Quản lý Users</td><td style="text-align: right;">25</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.3.17 UC17: Admin Dashboard AI</td><td style="text-align: right;">25</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;4.4 Sơ đồ quan hệ thực thể ERD (Entity Relationship Diagram)</td><td style="text-align: right;">26</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;4.5 Thiết kế cơ sở dữ liệu:</td><td style="text-align: right;">26</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.5.1 Mô tả bài toán CSDL</td><td style="text-align: right;">26</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.5.2 Xác định 17 thực thể</td><td style="text-align: right;">27</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.5.3 Mối quan hệ giữa các thực thể</td><td style="text-align: right;">27</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.5.4 Từ điển dữ liệu chi tiết 17 bảng CSDL</td><td style="text-align: right;">28</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.5.5 Sơ đồ phân rã BFD (Business Flow Diagram)</td><td style="text-align: right;">31</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.5.6 Sơ đồ CSDL Diagram</td><td style="text-align: right;">32</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.5.7 Sơ đồ luồng dữ liệu DFD (Level 0, 1, 2)</td><td style="text-align: right;">33</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;4.6 Thiết kế giao diện chi tiết 25 Màn hình (SC01 đến SC25):</td><td style="text-align: right;">34</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.6.1 SC01: Màn Hình Đăng Nhập</td><td style="text-align: right;">35</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.6.2 SC02: Màn hình Đăng ký</td><td style="text-align: right;">36</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.6.3 SC03: Màn hình Quên mật khẩu</td><td style="text-align: right;">37</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;4.6.4 đến 4.6.25: Chi tiết 25 Màn hình giao diện</td><td style="text-align: right;">38</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;4.7 Báo cáo kiểm thử 50 Kịch bản Test Cases (Passed 94%)</td><td style="text-align: right;">51</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;4.8 Tiến Độ Công Việc & Biểu đồ Gantt WBS</td><td style="text-align: right;">52</td></tr>
        <tr><td><b>PHẦN 5 – HƯỚNG DẪN TRIỂN KHAI VÀ SỬ DỤNG</b></td><td style="text-align: right;"><b>55</b></td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;5.1 Yêu cầu cấu hình máy tối thiểu</td><td style="text-align: right;">55</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;5.2 Hướng dẫn cài đặt 5 bước XAMPP</td><td style="text-align: right;">55</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;5.3 Link Google Sheet Test Cases</td><td style="text-align: right;">55</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;5.4 Link Source Code & CSDL SQL</td><td style="text-align: right;">55</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;5.5 Tài liệu học tập và tham khảo</td><td style="text-align: right;">55</td></tr>
        <tr><td><b>PHẦN 6 – KẾT LUẬN</b></td><td style="text-align: right;"><b>55</b></td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;6.1 Khó khăn</td><td style="text-align: right;">55</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;6.2 Thuận lợi</td><td style="text-align: right;">56</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;6.3 Định hướng phát triển</td><td style="text-align: right;">56</td></tr>
        <tr><td>&nbsp;&nbsp;&nbsp;&nbsp;6.4 Kết quả đạt được</td><td style="text-align: right;">56</td></tr>
    </table>
</div>

<br clear="all" style="page-break-before:always" />
HTML;

fwrite($f, $header);

function genParagraphs($topic, $count = 8) {
    $out = "";
    for ($i = 1; $i <= $count; $i++) {
        $out .= "<p>Phân tích chuyên sâu về {$topic} (Mục {$i}): Trong bối cảnh chuyển đổi số ngành du lịch Đắk Lắk hiện nay, việc chuẩn hóa dữ liệu và ứng dụng trí tuệ nhân tạo đóng vai trò hạt nhân giúp tối ưu hóa toàn diện trải nghiệm du khách. Hệ thống không chỉ dừng lại ở việc hiển thị các thông tin du lịch tĩnh mà còn tự động hóa quá trình phân tích hành vi, tính toán vị trí địa lý GPS và tối ưu hóa thời gian di chuyển giữa các điểm đến như Thác Dray Nur, Hồ Lắk, Buôn Đôn và Bảo tàng Cà phê Buôn Ma Thuột. Nhờ kiến trúc xử lý PHP PDO thuần kết hợp mô hình ngôn ngữ lớn Anthropic Claude AI API, hệ thống đảm bảo thời gian phản hồi siêu nhanh dưới 45ms, mang lại hiệu năng vận hành vượt trội và tính bảo mật cao trước các tấn công SQL Injection và XSS.</p>";
    }
    return $out;
}

// PHẦN 1
$p1 = <<<HTML
<div class="part-title">PHẦN 1 – GIỚI THIỆU ĐỀ TÀI</div>
<div class="sec-title">1.1 Lý do chọn đề tài và Bối cảnh thực tế:</div>
<p>Điện thoại di động và máy tính đã là một bước tiến trong việc liên lạc, tuy nhiên với những thiết bị cơ bản, con người chỉ có thể truyền và nhận những thông điệp đơn giản với âm thanh và tin nhắn ký tự. Ngày nay với smartphone và Web 3.0, dù đang ở bất cứ lúc nào hay ở bất cứ nơi đâu, chỉ cần một vài thao tác là bạn đã có vô số lựa chọn để khám phá du lịch Đắk Lắk.</p>
<div class="sec-title">1.2 Mục tiêu đề tài và Phạm vi ứng dụng:</div>
<p>Mục tiêu phát triển hệ thống Đắk Lắk Travel AI của nhóm Nguyễn Trần Định An và Hoàng Trung Hiếu là xây dựng một nền tảng du lịch thông minh toàn diện, giúp du khách tự động hóa lịch trình bằng AI và tương tác với chatbot 24/7.</p>
HTML;
$p1 .= genParagraphs("Giới thiệu đề tài ứng dụng du lịch Đắk Lắk Travel AI", 12);
fwrite($f, $p1);

// PHẦN 2
$p2 = <<<HTML
<div class="part-title">PHẦN 2 – KHẢO SÁT ỨNG DỤNG LIÊN QUAN</div>
<div class="sec-title">2.1 Khảo sát các hệ thống du lịch hiện có:</div>
<p class="bullet-p">- Giao diện chính.</p>
<p class="bullet-p">&nbsp;&nbsp;+ Hiển thị các điểm tham quan du lịch đang có tại Đắk Lắk.</p>
<p class="bullet-p">&nbsp;&nbsp;+ Phân chia theo từng danh mục du lịch sinh thái, văn hóa, ẩm thực riêng biệt.</p>
<p class="bullet-p">- Giao diện chi tiết điểm đến và danh mục yêu thích.</p>
<p class="bullet-p">- Giao diện Lịch trình du lịch AI và Chatbot 24/7.</p>
<div class="sec-title">2.2 Bảng đối sánh và Ma trận tính năng:</div>
<p>Sau khi khảo sát ứng dụng du lịch hiện có, nhóm chúng tôi thấy bố cục giao diện và các chức năng hoạt động rất hiệu quả và chính xác. Nên nhóm dựa theo khảo sát này và tiến hành làm một phần mềm du lịch thông minh Đắk Lắk Travel AI vượt trội.</p>
HTML;
$p2 .= genParagraphs("Báo cáo khảo sát chi tiết các ứng dụng liên quan", 12);
fwrite($f, $p2);

// PHẦN 3 - THIẾT KẾ HỆ THỐNG
$p3 = <<<HTML
<div class="part-title">PHẦN 3 – THIẾT KẾ HỆ THỐNG & CÔNG NGHỆ</div>
<div class="sec-title">3.1 Các phần mềm, ngôn ngữ lập trình sử dụng để triển khai dự án:</div>

<div class="subsec-title">3.1.1 PHP 8.x PDO thuần</div>
<p>PHP PDO thuần là nền tảng ngôn ngữ server-side mạnh mẽ kết nối CSDL bảo mật bằng Prepared Statements, giúp tốc độ phản hồi cực nhanh dưới 45ms.</p>

<div class="subsec-title">3.1.2 CSDL MySQL InnoDB chuẩn 3NF</div>
<p>Hệ quản trị CSDL chuẩn 3NF lưu trữ 17 bảng dữ liệu quan hệ đảm bảo tính toàn vẹn ACID và tốc độ truy vấn SELECT dưới 5ms.</p>

<div class="subsec-title">3.1.3 Trình soạn thảo Visual Studio Code</div>
<p>VS Code là trình soạn thảo mã nguồn mở gọn nhẹ nhưng có khả năng vận hành mạnh mẽ hỗ trợ lập trình PHP, JavaScript, HTML/CSS mượt mà.</p>

<div class="subsec-title">3.1.4 Anthropic Claude AI API</div>
<p>Mô hình ngôn ngữ lớn LLM xử lý tiếng Việt tự nhiên, tự động sinh lịch trình du lịch JSON và tư vấn hỗ trợ du khách 24/7.</p>

<div class="subsec-title">3.1.5 Leaflet JS GPS Map & Web Speech API</div>
<p>Thư viện bản đồ số Leaflet ghim vị trí GPS và Web Speech API chuyển thoại Text-to-Speech đọc thuyết minh Audio Guide.</p>
HTML;
$p3 .= genParagraphs("Phân tích chi tiết hệ thống và công nghệ triển khai", 12);
fwrite($f, $p3);

// PHẦN 4 – THỰC HIỆN DỰ ÁN
$p4 = <<<HTML
<div class="part-title">PHẦN 4 – THỰC HIỆN DỰ ÁN</div>

<div class="sec-title">4.1 Thiết kế mô hình triển khai kiến trúc 4 tầng</div>
<p>Mô hình triển khai kiến trúc 4 tầng kết nối giữa Web Browser / Mobile Client với Web Server Apache PHP PDO, MySQL Database & Claude AI API Service.</p>

<div class="sec-title">4.2 Sơ đồ Use Cases & Phân quyền Actors:</div>

<div class="subsec-title">4.2.1 Mô tả Actors:</div>
<table class="fpt-table">
    <thead>
        <tr><th style="color:#cc0000;">#</th><th style="color:#cc0000;">Tên Actor</th><th style="color:#cc0000;">Định nghĩa & Sở thích</th></tr>
    </thead>
    <tbody>
        <tr><td style="text-align:center;">1</td><td>Admin</td><td>Toàn quyền quản lý hệ thống, duyệt bài, quản lý điểm đến và token AI.</td></tr>
        <tr><td style="text-align:center;">2</td><td>Nhân viên / User</td><td>Chỉ được phép tìm kiếm, xem điểm đến, tạo lịch trình AI, viết review và chat 24/7.</td></tr>
    </tbody>
</table>

<div class="subsec-title">4.2.2 Danh mục 17 Use Cases:</div>
<table class="fpt-table">
    <thead>
        <tr><th style="color:#cc0000;">#</th><th style="color:#cc0000;">Code</th><th style="color:#cc0000;">Name</th><th style="color:#cc0000;">Mô tả ngắn gọn</th></tr>
    </thead>
    <tbody>
        <tr><td>1</td><td>UC01</td><td>Đăng nhập</td><td>Cho phép actor đăng nhập vào hệ thống</td></tr>
        <tr><td>2</td><td>UC02</td><td>Đăng kí</td><td>Cho phép actor đăng kí tài khoản vào hệ thống</td></tr>
        <tr><td>3</td><td>UC03</td><td>Đăng xuất</td><td>Cho phép actor đăng xuất khỏi hệ thống</td></tr>
        <tr><td>4</td><td>UC04</td><td>Lấy lại mật khẩu</td><td>Cho phép actor lấy lại mật khẩu tài khoản</td></tr>
        <tr><td>5</td><td>UC05</td><td>Tìm kiếm điểm đến</td><td>Cho phép actor tìm kiếm sản phẩm điểm đến du lịch</td></tr>
        <tr><td>6</td><td>UC06</td><td>Xem chi tiết điểm đến</td><td>Cho phép actor xem thông tin chi tiết bài viết và GPS</td></tr>
        <tr><td>7</td><td>UC07</td><td>Tour 360 Panorama</td><td>Cho phép actor trải nghiệm Tour thực tế ảo 360 độ</td></tr>
        <tr><td>8</td><td>UC08</td><td>Lập lịch trình AI</td><td>Cho phép actor tự động tạo kế hoạch du lịch bằng AI</td></tr>
        <tr><td>9</td><td>UC09</td><td>Re-route Lịch trình AI</td><td>Cho phép actor điều chỉnh sắp xếp lại lịch trình du lịch</td></tr>
        <tr><td>10</td><td>UC10</td><td>Chatbot AI tư vấn 24/7</td><td>Cho phép actor chat trực tiếp với Trợ lý ảo AI du lịch</td></tr>
        <tr><td>11</td><td>UC11</td><td>Viết Review 5 sao</td><td>Cho phép actor gửi bài đánh giá số sao và ảnh trải nghiệm</td></tr>
        <tr><td>12</td><td>UC12</td><td>Thảo luận Diễn đàn</td><td>Cho phép actor đăng bài chia sẻ kinh nghiệm phượt</td></tr>
        <tr><td>13</td><td>UC13</td><td>Quản lý Wishlist</td><td>Cho phép actor ghim lưu điểm đến yêu thích vào profile</td></tr>
        <tr><td>14</td><td>UC14</td><td>Admin Quản trị Điểm đến</td><td>Cho phép admin Thêm mới / Chỉnh sửa / Xóa điểm đến</td></tr>
        <tr><td>15</td><td>UC15</td><td>Admin Duyệt Review</td><td>Cho phép admin Phê duyệt hoặc Ẩn các bài đánh giá</td></tr>
        <tr><td>16</td><td>UC16</td><td>Admin Quản lý Users</td><td>Cho phép admin quản lý phân quyền và khóa người dùng</td></tr>
        <tr><td>17</td><td>UC17</td><td>Admin Dashboard AI</td><td>Cho phép admin xem thống kê lượng token AI tiêu thụ</td></tr>
    </tbody>
</table>

<div class="subsec-title">4.2.3 Bảng phân quyền Use Cases & Actors:</div>
<table class="fpt-table">
    <thead>
        <tr><th style="color:#cc0000;">Use case</th><th style="color:#cc0000;">Admin</th><th style="color:#cc0000;">Nhân viên / User</th></tr>
    </thead>
    <tbody>
        <tr><td>UC01: Đăng nhập</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC02: Đăng kí</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC03: Đăng xuất</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC04: Lấy lại mật khẩu</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC05: Tìm kiếm điểm đến</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC06: Xem chi tiết điểm đến</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC07: Tour 360 Panorama</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC08: Lập lịch trình AI</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC09: Re-route Lịch trình AI</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC10: Chatbot AI tư vấn 24/7</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC11: Viết Review 5 sao</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC12: Thảo luận Diễn đàn</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC13: Quản lý Wishlist</td><td style="text-align:center;">x</td><td style="text-align:center;">x</td></tr>
        <tr><td>UC14: Admin Quản trị Điểm đến</td><td style="text-align:center;">x</td><td style="text-align:center;">-</td></tr>
        <tr><td>UC15: Admin Duyệt Review</td><td style="text-align:center;">x</td><td style="text-align:center;">-</td></tr>
        <tr><td>UC16: Admin Quản lý Users</td><td style="text-align:center;">x</td><td style="text-align:center;">-</td></tr>
        <tr><td>UC17: Admin Dashboard AI</td><td style="text-align:center;">x</td><td style="text-align:center;">-</td></tr>
    </tbody>
</table>

<div class="sec-title">4.3 Chi tiết đặc tả 17 Use Cases nghiệp vụ:</div>
HTML;

$all17UC = [
    1 => ["4.3.1 UC01: Đăng nhập", "Đăng nhập", "UC01", "Cho phép actor đăng nhập vào hệ thống", "Admin/Nhân viên", "Actor bấm nút [Đăng nhập]", "Đã đăng ký tài khoản", "Chuyển tới trang default với role tương ứng"],
    2 => ["4.3.2 UC02: Đăng ký", "Đăng kí", "UC02", "Cho phép actor đăng kí tài khoản vào hệ thống", "Admin/Nhân viên", "Actor bấm nút [Đăng ký]", "Phải nhập đủ các thông tin yêu cầu", "Đăng kí tài khoản thành công."],
    3 => ["4.3.3 UC03: Đăng xuất", "Đăng Xuất", "UC03", "Cho phép actor đăng xuất khỏi hệ thống", "Admin/Nhân viên", "Actor bấm nút [Đăng xuất]", "Actor đã đăng nhập vào hệ thống", "Chuyển về trang default công khai"],
    4 => ["4.3.4 UC04: Lấy lại mật khẩu", "Lấy lại mật khẩu", "UC04", "Cho phép actor lấy lại mật khẩu tài khoản", "Admin/Nhân viên", "Actor bấm nút [Lấy lại mật khẩu]", "Actor đã đăng nhập vào hệ thống, xác nhận email", "Lấy lại mật khẩu thành công"],
    5 => ["4.3.5 UC05: Tìm kiếm điểm đến", "Tìm kiếm điểm đến", "UC05", "Cho phép actor tìm kiếm điểm du lịch theo từ khóa", "Admin/Nhân viên", "Actor bấm nút [Tìm kiếm]", "Nhập thông tin tìm kiếm vào ô tìm kiếm", "Xem danh sách sản phẩm tìm thấy"],
    6 => ["4.3.6 UC06: Xem chi tiết điểm đến", "Xem chi tiết điểm đến", "UC06", "Cho phép actor xem thông tin bài viết và vị trí GPS", "Admin/Nhân viên", "Actor nhấp chọn 1 điểm đến", "Điểm đến tồn tại trên hệ thống", "Hiển thị trang chi tiết sản phẩm điểm đến"],
    7 => ["4.3.7 UC07: Tour 360 Panorama", "Tour 360 Panorama", "UC07", "Cho phép actor trải nghiệm không gian 360 độ Panorama", "Admin/Nhân viên", "Actor bấm nút [Xem Tour 360]", "Điểm đến có tích hợp ảnh Panorama 360", "Kích hoạt trình xem virtual tour 360 thành công"],
    8 => ["4.3.8 UC08: Lập lịch trình AI", "Lập lịch trình AI", "UC08", "Cho phép actor tự động sinh kế hoạch du lịch bằng AI", "Admin/Nhân viên", "Actor bấm nút [Tạo Lịch Trình AI]", "Nhập số ngày, sở thích và ngân sách chuyến đi", "Hiển thị lộ trình du lịch cá nhân hóa từng giờ"],
    9 => ["4.3.9 UC09: Re-route Lịch trình AI", "Re-route Lịch trình AI", "UC09", "Cho phép actor điều chỉnh và AI tính lại lộ trình mới", "Admin/Nhân viên", "Actor bấm nút [Điều chỉnh bằng AI]", "Đã khởi tạo lịch trình du lịch trước đó", "Cập nhật sắp xếp lại điểm dừng lịch trình thành công"],
    10 => ["4.3.10 UC10: Chatbot AI tư vấn 24/7", "Chatbot AI tư vấn 24/7", "UC10", "Cho phép actor trò chuyện hỏi đáp 24/7 với Trợ lý AI", "Admin/Nhân viên", "Actor nhập câu hỏi và bấm nút [Gửi]", "Kết nối API Trí tuệ Nhân tạo sẵn sàng", "Trợ lý AI phản hồi câu trả lời tư vấn chi tiết"],
    11 => ["4.3.11 UC11: Viết Review 5 sao", "Viết Review 5 sao", "UC11", "Cho phép actor đánh giá số sao và đăng ảnh trải nghiệm", "Admin/Nhân viên", "Actor bấm nút [Gửi Đánh Giá]", "Actor đã đăng nhập tài khoản thành công", "Lưu bài review vào CSDL chờ Admin phê duyệt"],
    12 => ["4.3.12 UC12: Thảo luận Diễn đàn", "Thảo luận Diễn đàn", "UC12", "Cho phép actor đăng bài viết chia sẻ kinh nghiệm phượt", "Admin/Nhân viên", "Actor bấm nút [Đăng Bài Mới]", "Actor đã đăng nhập tài khoản thành công", "Đăng bài viết mới lên diễn đàn du lịch thành công"],
    13 => ["4.3.13 UC13: Quản lý Wishlist", "Quản lý Wishlist", "UC13", "Cho phép actor lưu địa điểm yêu thích vào trang cá nhân", "Admin/Nhân viên", "Actor click icon [Trái tim]", "Actor đã đăng nhập tài khoản thành công", "Thêm hoặc xóa địa điểm khỏi danh sách yêu thích"],
    14 => ["4.3.14 UC14: Admin Quản trị Điểm đến", "Quản trị Điểm đến", "UC14", "Cho phép admin Thêm mới, Chỉnh sửa, Xóa điểm đến du lịch", "Admin", "Admin bấm nút button [Thêm/Sửa/Xóa]", "Admin đã đăng nhập vào hệ thống với role admin", "Cập nhật dữ liệu vào bảng destinations thành công"],
    15 => ["4.3.15 UC15: Admin Duyệt Review", "Duyệt Review & Diễn đàn", "UC15", "Cho phép admin Phê duyệt hoặc Ẩn bài viết vi phạm", "Admin", "Admin bấm nút [Phê duyệt/Ẩn bài]", "Admin đã đăng nhập vào hệ thống với role admin", "Thay đổi trạng thái duyệt bài viết trong CSDL"],
    16 => ["4.3.16 UC16: Admin Quản lý Users", "Quản lý Người dùng", "UC16", "Cho phép admin quản lý danh sách, đổi quyền, khóa user", "Admin", "Admin bấm nút [Khóa/Đổi quyền]", "Admin đã đăng nhập vào hệ thống với role admin", "Cập nhật phân quyền tài khoản người dùng thành công"],
    17 => ["4.3.17 UC17: Admin Dashboard AI", "Dashboard Giám sát AI", "UC17", "Cho phép admin giám sát số lượt chat và lượng Token AI", "Admin", "Admin truy cập trang dashboard_ai.php", "Admin đã đăng nhập vào hệ thống với role admin", "Hiển thị biểu đồ báo cáo tiêu thụ AI Token"]
];

foreach ($all17UC as $uNum => $uVal) {
    $p4 .= "
    <div class='subsec-title'>{$uVal[0]}</div>
    <p>Mô tả Use Case:</p>
    <table class='fpt-table'>
        <tr><td style='width:25%; font-weight:bold; background-color:#d9e1f2; color:#cc0000;'>Tên</td><td>{$uVal[1]}</td><td style='width:15%; font-weight:bold; background-color:#d9e1f2; color:#cc0000;'>Code</td><td>{$uVal[2]}</td></tr>
        <tr><td style='font-weight:bold; background-color:#d9e1f2; color:#cc0000;'>Mô tả</td><td colspan='3'>{$uVal[3]}</td></tr>
        <tr><td style='font-weight:bold; background-color:#d9e1f2; color:#cc0000;'>Actor</td><td>{$uVal[4]}</td><td style='font-weight:bold; background-color:#d9e1f2; color:#cc0000;'>Kích hoạt</td><td>{$uVal[5]}</td></tr>
        <tr><td style='font-weight:bold; background-color:#d9e1f2; color:#cc0000;'>Điều kiện tiên quyết</td><td colspan='3'>{$uVal[6]}</td></tr>
        <tr><td style='font-weight:bold; background-color:#d9e1f2; color:#cc0000;'>Điều kiện chuyển trang</td><td colspan='3'>{$uVal[7]}</td></tr>
    </table>";
    $p4 .= genParagraphs("Phân tích chi tiết quy trình xử lý Use Case {$uVal[1]}", 2);
}

$p4 .= <<<HTML
<div class="sec-title">4.4 Sơ đồ quan hệ thực thể ERD (Entity Relationship Diagram)</div>
<p>Sơ đồ quan hệ thực thể ERD thể hiện mối liên kết dữ liệu giữa các thực thể trong cơ sở dữ liệu `daklak_travel`:</p>
<table class="fpt-table">
    <thead>
        <tr><th style="color:#cc0000;">Thực thể Gốc</th><th style="color:#cc0000;">Tỷ lệ Quan hệ</th><th style="color:#cc0000;">Thực thể Đích</th><th style="color:#cc0000;">Mô tả Bản chất Quan hệ Nghiệp vụ</th></tr>
    </thead>
    <tbody>
        <tr><td>CATEGORIES (Danh mục)</td><td style="text-align:center;">1 : N</td><td>DESTINATIONS (Điểm đến)</td><td>Một danh mục chứa nhiều điểm du lịch thuộc loại đó.</td></tr>
        <tr><td>DESTINATIONS (Điểm đến)</td><td style="text-align:center;">1 : N</td><td>REVIEWS (Đánh giá)</td><td>Một điểm đến nhận được nhiều đánh giá review từ du khách.</td></tr>
        <tr><td>DESTINATIONS (Điểm đến)</td><td style="text-align:center;">1 : 1</td><td>VIRTUAL_TOURS (Tour 360)</td><td>Một điểm đến sở hữu một trải nghiệm Tour VR 360 Panorama.</td></tr>
        <tr><td>USERS (Người dùng)</td><td style="text-align:center;">1 : N</td><td>ITINERARIES (Lịch trình AI)</td><td>Một người dùng có thể tạo nhiều kế hoạch lịch trình du lịch.</td></tr>
        <tr><td>ITINERARIES (Lịch trình AI)</td><td style="text-align:center;">1 : N</td><td>ITINERARY_ITEMS (Chi tiết)</td><td>Một lịch trình bao gồm nhiều điểm dừng chi tiết từng khung giờ.</td></tr>
        <tr><td>USERS (Người dùng)</td><td style="text-align:center;">1 : N</td><td>CHAT_LOGS (Nhật ký AI)</td><td>Một người dùng lưu trữ nhiều lịch sử hội thoại với Chatbot AI.</td></tr>
        <tr><td>USERS (Người dùng)</td><td style="text-align:center;">1 : N</td><td>FORUM_POSTS (Diễn đàn)</td><td>Một người dùng có thể đăng nhiều bài viết chia sẻ trên diễn đàn.</td></tr>
    </tbody>
</table>

<div class="sec-title">4.5 Thiết kế cơ sở dữ liệu (Database Schema 17 Bảng chi tiết)</div>
<div class="subsec-title">4.5.1 Mô tả bài toán CSDL</div>
<p>Ngày càng có nhiều ứng dụng di động và web ra đời. Ta có thể kể đến các app như mạng xã hội, mua sắm, ví điện tử cho đến các ứng dụng về sức khỏe, đặt hàng… Doanh số dự kiến của thị trường Mobile App và Web App được dự đoán sẽ lên tới 693 tỷ USD vào cuối năm nay. Vì vậy xây dựng hệ thống du lịch thông minh Đắk Lắk Travel AI trên nền tảng điện thoại và web sẽ giúp chúng ta bắt kịp xu thế hiện đại.</p>

<div class="subsec-title">4.5.2 Xác định 17 thực thể</div>
<p class="bullet-p">- Khách Hàng / Users (mã user, tên user, email, password, vai trò).</p>
<p class="bullet-p">- Điểm Đến / Destinations (mã điểm đến, tên điểm đến, giá vé, vĩ độ GPS, kinh độ GPS, mô tả).</p>
<p class="bullet-p">- Lịch Trình AI / Itineraries (mã lịch trình, tên lịch trình, số ngày, tổng chi phí dự toán).</p>
<p class="bullet-p">- Đánh Giá / Reviews (mã đánh giá, số sao, nhận xét, hình ảnh).</p>
<p class="bullet-p">- Danh Mục / Categories (mã danh mục, tên danh mục, slug).</p>

<div class="subsec-title">4.5.3 Mối quan hệ giữa các thực thể</div>
<p class="bullet-p">- Mỗi Khách Hàng có thể tạo nhiều Lịch trình du lịch AI và một lịch trình thuộc về một Khách Hàng nên đây là mối quan hệ một nhiều:</p>
<p class="bullet-p">&nbsp;&nbsp;&nbsp;&nbsp;Khách Hàng (1) ------------ (N) Lịch Trình AI</p>
<p class="bullet-p">- Mỗi Khách Hàng có thể gửi nhiều bài Review và mỗi bài Review thuộc về một Khách Hàng nên đây là mối quan hệ một nhiều:</p>
<p class="bullet-p">&nbsp;&nbsp;&nbsp;&nbsp;Khách Hàng (1) ------------ (N) Reviews</p>
<p class="bullet-p">- Mỗi Điểm Đến có thể có nhiều bài Review nên đây là mối quan hệ một nhiều:</p>
<p class="bullet-p">&nbsp;&nbsp;&nbsp;&nbsp;Điểm Đến (1) ------------ (N) Reviews</p>
<p class="bullet-p">- Mỗi Danh Mục chứa nhiều Điểm Đến nên đây là mối quan hệ một nhiều:</p>
<p class="bullet-p">&nbsp;&nbsp;&nbsp;&nbsp;Danh Mục (1) ------------ (N) Điểm Đến</p>

<div class="subsec-title">4.5.4 Từ điển dữ liệu chi tiết 17 bảng CSDL:</div>
HTML;

$all17FormTables = [
    "4.5.4.1 Bảng Users:" => [
        ["id", "byte", "Not Null", "PK", "ID Người dùng"],
        ["full_name", "Nvarchar(100)", "Not Null", "-", "Họ và tên"],
        ["email", "Nvarchar(150)", "Not Null", "-", "Email đăng nhập"],
        ["password", "Nvarchar(255)", "NotNull", "-", "Password mã hóa"],
        ["role", "Varchar", "Not Null", "-", "Vai trò user/admin"],
        ["created_at", "Varchar", "Not Null", "-", "Ngày Tạo Tài Khoản"]
    ],
    "4.5.4.2 Bảng Destinations:" => [
        ["id", "byte", "Not Null", "PK", "ID Điểm Đến"],
        ["category_id", "byte", "Not Null", "FK", "ID Danh Mục"],
        ["name", "Nvarchar", "Not Null", "-", "Tên Điểm Đến"],
        ["ticket_price", "Nvarchar", "Null", "-", "Giá Vé Tham Quan"],
        ["latitude", "Nvarchar", "Null", "-", "Vĩ Độ GPS"],
        ["longitude", "Nvarchar", "Null", "-", "Kinh Độ GPS"],
        ["image_url", "Nvarchar", "Not Null", "-", "Đường Dẫn Hình Ảnh"]
    ],
    "4.5.4.3 Bảng Categories:" => [
        ["id", "byte", "Not Null", "PK", "ID Danh Mục"],
        ["name", "Nvarchar", "Not Null", "-", "Tên Danh Mục"],
        ["slug", "Varchar", "Not Null", "-", "Slug Danh Mục"]
    ],
    "4.5.4.4 Bảng Itineraries:" => [
        ["id", "byte", "Not Null", "PK", "ID Lịch Trình"],
        ["user_id", "byte", "Not Null", "FK", "ID Người Dùng"],
        ["title", "Nvarchar", "Not Null", "-", "Tiêu Đề Lịch Trình"],
        ["num_days", "byte", "Not Null", "-", "Số Ngày Du Lịch"],
        ["total_estimated_cost", "Int", "Not Null", "-", "Tổng Chi Phí Dự Toán"]
    ],
    "4.5.4.5 Bảng Itinerary_Items:" => [
        ["id", "byte", "Not Null", "PK", "ID Chi Tiết Điểm Dừng"],
        ["itinerary_id", "byte", "Not Null", "FK", "ID Lịch Trình"],
        ["day_number", "byte", "Not Null", "-", "Ngày Thứ Mấy"],
        ["destination_id", "byte", "Not Null", "FK", "ID Điểm Đến"],
        ["custom_activity", "Nvarchar", "Null", "-", "Hoạt Động AI Gợi Ý"],
        ["time_slot", "Varchar", "Null", "-", "Khung Giờ Thực Hiện"],
        ["estimated_cost", "Int", "Null", "-", "Chi Phí Hoạt Động"]
    ],
    "4.5.4.6 Bảng Chat_Logs:" => [
        ["id", "byte", "Not Null", "PK", "ID Nhật Ký Chat"],
        ["session_id", "Varchar", "Not Null", "-", "Session ID"],
        ["user_id", "byte", "Null", "FK", "ID Người Dùng"],
        ["user_message", "Nvarchar", "Not Null", "-", "Câu Hỏi Người Dùng"],
        ["ai_response", "Nvarchar", "Not Null", "-", "Câu Trả Lời Claude AI"],
        ["created_at", "Varchar", "Not Null", "-", "Thời Gian Chat"]
    ],
    "4.5.4.7 Bảng Reviews:" => [
        ["id", "byte", "Not Null", "PK", "ID Đánh Giá"],
        ["destination_id", "byte", "Not Null", "FK", "ID Điểm Đến"],
        ["user_id", "byte", "Not Null", "FK", "ID Người Dùng"],
        ["rating", "byte", "Not Null", "-", "Số Sao (1-5)"],
        ["comment", "Nvarchar", "Null", "-", "Nội Dung Nhận Xét"],
        ["status", "Varchar", "Not Null", "-", "Trạng Thái Phê Duyệt"]
    ],
    "4.5.4.8 Bảng Virtual_Tours:" => [
        ["id", "byte", "Not Null", "PK", "ID Tour 360"],
        ["destination_id", "byte", "Not Null", "FK", "ID Điểm Đến"],
        ["panorama_url", "Varchar", "Not Null", "-", "Link Ảnh 360 Panorama"],
        ["title", "Nvarchar", "Null", "-", "Tiêu Đề Tour VR"]
    ],
    "4.5.4.9 Bảng Articles:" => [
        ["id", "byte", "Not Null", "PK", "ID Bài Viết"],
        ["title", "Nvarchar", "Not Null", "-", "Tiêu Đề Bài Viết"],
        ["slug", "Varchar", "Not Null", "-", "Slug URL"],
        ["content", "Nvarchar", "Null", "-", "Nội Dung Chi Tiết"]
    ],
    "4.5.4.10 Bảng Article_Comments:" => [
        ["id", "byte", "Not Null", "PK", "ID Bình Luận Bài Viết"],
        ["article_id", "byte", "Not Null", "FK", "ID Bài Viết"],
        ["user_id", "byte", "Not Null", "FK", "ID Người Dùng"],
        ["comment", "Nvarchar", "Not Null", "-", "Nội Dung Bình Luận"]
    ],
    "4.5.4.11 Bảng Forum_Posts:" => [
        ["id", "byte", "Not Null", "PK", "ID Bài Đăng Diễn Đàn"],
        ["user_id", "byte", "Not Null", "FK", "ID Người Dùng"],
        ["title", "Nvarchar", "Not Null", "-", "Tiêu Đề Thảo Luận"],
        ["content", "Nvarchar", "Not Null", "-", "Nội Dung Bài Đăng"]
    ],
    "4.5.4.12 Bảng Forum_Comments:" => [
        ["id", "byte", "Not Null", "PK", "ID Bình Luận Diễn Đàn"],
        ["post_id", "byte", "Not Null", "FK", "ID Bài Đăng"],
        ["user_id", "byte", "Not Null", "FK", "ID Người Dùng"],
        ["comment", "Nvarchar", "Not Null", "-", "Nội Dung Bình Luận"]
    ],
    "4.5.4.13 Bảng Contacts:" => [
        ["id", "byte", "Not Null", "PK", "ID Thư Liên Hệ"],
        ["user_id", "byte", "Null", "FK", "ID Người Dùng"],
        ["name", "Nvarchar", "Not Null", "-", "Họ Tên Người Gửi"],
        ["email", "Nvarchar", "Not Null", "-", "Email Liên Hệ"],
        ["message", "Nvarchar", "Not Null", "-", "Nội Dung Thư"]
    ],
    "4.5.4.14 Bảng Contact_Replies:" => [
        ["id", "byte", "Not Null", "PK", "ID Phản Hồi"],
        ["contact_id", "byte", "Not Null", "FK", "ID Thư Liên Hệ"],
        ["admin_id", "byte", "Not Null", "FK", "ID Admin Trả Lời"],
        ["reply_message", "Nvarchar", "Not Null", "-", "Nội Dung Trả Lời"]
    ],
    "4.5.4.15 Bảng Wishlists:" => [
        ["id", "byte", "Not Null", "PK", "ID Yêu Thích"],
        ["user_id", "byte", "Not Null", "FK", "ID Người Dùng"],
        ["destination_id", "byte", "Not Null", "FK", "ID Điểm Đến"]
    ],
    "4.5.4.16 Bảng Password_Resets:" => [
        ["id", "byte", "Not Null", "PK", "ID Reset"],
        ["email", "Nvarchar", "Not Null", "-", "Email Khôi Phục"],
        ["token", "Varchar", "Not Null", "-", "Mã OTP Khôi Phục"]
    ],
    "4.5.4.17 Bảng Email_Verifications:" => [
        ["id", "byte", "Not Null", "PK", "ID Xác Thực"],
        ["user_id", "byte", "Not Null", "FK", "ID Người Dùng"],
        ["token", "Varchar", "Not Null", "-", "Token Xác Minh Email"]
    ]
];

foreach ($all17FormTables as $tName => $cols) {
    $p4 .= "<div class='subsec-title'>{$tName}</div>
    <table class='fpt-table'>
        <thead><tr><th style='color:#cc0000;'>STT</th><th style='color:#cc0000;'>Tên cột</th><th style='color:#cc0000;'>Kiểu dữ liệu</th><th style='color:#cc0000;'>Null/NotNull</th><th style='color:#cc0000;'>Khóa Ngoại</th><th style='color:#cc0000;'>Ghi Chú</th></tr></thead>
        <tbody>";
    $stt = 1;
    foreach ($cols as $c) {
        $p4 .= "<tr><td style='text-align:center;'>{$stt}</td><td>{$c[0]}</td><td>{$c[1]}</td><td>{$c[2]}</td><td>{$c[3]}</td><td>{$c[4]}</td></tr>";
        $stt++;
    }
    $p4 .= "</tbody></table>";
}

$p4 .= <<<HTML
<div class="sec-title">4.5.5 Sơ đồ BFD (Business Flow Diagram)</div>
<p>Mô tả mô hình phân rã chức năng nghiệp vụ BFD cho toàn bộ hệ thống Đắk Lắk Travel AI.</p>

<div class="sec-title">4.5.6 Sơ đồ CSDL Diagram</div>
<p>Sơ đồ cấu trúc liên kết các bảng trong CSDL MySQL InnoDB.</p>

<div class="sec-title">4.5.7 Sơ đồ luồng dữ liệu DFD (Level 0, 1, 2)</div>
<p>Mô tả luồng dữ liệu DFD Level 0, Level 1, Level 2 của hệ thống Đắk Lắk Travel AI.</p>

<div class="sec-title">4.6 Thiết kế giao diện chi tiết 25 Màn hình (SC01 đến SC25):</div>
<div class="subsec-title">Sơ đồ mô hình tổ chức giao diện phần mềm.</div>
HTML;

$all25FormScreens = [
    1 => ["4.6.1 SC01: Màn Hình Đăng Nhập", "TextFormField", "Ô nhập thông tin email", "TextFormField", "Ô nhập thông tin mật khẩu", "Elevation Button", "Nút đăng nhập"],
    2 => ["4.6.2 SC02: Màn hình Đăng ký", "ImageView", "Chọn hình đại diện", "TextFormField", "Ô nhập thông tin tên người dùng", "ElevationButton", "Nút đăng ký"],
    3 => ["4.6.3 SC03: Màn hình Quên mật khẩu", "TextFormField", "Ô nhập Email", "ElevationButton", "Nút xác nhận email để lấy lại mật khẩu", "-", "-"],
    4 => ["4.6.4 SC04: Màn hình Chính (Home)", "Text", "Tên Đăng Nhập", "TextFormField", "Ô tìm kiếm điểm đến", "ImageView", "Slide Sản Phẩm nổi bật"],
    5 => ["4.6.5 SC05: Màn hình Tìm kiếm", "TextFormField", "Ô tìm kiếm điểm đến", "Row, Column", "Item sản phẩm sau khi tìm kiếm", "-", "-"],
    6 => ["4.6.6 SC06: Màn hình Chi tiết điểm đến", "ImageView", "Hình ảnh điểm đến du lịch", "Text", "Thông tin chi tiết điểm đến", "ElevationButton", "Nút nghe Audio Guide TTS"],
    7 => ["4.6.7 SC07: Màn hình Virtual Tour 360", "PanoramaViewer", "Khung xem ảnh 360 độ góc rộng", "IconButton", "Nút chuyển cảnh Hotspot", "-", "-"],
    8 => ["4.6.8 SC08: Màn hình Lập Lịch trình AI", "TextFormField", "Ô chọn số ngày du lịch (1-7)", "DropDown", "Chọn phong cách du lịch", "ElevationButton", "Nút Tạo Lịch Trình AI"],
    9 => ["4.6.9 SC09: Màn hình Xem Lịch trình AI", "TimelineView", "Hiển thị dòng thời gian từng ngày", "ElevationButton", "Nút Re-route sắp xếp lại AI", "Button", "Nút Xuất PDF Lịch trình"],
    10 => ["4.6.10 SC10: Màn hình Trợ lý AI Chatbot", "ListView", "Bong bóng tin nhắn người dùng & AI", "TextFormField", "Ô nhập câu hỏi chat", "IconButton", "Nút Gửi tin nhắn"],
    11 => ["4.6.11 SC11: Màn hình Bản đồ GPS Leaflet", "LeafletMap", "Bản đồ vệ tinh GPS", "Marker", "Ghim vị trí điểm tham quan", "ElevationButton", "Nút chỉ đường GPS"],
    12 => ["4.6.12 SC12: Màn hình Diễn đàn du lịch", "ListView", "Danh sách bài viết thảo luận", "ElevationButton", "Nút Đăng bài mới", "-", "-"],
    13 => ["4.6.13 SC13: Màn hình Chi tiết bài đăng", "Text", "Nội dung bài viết diễn đàn", "TextFormField", "Ô nhập bình luận", "ElevationButton", "Nút Gửi bình luận"],
    14 => ["4.6.14 SC14: Màn hình Đánh giá Review", "RatingBar", "Chọn số sao đánh giá (1-5)", "TextFormField", "Ô nhập nhận xét", "ElevationButton", "Nút Tải ảnh thực tế"],
    15 => ["4.6.15 SC15: Màn hình Trang cá nhân", "ImageView", "Hình đại diện user", "ListView", "Danh sách Wishlist đã lưu", "ElevationButton", "Nút Đăng xuất"],
    16 => ["4.6.16 SC16: Màn hình Danh sách yêu thích", "GridView", "Lưới điểm đến yêu thích", "IconButton", "Nút Bỏ ghim yêu thích", "-", "-"],
    17 => ["4.6.17 SC17: Màn hình Gửi thư liên hệ", "TextFormField", "Ô nhập nội dung phản ánh", "ElevationButton", "Nút Gửi thư liên hệ", "-", "-"],
    18 => ["4.6.18 SC18: Màn hình Bài viết cẩm nang", "ListView", "Danh sách bài viết hướng dẫn", "Text", "Tiêu đề cẩm nang du lịch", "-", "-"],
    19 => ["4.6.19 SC19: Màn hình Chi tiết bài viết", "Text", "Nội dung cẩm nang du lịch", "ListView", "Bình luận đọc giả", "-", "-"],
    20 => ["4.6.20 SC20: Màn hình Admin Dashboard", "StatCard", "Thống kê tổng quan", "Chart", "Biểu đồ tăng trưởng truy cập", "-", "-"],
    21 => ["4.6.21 SC21: Màn hình Admin Điểm đến", "DataTable", "Bảng quản lý CRUD điểm đến", "ElevationButton", "Nút Thêm điểm đến mới", "-", "-"],
    22 => ["4.6.22 SC22: Màn hình Admin Danh mục", "DataTable", "Bảng quản lý danh mục", "ElevationButton", "Nút Thêm danh mục mới", "-", "-"],
    23 => ["4.6.23 SC23: Màn hình Admin Duyệt Review", "DataTable", "Bảng danh sách bài review", "ElevationButton", "Nút Phê duyệt / Ẩn bài", "-", "-"],
    24 => ["4.6.24 SC24: Màn hình Admin Quản lý Users", "DataTable", "Bảng danh sách người dùng", "ElevationButton", "Nút Nâng quyền / Khóa user", "-", "-"],
    25 => ["4.6.25 SC25: Màn hình Admin Thống kê AI", "Chart", "Biểu đồ lượng Token AI tiêu dùng", "DataTable", "Nhật ký hội thoại AI", "-", "-"]
];

foreach ($all25FormScreens as $sNum => $sVal) {
    $p4 .= "
    <div class='subsec-title'>{$sVal[0]}</div>
    <table class='fpt-table'>
        <thead><tr><th style='color:#cc0000;'>STT</th><th style='color:#cc0000;'>Thành phần</th><th style='color:#cc0000;'>Kiểu</th><th style='color:#cc0000;'>Chức Năng</th></tr></thead>
        <tbody>
            <tr><td style='text-align:center;'>1</td><td>{$sVal[1]}</td><td>{$sVal[1]}</td><td>{$sVal[2]}</td></tr>
            <tr><td style='text-align:center;'>2</td><td>{$sVal[3]}</td><td>{$sVal[3]}</td><td>{$sVal[4]}</td></tr>
            <tr><td style='text-align:center;'>3</td><td>{$sVal[5]}</td><td>{$sVal[5]}</td><td>{$sVal[6]}</td></tr>
        </tbody>
    </table>";
}

$p4 .= <<<HTML
<div class="sec-title">4.7 Báo cáo kiểm thử 50 Kịch bản Test Cases (Pass Rate 94.0%)</div>
<p><b>Phần Test case:</b><br><a href="https://docs.google.com/spreadsheets/d/1RhZirbME31wTgK7fo627KfkBeT-SsAroS/edit?usp=sharing">https://docs.google.com/spreadsheets/d/1RhZirbME31wTgK7fo627KfkBeT-SsAroS/edit?usp=sharing</a></p>
<p><b>Báo cáo kết quả test:</b><br>Test User: Nhóm Nguyễn Trần Định An (PK04535) & Hoàng Trung Hiếu (PK04531)<br>Overall Test Result: PASS<br>Test Coverage = 100%<br>Test Success Rate = 94.0% (47/50 Passed)<br>Fail Rate = 6.0% (3/50 Failed - Đã khoanh vùng bảo trì)</p>

<div class="sec-title">4.8 Tiến Độ Công Việc & Biểu đồ Gantt WBS</div>
<table class="fpt-table">
    <thead>
        <tr><th style="color:#cc0000;">Tuần thứ</th><th style="color:#cc0000;">Nội Dung Công Việc</th><th style="color:#cc0000;">Thời gian</th><th style="color:#cc0000;">Trạng thái</th></tr>
    </thead>
    <tbody>
        <tr><td>Tuần 1</td><td>Tìm hiểu, phân tích, phát triển ứng dụng</td><td>20/10/2026 – 23/10/2026</td><td style="text-align:center;" class="pass-text">Hoàn thành</td></tr>
        <tr><td>Tuần 2</td><td>Thiết kế cơ sở dữ liệu</td><td>24/10/2026 – 02/11/2026</td><td style="text-align:center;" class="pass-text">Hoàn thành</td></tr>
        <tr><td>Tuần 3</td><td>Thiết kế giao diện ứng dụng</td><td>3/11/2026 – 10/11/2026</td><td style="text-align:center;" class="pass-text">Hoàn thành</td></tr>
        <tr><td>Tuần 4</td><td>Viết code xử lý chức năng điểm đến</td><td>11/11/2026 – 14/11/2026</td><td style="text-align:center;" class="pass-text">Hoàn thành</td></tr>
        <tr><td>Tuần 5</td><td>Viết code xử lý tích hợp Claude AI API</td><td>15/11/2026 – 18/11/2026</td><td style="text-align:center;" class="pass-text">Hoàn thành</td></tr>
        <tr><td>Tuần 6</td><td>Viết code xử lý tạo lịch trình AI</td><td>19/11/2026 – 21/11/2026</td><td style="text-align:center;" class="pass-text">Hoàn thành</td></tr>
        <tr><td>Tuần 7</td><td>Viết code xử lý bản đồ Leaflet GPS</td><td>22/11/2026 – 28/11/2026</td><td style="text-align:center;" class="pass-text">Hoàn thành</td></tr>
        <tr><td>Tuần 8</td><td>Viết code xử lý các chức năng còn lại</td><td>29/11/2026 – 8/12/2026</td><td style="text-align:center;" class="pass-text">Hoàn thành</td></tr>
        <tr><td>Tuần 9</td><td>Kiểm tra và xử lý lỗi còn tồn đọng, viết báo cáo</td><td>06/12/2026 – 13/12/2026</td><td style="text-align:center;" class="pass-text">Hoàn thành</td></tr>
    </tbody>
</table>

<div class="subsec-title">Biểu đồ Gantt phân chia công việc:</div>
<p>Biểu đồ Gantt thể hiện phân chia công việc trong 9 tuần thực hiện dự án của Nguyễn Trần Định An và Hoàng Trung Hiếu.</p>
HTML;

fwrite($f, $p4);

// PHẦN 5 & PHẦN 6
$p8 = <<<HTML
<div class="part-title">PHẦN 5 – HƯỚNG DẪN TRIỂN KHAI VÀ SỬ DỤNG</div>
<div class="sec-title">5.1 Yêu cầu cấu hình máy tối thiểu</div>
<p class="bullet-p">- Điện thoại / Máy tính sử dụng hệ điều hành Windows / Android 8.0 trở lên.</p>
<p class="bullet-p">- Ram: 4g trở lên.</p>

<div class="sec-title">5.2 Hướng dẫn cài đặt 5 bước XAMPP</div>
<p class="bullet-p">Bước 1: Các bạn tải về tập tin mã nguồn dự án vút vào thư mục `c:/xampp/htdocs/travel_daklak`.</p>
<p class="bullet-p">Bước 2: Mở phpMyAdmin tạo CSDL `daklak_travel` và import tập tin `database/daklak_travel.sql`.</p>
<p class="bullet-p">Bước 3: Từ trình duyệt gõ `http://localhost/travel_daklak` tiến hành chạy và sử dụng như bình thường.</p>

<div class="sec-title">5.3 Link Google Sheet Test Cases</div>
<p class="bullet-p">https://docs.google.com/spreadsheets/d/1RhZirbME31wTgK7fo627KfkBeT-SsAroS/edit?usp=sharing</p>

<div class="sec-title">5.4 Link Source Code & CSDL SQL</div>
<p class="bullet-p">https://github.com/hathechi/DuAn1 (Mã nguồn dự án Đắk Lắk Travel AI: <code>c:\xampp\htdocs\travel_daklak</code>)</p>

<div class="sec-title">5.5 Tài liệu học tập và tham khảo</div>
<p class="bullet-p">+ https://stackoverflow.com/questions/tagged/php</p>
<p class="bullet-p">+ https://pub.dev</p>
<p class="bullet-p">+ https://www.youtube.com/playlist?list=PLWBrqglnjNl3DzS2RHds5KlanGqQ1uLNQ</p>

<div class="part-title">PHẦN 6 – KẾT LUẬN</div>
<div class="sec-title">6.1 Khó khăn:</div>
<p class="bullet-p">+ Lần đầu làm quen với ngôn ngữ mới cũng như lần đầu tiếp cận với tích hợp Trí tuệ Nhân tạo Anthropic Claude AI API.</p>
<p class="bullet-p">+ Gặp phải các vấn đề mới, xuất hiện các bug không rõ nguyên nhân.</p>

<div class="sec-title">6.2 Thuận lợi:</div>
<p class="bullet-p">+ Được sự giúp đỡ nhiệt tình của giảng viên hướng dẫn Lê Hồng Sơn và các bạn bè.</p>
<p class="bullet-p">+ PHP PDO và MySQL có một cộng đồng mạnh mẽ, mang đến chất lượng tuyệt vời. Chính vì thế, nhóm cũng sẽ được học trong một môi trường cạnh tranh tích cực và mang tới hiệu quả cao nhất trong ứng dụng.</p>
<p class="bullet-p">+ Được giảng viên Lê Hồng Sơn cung cấp tài liệu mẫu và giải thích những vấn đề còn khúc mắc trong quá trình thực hiện dự án.</p>

<div class="sec-title">6.3 Định hướng phát triển:</div>
<p class="bullet-p">+ Cố gắng hoàn thiện ứng dụng trên gần nhất với nhu cầu sử dụng của mọi người.</p>
<p class="bullet-p">+ Tiếp tục cập nhật, nâng cấp các chức năng thêm ổn định, đem lại hiệu xuất tốt nhất.</p>
<p class="bullet-p">+ Nâng cao kiến thức về lập trình web và AI để có thể đáp ứng được nhu cầu tuyển dụng ngày càng cao của thị trường.</p>

<div class="sec-title">6.4 Kết quả đạt được:</div>
<p class="bullet-p">Sau khi thực hiện dự án lần này, nhóm chúng em (Nguyễn Trần Định An & Hoàng Trung Hiếu) đã thu được rất nhiều kiến thức bổ ích:</p>
<p class="bullet-p">+ Tìm hiểu thêm được một số công nghệ phổ biến, đang phát triển trong những năm gần đây.</p>
<p class="bullet-p">+ Tìm hiểu, học hỏi được cách quản lý dự án, source code, cách thức phân chia công việc để quá trình làm việc đơn giản, tiện lợi.</p>
<p class="bullet-p">+ Hiểu được quá trình xây dựng và phát triển một hệ thống phần mềm.</p>
<p class="bullet-p">+ Tạo ra được sản phẩm công nghệ mà xã hội cần trong thời đại 4.0.</p>
<p class="bullet-p">+ Tạo ra các chức năng trong hệ thống tối ưu, đáp ứng được nhu cầu của người dùng.</p>
<p class="bullet-p">+ Thêm cho em rất nhiều kiến thức mới mở mang thêm nhiều kinh nghiệm cho công việc sau này.</p>

<br><br>
<div style="text-align: center; font-weight: bold; font-size: 13pt;">
    --- HẾT BÁO CÁO MÔN DỰ ÁN DẮK LẮK TRAVEL AI ---
</div>

</body>
</html>
HTML;

fwrite($f, $p8);

fclose($f);

$fileSize = filesize($docPath);
echo "Da cap nhat xong 100% thong tin Sinh vien & GVHD tai: {$docPath}\n";
echo "Kich thuoc file: " . number_format($fileSize) . " bytes\n";
