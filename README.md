# HƯỚNG DẪN BÀI TẬP LỚN ĐỀ TÀI 18 (PHP 8 - XAMPP)

> **Tên bài:** Xây dựng hệ thống xếp thời khóa biểu theo ràng buộc Giảng viên, Phòng học và Lớp học  
> **Ngôn ngữ:** PHP 8 (Chạy trên XAMPP hoặc PHP CLI)  
> **Tệp chạy:** `index.php`, `admin.php`, `teacher.php`, `class.php`  
> **Mã nguồn được chia theo chức năng:** `app/` và `views/`

---

## 1. Cấu trúc thư mục

```text
C:\xampp\htdocs\BTL.php\
├── index.php        # Trang chính: thời khóa biểu toàn trường
├── admin.php        # Trang quản trị và xếp lịch
├── teacher.php      # Trang thời khóa biểu và đăng ký môn của giảng viên
├── class.php        # Trang thời khóa biểu lớp học
├── BTL.php          # Tệp tương thích cũ, chuyển tiếp tới app/controller.php
├── app\
│   ├── controller.php # Kết nối dữ liệu, xử lý đăng nhập và các thao tác
│   └── scheduler.php  # Kiểm tra ràng buộc và thuật toán xếp lịch
├── views\
│   ├── layout.php     # Khung HTML dùng chung
│   ├── pages\
│   │   ├── admin.php  # Giao diện quản trị
│   │   ├── teacher.php # Giao diện giảng viên
│   │   └── class.php  # Giao diện lớp
│   └── partials\
│       ├── auth_modal.php   # Đăng nhập / đăng ký
│       ├── adjust_modal.php # Hộp thoại đổi lịch
│       ├── footer.php
│       └── scripts.php
├── database.php     # Kết nối MySQL và đọc dữ liệu từ các bảng
├── database.sql     # Cấu trúc bảng và dữ liệu mẫu để nhập vào phpMyAdmin
├── test_btl.php     # Kiểm thử xếp lịch và khung giờ cố định
├── README.md        # Hướng dẫn chi tiết
└── .vscode/         # Cấu hình tự động cho Visual Studio Code
    ├── settings.json
    ├── tasks.json
    └── launch.json
```

---

## 2. Cách chạy ứng dụng

### Chuẩn bị cơ sở dữ liệu MySQL bằng phpMyAdmin
1. Mở XAMPP Control Panel và bấm **Start** tại **Apache** và **MySQL**.
2. Mở `http://localhost/phpmyadmin`.
3. Chọn **Import**, chọn tệp `database.sql` trong thư mục dự án rồi bấm **Import**. Tệp sẽ tạo cơ sở dữ liệu `btl_timetable`, các bảng giảng viên, lớp, phòng, khung giờ, buổi học, lịch bận và tài khoản mẫu.
4. Mặc định ứng dụng kết nối `127.0.0.1`, database `btl_timetable`, user `root`, mật khẩu trống (mặc định XAMPP). Nếu cấu hình MySQL khác, đặt biến môi trường `DB_HOST`, `DB_NAME`, `DB_USER` và `DB_PASSWORD` trước khi chạy PHP.
5. Thay đổi dữ liệu trong phpMyAdmin sẽ được đọc bởi ứng dụng. Trạng thái khóa/mở khóa buổi học, tài khoản, lịch đang dùng và các phương án được lưu trong MySQL. Ứng dụng tự tạo/cập nhật danh mục `subjects`, bảng yêu cầu môn học và lịch bận, bảng `schedule_plans`, trường ưu tiên khung giờ và chuyển lịch cũ sang 10 buổi nửa ngày mỗi tuần.
6. Chọn **Đăng nhập / Đăng ký** để tạo tài khoản giảng viên hoặc đại diện lớp, liên kết với hồ sơ tương ứng chưa có tài khoản. Hồ sơ đã gắn tài khoản mẫu sẽ hiện trạng thái đã có tài khoản.
7. Với database cũ, có thể nhập lại `database.sql`; ngoài ra ứng dụng tự bổ sung các cột/bảng phục vụ ưu tiên và lưu phương án khi mở trang, không xóa dữ liệu hiện có.
8. Giảng viên đăng nhập bằng tài khoản của mình, vào trang **Lịch Giảng viên**, chọn môn học có sẵn, lớp học và ngày/buổi mong muốn trong mục **Đăng ký môn học**. Ngày/buổi đã chọn là khung giờ ưu tiên, không phải cam kết cố định; thuật toán có thể xếp sang ca khác nếu bị vướng điều kiện bắt buộc. Yêu cầu được gửi ở trạng thái chờ duyệt; môn chỉ được tạo và xếp lịch sau khi Admin duyệt.
9. Giảng viên cũng có thể gửi **Đăng ký lịch bận** với ngày/buổi và lý do. Yêu cầu sẽ ở trạng thái chờ duyệt và chưa ảnh hưởng đến việc xếp lịch. Admin xem tại tab **Duyệt lịch bận**; chỉ khi duyệt, khung giờ mới được thêm vào lịch bận bắt buộc và lịch được xếp lại. Giảng viên xem trạng thái cùng ghi chú phản hồi tại trang cá nhân.
10. Admin đăng nhập bằng tài khoản `admin` / `123` để theo dõi lịch và quản lý dữ liệu. Tab **Giảng viên / Phòng / Khung giờ** cho phép quản lý giảng viên, phòng, sức chứa, loại phòng, thời gian và các lịch bận hiện có; **Quản lý lớp học** và **Quản lý buổi học** quản lý dữ liệu còn lại. Các mục đang được tham chiếu không thể bị xóa.
11. Khi thêm/sửa buổi học, Admin có thể đặt **khung giờ ưu tiên**. Đây là mong muốn mềm: nếu khung đó không khả thi, thuật toán tiếp tục tìm khung khác; phương án ghi lại số ưu tiên chưa đạt. Xung đột giảng viên/lớp/phòng, lịch bận, loại phòng và sức chứa vẫn là điều kiện bắt buộc.
12. Nút **Tạo phương án** cho phép chạy thuật toán cơ bản (Greedy First-Fit) hoặc cải thiện (MRV + Best-Fit Room). Mỗi phương án được lưu để so sánh số buổi xếp được, số buổi chưa xếp/vi phạm và số ưu tiên không đạt. Chọn **Sử dụng** để áp dụng phương án; các buổi đã khóa được giữ nguyên.
13. Thao tác đổi lịch kiểm tra lại điều kiện bắt buộc. Nếu thao tác gây xung đột, thông báo nêu các buổi liên quan và lịch không bị thay đổi; các buổi chưa xếp của phương án hiện hành được hiển thị riêng. Có thể in hoặc lưu PDF thời khóa biểu theo lớp hoặc giảng viên từ các trang tương ứng.

