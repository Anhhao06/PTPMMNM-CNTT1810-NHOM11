<?php

function createDatabaseConnection(): PDO {
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $database = getenv('DB_NAME') ?: 'btl_timetable';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function ensureLessonRequestsTable(PDO $pdo): void {
    $tableCheck = $pdo->prepare(
        'SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = :table_name LIMIT 1'
    );
    $tableCheck->execute(['table_name' => 'lesson_requests']);
    if (!$tableCheck->fetchColumn()) {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS lesson_requests (
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
            ) ENGINE=InnoDB"
        );
    }

    $columnCheck = $pdo->prepare(
        'SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :table_name
           AND column_name = :column_name LIMIT 1'
    );
    $columnCheck->execute(['table_name' => 'lesson_requests', 'column_name' => 'preferred_slot_id']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec(
            'ALTER TABLE lesson_requests
             ADD preferred_slot_id VARCHAR(10) NULL,
             ADD CONSTRAINT fk_lesson_requests_preferred_slot FOREIGN KEY (preferred_slot_id)
                 REFERENCES time_slots(id) ON DELETE SET NULL'
        );
    }
}

function ensureTeacherUnavailabilityRequestsTable(PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS teacher_unavailability_requests (
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
            CONSTRAINT fk_unavailability_requests_teacher FOREIGN KEY (teacher_id)
                REFERENCES teachers(id),
            CONSTRAINT fk_unavailability_requests_slot FOREIGN KEY (slot_id)
                REFERENCES time_slots(id)
        ) ENGINE=InnoDB"
    );
}

