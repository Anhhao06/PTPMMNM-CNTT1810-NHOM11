<?php
/**
 * Kiểm thử xếp lịch, ràng buộc và buổi học nửa ngày của Đề tài 18 (PHP CLI)
 */

define('CLI_TEST', true);
require_once __DIR__ . '/BTL.php';

$initData = loadInitialDataFromDatabase(createDatabaseConnection());
$basic = scheduleBasic($initData);
$improved = scheduleImproved($initData);
$slotsByDay = [];
$periodNamesMatch = true;
foreach ($initData['slots'] as $slot) {
    $slotsByDay[$slot['day']][] = $slot['period_index'];
    $expectedName = $slot['period_index'] === 1
        ? 'Buổi sáng (07:00-12:00)'
        : 'Buổi chiều (13:00-18:00)';
    if ($slot['period'] !== $expectedName) {
        $periodNamesMatch = false;
    }
}

echo "========================================================\n";
echo " KIỂM THỬ XẾP LỊCH VÀ BUỔI NỬA NGÀY (ĐỀ TÀI 18) TRÊN PHP 8\n";
echo "========================================================\n";

$passCount = 0;

// Phép thử 1: Không trùng GV, Lớp, Phòng cùng khung giờ
$test1Passed = true;
foreach ([$basic, $improved] as $v) {
    $slotRooms = [];
    $slotTeachers = [];
    $slotClasses = [];

    foreach ($v['assignments'] as $a) {
        $lesson = $initData['lessons'][$a['lesson_id']];
        $rKey = $a['slot_id'] . '_' . $a['room_id'];
        $tKey = $a['slot_id'] . '_' . $lesson['teacher_id'];
        $cKey = $a['slot_id'] . '_' . $lesson['class_id'];

        if (isset($slotRooms[$rKey]) || isset($slotTeachers[$tKey]) || isset($slotClasses[$cKey])) {
            $test1Passed = false;
            break 2;
        }
        $slotRooms[$rKey] = true;
        $slotTeachers[$tKey] = true;
        $slotClasses[$cKey] = true;
    }
}
if ($test1Passed) {
    echo " Phép thử 1: ĐẠT (Không trùng GV, lớp hoặc phòng trong cùng khung giờ)\n";
    $passCount++;
} else {
    echo " Phép thử 1: KHÔNG ĐẠT\n";
}

// Phép thử 2: Sức chứa, loại phòng, thời điểm bận
$test2Passed = true;
foreach ([$basic, $improved] as $v) {
    foreach ($v['assignments'] as $a) {
        $lesson = $initData['lessons'][$a['lesson_id']];
        $room = $initData['rooms'][$a['room_id']];

        if ($room['capacity'] < $lesson['size'] || $room['type'] !== $lesson['required_type']) {
            $test2Passed = false;
            break 2;
        }
        foreach ($initData['unavailabilities'] as $u) {
            if ($u['teacher_id'] === $lesson['teacher_id'] && $u['slot_id'] === $a['slot_id']) {
                $test2Passed = false;
                break 3;
            }
        }
    }
}
if ($test2Passed) {
    echo " Phép thử 2: ĐẠT (Phòng nhỏ hơn sĩ số, sai loại hoặc GV bận đều bị loại)\n";
    $passCount++;
} else {
    echo " Phép thử 2: KHÔNG ĐẠT\n";
}

// Phép thử 3: Giữ nguyên các buổi đã khóa
$test3Passed = true;
foreach ([$basic, $improved] as $v) {
    foreach ($v['assignments'] as $a) {
        $lesson = $initData['lessons'][$a['lesson_id']];
        if (!empty($lesson['is_locked'])) {
            if ($a['slot_id'] !== $lesson['locked_slot'] || $a['room_id'] !== $lesson['locked_room']) {
                $test3Passed = false;
                break 2;
            }
        }
    }
}
if ($test3Passed) {
    echo " Phép thử 3: ĐẠT (Xếp lại không thay đổi những buổi đã khóa: B01, B02)\n";
    $passCount++;
} else {
    echo " Phép thử 3: KHÔNG ĐẠT\n";
}

// Phép thử 4: Trả danh sách chưa xếp khi bế tắc
$test4Passed = false;
if (count($basic['unassigned_lessons']) > 0 && count($improved['unassigned_lessons']) > 0) {
    $hasB20 = false;
    foreach ($improved['unassigned_lessons'] as $it) {
        if ($it['lesson_id'] === 'B20' && !empty($it['reason'])) {
            $hasB20 = true;
            break;
        }
    }
    $allLessonsReported = $improved['assigned_count'] + count($improved['unassigned_lessons'])
        === count($initData['lessons']);
    if ($hasB20 && $allLessonsReported) {
        $test4Passed = true;
    }
}
if ($test4Passed) {
    echo " Phép thử 4: ĐẠT (Bế tắc trả danh sách chưa xếp có buổi B20 kèm lý do, không giấu lỗi)\n";
    $passCount++;
} else {
    echo " Phép thử 4: KHÔNG ĐẠT\n";
}

