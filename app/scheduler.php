<?php

function validateAssignment($lesson, $slotId, $roomId, $assignments, $data) {
    $reasons = [];
    if (!isset($data['slots'][$slotId])) {
        return [false, ['Khung giờ không tồn tại']];
    }
    $room = $data['rooms'][$roomId] ?? null;
    if (!$room) {
        return [false, ['Phòng học không tồn tại']];
    }
    // 1. Kiểm tra loại phòng
    if ($room['type'] !== $lesson['required_type']) {
        $reasons[] = "Sai loại phòng: cần {$lesson['required_type']}, phòng là {$room['type']}";
    }

    // 2. Kiểm tra sức chứa
    if ($room['capacity'] < $lesson['size']) {
        $reasons[] = "Sức chứa không đủ: lớp {$lesson['size']} chỗ, phòng chỉ có {$room['capacity']} chỗ";
    }

    // 3. Giảng viên bận
    foreach ($data['unavailabilities'] as $u) {
        if ($u['teacher_id'] === $lesson['teacher_id'] && $u['slot_id'] === $slotId) {
            $reasons[] = "Giảng viên bận ({$u['reason']})";
            break;
        }
    }

    // 4, 5, 6. Kiểm tra trùng phòng, trùng GV, trùng Lớp
    foreach ($assignments as $a) {
        if ($a['lesson_id'] === $lesson['id']) continue;
        if ($a['slot_id'] === $slotId) {
            $assignedLesson = $data['lessons'][$a['lesson_id']] ?? null;
            if (!$assignedLesson) continue;

            if ($a['room_id'] === $roomId) {
                $reasons[] = "Phòng {$room['name']} đã được xếp cho buổi '{$assignedLesson['name']}'";
            }
            if ($assignedLesson['teacher_id'] === $lesson['teacher_id']) {
                $tName = $data['teachers'][$lesson['teacher_id']]['name'] ?? $lesson['teacher_id'];
                $reasons[] = "Giảng viên $tName đã có tiết dạy buổi '{$assignedLesson['name']}' trong ca này";
            }
            if ($assignedLesson['class_id'] === $lesson['class_id']) {
                $cName = $data['classes'][$lesson['class_id']]['name'] ?? $lesson['class_id'];
                $reasons[] = "Lớp $cName đã có buổi '{$assignedLesson['name']}' trong ca này";
            }
        }
    }

    return [count($reasons) === 0, $reasons];
}

function schedulePreferenceViolations(array $assignments, array $data): int {
    $violations = 0;
    foreach ($assignments as $assignment) {
        $lesson = $data['lessons'][$assignment['lesson_id']] ?? null;
        if ($lesson && !empty($lesson['preferred_slot']) && $lesson['preferred_slot'] !== $assignment['slot_id']) {
            $violations++;
        }
    }
    return $violations;
}

function scheduleDataFingerprint(array $data): string {
    $snapshot = [];
    foreach (['teachers', 'classes', 'rooms', 'slots', 'lessons'] as $key) {
        $snapshot[$key] = $data[$key] ?? [];
        ksort($snapshot[$key]);
    }
    $snapshot['unavailabilities'] = $data['unavailabilities'] ?? [];
    usort($snapshot['unavailabilities'], static function ($a, $b): int {
        return [$a['teacher_id'], $a['slot_id'], $a['reason']]
            <=> [$b['teacher_id'], $b['slot_id'], $b['reason']];
    });
    return hash('sha256', serialize($snapshot));
}

function scheduleLockedLessons(array $data, array &$assignments, array &$unassigned): void {
    foreach ($data['lessons'] as $lessonId => $lesson) {
        if (empty($lesson['is_locked'])) {
            continue;
        }
        if (empty($lesson['locked_slot']) || empty($lesson['locked_room'])) {
            $unassigned[] = [
                'lesson_id' => $lessonId,
                'lesson_name' => $lesson['name'],
                'class_id' => $lesson['class_id'],
                'teacher_id' => $lesson['teacher_id'],
                'reason' => 'Buổi đã khóa nhưng chưa có khung giờ và phòng hợp lệ',
            ];
            continue;
        }
        [$valid, $reasons] = validateAssignment(
            $lesson,
            $lesson['locked_slot'],
            $lesson['locked_room'],
            $assignments,
            $data
        );
        if ($valid) {
            $assignments[] = [
                'lesson_id' => $lessonId,
                'slot_id' => $lesson['locked_slot'],
                'room_id' => $lesson['locked_room'],
                'is_locked' => true,
            ];
        } else {
            $unassigned[] = [
                'lesson_id' => $lessonId,
                'lesson_name' => $lesson['name'],
                'class_id' => $lesson['class_id'],
                'teacher_id' => $lesson['teacher_id'],
                'reason' => 'Buổi khóa bị xung đột: ' . implode('; ', $reasons),
            ];
        }
    }
}

function scheduleResult(string $name, string $method, array $assignments, array $unassigned, array $data): array {
    return [
        'name' => $name,
        'method' => $method,
        'created_at' => date('d/m/Y H:i:s'),
        'assignments' => $assignments,
        'unassigned_lessons' => $unassigned,
        'assigned_count' => count($assignments),
        'violations_count' => count($unassigned),
        'preference_violations' => schedulePreferenceViolations($assignments, $data),
        'data_hash' => scheduleDataFingerprint($data),
        'total_lessons' => count($data['lessons']),
    ];
}

function nextPrefixedId(array $entities, string $prefix): string {
    $highestId = 0;
    foreach (array_keys($entities) as $existingId) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $existingId, $matches)) {
            $highestId = max($highestId, (int) $matches[1]);
        }
    }
    return sprintf('%s%02d', $prefix, $highestId + 1);
}

