<?php
/**
 * ĐỀ TÀI 18: XÂY DỰNG HỆ THỐNG XẾP THỜI KHÓA BIỂU THEO RÀNG BUỘC
 * Giảng viên, Phòng học và Lớp học.
 * Ngôn ngữ: PHP (Phiên bản 8.x)
 * Bộ điều phối ứng dụng; thuật toán nằm trong app/scheduler.php.
 */

session_start();

// =========================================================================
// 1. DỮ LIỆU BAN ĐẦU VÀ ĐỒNG BỘ CƠ SỞ DỮ LIỆU
// =========================================================================

require_once dirname(__DIR__) . '/database.php';
require_once __DIR__ . '/scheduler.php';

$pdo = createDatabaseConnection();
ensureLessonRequestsTable($pdo);
$timetableLayoutChanged = ensureHalfDaySlots($pdo);
ensureTeacherUnavailabilityRequestsTable($pdo);
ensureSchedulePlanningSchema($pdo);
ensureTechnologyClass($pdo);
ensureSubjectCatalog($pdo);
$data = loadInitialDataFromDatabase($pdo);

// =========================================================================
// 2. KHỞI TẠO LỊCH ĐANG SỬ DỤNG
// =========================================================================

$scheduleVersion = scheduleBasic($data);
$basicUnassigned = $scheduleVersion['unassigned_lessons'];
$savedAssignments = loadSavedScheduleAssignments($pdo);
$schedulePlans = loadSchedulePlans($pdo);
$activePlan = null;
foreach ($schedulePlans as $plan) {
    if ($plan['is_active']) {
        $activePlan = $plan;
        break;
    }
}
if ($activePlan && $activePlan['data_hash'] !== scheduleDataFingerprint($data)) {
    $pdo->exec('UPDATE schedule_plans SET is_active = 0 WHERE is_active = 1');
    $activePlan = null;
    $savedAssignments = [];
}
if ($activePlan) {
    $scheduleVersion = $activePlan;
}
if ($savedAssignments && !$timetableLayoutChanged) {
    $scheduleVersion['assignments'] = array_map(
        static function ($assignment) {
            return [
                'lesson_id' => $assignment['lesson_id'],
                'slot_id' => $assignment['slot_id'],
                'room_id' => $assignment['room_id'],
                'is_locked' => (bool) $assignment['is_locked'],
            ];
        },
        $savedAssignments
    );
    $assignedIds = array_column($scheduleVersion['assignments'], 'lesson_id');
    $scheduleVersion['unassigned_lessons'] = array_values(array_filter(
        $scheduleVersion['unassigned_lessons'],
        static fn($lesson) => isset($data['lessons'][$lesson['lesson_id']])
            && !in_array($lesson['lesson_id'], $assignedIds, true)
    ));
    $reportedIds = array_merge($assignedIds, array_column($scheduleVersion['unassigned_lessons'], 'lesson_id'));
    foreach ($data['lessons'] as $lessonId => $lesson) {
        if (!in_array($lessonId, $reportedIds, true)) {
            $missing = null;
            foreach ($basicUnassigned as $item) {
                if ($item['lesson_id'] === $lessonId) {
                    $missing = $item;
                    break;
                }
            }
            $scheduleVersion['unassigned_lessons'][] = $missing ?? [
                'lesson_id' => $lessonId,
                'lesson_name' => $lesson['name'],
                'class_id' => $lesson['class_id'],
                'teacher_id' => $lesson['teacher_id'],
                'reason' => 'Phương án đã lưu không chứa buổi học này; hãy tạo phương án mới.',
            ];
        }
    }
    $scheduleVersion['assigned_count'] = count($scheduleVersion['assignments']);
    $scheduleVersion['violations_count'] = count($scheduleVersion['unassigned_lessons']);
    $scheduleVersion['preference_violations'] = schedulePreferenceViolations($scheduleVersion['assignments'], $data);
} else {
    saveScheduleAssignments($pdo, $scheduleVersion['assignments']);
}
if (!$activePlan && !defined('CLI_TEST')) {
    $scheduleVersion['name'] = 'Phương án khởi tạo cơ bản';
    saveSchedulePlan($pdo, $scheduleVersion);
    $schedulePlans = loadSchedulePlans($pdo);
}
$_SESSION['versions']['v1'] = $scheduleVersion;
$currentVersion = &$_SESSION['versions']['v1'];
$rebuildSchedule = static function () use ($pdo, &$data, &$currentVersion): void {
    $data = loadInitialDataFromDatabase($pdo);
    $newVersion = scheduleBasic($data);
    $newVersion['name'] .= ' · Cập nhật ' . date('d/m/Y H:i');
    saveSchedulePlan($pdo, $newVersion);
    $currentVersion = $newVersion;
};

// =========================================================================
// 3. XỬ LÝ ACTIONS (LOGIN, LOGOUT, GENERATE, TOGGLE LOCK, MANUAL ADJUST)
// =========================================================================

$message = '';
$messageType = '';
$notices = [
    'lesson_saved' => 'Buổi học đã được lưu và hệ thống đã xếp lại lịch.',
    'lesson_deleted' => 'Buổi học đã được xóa và lịch đã được cập nhật.',
    'class_saved' => 'Thông tin lớp học đã được lưu và thời khóa biểu đã cập nhật.',
    'class_deleted' => 'Lớp học đã được xóa.',
    'lesson_request_submitted' => 'Yêu cầu đăng ký môn đã được gửi. Môn học sẽ được xếp lịch sau khi Admin duyệt.',
    'lesson_request_approved' => 'Đã duyệt yêu cầu và tạo buổi học, lịch đã được xếp lại.',
    'lesson_request_rejected' => 'Đã từ chối yêu cầu đăng ký môn học.',
    'unavailability_request_submitted' => 'Yêu cầu đăng ký lịch bận đã được gửi. Lịch bận chỉ có hiệu lực sau khi Admin duyệt.',
    'unavailability_request_approved' => 'Đã duyệt yêu cầu lịch bận và cập nhật thời khóa biểu.',
    'unavailability_request_rejected' => 'Đã từ chối yêu cầu đăng ký lịch bận.',
];
if (isset($_GET['notice'], $notices[$_GET['notice']])) {
    $message = $notices[$_GET['notice']];
    $messageType = 'success';
}