### Các trang giao diện
- Trang chính / thời khóa biểu toàn trường: `http://localhost/BTL.php/`
- Quản trị: `http://localhost/BTL.php/admin.php`
- Giảng viên: `http://localhost/BTL.php/teacher.php`
- Lớp học: `http://localhost/BTL.php/class.php`

### Cách 1: Sử dụng Visual Studio Code (Khuyên dùng)
1. Đã mở thư mục `C:\xampp\htdocs\BTL.php` trên VS Code.
2. Mở trang cần chạy (`index.php`, `admin.php`, `teacher.php` hoặc `class.php`). Để sửa xử lý nghiệp vụ, mở `app/controller.php`; để sửa giao diện, mở trang tương ứng trong `views/pages/`.
3. Bấm tổ hợp phím **Ctrl + Shift + B** để chạy PHP Built-in Server (hoặc vào menu `Terminal` -> `Run Task...` -> `Chạy PHP Built-in Server`).
4. Mở trình duyệt web truy cập trang chính; các trang còn lại dùng đường dẫn cùng thư mục:
   ```text
   http://localhost:8000/
   http://localhost:8000/admin.php
   http://localhost:8000/teacher.php
   http://localhost:8000/class.php
   ```

### Cách 2: Sử dụng XAMPP Control Panel (Apache)
1. Mở ứng dụng **XAMPP Control Panel**.
2. Bấm **Start** tại module **Apache**.
3. Mở trình duyệt web truy cập:
   ```text
   http://localhost/BTL.php/
   ```

---

## 3. Tài khoản đăng nhập sẵn (Hoặc dùng nút 1-click)

| Vai trò | Tài khoản | Mật khẩu | Mô tả chức năng |
| :--- | :--- | :--- | :--- |
| **Người xếp lịch (Admin)** | `admin` | `123` | Quản lý dữ liệu, tạo/so sánh phương án, khóa/mở khóa buổi, đổi lịch và xem buổi chưa xếp |
| **Giảng viên** | `gv01` | `123` | Xem thời khóa biểu cá nhân của TS. Nguyễn Văn A & các ca bận công tác |
| **Đại diện Lớp** | `lop01` | `123` | Xem thời khóa biểu của Lớp KTPM01, phòng học, giảng viên |

---

## 4. Chạy kiểm thử xếp lịch và buổi nửa ngày

Khởi động MySQL và nhập `database.sql` trước khi chạy kiểm thử. Ứng dụng tự bổ sung cấu trúc mới cho cơ sở dữ liệu cũ khi mở trang.

Trong VS Code, vào `Terminal` -> `New Terminal`, gõ lệnh:
```powershell
C:\xampp\php\php.exe test_btl.php
```
Kết quả:
- **✔ Phép thử 1:** Không trùng GV, lớp hoặc phòng trong cùng khung giờ.
- **✔ Phép thử 2:** Loại bỏ phòng nhỏ hơn sĩ số, sai loại hoặc GV bận.
- **✔ Phép thử 3:** Xếp lại bảo toàn 100% các buổi đã khóa (B01, B02).
- **✔ Phép thử 4:** Bế tắc trả danh sách chưa xếp có buổi B20 kèm lý do cụ thể.
- **✔ Phép thử 5:** So sánh 2 phương án trên cùng bộ dữ liệu.
- **✔ Phép thử 6:** Mỗi ngày có 2 buổi nửa ngày: sáng (07:00–12:00) và chiều (13:00–18:00), tổng 10 buổi/tuần.
- **✔ Phép thử 7:** Lớp CNTT01 có sẵn với ngành và sĩ số đúng.
- **✔ Phép thử 8:** Danh mục môn có ngành và khớp với chuyên ngành từng giảng viên.
- **✔ Phép thử 9:** Chạy migration lịch nửa ngày lặp lại an toàn.
- **✔ Phép thử 10:** Ưu tiên mềm không lấn át lịch bận bắt buộc ở cả hai thuật toán.
- **✔ Phép thử 11:** Phát hiện dữ liệu ràng buộc thay đổi để không áp dụng phương án cũ.
- **✔ Phép thử 12:** Cột lưu ngày/buổi mong muốn trong yêu cầu đăng ký được tạo đầy đủ.
- **✔ Phép thử 13:** Yêu cầu lịch bận có đủ dữ liệu và trạng thái duyệt.
- **✔ Phép thử 14:** Duyệt yêu cầu thêm lịch bận chính thức trong giao dịch an toàn.