// Cách 1: Cơ bản (Greedy First-Fit)
function scheduleBasic($data) {
    $assignments = [];
    $unassigned = [];

    scheduleLockedLessons($data, $assignments, $unassigned);

    // Bước 2: Duyệt First-Fit
    foreach ($data['lessons'] as $lid => $lesson) {
        if (!empty($lesson['is_locked'])) continue;

        $placed = false;
        $failReasons = [];
        $slots = $data['slots'];
        $preferredSlot = $lesson['preferred_slot'] ?? null;
        if ($preferredSlot && isset($slots[$preferredSlot])) {
            $slots = [$preferredSlot => $slots[$preferredSlot]]
                + array_diff_key($slots, [$preferredSlot => true]);
        }
        foreach (array_keys($slots) as $sid) {
            foreach (array_keys($data['rooms']) as $rid) {
                list($valid, $reasons) = validateAssignment($lesson, $sid, $rid, $assignments, $data);
                if ($valid) {
                    $assignments[] = [
                        'lesson_id' => $lid,
                        'slot_id' => $sid,
                        'room_id' => $rid,
                        'is_locked' => false
                    ];
                    $placed = true;
                    break 2;
                } else {
                    $failReasons = array_merge($failReasons, $reasons);
                }
            }
        }

        if (!$placed) {
            $uniqueReasons = array_unique($failReasons);
            $unassigned[] = [
                'lesson_id' => $lid,
                'lesson_name' => $lesson['name'],
                'class_id' => $lesson['class_id'],
                'teacher_id' => $lesson['teacher_id'],
                'reason' => 'Bế tắc First-Fit (' . (!empty($uniqueReasons) ? implode('; ', array_slice($uniqueReasons, 0, 2)) : 'Hết phòng/slot') . ')'
            ];
        }
    }

    return scheduleResult('Phương án cơ bản (Greedy First-Fit)', 'basic', $assignments, $unassigned, $data);
}

// Cách 2: Cải tiến (MRV + Best-Fit Room)
function scheduleImproved($data) {
    $assignments = [];
    $unassigned = [];

    scheduleLockedLessons($data, $assignments, $unassigned);

    // Bước 2: Sắp xếp theo độ khó (MRV: LAB hiếm xếp trước, sĩ số lớn xếp trước)
    $unlocked = [];
    foreach ($data['lessons'] as $lid => $l) {
        if (empty($l['is_locked'])) {
            $unlocked[] = $l;
        }
    }
    usort($unlocked, function($a, $b) {
        $scoreA = ($a['required_type'] === 'LAB' ? 100 : 0) + $a['size'];
        $scoreB = ($b['required_type'] === 'LAB' ? 100 : 0) + $b['size'];
        $difficulty = $scoreB <=> $scoreA;
        if ($difficulty !== 0) {
            return $difficulty;
        }
        return (!empty($b['preferred_slot']) ? 1 : 0) <=> (!empty($a['preferred_slot']) ? 1 : 0);
    });

    // Bước 3: Phân bổ với Best-Fit Room
    foreach ($unlocked as $lesson) {
        $suitableRooms = [];
        foreach ($data['rooms'] as $r) {
            if ($r['type'] === $lesson['required_type'] && $r['capacity'] >= $lesson['size']) {
                $suitableRooms[] = $r;
            }
        }
        usort($suitableRooms, function($a, $b) use ($lesson) {
            return ($a['capacity'] - $lesson['size']) <=> ($b['capacity'] - $lesson['size']);
        });

        if (empty($suitableRooms)) {
            $unassigned[] = [
                'lesson_id' => $lesson['id'],
                'lesson_name' => $lesson['name'],
                'class_id' => $lesson['class_id'],
                'teacher_id' => $lesson['teacher_id'],
                'reason' => "Không có phòng nào thỏa mãn (Cần loại {$lesson['required_type']}, sức chứa ≥ {$lesson['size']})"
            ];
            continue;
        }

        $placed = false;
        $failReasons = [];
        $slots = $data['slots'];
        $preferredSlot = $lesson['preferred_slot'] ?? null;
        if ($preferredSlot && isset($slots[$preferredSlot])) {
            $slots = [$preferredSlot => $slots[$preferredSlot]]
                + array_diff_key($slots, [$preferredSlot => true]);
        }
        foreach (array_keys($slots) as $sid) {
            foreach ($suitableRooms as $room) {
                list($valid, $reasons) = validateAssignment($lesson, $sid, $room['id'], $assignments, $data);
                if ($valid) {
                    $assignments[] = [
                        'lesson_id' => $lesson['id'],
                        'slot_id' => $sid,
                        'room_id' => $room['id'],
                        'is_locked' => false
                    ];
                    $placed = true;
                    break 2;
                } else {
                    $failReasons = array_merge($failReasons, $reasons);
                }
            }
        }

        if (!$placed) {
            $uniqueReasons = array_unique($failReasons);
            $unassigned[] = [
                'lesson_id' => $lesson['id'],
                'lesson_name' => $lesson['name'],
                'class_id' => $lesson['class_id'],
                'teacher_id' => $lesson['teacher_id'],
                'reason' => 'Xung đột ràng buộc (' . (!empty($uniqueReasons) ? implode('; ', array_slice($uniqueReasons, 0, 2)) : 'Không còn slot') . ')'
            ];
        }
    }

    return scheduleResult('Phương án cải thiện (MRV + Best-Fit Room)', 'improved', $assignments, $unassigned, $data);
}