// API / POST Actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $adminActions = [
        'generate',
        'activate_plan',
        'toggle_lock',
        'manual_adjust',
        'teacher_save',
        'teacher_delete',
        'room_save',
        'room_delete',
        'slot_save',
        'slot_delete',
        'unavailability_save',
        'unavailability_delete',
        'lesson_save',
        'lesson_delete',
        'lesson_request_review',
        'unavailability_request_review',
        'class_save',
        'class_delete',
    ];

    if (in_array($action, $adminActions, true) && ($_SESSION['user']['role'] ?? '') !== 'admin') {
        $message = 'Bạn cần đăng nhập tài khoản quản trị để thực hiện thao tác này.';
        $messageType = 'danger';
    } elseif ($action === 'login') {
        $u = trim($_POST['username'] ?? '');
        $p = trim($_POST['password'] ?? '');
        $loginQuery = $pdo->prepare(
            'SELECT username, password_hash, role, display_name, ref_id
             FROM users WHERE username = :username LIMIT 1'
        );
        $loginQuery->execute(['username' => $u]);
        $user = $loginQuery->fetch();
        if ($user && password_verify($p, $user['password_hash'])) {
            session_regenerate_id(true);
            $found = [
                'username' => $user['username'],
                'role' => $user['role'],
                'name' => $user['display_name'],
                'ref_id' => $user['ref_id'],
            ];
            $_SESSION['user'] = $found;
            if ($found['role'] === 'teacher') {
                $destination = 'teacher.php?teacher_id=' . rawurlencode($found['ref_id']);
            } elseif ($found['role'] === 'class') {
                $destination = 'class.php?class_id=' . rawurlencode($found['ref_id']);
            } else {
                $destination = 'admin.php';
            }
            header('Location: ' . $destination);
            exit;
        } else {
            $message = 'Tên đăng nhập hoặc mật khẩu không đúng!';
            $messageType = 'danger';
        }
    } elseif ($action === 'register') {
        $u = trim($_POST['username'] ?? '');
        $p = trim($_POST['password'] ?? '');
        $passwordConfirmation = trim($_POST['password_confirmation'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $ref_id = trim($role === 'teacher'
            ? ($_POST['teacher_ref_id'] ?? '')
            : ($_POST['class_ref_id'] ?? ''));

        if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $u)) {
            $message = 'Tên đăng nhập cần từ 3 đến 50 ký tự, chỉ gồm chữ, số, dấu chấm, gạch ngang hoặc gạch dưới.';
            $messageType = 'danger';
        } elseif (strlen($p) < 6) {
            $message = 'Mật khẩu cần có ít nhất 6 ký tự.';
            $messageType = 'danger';
        } elseif ($p !== $passwordConfirmation) {
            $message = 'Mật khẩu xác nhận không khớp.';
            $messageType = 'danger';
        } elseif (!in_array($role, ['teacher', 'class'], true)) {
            $message = 'Chỉ cho phép đăng ký tài khoản giảng viên hoặc lớp học.';
            $messageType = 'danger';
        } else {
            $profile = $role === 'teacher'
                ? ($data['teachers'][$ref_id] ?? null)
                : ($data['classes'][$ref_id] ?? null);

            if (!$profile) {
                $message = 'Vui lòng chọn giảng viên hoặc lớp học có trong danh sách.';
                $messageType = 'danger';
            } else {
                $name = $profile['name'];
                $existingUser = $pdo->prepare('SELECT 1 FROM users WHERE username = :username');
                $existingUser->execute(['username' => $u]);
                if ($existingUser->fetchColumn()) {
                    $message = 'Tên đăng nhập đã tồn tại!';
                    $messageType = 'danger';
                } else {
                    $existingProfileUser = $pdo->prepare(
                        'SELECT 1 FROM users WHERE role = :role AND ref_id = :ref_id'
                    );
                    $existingProfileUser->execute([
                        'role' => $role,
                        'ref_id' => $ref_id,
                    ]);
                    if ($existingProfileUser->fetchColumn()) {
                        $message = 'Hồ sơ này đã có tài khoản đăng nhập.';
                        $messageType = 'danger';
                    } else {
                        $passwordHash = password_hash($p, PASSWORD_DEFAULT);
                        try {
                            $statement = $pdo->prepare(
                                'INSERT INTO users (username, password_hash, role, display_name, ref_id)
                                 VALUES (:username, :password_hash, :role, :display_name, :ref_id)'
                            );
                            $statement->execute([
                                'username' => $u,
                                'password_hash' => $passwordHash,
                                'role' => $role,
                                'display_name' => $name,
                                'ref_id' => $ref_id,
                            ]);
                        } catch (PDOException $exception) {
                            if ($exception->getCode() !== '23000') {
                                throw $exception;
                            }
                            $message = 'Tên đăng nhập đã được đăng ký, vui lòng chọn tên khác.';
                            $messageType = 'danger';
                        }

                        if ($messageType !== 'danger') {
                            $data['users'][] = [
                                'username' => $u,
                                'password_hash' => $passwordHash,
                                'role' => $role,
                                'name' => $name,
                                'ref_id' => $ref_id
                            ];
                            $message = 'Đăng ký thành công! Bạn có thể đăng nhập bằng tài khoản vừa tạo.';
                            $messageType = 'success';
                        }
                    }
                }
            }
        }
    } elseif ($action === 'teacher_lesson_request') {
        $teacherId = $_SESSION['user']['ref_id'] ?? '';
        $lessonName = trim($_POST['subject_name'] ?? '');
        $classId = trim($_POST['class_id'] ?? '');
        $preferredSlot = trim($_POST['preferred_slot_id'] ?? '');
        $subject = $data['subjects'][$lessonName] ?? null;

        if (($_SESSION['user']['role'] ?? '') !== 'teacher' || !isset($data['teachers'][$teacherId])) {
            $message = 'Đăng nhập bằng tài khoản giảng viên để gửi yêu cầu mở lớp.';
            $messageType = 'danger';
        } elseif (!$subject || $subject['department'] !== ($data['teachers'][$teacherId]['subject'] ?? '')) {
            $message = 'Môn học đã chọn không thuộc chuyên ngành của giảng viên.';
            $messageType = 'danger';
        } elseif (!isset($data['classes'][$classId])) {
            $message = 'Vui lòng chọn lớp học hợp lệ.';
            $messageType = 'danger';
        } elseif (!isset($data['slots'][$preferredSlot])) {
            $message = 'Vui lòng chọn ngày và buổi học hợp lệ.';
            $messageType = 'danger';
        } else {
            $requiredType = $subject['required_type'];
            $existingLesson = $pdo->prepare(
                'SELECT 1 FROM lessons
                 WHERE teacher_id = :teacher_id AND class_id = :class_id
                   AND name = :lesson_name LIMIT 1'
            );
            $existingLesson->execute([
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'lesson_name' => $lessonName,
            ]);
            $duplicateRequest = $pdo->prepare(
                "SELECT 1 FROM lesson_requests
                 WHERE teacher_id = :teacher_id AND class_id = :class_id
                   AND lesson_name = :lesson_name AND status = 'pending' LIMIT 1"
            );
            $duplicateRequest->execute([
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'lesson_name' => $lessonName,
            ]);
            if ($existingLesson->fetchColumn()) {
                $message = 'Môn học này đã được tạo cho lớp đã chọn.';
                $messageType = 'danger';
            } elseif ($duplicateRequest->fetchColumn()) {
                $message = 'Yêu cầu môn học này đang chờ Admin duyệt.';
                $messageType = 'danger';
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO lesson_requests
                     (teacher_id, lesson_name, class_id, required_type, preferred_slot_id)
                     VALUES (:teacher_id, :lesson_name, :class_id, :required_type, :preferred_slot_id)'
                );
                $statement->execute([
                    'teacher_id' => $teacherId,
                    'lesson_name' => $lessonName,
                    'class_id' => $classId,
                    'required_type' => $requiredType,
                    'preferred_slot_id' => $preferredSlot,
                ]);
                header('Location: teacher.php?notice=lesson_request_submitted');
                exit;
            }
        }
    } elseif ($action === 'teacher_unavailability_request') {
        $teacherId = $_SESSION['user']['ref_id'] ?? '';
        $slotId = trim($_POST['slot_id'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if (($_SESSION['user']['role'] ?? '') !== 'teacher' || !isset($data['teachers'][$teacherId])) {
            $message = 'Đăng nhập bằng tài khoản giảng viên để đăng ký lịch bận.';
            $messageType = 'danger';
        } elseif (!isset($data['slots'][$slotId])) {
            $message = 'Vui lòng chọn ngày và buổi hợp lệ.';
            $messageType = 'danger';
        } elseif ($reason === '' || strlen($reason) > 200) {
            $message = 'Lý do bận không được để trống và tối đa 200 ký tự.';
            $messageType = 'danger';
        } else {
            $existingBusy = $pdo->prepare(
                'SELECT 1 FROM teacher_unavailabilities
                 WHERE teacher_id = :teacher_id AND slot_id = :slot_id'
            );
            $existingBusy->execute(['teacher_id' => $teacherId, 'slot_id' => $slotId]);
            $pendingBusyRequest = $pdo->prepare(
                "SELECT 1 FROM teacher_unavailability_requests
                 WHERE teacher_id = :teacher_id AND slot_id = :slot_id
                   AND status = 'pending' LIMIT 1"
            );
            $pendingBusyRequest->execute(['teacher_id' => $teacherId, 'slot_id' => $slotId]);

            if ($existingBusy->fetchColumn()) {
                $message = 'Khung giờ này đã được duyệt là lịch bận của bạn.';
                $messageType = 'danger';
            } elseif ($pendingBusyRequest->fetchColumn()) {
                $message = 'Yêu cầu lịch bận ở khung giờ này đang chờ Admin duyệt.';
                $messageType = 'danger';
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO teacher_unavailability_requests (teacher_id, slot_id, reason)
                     VALUES (:teacher_id, :slot_id, :reason)'
                );
                $statement->execute([
                    'teacher_id' => $teacherId,
                    'slot_id' => $slotId,
                    'reason' => $reason,
                ]);
                header('Location: teacher.php?notice=unavailability_request_submitted');
                exit;
            }
        }
    } elseif ($action === 'lesson_request_review') {
        $requestId = filter_var($_POST['request_id'] ?? '', FILTER_VALIDATE_INT);
        $decision = $_POST['decision'] ?? '';
        $adminNote = trim($_POST['admin_note'] ?? '');
        $requestQuery = $pdo->prepare(
            "SELECT id, teacher_id, lesson_name, class_id, required_type, preferred_slot_id
             FROM lesson_requests WHERE id = :id AND status = 'pending' LIMIT 1"
        );
        $requestQuery->execute(['id' => $requestId]);
        $request = $requestQuery->fetch();

        if (!$request || !in_array($decision, ['approve', 'reject'], true)) {
            $message = 'Yêu cầu không tồn tại, đã được xử lý hoặc thao tác không hợp lệ.';
            $messageType = 'danger';
        } elseif (strlen($adminNote) > 500) {
            $message = 'Ghi chú duyệt tối đa 500 ký tự.';
            $messageType = 'danger';
        } else {
            $pdo->beginTransaction();
            try {
                if ($decision === 'approve') {
                    $lessonId = nextPrefixedId($data['lessons'], 'B');
                    $lesson = [
                        'id' => $lessonId,
                        'name' => $request['lesson_name'],
                        'teacher_id' => $request['teacher_id'],
                        'class_id' => $request['class_id'],
                        'required_type' => $request['required_type'],
                        'size' => $data['classes'][$request['class_id']]['size'],
                        'is_locked' => false,
                        'locked_slot' => null,
                        'locked_room' => null,
                        'preferred_slot' => $request['preferred_slot_id'],
                    ];
                    $insertLesson = $pdo->prepare(
                        'INSERT INTO lessons
                         (id, name, teacher_id, class_id, required_type, student_count,
                          is_locked, locked_slot_id, locked_room_id, preferred_slot_id)
                         VALUES (:id, :name, :teacher_id, :class_id, :required_type,
                                 :student_count, 0, NULL, NULL, :preferred_slot_id)'
                    );
                    $insertLesson->execute([
                        'id' => $lessonId,
                        'name' => $lesson['name'],
                        'teacher_id' => $lesson['teacher_id'],
                        'class_id' => $lesson['class_id'],
                        'required_type' => $lesson['required_type'],
                        'student_count' => $lesson['size'],
                        'preferred_slot_id' => $lesson['preferred_slot'],
                    ]);
                    $data['lessons'][$lessonId] = $lesson;
                    $newVersion = scheduleBasic($data);
                    saveSchedulePlan($pdo, $newVersion);
                }

                $updateRequest = $pdo->prepare(
                    'UPDATE lesson_requests
                     SET status = :status, admin_note = :admin_note, reviewed_at = CURRENT_TIMESTAMP
                     WHERE id = :id AND status = \'pending\''
                );
                $updateRequest->execute([
                    'status' => $decision === 'approve' ? 'approved' : 'rejected',
                    'admin_note' => $adminNote === '' ? null : $adminNote,
                    'id' => $request['id'],
                ]);
                if ($updateRequest->rowCount() !== 1) {
                    throw new RuntimeException('Yêu cầu đã được xử lý bởi một phiên khác.');
                }
                $pdo->commit();

                if ($decision === 'approve') {
                    $_SESSION['versions']['v1'] = $newVersion;
                    header('Location: admin.php?notice=lesson_request_approved');
                } else {
                    header('Location: admin.php?notice=lesson_request_rejected');
                }
                exit;
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
        }
    } elseif ($action === 'unavailability_request_review') {
        $requestId = filter_var($_POST['request_id'] ?? '', FILTER_VALIDATE_INT);
        $decision = $_POST['decision'] ?? '';
        $adminNote = trim($_POST['admin_note'] ?? '');
        $requestQuery = $pdo->prepare(
            "SELECT id, teacher_id, slot_id, reason
             FROM teacher_unavailability_requests
             WHERE id = :id AND status = 'pending' LIMIT 1"
        );
        $requestQuery->execute(['id' => $requestId]);
        $request = $requestQuery->fetch();

        if (!$request || !in_array($decision, ['approve', 'reject'], true)) {
            $message = 'Yêu cầu lịch bận không tồn tại, đã được xử lý hoặc thao tác không hợp lệ.';
            $messageType = 'danger';
        } elseif (strlen($adminNote) > 500) {
            $message = 'Ghi chú duyệt tối đa 500 ký tự.';
            $messageType = 'danger';
        } else {
            $pdo->beginTransaction();
            try {
                if ($decision === 'approve') {
                    $saveBusy = $pdo->prepare(
                        'INSERT INTO teacher_unavailabilities (teacher_id, slot_id, reason)
                         VALUES (:teacher_id, :slot_id, :reason)
                         ON DUPLICATE KEY UPDATE reason = VALUES(reason)'
                    );
                    $saveBusy->execute([
                        'teacher_id' => $request['teacher_id'],
                        'slot_id' => $request['slot_id'],
                        'reason' => $request['reason'],
                    ]);
                }

                $updateRequest = $pdo->prepare(
                    'UPDATE teacher_unavailability_requests
                     SET status = :status, admin_note = :admin_note, reviewed_at = CURRENT_TIMESTAMP
                     WHERE id = :id AND status = \'pending\''
                );
                $updateRequest->execute([
                    'status' => $decision === 'approve' ? 'approved' : 'rejected',
                    'admin_note' => $adminNote === '' ? null : $adminNote,
                    'id' => $request['id'],
                ]);
                if ($updateRequest->rowCount() !== 1) {
                    throw new RuntimeException('Yêu cầu lịch bận đã được xử lý bởi một phiên khác.');
                }
                $pdo->commit();

                if ($decision === 'approve') {
                    $rebuildSchedule();
                    header('Location: admin.php?notice=unavailability_request_approved');
                } else {
                    header('Location: admin.php?notice=unavailability_request_rejected');
                }
                exit;
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
        }
    } elseif ($action === 'teacher_save') {
        $teacherId = strtoupper(trim($_POST['teacher_id'] ?? ''));
        $teacherName = trim($_POST['teacher_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $isEdit = $teacherId !== '';
        if ($teacherName === '' || strlen($teacherName) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150 || $subject === '' || strlen($subject) > 150) {
            $message = 'Vui lòng nhập tên, email hợp lệ và chuyên ngành (tối đa 150 ký tự).';
            $messageType = 'danger';
        } elseif ($isEdit && !isset($data['teachers'][$teacherId])) {
            $message = 'Không tìm thấy giảng viên cần cập nhật.';
            $messageType = 'danger';
        } else {
            if (!$isEdit) {
                $highest = 0;
                foreach (array_keys($data['teachers']) as $existingId) {
                    if (preg_match('/^GV(\d+)$/', $existingId, $matches)) {
                        $highest = max($highest, (int) $matches[1]);
                    }
                }
                $teacherId = sprintf('GV%02d', $highest + 1);
            }
            $statement = $pdo->prepare($isEdit
                ? 'UPDATE teachers SET name = :name, email = :email, subject = :subject WHERE id = :id'
                : 'INSERT INTO teachers (id, name, email, subject) VALUES (:id, :name, :email, :subject)');
            try {
                $statement->execute(['id' => $teacherId, 'name' => $teacherName, 'email' => $email, 'subject' => $subject]);
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $message = 'Email đã được sử dụng bởi giảng viên khác.';
                $messageType = 'danger';
            }
            if ($messageType !== 'danger') {
                $data['teachers'][$teacherId] = ['id' => $teacherId, 'name' => $teacherName, 'email' => $email, 'subject' => $subject];
                $subjectCatalog = $pdo->prepare(
                    "INSERT IGNORE INTO subjects (name, required_type, department)
                     VALUES (:name, 'LT', :department)"
                );
                $subjectCatalog->execute([
                    'name' => 'Môn chuyên ngành: ' . $subject,
                    'department' => $subject,
                ]);
                header('Location: admin.php?tab=admin');
                exit;
            }
        }
    } elseif ($action === 'teacher_delete') {
        $teacherId = strtoupper(trim($_POST['teacher_id'] ?? ''));
        if (!isset($data['teachers'][$teacherId])) {
            $message = 'Không tìm thấy giảng viên.';
            $messageType = 'danger';
        } else {
            $references = $pdo->prepare(
                'SELECT (SELECT COUNT(*) FROM lessons WHERE teacher_id = :lesson_id)
                      + (SELECT COUNT(*) FROM lesson_requests WHERE teacher_id = :request_id)
                      + (SELECT COUNT(*) FROM teacher_unavailabilities WHERE teacher_id = :unavailability_id)
                      + (SELECT COUNT(*) FROM teacher_unavailability_requests WHERE teacher_id = :busy_request_id)
                      + (SELECT COUNT(*) FROM users WHERE role = \'teacher\' AND ref_id = :user_id)'
            );
            $references->execute([
                'lesson_id' => $teacherId,
                'request_id' => $teacherId,
                'unavailability_id' => $teacherId,
                'busy_request_id' => $teacherId,
                'user_id' => $teacherId,
            ]);
            if ((int) $references->fetchColumn() > 0) {
                $message = 'Không thể xóa giảng viên đang có buổi học, yêu cầu, lịch bận hoặc tài khoản liên kết.';
                $messageType = 'danger';
            } else {
                $statement = $pdo->prepare('DELETE FROM teachers WHERE id = :id');
                $statement->execute(['id' => $teacherId]);
                header('Location: admin.php?tab=admin');
                exit;
            }
        }
    } elseif ($action === 'room_save') {
        $roomId = strtoupper(trim($_POST['room_id'] ?? ''));
        $roomName = trim($_POST['room_name'] ?? '');
        $capacity = filter_var($_POST['capacity'] ?? '', FILTER_VALIDATE_INT);
        $roomType = $_POST['room_type'] ?? '';
        $isEdit = $roomId !== '';
        if ($roomName === '' || strlen($roomName) > 150 || $capacity === false || $capacity < 1 || $capacity > 9999 || !in_array($roomType, ['LT', 'LAB'], true)) {
            $message = 'Tên phòng, sức chứa (1–9999) hoặc loại phòng không hợp lệ.';
            $messageType = 'danger';
        } elseif ($isEdit && !isset($data['rooms'][$roomId])) {
            $message = 'Không tìm thấy phòng cần cập nhật.';
            $messageType = 'danger';
        } else {
            if (!$isEdit) {
                $highest = 0;
                foreach (array_keys($data['rooms']) as $existingId) {
                    if (preg_match('/^P(\d+)$/', $existingId, $matches)) {
                        $highest = max($highest, (int) $matches[1]);
                    }
                }
                $roomId = sprintf('P%02d', $highest + 1);
            }
            $statement = $pdo->prepare($isEdit
                ? 'UPDATE rooms SET name = :name, capacity = :capacity, type = :type WHERE id = :id'
                : 'INSERT INTO rooms (id, name, capacity, type) VALUES (:id, :name, :capacity, :type)');
            $statement->execute(['id' => $roomId, 'name' => $roomName, 'capacity' => $capacity, 'type' => $roomType]);
            $rebuildSchedule();
            header('Location: admin.php?tab=admin');
            exit;
        }
    } elseif ($action === 'room_delete') {
        $roomId = strtoupper(trim($_POST['room_id'] ?? ''));
        if (!isset($data['rooms'][$roomId])) {
            $message = 'Không tìm thấy phòng.';
            $messageType = 'danger';
        } else {
            $references = $pdo->prepare(
                'SELECT (SELECT COUNT(*) FROM schedule_assignments WHERE room_id = :assignment_id)
                      + (SELECT COUNT(*) FROM lessons WHERE locked_room_id = :locked_id)'
            );
            $references->execute(['assignment_id' => $roomId, 'locked_id' => $roomId]);
            if ((int) $references->fetchColumn() > 0) {
                $message = 'Không thể xóa phòng đang được dùng trong lịch hoặc buổi khóa.';
                $messageType = 'danger';
            } else {
                $statement = $pdo->prepare('DELETE FROM rooms WHERE id = :id');
                $statement->execute(['id' => $roomId]);
                header('Location: admin.php?tab=admin');
                exit;
            }
        }
    } elseif ($action === 'slot_save') {
        $slotId = strtoupper(trim($_POST['slot_id'] ?? ''));
        $dayName = trim($_POST['day_name'] ?? '');
        $dayOrder = filter_var($_POST['day_order'] ?? '', FILTER_VALIDATE_INT);
        $periodIndex = filter_var($_POST['period_index'] ?? '', FILTER_VALIDATE_INT);
        $periodName = $periodIndex === 1 ? 'Buổi sáng (07:00-12:00)' : 'Buổi chiều (13:00-18:00)';
        $isEdit = $slotId !== '';
        $inconsistentDay = false;
        foreach ($data['slots'] as $existingSlotId => $existingSlot) {
            if ($existingSlotId === $slotId) {
                continue;
            }
            if (
                ($existingSlot['day_order'] === $dayOrder && $existingSlot['day'] !== $dayName)
                || ($existingSlot['day'] === $dayName && $existingSlot['day_order'] !== $dayOrder)
            ) {
                $inconsistentDay = true;
                break;
            }
        }
        if ($dayName === '' || strlen($dayName) > 20 || $dayOrder === false || $dayOrder < 1 || $dayOrder > 7 || !in_array($periodIndex, [1, 2], true)) {
            $message = 'Ngày phải có tên hợp lệ, thứ tự từ 1–7 và buổi là sáng hoặc chiều.';
            $messageType = 'danger';
        } elseif ($inconsistentDay) {
            $message = 'Tên ngày và thứ tự ngày phải thống nhất với các khung giờ cùng ngày.';
            $messageType = 'danger';
        } elseif ($isEdit && !isset($data['slots'][$slotId])) {
            $message = 'Không tìm thấy khung giờ cần cập nhật.';
            $messageType = 'danger';
        } else {
            if (!$isEdit) {
                $highest = 0;
                foreach (array_keys($data['slots']) as $existingId) {
                    if (preg_match('/^SL(\d+)$/', $existingId, $matches)) {
                        $highest = max($highest, (int) $matches[1]);
                    }
                }
                $slotId = sprintf('SL%02d', $highest + 1);
            }
            try {
                $statement = $pdo->prepare($isEdit
                    ? 'UPDATE time_slots SET day_name = :day_name, day_order = :day_order, period_index = :period_index, period_name = :period_name WHERE id = :id'
                    : 'INSERT INTO time_slots (id, day_name, day_order, period_index, period_name) VALUES (:id, :day_name, :day_order, :period_index, :period_name)');
                $statement->execute(['id' => $slotId, 'day_name' => $dayName, 'day_order' => $dayOrder, 'period_index' => $periodIndex, 'period_name' => $periodName]);
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $message = 'Ngày này đã có khung giờ sáng/chiều tương ứng.';
                $messageType = 'danger';
            }
            if ($messageType !== 'danger') {
                $rebuildSchedule();
                header('Location: admin.php?tab=admin');
                exit;
            }
        }
    } elseif ($action === 'slot_delete') {
        $slotId = strtoupper(trim($_POST['slot_id'] ?? ''));
        if (!isset($data['slots'][$slotId])) {
            $message = 'Không tìm thấy khung giờ.';
            $messageType = 'danger';
        } else {
            $references = $pdo->prepare(
                'SELECT (SELECT COUNT(*) FROM schedule_assignments WHERE slot_id = :assignment_id)
                      + (SELECT COUNT(*) FROM lessons WHERE locked_slot_id = :locked_id)
                      + (SELECT COUNT(*) FROM teacher_unavailabilities WHERE slot_id = :unavailability_id)
                      + (SELECT COUNT(*) FROM teacher_unavailability_requests WHERE slot_id = :request_slot_id)'
            );
            $references->execute([
                'assignment_id' => $slotId,
                'locked_id' => $slotId,
                'unavailability_id' => $slotId,
                'request_slot_id' => $slotId,
            ]);
            if ((int) $references->fetchColumn() > 0) {
                $message = 'Không thể xóa khung giờ đang được dùng trong lịch, buổi khóa, lịch bận hoặc yêu cầu đăng ký.';
                $messageType = 'danger';
            } else {
                $statement = $pdo->prepare('DELETE FROM time_slots WHERE id = :id');
                $statement->execute(['id' => $slotId]);
                $rebuildSchedule();
                header('Location: admin.php?tab=admin');
                exit;
            }
        }
    } elseif ($action === 'unavailability_save') {
        $teacherId = trim($_POST['teacher_id'] ?? '');
        $slotId = trim($_POST['slot_id'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $oldTeacherId = trim($_POST['old_teacher_id'] ?? '');
        $oldSlotId = trim($_POST['old_slot_id'] ?? '');
        if (!isset($data['teachers'][$teacherId], $data['slots'][$slotId]) || $reason === '' || strlen($reason) > 200) {
            $message = 'Chọn giảng viên, khung giờ hợp lệ và nhập lý do (tối đa 200 ký tự).';
            $messageType = 'danger';
        } else {
            if ($oldTeacherId !== '' && $oldSlotId !== '') {
                $deleteOld = $pdo->prepare('DELETE FROM teacher_unavailabilities WHERE teacher_id = :teacher_id AND slot_id = :slot_id');
                $deleteOld->execute(['teacher_id' => $oldTeacherId, 'slot_id' => $oldSlotId]);
            }
            try {
                $statement = $pdo->prepare(
                    'INSERT INTO teacher_unavailabilities (teacher_id, slot_id, reason)
                     VALUES (:teacher_id, :slot_id, :reason)
                     ON DUPLICATE KEY UPDATE reason = VALUES(reason)'
                );
                $statement->execute(['teacher_id' => $teacherId, 'slot_id' => $slotId, 'reason' => $reason]);
                $rebuildSchedule();
                header('Location: admin.php?tab=admin');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $message = 'Không thể lưu lịch bận do dữ liệu đang được tham chiếu.';
                $messageType = 'danger';
            }
        }
    } elseif ($action === 'unavailability_delete') {
        $teacherId = trim($_POST['teacher_id'] ?? '');
        $slotId = trim($_POST['slot_id'] ?? '');
        $statement = $pdo->prepare('DELETE FROM teacher_unavailabilities WHERE teacher_id = :teacher_id AND slot_id = :slot_id');
        $statement->execute(['teacher_id' => $teacherId, 'slot_id' => $slotId]);
        $rebuildSchedule();
        header('Location: admin.php?tab=admin');
        exit;
    } elseif ($action === 'class_save') {
        $classId = strtoupper(trim($_POST['class_id'] ?? ''));
        $className = trim($_POST['class_name'] ?? '');
        $major = trim($_POST['major'] ?? '');
        $studentCount = filter_var($_POST['student_count'] ?? '', FILTER_VALIDATE_INT);
        $isEdit = $classId !== '';

        if ($className === '' || strlen($className) > 150 || $major === '' || strlen($major) > 150) {
            $message = 'Tên lớp và ngành học không được để trống, tối đa 150 ký tự.';
            $messageType = 'danger';
        } elseif ($studentCount === false || $studentCount < 1 || $studentCount > 9999) {
            $message = 'Sĩ số lớp phải nằm trong khoảng 1 đến 9999 sinh viên.';
            $messageType = 'danger';
        } elseif ($isEdit && !isset($data['classes'][$classId])) {
            $message = 'Không tìm thấy lớp học cần cập nhật.';
            $messageType = 'danger';
        } else {
            if (!$isEdit) {
                $classId = nextPrefixedId($data['classes'], 'L');
                $statement = $pdo->prepare(
                    'INSERT INTO classes (id, name, student_count, major)
                     VALUES (:id, :name, :student_count, :major)'
                );
            } else {
                $statement = $pdo->prepare(
                    'UPDATE classes
                     SET name = :name, student_count = :student_count, major = :major
                     WHERE id = :id'
                );
            }
            $statement->execute([
                'id' => $classId,
                'name' => $className,
                'student_count' => $studentCount,
                'major' => $major,
            ]);
            $data['classes'][$classId] = [
                'id' => $classId,
                'name' => $className,
                'student_count' => $studentCount,
                'size' => $studentCount,
                'major' => $major,
            ];
            if ($isEdit) {
                $updateLessonSizes = $pdo->prepare(
                    'UPDATE lessons SET student_count = :student_count WHERE class_id = :class_id'
                );
                $updateLessonSizes->execute([
                    'student_count' => $studentCount,
                    'class_id' => $classId,
                ]);
                foreach ($data['lessons'] as &$classLesson) {
                    if ($classLesson['class_id'] === $classId) {
                        $classLesson['size'] = $studentCount;
                    }
                }
                unset($classLesson);
                $rebuildSchedule();
            }
            header('Location: admin.php?notice=class_saved');
            exit;
        }
    } elseif ($action === 'class_delete') {
        $classId = strtoupper(trim($_POST['class_id'] ?? ''));
        if (!isset($data['classes'][$classId])) {
            $message = 'Không tìm thấy lớp học cần xóa.';
            $messageType = 'danger';
        } else {
            $references = $pdo->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM lessons WHERE class_id = :lesson_class_id)
                    + (SELECT COUNT(*) FROM lesson_requests WHERE class_id = :request_class_id)
                    + (SELECT COUNT(*) FROM users WHERE role = \'class\' AND ref_id = :user_class_id)'
            );
            $references->execute([
                'lesson_class_id' => $classId,
                'request_class_id' => $classId,
                'user_class_id' => $classId,
            ]);
            if ((int) $references->fetchColumn() > 0) {
                $message = 'Không thể xóa lớp đang có môn học, yêu cầu đăng ký hoặc tài khoản liên kết.';
                $messageType = 'danger';
            } else {
                $statement = $pdo->prepare('DELETE FROM classes WHERE id = :id');
                $statement->execute(['id' => $classId]);
                unset($data['classes'][$classId]);
                header('Location: admin.php?notice=class_deleted');
                exit;
            }
        }
    } elseif ($action === 'lesson_save') {
        $lessonId = strtoupper(trim($_POST['lesson_id'] ?? ''));
        $lessonName = trim($_POST['lesson_name'] ?? '');
        $teacherId = trim($_POST['teacher_id'] ?? '');
        $classId = trim($_POST['class_id'] ?? '');
        $subject = $data['subjects'][$lessonName] ?? null;
        $preferredSlot = trim($_POST['preferred_slot_id'] ?? '');
        $isEdit = $lessonId !== '';

        if ($lessonName === '' || strlen($lessonName) > 200) {
            $message = 'Tên buổi học không được để trống và tối đa 200 ký tự.';
            $messageType = 'danger';
        } elseif (!$subject) {
            $message = 'Vui lòng chọn môn học có sẵn trong danh sách.';
            $messageType = 'danger';
        } elseif (!isset($data['teachers'][$teacherId]) || !isset($data['classes'][$classId])) {
            $message = 'Vui lòng chọn giảng viên và lớp học hợp lệ.';
            $messageType = 'danger';
        } elseif ($preferredSlot !== '' && !isset($data['slots'][$preferredSlot])) {
            $message = 'Khung giờ ưu tiên không hợp lệ.';
            $messageType = 'danger';
        } elseif ($isEdit && !isset($data['lessons'][$lessonId])) {
            $message = 'Không tìm thấy buổi học cần sửa.';
            $messageType = 'danger';
        } elseif ($isEdit && !empty($data['lessons'][$lessonId]['is_locked'])) {
            $message = 'Hãy mở khóa buổi học trước khi sửa.';
            $messageType = 'danger';
        } else {
            $requiredType = $subject['required_type'];
            if (!$isEdit) {
                $lessonId = nextPrefixedId($data['lessons'], 'B');
            }

            $lesson = [
                'id' => $lessonId,
                'name' => $lessonName,
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'required_type' => $requiredType,
                'size' => $data['classes'][$classId]['size'],
                'is_locked' => false,
                'locked_slot' => null,
                'locked_room' => null,
                'preferred_slot' => $preferredSlot === '' ? null : $preferredSlot,
            ];

            if ($isEdit) {
                $statement = $pdo->prepare(
                    'UPDATE lessons
                     SET name = :name, teacher_id = :teacher_id, class_id = :class_id,
                         required_type = :required_type, student_count = :student_count,
                         preferred_slot_id = :preferred_slot_id,
                         is_locked = 0, locked_slot_id = NULL, locked_room_id = NULL
                     WHERE id = :id'
                );
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO lessons
                     (id, name, teacher_id, class_id, required_type, student_count,
                      is_locked, locked_slot_id, locked_room_id, preferred_slot_id)
                     VALUES (:id, :name, :teacher_id, :class_id, :required_type,
                             :student_count, 0, NULL, NULL, :preferred_slot_id)'
                );
            }

            $statement->execute([
                'id' => $lessonId,
                'name' => $lessonName,
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'required_type' => $requiredType,
                'student_count' => $lesson['size'],
                'preferred_slot_id' => $lesson['preferred_slot'],
            ]);
            $data['lessons'][$lessonId] = $lesson;
            $rebuildSchedule();
            header('Location: admin.php?notice=lesson_saved');
            exit;
        }
    } elseif ($action === 'lesson_delete') {
        $lessonId = strtoupper(trim($_POST['lesson_id'] ?? ''));
        if (!isset($data['lessons'][$lessonId])) {
            $message = 'Không tìm thấy buổi học cần xóa.';
            $messageType = 'danger';
        } elseif (!empty($data['lessons'][$lessonId]['is_locked'])) {
            $message = 'Hãy mở khóa buổi học trước khi xóa.';
            $messageType = 'danger';
        } else {
            $statement = $pdo->prepare('DELETE FROM lessons WHERE id = :id');
            $statement->execute(['id' => $lessonId]);
            unset($data['lessons'][$lessonId]);
            $rebuildSchedule();
            header('Location: admin.php?notice=lesson_deleted');
            exit;
        }
    } elseif ($action === 'generate') {
        $method = ($_POST['method'] ?? 'basic') === 'improved' ? 'improved' : 'basic';
        $newPlan = $method === 'improved' ? scheduleImproved($data) : scheduleBasic($data);
        $newPlan['name'] .= ' · ' . date('d/m/Y H:i');
        saveSchedulePlan($pdo, $newPlan);
        $_SESSION['versions']['v1'] = $newPlan;
        header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=admin&plan_saved=1');
        exit;
    } elseif ($action === 'activate_plan') {
        $planId = filter_var($_POST['plan_id'] ?? '', FILTER_VALIDATE_INT);
        $candidatePlan = null;
        foreach ($schedulePlans as $plan) {
            if ($plan['id'] === $planId) {
                $candidatePlan = $plan;
                break;
            }
        }
        $activationError = '';
        if ($candidatePlan) {
            if ($candidatePlan['data_hash'] !== scheduleDataFingerprint($data)) {
                $activationError = 'Dữ liệu đã thay đổi kể từ khi tạo phương án. Hãy tạo phương án mới trước khi áp dụng.';
            }
            $candidateLessonIds = array_column($candidatePlan['assignments'], 'lesson_id');
            foreach ($candidatePlan['unassigned_lessons'] as $unassignedLesson) {
                $candidateLessonIds[] = $unassignedLesson['lesson_id'];
            }
            $expectedLessonIds = array_keys($data['lessons']);
            sort($candidateLessonIds);
            sort($expectedLessonIds);
            if ($activationError === '' && $candidateLessonIds !== $expectedLessonIds) {
                $activationError = 'Dữ liệu buổi học đã thay đổi kể từ khi tạo phương án. Hãy tạo phương án mới trước khi áp dụng.';
            } elseif ($activationError === '') {
                $validatedAssignments = [];
                foreach ($candidatePlan['assignments'] as $assignment) {
                    $lesson = $data['lessons'][$assignment['lesson_id']] ?? null;
                    if (!$lesson) {
                        $activationError = 'Phương án chứa buổi học không còn tồn tại.';
                        break;
                    }
                    if (
                        !empty($lesson['is_locked'])
                        && ($assignment['slot_id'] !== $lesson['locked_slot'] || $assignment['room_id'] !== $lesson['locked_room'])
                    ) {
                        $activationError = 'Phương án làm thay đổi vị trí của buổi đã khóa.';
                        break;
                    }
                    [$valid, $reasons] = validateAssignment(
                        $lesson,
                        $assignment['slot_id'],
                        $assignment['room_id'],
                        $validatedAssignments,
                        $data
                    );
                    if (!$valid) {
                        $activationError = 'Phương án hiện không còn hợp lệ: ' . implode('; ', $reasons);
                        break;
                    }
                    $validatedAssignments[] = $assignment;
                }
            }
        }
        $selectedPlan = $candidatePlan && $activationError === ''
            ? activateSchedulePlan($pdo, $planId)
            : null;
        if (!$selectedPlan) {
            $message = $activationError !== '' ? $activationError : 'Không tìm thấy phương án cần sử dụng.';
            $messageType = 'danger';
        } else {
            $_SESSION['versions']['v1'] = $selectedPlan;
            header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=admin&plan_activated=1');
            exit;
        }
    } elseif ($action === 'toggle_lock') {
        $lessonId = $_POST['lesson_id'] ?? '';
        if (isset($data['lessons'][$lessonId])) {
            $data['lessons'][$lessonId]['is_locked'] = !$data['lessons'][$lessonId]['is_locked'];
            $lockedSlot = null;
            $lockedRoom = null;
            
            // Tìm trong current assignments
            foreach ($currentVersion['assignments'] as &$a) {
                if ($a['lesson_id'] === $lessonId) {
                    $a['is_locked'] = $data['lessons'][$lessonId]['is_locked'];
                    if ($a['is_locked']) {
                        $data['lessons'][$lessonId]['locked_slot'] = $a['slot_id'];
                        $data['lessons'][$lessonId]['locked_room'] = $a['room_id'];
                        $lockedSlot = $a['slot_id'];
                        $lockedRoom = $a['room_id'];
                    } else {
                        $data['lessons'][$lessonId]['locked_slot'] = null;
                        $data['lessons'][$lessonId]['locked_room'] = null;
                    }
                    break;
                }
            }
            $statement = $pdo->prepare(
                'UPDATE lessons
                 SET is_locked = :is_locked, locked_slot_id = :locked_slot_id,
                     locked_room_id = :locked_room_id
                 WHERE id = :id'
            );
            $statement->execute([
                'is_locked' => (int) $data['lessons'][$lessonId]['is_locked'],
                'locked_slot_id' => $lockedSlot,
                'locked_room_id' => $lockedRoom,
                'id' => $lessonId,
            ]);
            $currentVersion['preference_violations'] = schedulePreferenceViolations($currentVersion['assignments'], $data);
            $currentVersion['data_hash'] = scheduleDataFingerprint($data);
            $currentVersion['name'] .= ' · Cập nhật trạng thái khóa ' . date('d/m/Y H:i');
            saveSchedulePlan($pdo, $currentVersion);
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($action === 'manual_adjust') {
        $lessonId = $_POST['lesson_id'] ?? '';
        $newSlot = $_POST['new_slot_id'] ?? '';
        $newRoom = $_POST['new_room_id'] ?? '';

        $lesson = $data['lessons'][$lessonId] ?? null;
        if ($lesson && !empty($lesson['is_locked'])) {
            $message = 'Buổi học này đã bị KHÓA! Hãy mở khóa trước khi đổi vị trí.';
            $messageType = 'danger';
        } elseif ($lesson) {
            list($valid, $reasons) = validateAssignment($lesson, $newSlot, $newRoom, $currentVersion['assignments'], $data);
            if (!$valid) {
                $affected = [];
                foreach ($currentVersion['assignments'] as $assignment) {
                    if ($assignment['lesson_id'] === $lessonId || $assignment['slot_id'] !== $newSlot) {
                        continue;
                    }
                    $otherLesson = $data['lessons'][$assignment['lesson_id']] ?? null;
                    if (
                        $otherLesson
                        && ($assignment['room_id'] === $newRoom
                            || $otherLesson['teacher_id'] === $lesson['teacher_id']
                            || $otherLesson['class_id'] === $lesson['class_id'])
                    ) {
                        $affected[] = $otherLesson['id'] . ' - ' . $otherLesson['name'];
                    }
                }
                $impact = $affected ? ' Các buổi bị ảnh hưởng: ' . implode(', ', $affected) . '.' : '';
                $message = 'Không thể đổi lịch do vi phạm điều kiện bắt buộc: ' . implode('; ', $reasons) . '.' . $impact;
                $messageType = 'danger';
            } else {
                $found = false;
                foreach ($currentVersion['assignments'] as &$a) {
                    if ($a['lesson_id'] === $lessonId) {
                        $a['slot_id'] = $newSlot;
                        $a['room_id'] = $newRoom;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $currentVersion['assignments'][] = [
                        'lesson_id' => $lessonId,
                        'slot_id' => $newSlot,
                        'room_id' => $newRoom,
                        'is_locked' => false
                    ];
                }
                $currentVersion['unassigned_lessons'] = array_values(array_filter(
                    $currentVersion['unassigned_lessons'],
                    static fn($item) => $item['lesson_id'] !== $lessonId
                ));
                $currentVersion['assigned_count'] = count($currentVersion['assignments']);
                $currentVersion['violations_count'] = count($currentVersion['unassigned_lessons']);
                $currentVersion['preference_violations'] = schedulePreferenceViolations($currentVersion['assignments'], $data);
                $currentVersion['data_hash'] = scheduleDataFingerprint($data);
                $currentVersion['name'] .= ' · Điều chỉnh thủ công ' . date('d/m/Y H:i');
                saveSchedulePlan($pdo, $currentVersion);
                $schedulePlans = loadSchedulePlans($pdo);
                $message = "Điều chỉnh lịch thành công cho buổi {$lesson['name']}. Các buổi khác không bị chuyển.";
                $messageType = 'success';
            }
        } else {
            $message = 'Không tìm thấy buổi học cần điều chỉnh.';
            $messageType = 'danger';
        }
    }
}

// Xử lý Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['user']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Lấy user hiện tại
$currentUser = $_SESSION['user'] ?? null;
$viewRole = $currentUser['role'] ?? 'guest';

// Ma trận xếp lịch theo Slot x Room
$scheduleMatrix = [];
foreach ($data['slots'] as $sid => $slot) {
    $scheduleMatrix[$sid] = [];
    foreach ($data['rooms'] as $rid => $room) {
        $scheduleMatrix[$sid][$rid] = null;
    }
}
if ($currentVersion) {
    foreach ($currentVersion['assignments'] as $a) {
        $l = $data['lessons'][$a['lesson_id']] ?? null;
        if (
            $l
            && isset($scheduleMatrix[$a['slot_id']])
            && array_key_exists($a['room_id'], $scheduleMatrix[$a['slot_id']])
        ) {
            $scheduleMatrix[$a['slot_id']][$a['room_id']] = [
                'lesson' => $l,
                'teacher' => $data['teachers'][$l['teacher_id']] ?? null,
                'class' => $data['classes'][$l['class_id']] ?? null,
                'room' => $data['rooms'][$a['room_id']] ?? null,
                'is_locked' => $a['is_locked']
            ];
        }
    }
}

// View filter cho Giảng viên và Lớp
$teacherFilterId = $_GET['teacher_id'] ?? (!empty($currentUser['role']) && $currentUser['role'] === 'teacher' ? $currentUser['ref_id'] : 'GV01');
$classFilterId = $_GET['class_id'] ?? (!empty($currentUser['role']) && $currentUser['role'] === 'class' ? $currentUser['ref_id'] : 'L01');
$registeredProfiles = [];
foreach ($data['users'] as $registeredUser) {
    $registeredProfiles[$registeredUser['role'] . ':' . $registeredUser['ref_id']] = true;
}
$lessonRequests = $pdo->query(
    'SELECT lr.id, lr.teacher_id, t.name AS teacher_name, lr.lesson_name,
            lr.class_id, c.name AS class_name, lr.required_type, lr.status,
            lr.preferred_slot_id, lr.admin_note, lr.created_at
     FROM lesson_requests lr
     INNER JOIN teachers t ON t.id = lr.teacher_id
     INNER JOIN classes c ON c.id = lr.class_id
     ORDER BY (lr.status = \'pending\') DESC, lr.created_at DESC, lr.id DESC'
)->fetchAll();
$teacherLessonRequests = array_values(array_filter(
    $lessonRequests,
    static fn($request) => $request['teacher_id'] === ($currentUser['ref_id'] ?? '')
));
$pendingLessonRequestCount = count(array_filter(
    $lessonRequests,
    static fn($request) => $request['status'] === 'pending'
));
$unavailabilityRequests = $pdo->query(
    'SELECT ur.id, ur.teacher_id, t.name AS teacher_name, ur.slot_id,
            ur.reason, ur.status, ur.admin_note, ur.created_at
     FROM teacher_unavailability_requests ur
     INNER JOIN teachers t ON t.id = ur.teacher_id
     ORDER BY (ur.status = \'pending\') DESC, ur.created_at DESC, ur.id DESC'
)->fetchAll();
$teacherUnavailabilityRequests = array_values(array_filter(
    $unavailabilityRequests,
    static fn($request) => $request['teacher_id'] === ($currentUser['ref_id'] ?? '')
));
$pendingUnavailabilityRequestCount = count(array_filter(
    $unavailabilityRequests,
    static fn($request) => $request['status'] === 'pending'
));

// Nếu đang chạy test bằng PHP CLI thì không xuất mã HTML
if (defined('CLI_TEST') && CLI_TEST) {
    return;
}

require dirname(__DIR__) . '/views/layout.php';
