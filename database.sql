CREATE DATABASE IF NOT EXISTS btl_timetable
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE btl_timetable;

CREATE TABLE IF NOT EXISTS teachers (
    id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    subject VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS classes (
    id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    student_count INT UNSIGNED NOT NULL,
    major VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rooms (
    id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    capacity INT UNSIGNED NOT NULL,
    type ENUM('LT', 'LAB') NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS time_slots (
    id VARCHAR(10) PRIMARY KEY,
    day_name VARCHAR(20) NOT NULL,
    day_order TINYINT UNSIGNED NOT NULL,
    period_index TINYINT UNSIGNED NOT NULL,
    period_name VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_day_period (day_order, period_index)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lessons (
    id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    teacher_id VARCHAR(10) NOT NULL,
    class_id VARCHAR(10) NOT NULL,
    required_type ENUM('LT', 'LAB') NOT NULL,
    student_count INT UNSIGNED NOT NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    locked_slot_id VARCHAR(10) NULL,
    locked_room_id VARCHAR(10) NULL,
    preferred_slot_id VARCHAR(10) NULL,
    CONSTRAINT fk_lessons_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    CONSTRAINT fk_lessons_class FOREIGN KEY (class_id) REFERENCES classes(id),
    CONSTRAINT fk_lessons_locked_slot FOREIGN KEY (locked_slot_id) REFERENCES time_slots(id),
    CONSTRAINT fk_lessons_locked_room FOREIGN KEY (locked_room_id) REFERENCES rooms(id),
    CONSTRAINT fk_lessons_preferred_slot FOREIGN KEY (preferred_slot_id) REFERENCES time_slots(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subjects (
    name VARCHAR(200) PRIMARY KEY,
    required_type ENUM('LT', 'LAB') NOT NULL,
    department VARCHAR(150) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_unavailabilities (
    teacher_id VARCHAR(10) NOT NULL,
    slot_id VARCHAR(10) NOT NULL,
    reason VARCHAR(200) NOT NULL,
    PRIMARY KEY (teacher_id, slot_id),
    CONSTRAINT fk_unavailabilities_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    CONSTRAINT fk_unavailabilities_slot FOREIGN KEY (slot_id) REFERENCES time_slots(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS schedule_assignments (
    lesson_id VARCHAR(10) PRIMARY KEY,
    slot_id VARCHAR(10) NOT NULL,
    room_id VARCHAR(10) NOT NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_schedule_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    CONSTRAINT fk_schedule_slot FOREIGN KEY (slot_id) REFERENCES time_slots(id),
    CONSTRAINT fk_schedule_room FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS schedule_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    method VARCHAR(30) NOT NULL,
    assignments_json LONGTEXT NOT NULL,
    unassigned_json LONGTEXT NOT NULL,
    assigned_count INT UNSIGNED NOT NULL,
    violations_count INT UNSIGNED NOT NULL,
    preference_violations INT UNSIGNED NOT NULL DEFAULT 0,
    data_hash CHAR(64) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_schedule_plans_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    username VARCHAR(50) PRIMARY KEY,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'class') NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    ref_id VARCHAR(10) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lesson_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(10) NOT NULL,
    lesson_name VARCHAR(200) NOT NULL,
    class_id VARCHAR(10) NOT NULL,
    required_type ENUM('LT', 'LAB') NOT NULL,
    preferred_slot_id VARCHAR(10) NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_lesson_requests_status_created (status, created_at),
    KEY idx_lesson_requests_teacher_created (teacher_id, created_at),
    CONSTRAINT fk_lesson_requests_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    CONSTRAINT fk_lesson_requests_class FOREIGN KEY (class_id) REFERENCES classes(id),
    CONSTRAINT fk_lesson_requests_preferred_slot FOREIGN KEY (preferred_slot_id)
        REFERENCES time_slots(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_unavailability_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(10) NOT NULL,
    slot_id VARCHAR(10) NOT NULL,
    reason VARCHAR(200) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_unavailability_requests_status_created (status, created_at),
    KEY idx_unavailability_requests_teacher_created (teacher_id, created_at),
    CONSTRAINT fk_unavailability_requests_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id),
    CONSTRAINT fk_unavailability_requests_slot FOREIGN KEY (slot_id) REFERENCES time_slots(id)
) ENGINE=InnoDB;

INSERT IGNORE INTO teachers (id, name, email, subject) VALUES
('GV01', 'TS. Nguyễn Văn A', 'vana@ptit.edu.vn', 'Công nghệ phần mềm'),
('GV02', 'ThS. Trần Thị B', 'thib@ptit.edu.vn', 'Khoa học máy tính'),
('GV03', 'TS. Lê Hoàng C', 'hoangc@ptit.edu.vn', 'Hệ thống thông tin'),
('GV04', 'ThS. Phạm Thu D', 'thud@ptit.edu.vn', 'An toàn thông tin'),
('GV05', 'ThS. Đỗ Minh E', 'minhe@ptit.edu.vn', 'Mạng máy tính'),
('GV06', 'TS. Vũ Quốc F', 'quocf@ptit.edu.vn', 'Trí tuệ nhân tạo');

INSERT IGNORE INTO classes (id, name, student_count, major) VALUES
('L01', 'KTPM01 (Kỹ nghệ phần mềm)', 45, 'Kỹ thuật phần mềm'),
('L02', 'KHMT01 (Khoa học máy tính)', 35, 'Khoa học máy tính'),
('L03', 'HTTT01 (Hệ thống thông tin)', 55, 'Hệ thống thông tin'),
('L04', 'ATTT01 (An toàn thông tin)', 30, 'An toàn thông tin'),
('L05', 'CNTT01 (Công nghệ thông tin)', 35, 'Công nghệ thông tin');

INSERT IGNORE INTO rooms (id, name, capacity, type) VALUES
('P101', 'P.101 (Lý thuyết)', 60, 'LT'),
('P102', 'P.102 (Lý thuyết)', 40, 'LT'),
('P201', 'P.201 (Lab Máy Tính)', 40, 'LAB'),
('P202', 'P.202 (Lab Máy Tính)', 35, 'LAB'),
('P301', 'P.301 (Hội trường lớn)', 80, 'LT');

INSERT IGNORE INTO time_slots (id, day_name, day_order, period_index, period_name) VALUES
('SL01', 'Thứ 2', 1, 1, 'Buổi sáng (07:00-12:00)'),
('SL03', 'Thứ 2', 1, 2, 'Buổi chiều (13:00-18:00)'),
('SL05', 'Thứ 3', 2, 1, 'Buổi sáng (07:00-12:00)'),
('SL07', 'Thứ 3', 2, 2, 'Buổi chiều (13:00-18:00)'),
('SL09', 'Thứ 4', 3, 1, 'Buổi sáng (07:00-12:00)'),
('SL11', 'Thứ 4', 3, 2, 'Buổi chiều (13:00-18:00)'),
('SL13', 'Thứ 5', 4, 1, 'Buổi sáng (07:00-12:00)'),
('SL15', 'Thứ 5', 4, 2, 'Buổi chiều (13:00-18:00)'),
('SL17', 'Thứ 6', 5, 1, 'Buổi sáng (07:00-12:00)'),
('SL19', 'Thứ 6', 5, 2, 'Buổi chiều (13:00-18:00)');

INSERT IGNORE INTO lessons
(id, name, teacher_id, class_id, required_type, student_count, is_locked, locked_slot_id, locked_room_id)
VALUES
('B01', 'Kiến trúc phần mềm', 'GV01', 'L01', 'LT', 45, 1, 'SL03', 'P101'),
('B02', 'Thực hành Web nâng cao', 'GV01', 'L02', 'LAB', 35, 1, 'SL07', 'P201'),
('B03', 'Cấu trúc dữ liệu & Giải thuật', 'GV02', 'L01', 'LT', 45, 0, NULL, NULL),
('B04', 'Thực hành Cấu trúc dữ liệu', 'GV02', 'L02', 'LAB', 35, 0, NULL, NULL),
('B05', 'Cơ sở dữ liệu', 'GV03', 'L03', 'LT', 55, 0, NULL, NULL),
('B06', 'Thực hành Hệ quản trị CSDL', 'GV03', 'L04', 'LAB', 30, 0, NULL, NULL),
('B07', 'Mạng máy tính', 'GV05', 'L01', 'LT', 45, 0, NULL, NULL),
('B08', 'Thực hành Mạng máy tính', 'GV05', 'L04', 'LAB', 30, 0, NULL, NULL),
('B09', 'An toàn mạng', 'GV04', 'L04', 'LT', 30, 0, NULL, NULL),
('B10', 'Mã hóa và An ninh dữ liệu', 'GV04', 'L02', 'LT', 35, 0, NULL, NULL),
('B11', 'Trí tuệ nhân tạo cơ bản', 'GV06', 'L02', 'LT', 35, 0, NULL, NULL),
('B12', 'Học máy ứng dụng', 'GV06', 'L01', 'LT', 45, 0, NULL, NULL),
('B13', 'Quản trị dự án CNTT', 'GV01', 'L03', 'LT', 55, 0, NULL, NULL),
('B14', 'Lập trình thiết bị di động', 'GV02', 'L04', 'LAB', 30, 0, NULL, NULL),
('B15', 'Phân tích thiết kế hệ thống', 'GV03', 'L01', 'LT', 45, 0, NULL, NULL),
('B16', 'Kiểm thử phần mềm', 'GV01', 'L04', 'LT', 30, 0, NULL, NULL),
('B17', 'Khai phá dữ liệu', 'GV06', 'L03', 'LT', 55, 0, NULL, NULL),
('B18', 'Điện toán đám mây', 'GV05', 'L02', 'LT', 35, 0, NULL, NULL),
('B19', 'Hội thảo Chuyên đề Công nghệ', 'GV02', 'L03', 'LT', 55, 0, NULL, NULL),
('B20', 'Chuyên đề Lab An ninh mạng nâng cao', 'GV04', 'L03', 'LAB', 55, 0, NULL, NULL);

INSERT IGNORE INTO teacher_unavailabilities (teacher_id, slot_id, reason) VALUES
('GV01', 'SL01', 'Bận họp hội đồng khoa học; bận giảng dạy cao học'),
('GV02', 'SL05', 'Nghỉ sinh hoạt chuyên môn'),
('GV03', 'SL09', 'Công tác đối ngoại'),
('GV04', 'SL13', 'Bận hướng dẫn luận văn'),
('GV05', 'SL17', 'Họp chi bộ'),
('GV06', 'SL03', 'Nghiên cứu tại phòng Lab');

INSERT IGNORE INTO users (username, password_hash, role, display_name, ref_id) VALUES
('admin', '$2y$10$e8b7/j/FsNL7G5CI0xzdDeFKDF50SouzKhSLLK3Vc/c3WTqeODYDa', 'admin', 'Nguyễn Quản Trị (Người Xếp Lịch)', 'ADMIN'),
('gv01', '$2y$10$e8b7/j/FsNL7G5CI0xzdDeFKDF50SouzKhSLLK3Vc/c3WTqeODYDa', 'teacher', 'TS. Nguyễn Văn A', 'GV01'),
('gv02', '$2y$10$e8b7/j/FsNL7G5CI0xzdDeFKDF50SouzKhSLLK3Vc/c3WTqeODYDa', 'teacher', 'ThS. Trần Thị B', 'GV02'),
('lop01', '$2y$10$e8b7/j/FsNL7G5CI0xzdDeFKDF50SouzKhSLLK3Vc/c3WTqeODYDa', 'class', 'Lớp KTPM01', 'L01'),
('lop02', '$2y$10$e8b7/j/FsNL7G5CI0xzdDeFKDF50SouzKhSLLK3Vc/c3WTqeODYDa', 'class', 'Lớp KHMT01', 'L02');