// Phép thử 5: So sánh trên cùng dữ liệu
$test5Passed = $basic['total_lessons'] === $improved['total_lessons']
    && $basic['method'] === 'basic'
    && $improved['method'] === 'improved'
    && isset($basic['preference_violations'], $improved['preference_violations'])
    && strlen($basic['data_hash']) === 64
    && $basic['total_lessons'] === count($initData['lessons'])
    && $basic['assigned_count'] + count($basic['unassigned_lessons']) === count($initData['lessons'])
    && $improved['assigned_count'] + count($improved['unassigned_lessons']) === count($initData['lessons']);
if ($test5Passed) {
    echo " Phép thử 5: ĐẠT (Hai phương án so sánh trên cùng toàn bộ dữ liệu buổi học)\n";
    $passCount++;
} else {
    echo " Phép thử 5: KHÔNG ĐẠT\n";
}

$expectedPeriods = [1, 2];
$test6Passed = count($initData['slots']) === 10
    && count($slotsByDay) === 5
    && $periodNamesMatch;
foreach ($slotsByDay as $dayPeriods) {
    sort($dayPeriods);
    if ($dayPeriods !== $expectedPeriods) {
        $test6Passed = false;
        break;
    }
}
if ($test6Passed) {
    echo " Phép thử 6: ĐẠT (Mỗi ngày có 2 buổi nửa ngày: sáng 07:00-12:00, chiều 13:00-18:00)\n";
    $passCount++;
} else {
    echo " Phép thử 6: KHÔNG ĐẠT\n";
}

// Phép thử 7: Lớp Công nghệ thông tin có sẵn để đăng ký
$technologyClass = $initData['classes']['L05'] ?? null;
$test7Passed = $technologyClass
    && $technologyClass['name'] === 'CNTT01 (Công nghệ thông tin)'
    && $technologyClass['major'] === 'Công nghệ thông tin'
    && $technologyClass['size'] === 35;
if ($test7Passed) {
    echo "✔ Phép thử 7: ĐẠT (Lớp CNTT01 có sẵn và đúng ngành/sĩ số)\n";
    $passCount++;
} else {
    echo " Phép thử 7: KHÔNG ĐẠT\n";
}

// Phép thử 8: Danh mục môn có ngành và khớp với chuyên ngành giảng viên
$test8Passed = !empty($initData['subjects']);
foreach ($initData['subjects'] as $subject) {
    if (empty($subject['department'])) {
        $test8Passed = false;
        break;
    }
}
foreach ($initData['teachers'] as $teacher) {
    $hasMatchingSubject = false;
    foreach ($initData['subjects'] as $subject) {
        if ($subject['department'] === $teacher['subject']) {
            $hasMatchingSubject = true;
            break;
        }
    }
    if (!$hasMatchingSubject) {
        $test8Passed = false;
        break;
    }
}
if ($test8Passed) {
    echo "✔ Phép thử 8: ĐẠT (Mỗi giảng viên có môn trong danh mục đúng chuyên ngành)\n";
    $passCount++;
} else {
    echo "❌ Phép thử 8: KHÔNG ĐẠT\n";
}

// Phép thử 9: Chạy migration lần nữa không thay đổi lịch đã chuẩn hóa
$test9Passed = !ensureHalfDaySlots($pdo);
if ($test9Passed) {
    echo "✔ Phép thử 9: ĐẠT (Migration lịch nửa ngày chạy lặp an toàn)\n";
    $passCount++;
} else {
    echo "❌ Phép thử 9: KHÔNG ĐẠT\n";
}

$preferenceFixture = [
    'teachers' => ['T1' => ['id' => 'T1', 'name' => 'Giảng viên thử']],
    'classes' => ['C1' => ['id' => 'C1', 'name' => 'Lớp thử']],
    'rooms' => ['R1' => ['id' => 'R1', 'name' => 'Phòng thử', 'capacity' => 20, 'type' => 'LT']],
    'slots' => ['S1' => ['id' => 'S1'], 'S2' => ['id' => 'S2']],
    'unavailabilities' => [['teacher_id' => 'T1', 'slot_id' => 'S1', 'reason' => 'Bận']],
    'lessons' => [
        'L1' => [
            'id' => 'L1',
            'name' => 'Môn thử',
            'teacher_id' => 'T1',
            'class_id' => 'C1',
            'required_type' => 'LT',
            'size' => 10,
            'is_locked' => false,
            'locked_slot' => null,
            'locked_room' => null,
            'preferred_slot' => 'S1',
        ],
    ],
];
$preferenceTestPassed = true;
foreach ([scheduleBasic($preferenceFixture), scheduleImproved($preferenceFixture)] as $preferencePlan) {
    $preferenceTestPassed = $preferenceTestPassed
        && $preferencePlan['assigned_count'] === 1
        && $preferencePlan['violations_count'] === 0
        && $preferencePlan['assignments'][0]['slot_id'] === 'S2'
        && $preferencePlan['preference_violations'] === 1;
}
if ($preferenceTestPassed) {
    echo " Phép thử 10: ĐẠT (Ưu tiên mềm được tính điểm nhưng không lấn át lịch bận bắt buộc)\n";
    $passCount++;
} else {
    echo " Phép thử 10: KHÔNG ĐẠT\n";
}