function ensureHalfDaySlots(PDO $pdo): bool {
    $slots = $pdo->query(
        'SELECT id, day_order, period_index FROM time_slots
         ORDER BY day_order, period_index'
    )->fetchAll();
    if (!$slots) {
        return false;
    }

    $maxPeriodByDay = [];
    $countByDay = [];
    foreach ($slots as $slot) {
        $dayOrder = (int) $slot['day_order'];
        $maxPeriodByDay[$dayOrder] = max(
            $maxPeriodByDay[$dayOrder] ?? 0,
            (int) $slot['period_index']
        );
        $countByDay[$dayOrder] = ($countByDay[$dayOrder] ?? 0) + 1;
    }

    $slotMap = [];
    $canonicalSlots = [];
    $sessionPositionByDay = [];
    foreach ($slots as $slot) {
        $dayOrder = (int) $slot['day_order'];
        $periodIndex = (int) $slot['period_index'];
        if ($countByDay[$dayOrder] === 2) {
            $sessionPositionByDay[$dayOrder] = ($sessionPositionByDay[$dayOrder] ?? 0) + 1;
            $sessionIndex = $sessionPositionByDay[$dayOrder];
        } elseif ($maxPeriodByDay[$dayOrder] <= 4) {
            $sessionIndex = $periodIndex <= 2 ? 1 : 2;
        } else {
            $sessionIndex = $periodIndex <= 5 ? 1 : 2;
        }
        $sessionKey = $dayOrder . ':' . $sessionIndex;
        if (!isset($canonicalSlots[$sessionKey])) {
            $canonicalSlots[$sessionKey] = [
                'id' => $slot['id'],
                'session_index' => $sessionIndex,
            ];
        }
        $slotMap[$slot['id']] = $canonicalSlots[$sessionKey]['id'];
    }
    $layoutChanged = count($slots) !== count($canonicalSlots);

    $pdo->beginTransaction();
    try {
        $updateAssignment = $pdo->prepare(
            'UPDATE schedule_assignments SET slot_id = :canonical_id WHERE slot_id = :old_id'
        );
        $updateLockedLesson = $pdo->prepare(
            'UPDATE lessons SET locked_slot_id = :canonical_id WHERE locked_slot_id = :old_id'
        );
        foreach ($slotMap as $oldId => $canonicalId) {
            if ($oldId === $canonicalId) {
                continue;
            }
            $updateAssignment->execute(['canonical_id' => $canonicalId, 'old_id' => $oldId]);
            $updateLockedLesson->execute(['canonical_id' => $canonicalId, 'old_id' => $oldId]);
        }

        $unavailabilities = $pdo->query(
            'SELECT teacher_id, slot_id, reason FROM teacher_unavailabilities'
        )->fetchAll();
        $normalizedUnavailabilities = [];
        foreach ($unavailabilities as $unavailability) {
            $canonicalId = $slotMap[$unavailability['slot_id']] ?? null;
            if ($canonicalId === null) {
                continue;
            }
            $key = $unavailability['teacher_id'] . ':' . $canonicalId;
            $normalizedUnavailabilities[$key] ??= [
                'teacher_id' => $unavailability['teacher_id'],
                'slot_id' => $canonicalId,
                'reasons' => [],
            ];
            $normalizedUnavailabilities[$key]['reasons'][] = $unavailability['reason'];
        }
        $pdo->exec('DELETE FROM teacher_unavailabilities');
        $insertUnavailability = $pdo->prepare(
            'INSERT INTO teacher_unavailabilities (teacher_id, slot_id, reason)
             VALUES (:teacher_id, :slot_id, :reason)'
        );
        foreach ($normalizedUnavailabilities as $unavailability) {
            $insertUnavailability->execute([
                'teacher_id' => $unavailability['teacher_id'],
                'slot_id' => $unavailability['slot_id'],
                'reason' => substr(implode('; ', array_unique($unavailability['reasons'])), 0, 200),
            ]);
        }

        $canonicalIds = array_column($canonicalSlots, 'id');
        $deleteSlots = $pdo->prepare(
            'DELETE FROM time_slots WHERE id NOT IN (' .
            implode(', ', array_fill(0, count($canonicalIds), '?')) .
            ')'
        );
        $deleteSlots->execute($canonicalIds);

        $updateSlot = $pdo->prepare(
            'UPDATE time_slots
             SET period_index = :period_index, period_name = :period_name
             WHERE id = :id'
        );
        foreach ($canonicalSlots as $slot) {
            $updateSlot->execute([
                'period_index' => $slot['session_index'],
                'period_name' => $slot['session_index'] === 1
                    ? 'Buổi sáng (07:00-12:00)'
                    : 'Buổi chiều (13:00-18:00)',
                'id' => $slot['id'],
            ]);
        }
        $pdo->commit();
        return $layoutChanged;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function ensureTechnologyClass(PDO $pdo): void {
    $statement = $pdo->prepare(
        "INSERT IGNORE INTO classes (id, name, student_count, major)
         VALUES ('L05', 'CNTT01 (Công nghệ thông tin)', 35, 'Công nghệ thông tin')"
    );
    $statement->execute();
}

function ensureSubjectCatalog(PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS subjects (
            name VARCHAR(200) PRIMARY KEY,
            required_type ENUM('LT', 'LAB') NOT NULL,
            department VARCHAR(150) NOT NULL DEFAULT ''
        ) ENGINE=InnoDB"
    );
    $columnCheck = $pdo->prepare(
        'SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :table_name
           AND column_name = :column_name LIMIT 1'
    );
    $columnCheck->execute(['table_name' => 'subjects', 'column_name' => 'department']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE subjects ADD department VARCHAR(150) NOT NULL DEFAULT ''");
    }
    $pdo->exec(
        "INSERT IGNORE INTO subjects (name, required_type, department)
         SELECT l.name, l.required_type, t.subject
         FROM lessons l
         INNER JOIN teachers t ON t.id = l.teacher_id"
    );
    $pdo->exec(
        "UPDATE subjects s
         INNER JOIN lessons l ON l.name = s.name
         INNER JOIN teachers t ON t.id = l.teacher_id
         SET s.department = t.subject
         WHERE s.department = ''"
    );
}

function ensureSchedulePlanningSchema(PDO $pdo): void {
    $columnCheck = $pdo->prepare(
        'SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :table_name
           AND column_name = :column_name LIMIT 1'
    );
    $columnCheck->execute(['table_name' => 'lessons', 'column_name' => 'preferred_slot_id']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec(
            'ALTER TABLE lessons ADD preferred_slot_id VARCHAR(10) NULL,
             ADD CONSTRAINT fk_lessons_preferred_slot
             FOREIGN KEY (preferred_slot_id) REFERENCES time_slots(id) ON DELETE SET NULL'
        );
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS schedule_plans (
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
        ) ENGINE=InnoDB"
    );
    $columnCheck->execute(['table_name' => 'schedule_plans', 'column_name' => 'is_active']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec('ALTER TABLE schedule_plans ADD is_active TINYINT(1) NOT NULL DEFAULT 0');
    }
    $columnCheck->execute(['table_name' => 'schedule_plans', 'column_name' => 'data_hash']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE schedule_plans ADD data_hash CHAR(64) NOT NULL DEFAULT ''");
    }
}

function loadInitialDataFromDatabase(PDO $pdo): array {
    $teachers = [];
    foreach ($pdo->query('SELECT id, name, email, subject FROM teachers ORDER BY id') as $row) {
        $teachers[$row['id']] = $row;
    }

    $classes = [];
    foreach ($pdo->query('SELECT id, name, student_count, major FROM classes ORDER BY id') as $row) {
        $row['size'] = (int) $row['student_count'];
        unset($row['student_count']);
        $classes[$row['id']] = $row;
    }

    $subjects = [];
    foreach ($pdo->query('SELECT name, required_type, department FROM subjects ORDER BY name') as $row) {
        $subjects[$row['name']] = $row;
    }

    $rooms = [];
    foreach ($pdo->query('SELECT id, name, capacity, type FROM rooms ORDER BY id') as $row) {
        $row['capacity'] = (int) $row['capacity'];
        $rooms[$row['id']] = $row;
    }

    $slots = [];
    $days = [];
    $periods = [];
    $slotQuery = $pdo->query(
        'SELECT id, day_name, day_order, period_index, period_name
         FROM time_slots
         ORDER BY day_order, period_index'
    );
    foreach ($slotQuery as $row) {
        $periodIndex = (int) $row['period_index'];
        $slots[$row['id']] = [
            'id' => $row['id'],
            'day' => $row['day_name'],
            'day_order' => (int) $row['day_order'],
            'period' => $row['period_name'],
            'period_index' => $periodIndex,
        ];
        $days[$row['day_order']] = $row['day_name'];
        $periods[$periodIndex] = $row['period_name'];
    }
    ksort($days);
    $days = array_values($days);
    ksort($periods);

    $unavailabilities = $pdo->query(
        'SELECT teacher_id, slot_id, reason FROM teacher_unavailabilities'
    )->fetchAll();

    $lessons = [];
    $lessonQuery = $pdo->query(
        'SELECT id, name, teacher_id, class_id, required_type, student_count,
                is_locked, locked_slot_id, locked_room_id, preferred_slot_id
         FROM lessons
         ORDER BY id'
    );
    foreach ($lessonQuery as $row) {
        $row['size'] = (int) $row['student_count'];
        $row['is_locked'] = (bool) $row['is_locked'];
        $row['locked_slot'] = $row['locked_slot_id'];
        $row['locked_room'] = $row['locked_room_id'];
        $row['preferred_slot'] = $row['preferred_slot_id'];
        unset($row['student_count'], $row['locked_slot_id'], $row['locked_room_id'], $row['preferred_slot_id']);
        $lessons[$row['id']] = $row;
    }

    $users = [];
    foreach ($pdo->query(
        'SELECT username, password_hash, role, display_name, ref_id FROM users ORDER BY username'
    ) as $row) {
        $users[] = [
            'username' => $row['username'],
            'password_hash' => $row['password_hash'],
            'role' => $row['role'],
            'name' => $row['display_name'],
            'ref_id' => $row['ref_id'],
        ];
    }

    return compact(
        'teachers',
        'classes',
        'subjects',
        'rooms',
        'slots',
        'days',
        'periods',
        'unavailabilities',
        'lessons',
        'users'
    );
}

function loadSavedScheduleAssignments(PDO $pdo): array {
    return $pdo->query(
        'SELECT lesson_id, slot_id, room_id, is_locked
         FROM schedule_assignments
         ORDER BY lesson_id'
    )->fetchAll();
}

function saveScheduleAssignments(PDO $pdo, array $assignments): void {
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $pdo->exec('DELETE FROM schedule_assignments');
        $statement = $pdo->prepare(
            'INSERT INTO schedule_assignments (lesson_id, slot_id, room_id, is_locked)
             VALUES (:lesson_id, :slot_id, :room_id, :is_locked)'
        );
        foreach ($assignments as $assignment) {
            $statement->execute([
                'lesson_id' => $assignment['lesson_id'],
                'slot_id' => $assignment['slot_id'],
                'room_id' => $assignment['room_id'],
                'is_locked' => (int) $assignment['is_locked'],
            ]);
        }
        if ($ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function loadSchedulePlans(PDO $pdo): array {
    $plans = [];
    foreach ($pdo->query(
        'SELECT id, name, method, assignments_json, unassigned_json, assigned_count,
                violations_count, preference_violations, data_hash, is_active, created_at
         FROM schedule_plans ORDER BY created_at DESC, id DESC'
    ) as $row) {
        $plans[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'method' => $row['method'],
            'assignments' => json_decode($row['assignments_json'], true, 512, JSON_THROW_ON_ERROR),
            'unassigned_lessons' => json_decode($row['unassigned_json'], true, 512, JSON_THROW_ON_ERROR),
            'assigned_count' => (int) $row['assigned_count'],
            'violations_count' => (int) $row['violations_count'],
            'preference_violations' => (int) $row['preference_violations'],
            'data_hash' => $row['data_hash'],
            'is_active' => (bool) $row['is_active'],
            'total_lessons' => (int) $row['assigned_count'] + (int) $row['violations_count'],
            'created_at' => $row['created_at'],
        ];
    }
    usort($plans, static function (array $a, array $b): int {
        return ($b['assigned_count'] <=> $a['assigned_count'])
            ?: ($a['violations_count'] <=> $b['violations_count'])
            ?: ($a['preference_violations'] <=> $b['preference_violations'])
            ?: strcmp($b['created_at'], $a['created_at']);
    });
    return $plans;
}

function saveSchedulePlan(PDO $pdo, array $plan): int {
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $pdo->exec('UPDATE schedule_plans SET is_active = 0');
        $statement = $pdo->prepare(
            'INSERT INTO schedule_plans
             (name, method, assignments_json, unassigned_json, assigned_count,
                  violations_count, preference_violations, data_hash, is_active)
             VALUES (:name, :method, :assignments, :unassigned, :assigned_count,
                     :violations_count, :preference_violations, :data_hash, 1)'
        );
        $statement->execute([
            'name' => $plan['name'],
            'method' => $plan['method'],
            'assignments' => json_encode($plan['assignments'], JSON_THROW_ON_ERROR),
            'unassigned' => json_encode($plan['unassigned_lessons'], JSON_THROW_ON_ERROR),
            'assigned_count' => $plan['assigned_count'],
            'violations_count' => $plan['violations_count'],
            'preference_violations' => $plan['preference_violations'] ?? 0,
            'data_hash' => $plan['data_hash'] ?? '',
        ]);
        $id = (int) $pdo->lastInsertId();
        saveScheduleAssignments($pdo, $plan['assignments']);
        if ($ownsTransaction) {
            $pdo->commit();
        }
        return $id;
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function activateSchedulePlan(PDO $pdo, int $planId): ?array {
    $plans = loadSchedulePlans($pdo);
    $selectedPlan = null;
    foreach ($plans as $plan) {
        if ($plan['id'] === $planId) {
            $selectedPlan = $plan;
            break;
        }
    }
    if (!$selectedPlan) {
        return null;
    }
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $pdo->exec('UPDATE schedule_plans SET is_active = 0');
        $statement = $pdo->prepare('UPDATE schedule_plans SET is_active = 1 WHERE id = :id');
        $statement->execute(['id' => $planId]);
        saveScheduleAssignments($pdo, $selectedPlan['assignments']);
        if ($ownsTransaction) {
            $pdo->commit();
        }
        $selectedPlan['is_active'] = true;
        return $selectedPlan;
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