$fingerprintTestPassed = scheduleDataFingerprint($initData) === scheduleDataFingerprint($initData);
$changedData = $initData;
$changedData['unavailabilities'][] = [
    'teacher_id' => 'GV01',
    'slot_id' => 'SL19',
    'reason' => 'Thay đổi thử',
];
$fingerprintTestPassed = $fingerprintTestPassed
    && scheduleDataFingerprint($initData) !== scheduleDataFingerprint($changedData);
if ($fingerprintTestPassed) {
    echo " Phép thử 11: ĐẠT (Phát hiện dữ liệu ràng buộc đổi để chặn áp dụng phương án cũ)\n";
    $passCount++;
} else {
    echo " Phép thử 11: KHÔNG ĐẠT\n";
}

$preferredRequestColumn = $pdo->prepare(
    "SELECT 1 FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'lesson_requests'
       AND column_name = 'preferred_slot_id' LIMIT 1"
);
$preferredRequestColumn->execute();
$test12Passed = (bool) $preferredRequestColumn->fetchColumn();
if ($test12Passed) {
    echo "✔ Phép thử 12: ĐẠT (Yêu cầu đăng ký có lưu được ngày/buổi mong muốn)\n";
    $passCount++;
} else {
    echo "❌ Phép thử 12: KHÔNG ĐẠT\n";
}

$busyRequestTable = $pdo->prepare(
    "SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'teacher_unavailability_requests'
       AND column_name IN ('teacher_id', 'slot_id', 'reason', 'status', 'admin_note')"
);
$busyRequestTable->execute();
$test13Passed = (int) $busyRequestTable->fetchColumn() === 5;
if ($test13Passed) {
    echo "✔ Phép thử 13: ĐẠT (Yêu cầu lịch bận có đủ dữ liệu và trạng thái duyệt)\n";
    $passCount++;
} else {
    echo "❌ Phép thử 13: KHÔNG ĐẠT\n";
}

$pdo->beginTransaction();
try {
    $insertBusyRequest = $pdo->prepare(
        "INSERT INTO teacher_unavailability_requests (teacher_id, slot_id, reason)
         VALUES ('GV01', 'SL19', 'Kiểm thử yêu cầu lịch bận')"
    );
    $insertBusyRequest->execute();
    $requestId = (int) $pdo->lastInsertId();
    $approveBusy = $pdo->prepare(
        'INSERT INTO teacher_unavailabilities (teacher_id, slot_id, reason)
         VALUES (\'GV01\', \'SL19\', \'Kiểm thử yêu cầu lịch bận\')
         ON DUPLICATE KEY UPDATE reason = VALUES(reason)'
    );
    $approveBusy->execute();
    $markApproved = $pdo->prepare(
        "UPDATE teacher_unavailability_requests
         SET status = 'approved', reviewed_at = CURRENT_TIMESTAMP
         WHERE id = :id AND status = 'pending'"
    );
    $markApproved->execute(['id' => $requestId]);
    $approvedBusy = $pdo->prepare(
        "SELECT COUNT(*)
         FROM teacher_unavailability_requests r
         INNER JOIN teacher_unavailabilities u
             ON u.teacher_id = r.teacher_id AND u.slot_id = r.slot_id
         WHERE r.id = :id AND r.status = 'approved'"
    );
    $approvedBusy->execute(['id' => $requestId]);
    $test14Passed = (int) $approvedBusy->fetchColumn() === 1;
} finally {
    $pdo->rollBack();
}
if ($test14Passed) {
    echo "✔ Phép thử 14: ĐẠT (Duyệt yêu cầu mới thêm lịch bận chính thức)\n";
    $passCount++;
} else {
    echo "❌ Phép thử 14: KHÔNG ĐẠT\n";
}

echo "--------------------------------------------------------\n";
echo " KẾT QUẢ: $passCount/14 PHÉP THỬ THÀNH CÔNG!\n";
echo "========================================================\n";
