        <?php 
        $activeTab = defined('APP_PAGE') ? APP_PAGE : ($_GET['tab'] ?? 'schedule');
        
        // VIEW 1: TRANG NGƯỜI XẾP LỊCH (ADMIN)
        if ($activeTab === 'admin' && $viewRole !== 'admin'):
        ?>
            <section class="mx-auto max-w-2xl rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
                <i class="fa-solid fa-lock mb-3 text-3xl text-amber-600"></i>
                <h1 class="text-xl font-black text-slate-900">Khu vực dành cho quản trị viên</h1>
                <p class="mt-2 text-sm text-slate-600">Đăng nhập bằng tài khoản admin để tạo, sửa, xóa buổi học và quản lý lịch.</p>
                <a href="index.php" class="mt-5 inline-flex rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Về trang thời khóa biểu</a>
            </section>
        <?php
        elseif ($activeTab === 'admin' || $activeTab === 'schedule'): 
        ?>
            <div class="space-y-6">
                <?php if ($activeTab === 'schedule'): ?>
                    <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-900 px-6 py-8 text-white shadow-xl sm:px-10 sm:py-10">
                        <div class="absolute -right-10 -top-16 h-64 w-64 rounded-full border-[36px] border-white/5"></div>
                        <div class="absolute right-24 top-20 h-24 w-24 rounded-full bg-cyan-400/10 blur-2xl"></div>
                        <div class="relative grid gap-8 lg:grid-cols-[1.4fr_0.6fr] lg:items-center">
                            <div>
                                <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-cyan-200/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-cyan-100">
                                    <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                                    CỔNG THÔNG TIN ĐÀO TẠO
                                </div>
                                <h1 class="max-w-2xl text-3xl font-black leading-tight sm:text-5xl">Thời khóa biểu<br><span class="text-cyan-300">toàn trường</span></h1>
                                <p class="mt-4 max-w-xl text-sm leading-6 text-blue-100 sm:text-base">
                                    Tra cứu lịch học theo phòng, khung giờ, giảng viên và lớp học. Lịch được cập nhật từ hệ thống xếp thời khóa biểu.
                                </p>
                                <div class="mt-6 flex flex-wrap gap-3">
                                    <a href="teacher.php" class="rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-cyan-100">
                                        <i class="fa-solid fa-chalkboard-user mr-2 text-blue-700"></i>Tra cứu lịch giảng viên
                                    </a>
                                    <a href="class.php" class="rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/20">
                                        <i class="fa-solid fa-users mr-2"></i>Tra cứu lịch lớp
                                    </a>
                                </div>
                            </div>
                            <div class="relative grid grid-cols-2 gap-3">
                                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                    <div class="text-3xl font-black"><?= count($data['lessons']) ?></div>
                                    <div class="mt-1 text-xs font-medium text-blue-100">Buổi học</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                    <div class="text-3xl font-black"><?= count($data['rooms']) ?></div>
                                    <div class="mt-1 text-xs font-medium text-blue-100">Phòng học</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                    <div class="text-3xl font-black"><?= count($data['teachers']) ?></div>
                                    <div class="mt-1 text-xs font-medium text-blue-100">Giảng viên</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                                    <div class="text-3xl font-black"><?= count($data['slots']) ?></div>
                                    <div class="mt-1 text-xs font-medium text-blue-100">Buổi dạy trong tuần</div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Lịch học trong tuần</div>
                            <h2 class="mt-1 text-2xl font-black text-slate-900">Tra cứu theo phòng học</h2>
                            <p class="mt-1 text-sm text-slate-500">Chọn mục Giảng viên hoặc Lớp học để xem lịch cá nhân.</p>
                        </div>
                        <button onclick="document.getElementById('authModal').classList.remove('hidden')" class="rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-bold text-blue-800 shadow-sm transition hover:bg-blue-50">
                            <i class="fa-solid fa-user-plus mr-2"></i>Đăng ký tài khoản
                        </button>
                    </div>
                <?php else: ?>
                <!-- Top Action Header -->
                <div class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-r from-slate-950 via-slate-900 to-blue-950 p-6 text-white shadow-xl shadow-slate-900/10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="pointer-events-none absolute -right-8 -top-20 h-56 w-56 rounded-full border-[28px] border-cyan-300/10"></div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="text-[10px] font-extrabold text-cyan-200 uppercase tracking-[0.2em]">Control room · Quản trị đào tạo</span>
                        </div>
                        <h1 class="text-2xl font-black text-white mt-1 sm:text-3xl">Bảng điều khiển thời khóa biểu</h1>
                        <p class="text-xs text-slate-300 mt-1">Theo dõi lịch toàn trường, quản lý lớp học phần và đồng bộ đăng ký giảng viên.</p>
                    </div>

                    <!-- Action Controls -->
                    <div class="relative flex flex-wrap items-center gap-2">
                        <!-- Xếp thời khóa biểu -->
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="generate">
                            <select name="method" aria-label="Thuật toán xếp lịch" class="rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-xs font-bold text-white">
                                <option value="basic">Cơ bản: xếp lần lượt, chọn chỗ trống đầu tiên</option>
                                <option value="improved">Cải thiện: xếp buổi khó trước, chọn phòng vừa đủ</option>
                            </select>
                            <button type="submit" class="px-4 py-2.5 bg-cyan-300 hover:bg-cyan-200 text-slate-950 text-xs font-extrabold rounded-xl transition-all flex items-center gap-2 shadow-lg shadow-cyan-950/20">
                                <i class="fa-solid fa-arrows-rotate"></i>
                                <span>Tạo phương án</span>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($activeTab === 'admin'): ?>
                <!-- Stats Metric Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="bg-white p-4 rounded-xl border border-slate-200 border-t-2 border-t-blue-500 shadow-sm">
                        <div class="text-[11px] font-bold text-slate-400 uppercase">Buổi học</div>
                        <div class="text-2xl font-black text-slate-800 mt-1"><?= count($data['lessons']) ?></div>
                        <div class="text-[11px] text-blue-600 font-medium">2 buổi khóa cố định</div>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-200 border-t-2 border-t-violet-500 shadow-sm">
                        <div class="text-[11px] font-bold text-slate-400 uppercase">Giảng viên</div>
                        <div class="text-2xl font-black text-slate-800 mt-1"><?= count($data['teachers']) ?></div>
                        <div class="text-[11px] text-emerald-600 font-medium">Kèm lịch bận từng GV</div>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-200 border-t-2 border-t-cyan-500 shadow-sm">
                        <div class="text-[11px] font-bold text-slate-400 uppercase">Phòng học</div>
                        <div class="text-2xl font-black text-slate-800 mt-1"><?= count($data['rooms']) ?></div>
                        <div class="text-[11px] text-indigo-600 font-medium">3 LT + 2 Lab máy tính</div>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-slate-200 border-t-2 border-t-amber-500 shadow-sm">
                        <div class="text-[11px] font-bold text-slate-400 uppercase">Buổi dạy / tuần</div>
                        <div class="text-2xl font-black text-slate-800 mt-1"><?= count($data['slots']) ?></div>
                        <div class="text-[11px] text-slate-500 font-medium">Thứ 2 → Thứ 6 (mỗi ngày 1 sáng · 1 chiều)</div>
                    </div>
                    <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200 border-t-2 border-t-emerald-500 shadow-sm">
                        <div class="text-[11px] font-bold text-emerald-700 uppercase">Đã xếp thành công</div>
                        <div class="text-2xl font-black text-emerald-700 mt-1"><?= $currentVersion['assigned_count'] ?> / <?= count($data['lessons']) ?></div>
                        <div class="text-[11px] text-emerald-600 font-medium">Đáp ứng đủ ràng buộc</div>
                    </div>
                    <div class="bg-rose-50 p-4 rounded-xl border border-rose-200 border-t-2 border-t-rose-500 shadow-sm">
                        <div class="text-[11px] font-bold text-rose-700 uppercase">Chưa xếp / Bế tắc</div>
                        <div class="text-2xl font-black text-rose-700 mt-1"><?= $currentVersion['violations_count'] ?></div>
                        <div class="text-[11px] text-rose-600 font-medium">Báo rõ lý do xung đột</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sub-Tabs: Bảng lịch | Buổi chưa xếp -->
                <?php if ($activeTab === 'admin'): ?>
                <div class="border border-slate-200 border-b-0 bg-white rounded-t-2xl px-4 pt-2 shadow-sm flex flex-wrap gap-2">
                    <button onclick="openSubTab('sub-grid')" id="btn-sub-grid" class="subtab-btn px-4 py-3 text-xs font-bold border-b-2 border-blue-600 text-blue-600 flex items-center gap-2">
                        <i class="fa-solid fa-table-cells text-sm"></i>
                        <span>Thời Khóa Biểu Tổng Thể</span>
                    </button>
                    <button onclick="openSubTab('sub-unassigned')" id="btn-sub-unassigned" class="subtab-btn px-4 py-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 text-sm"></i>
                        <span>Buổi Chưa Xếp & Bế Tắc (<?= count($currentVersion['unassigned_lessons']) ?>)</span>
                    </button>
                    <button onclick="openSubTab('sub-lessons')" id="btn-sub-lessons" class="subtab-btn px-4 py-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-book-open text-sm"></i>
                        <span>Đăng ký / Quản lý buổi học</span>
                    </button>
                    <button onclick="openSubTab('sub-classes')" id="btn-sub-classes" class="subtab-btn px-4 py-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-users-rectangle text-sm"></i>
                        <span>Quản lý lớp học</span>
                    </button>
                    <button onclick="openSubTab('sub-resources')" id="btn-sub-resources" class="subtab-btn px-4 py-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-sitemap text-sm"></i>
                        <span>Giảng viên / Phòng / Khung giờ</span>
                    </button>
                    <button onclick="openSubTab('sub-plans')" id="btn-sub-plans" class="subtab-btn px-4 py-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-code-compare text-sm"></i>
                        <span>Phương án lịch</span>
                    </button>
                    <button onclick="openSubTab('sub-requests')" id="btn-sub-requests" class="subtab-btn px-4 py-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-clipboard-check text-sm"></i>
                        <span>Duyệt đăng ký môn học (<?= $pendingLessonRequestCount ?> chờ xử lý)</span>
                    </button>
                    <button onclick="openSubTab('sub-unavailability-requests')" id="btn-sub-unavailability-requests" class="subtab-btn px-4 py-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-calendar-check text-sm"></i>
                        <span>Duyệt lịch bận (<?= $pendingUnavailabilityRequestCount ?> chờ xử lý)</span>
                    </button>
                </div>
                <?php endif; ?>

                <?php if ($activeTab === 'admin'): ?>
                <section id="sub-lessons" class="subtab-content hidden rounded-b-xl border border-t-0 border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Quản lý buổi học</h2>
                            <p class="mt-1 text-xs text-slate-500">Thêm buổi học sẽ tự chạy xếp lịch; sửa hoặc xóa sẽ cập nhật lịch trong MySQL.</p>
                        </div>
                    </div>

                    <form id="lessonForm" method="POST" class="mb-6 grid gap-3 rounded-2xl border border-blue-100 bg-blue-50/50 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        <input type="hidden" name="action" value="lesson_save">
                        <input type="hidden" name="lesson_id" id="lesson_form_id" value="">
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="mb-1 block text-xs font-bold text-slate-700">Tên buổi học / môn học</label>
                            <select name="lesson_name" id="lesson_form_name" required onchange="updateLessonType()" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                <option value="" disabled selected>Chọn môn học có sẵn</option>
                                <?php foreach ($data['subjects'] as $subjectName => $subject): ?>
                                    <option value="<?= htmlspecialchars($subjectName) ?>" data-required-type="<?= htmlspecialchars($subject['required_type']) ?>">
                                        <?= htmlspecialchars($subjectName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Giảng viên</label>
                            <select name="teacher_id" id="lesson_form_teacher" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                <?php foreach ($data['teachers'] as $teacherId => $teacher): ?>
                                    <option value="<?= htmlspecialchars($teacherId) ?>">
                                        <?= htmlspecialchars($teacher['name']) ?> (<?= htmlspecialchars($teacherId) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Lớp học</label>
                            <select name="class_id" id="lesson_form_class" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                <?php foreach ($data['classes'] as $classId => $class): ?>
                                    <option value="<?= htmlspecialchars($classId) ?>"><?= htmlspecialchars($class['name']) ?> (<?= (int) $class['size'] ?> SV)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Loại phòng yêu cầu</label>
                            <input type="hidden" name="required_type" id="lesson_form_type" value="">
                            <select id="lesson_form_type_display" disabled class="w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2.5 text-sm">
                                <option value="">Tự động theo môn học</option>
                                <option value="LT">Lý thuyết</option>
                                <option value="LAB">Thực hành / Lab</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Khung giờ ưu tiên (không bắt buộc)</label>
                            <select name="preferred_slot_id" id="lesson_form_preferred_slot" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                <option value="">Không có ưu tiên</option>
                                <?php foreach ($data['slots'] as $sid => $slot): ?>
                                    <option value="<?= htmlspecialchars($sid) ?>"><?= htmlspecialchars($slot['day'] . ' · ' . $slot['period']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-[10px] text-slate-500">Ưu tiên là mong muốn, không chặn lịch nếu không thể thỏa mãn.</p>
                        </div>
                        <div class="flex flex-wrap items-end gap-2 sm:col-span-2 lg:col-span-3">
                            <button id="lesson_form_submit" type="submit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700">
                                <i class="fa-solid fa-calendar-plus mr-1"></i> Lưu và xếp lịch
                            </button>
                            <button type="button" onclick="resetLessonForm()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100">Làm mới</button>
                            <span class="text-[11px] text-slate-500">Sĩ số lấy tự động theo sĩ số của lớp đã chọn.</span>
                        </div>
                    </form>

                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full min-w-[760px] text-left text-xs">
                            <thead class="bg-slate-100 text-slate-600">
                                <tr>
                                    <th class="p-3">Mã / Tên buổi</th>
                                    <th class="p-3">Giảng viên</th>
                                    <th class="p-3">Lớp / Loại</th>
                                    <th class="p-3">Trạng thái</th>
                                    <th class="p-3 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php foreach ($data['lessons'] as $lessonId => $lesson): ?>
                                    <tr>
                                        <td class="p-3">
                                            <span class="font-mono font-bold text-blue-800"><?= htmlspecialchars($lessonId) ?></span>
                                            <div class="mt-1 font-bold text-slate-800"><?= htmlspecialchars($lesson['name']) ?></div>
                                        </td>
                                        <td class="p-3"><?= htmlspecialchars($data['teachers'][$lesson['teacher_id']]['name'] ?? $lesson['teacher_id']) ?></td>
                                        <td class="p-3">
                                            <?= htmlspecialchars($data['classes'][$lesson['class_id']]['name'] ?? $lesson['class_id']) ?>
                                            <div class="mt-1 text-slate-500"><?= htmlspecialchars($lesson['required_type']) ?> · <?= (int) $lesson['size'] ?> SV</div>
                                        </td>
                                        <td class="p-3">
                                            <?php if (!empty($lesson['is_locked'])): ?>
                                                <span class="rounded-full bg-amber-100 px-2 py-1 font-bold text-amber-800">Đã khóa</span>
                                            <?php elseif (in_array($lessonId, array_column($currentVersion['assignments'], 'lesson_id'), true)): ?>
                                                <span class="rounded-full bg-emerald-100 px-2 py-1 font-bold text-emerald-800">Đã xếp</span>
                                            <?php else: ?>
                                                <span class="rounded-full bg-rose-100 px-2 py-1 font-bold text-rose-800">Chưa xếp</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" <?= !empty($lesson['is_locked']) ? 'disabled' : '' ?> onclick="editLesson(<?= htmlspecialchars(json_encode([
                                                    'id' => $lessonId,
                                                    'name' => $lesson['name'],
                                                    'teacher_id' => $lesson['teacher_id'],
                                                    'class_id' => $lesson['class_id'],
                                                    'required_type' => $lesson['required_type'],
                                                    'preferred_slot' => $lesson['preferred_slot'] ?? '',
                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>)" class="rounded-lg border border-blue-200 px-3 py-2 font-bold text-blue-700 hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-40">
                                                    Sửa
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Xóa buổi học <?= htmlspecialchars($lessonId, ENT_QUOTES) ?> khỏi hệ thống?')">
                                                    <input type="hidden" name="action" value="lesson_delete">
                                                    <input type="hidden" name="lesson_id" value="<?= htmlspecialchars($lessonId) ?>">
                                                    <button type="submit" <?= !empty($lesson['is_locked']) ? 'disabled' : '' ?> class="rounded-lg border border-rose-200 px-3 py-2 font-bold text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40">Xóa</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeTab === 'admin'): ?>
                <section id="sub-classes" class="subtab-content hidden rounded-b-xl border border-t-0 border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h2 class="text-lg font-black text-slate-900">Quản lý lớp học và ngành đào tạo</h2>
                        <p class="mt-1 text-xs text-slate-500">Thêm lớp mới hoặc cập nhật sĩ số, ngành học. Lịch học sẽ được xếp lại khi thay đổi sĩ số.</p>
                    </div>
                    <form id="classForm" method="POST" class="mb-6 grid gap-3 rounded-2xl border border-violet-100 bg-violet-50/50 p-4 sm:grid-cols-2 lg:grid-cols-4">
                        <input type="hidden" name="action" value="class_save">
                        <input type="hidden" name="class_id" id="class_form_id" value="">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-bold text-slate-700">Tên lớp</label>
                            <input type="text" name="class_name" id="class_form_name" required maxlength="150" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="Ví dụ: CNTT01">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Ngành học</label>
                            <input type="text" name="major" id="class_form_major" required maxlength="150" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="Công nghệ thông tin">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Sĩ số</label>
                            <input type="number" name="student_count" id="class_form_size" required min="1" max="9999" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="35">
                        </div>
                        <div class="flex flex-wrap items-end gap-2 sm:col-span-2 lg:col-span-4">
                            <button id="class_form_submit" type="submit" class="rounded-xl bg-violet-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-violet-800">
                                <i class="fa-solid fa-plus mr-1"></i> Thêm lớp
                            </button>
                            <button type="button" onclick="resetClassForm()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100">Làm mới</button>
                            <span class="text-[11px] text-slate-500">Lớp CNTT01 đã được thêm vào danh sách để giảng viên đăng ký môn.</span>
                        </div>
                    </form>
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full min-w-[720px] text-left text-xs">
                            <thead class="bg-slate-100 text-slate-600">
                                <tr>
                                    <th class="p-3">Mã lớp</th>
                                    <th class="p-3">Lớp học</th>
                                    <th class="p-3">Ngành</th>
                                    <th class="p-3">Sĩ số</th>
                                    <th class="p-3 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php foreach ($data['classes'] as $classId => $class): ?>
                                    <tr>
                                        <td class="p-3 font-mono font-bold text-violet-800"><?= htmlspecialchars($classId) ?></td>
                                        <td class="p-3 font-bold text-slate-800"><?= htmlspecialchars($class['name']) ?></td>
                                        <td class="p-3"><?= htmlspecialchars($class['major']) ?></td>
                                        <td class="p-3"><?= (int) $class['size'] ?> sinh viên</td>
                                        <td class="p-3">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" onclick="editClass(<?= htmlspecialchars(json_encode([
                                                    'id' => $classId,
                                                    'name' => $class['name'],
                                                    'major' => $class['major'],
                                                    'size' => $class['size'],
                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>)" class="rounded-lg border border-violet-200 px-3 py-2 font-bold text-violet-700 hover:bg-violet-50">Sửa</button>
                                                <form method="POST" onsubmit="return confirm('Xóa lớp <?= htmlspecialchars($classId, ENT_QUOTES) ?>? Chỉ xóa được lớp chưa có môn học, yêu cầu đăng ký hoặc tài khoản liên kết.')">
                                                    <input type="hidden" name="action" value="class_delete">
                                                    <input type="hidden" name="class_id" value="<?= htmlspecialchars($classId) ?>">
                                                    <button type="submit" class="rounded-lg border border-rose-200 px-3 py-2 font-bold text-rose-700 hover:bg-rose-50">Xóa</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($activeTab === 'admin'): ?>
                <section id="sub-resources" class="subtab-content hidden space-y-5 rounded-b-xl border border-t-0 border-slate-200 bg-white p-6 shadow-sm">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Quản lý tài nguyên và lịch bận</h2>
                        <p class="mt-1 text-xs text-slate-500">Ràng buộc bắt buộc: không trùng lịch, đúng loại phòng/sức chứa và giảng viên không bận.</p>
                    </div>
                    <div class="grid gap-5 xl:grid-cols-2">
                        <article class="rounded-xl border border-slate-200 p-4">
                            <h3 class="mb-3 font-bold text-slate-800">Giảng viên</h3>
                            <form id="teacherForm" method="POST" class="mb-4 grid gap-2 sm:grid-cols-2">
                                <input type="hidden" name="action" value="teacher_save">
                                <input type="hidden" name="teacher_id" id="teacher_form_id">
                                <input name="teacher_name" id="teacher_form_name" required maxlength="150" placeholder="Họ tên" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <input name="email" id="teacher_form_email" type="email" required maxlength="150" placeholder="Email" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <input name="subject" id="teacher_form_subject" required maxlength="150" placeholder="Chuyên ngành" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <div class="flex gap-2"><button id="teacher_form_submit" class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-bold text-white">Thêm giảng viên</button><button type="button" onclick="resetResourceForm('teacherForm', 'teacher_form_id', 'teacher_form_submit', 'Thêm giảng viên')" class="rounded-lg border px-3 py-2 text-xs">Mới</button></div>
                            </form>
                            <div class="max-h-60 overflow-auto">
                                <table class="w-full text-left text-xs">
                                    <thead><tr class="text-slate-500"><th class="p-2">Giảng viên</th><th class="p-2">Chuyên ngành</th><th class="p-2">Thao tác</th></tr></thead>
                                    <tbody class="divide-y">
                                    <?php foreach ($data['teachers'] as $teacherId => $teacher): ?>
                                        <tr>
                                            <td class="p-2"><?= htmlspecialchars($teacher['name']) ?><div class="text-slate-400"><?= htmlspecialchars($teacher['email']) ?></div></td>
                                            <td class="p-2"><?= htmlspecialchars($teacher['subject']) ?></td>
                                            <td class="p-2">
                                                <button type="button" onclick="editTeacher(<?= htmlspecialchars(json_encode(['id' => $teacherId, 'name' => $teacher['name'], 'email' => $teacher['email'], 'subject' => $teacher['subject']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>)" class="text-blue-700">Sửa</button>
                                                <form method="POST" class="inline" onsubmit="return confirm('Xóa giảng viên này? Chỉ xóa được hồ sơ chưa được sử dụng.')">
                                                    <input type="hidden" name="action" value="teacher_delete"><input type="hidden" name="teacher_id" value="<?= htmlspecialchars($teacherId) ?>">
                                                    <button class="ml-2 text-rose-700">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </article>

                        <article class="rounded-xl border border-slate-200 p-4">
                            <h3 class="mb-3 font-bold text-slate-800">Phòng học</h3>
                            <form id="roomForm" method="POST" class="mb-4 grid gap-2 sm:grid-cols-2">
                                <input type="hidden" name="action" value="room_save"><input type="hidden" name="room_id" id="room_form_id">
                                <input name="room_name" id="room_form_name" required maxlength="150" placeholder="Tên phòng" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <input name="capacity" id="room_form_capacity" required type="number" min="1" max="9999" placeholder="Sức chứa" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <select name="room_type" id="room_form_type" class="rounded-lg border border-slate-300 px-3 py-2 text-xs"><option value="LT">Lý thuyết</option><option value="LAB">Thực hành / Lab</option></select>
                                <div class="flex gap-2"><button id="room_form_submit" class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-bold text-white">Thêm phòng</button><button type="button" onclick="resetResourceForm('roomForm', 'room_form_id', 'room_form_submit', 'Thêm phòng')" class="rounded-lg border px-3 py-2 text-xs">Mới</button></div>
                            </form>
                            <div class="max-h-60 overflow-auto">
                                <table class="w-full text-left text-xs"><thead><tr class="text-slate-500"><th class="p-2">Phòng</th><th class="p-2">Loại / Sức chứa</th><th class="p-2">Thao tác</th></tr></thead><tbody class="divide-y">
                                <?php foreach ($data['rooms'] as $roomId => $room): ?>
                                    <tr><td class="p-2"><?= htmlspecialchars($room['name']) ?> <span class="text-slate-400"><?= htmlspecialchars($roomId) ?></span></td><td class="p-2"><?= htmlspecialchars($room['type']) ?> / <?= (int) $room['capacity'] ?></td><td class="p-2">
                                        <button type="button" onclick="editRoom(<?= htmlspecialchars(json_encode(['id' => $roomId, 'name' => $room['name'], 'capacity' => $room['capacity'], 'type' => $room['type']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>)" class="text-blue-700">Sửa</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Xóa phòng này?')"><input type="hidden" name="action" value="room_delete"><input type="hidden" name="room_id" value="<?= htmlspecialchars($roomId) ?>"><button class="ml-2 text-rose-700">Xóa</button></form>
                                    </td></tr>
                                <?php endforeach; ?>
                                </tbody></table>
                            </div>
                        </article>

                        <article class="rounded-xl border border-slate-200 p-4">
                            <h3 class="mb-3 font-bold text-slate-800">Khung giờ</h3>
                            <form id="slotForm" method="POST" class="mb-4 grid gap-2 sm:grid-cols-2">
                                <input type="hidden" name="action" value="slot_save"><input type="hidden" name="slot_id" id="slot_form_id">
                                <input name="day_name" id="slot_form_day_name" required maxlength="20" placeholder="Tên ngày (VD: Thứ 7)" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <input name="day_order" id="slot_form_day_order" required type="number" min="1" max="7" placeholder="Thứ tự ngày (1-7)" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <select name="period_index" id="slot_form_period" class="rounded-lg border border-slate-300 px-3 py-2 text-xs"><option value="1">Sáng</option><option value="2">Chiều</option></select>
                                <div class="flex gap-2"><button id="slot_form_submit" class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-bold text-white">Thêm khung giờ</button><button type="button" onclick="resetResourceForm('slotForm', 'slot_form_id', 'slot_form_submit', 'Thêm khung giờ')" class="rounded-lg border px-3 py-2 text-xs">Mới</button></div>
                            </form>
                            <div class="max-h-60 overflow-auto">
                                <table class="w-full text-left text-xs"><thead><tr class="text-slate-500"><th class="p-2">Ngày</th><th class="p-2">Buổi</th><th class="p-2">Thao tác</th></tr></thead><tbody class="divide-y">
                                <?php foreach ($data['slots'] as $slotId => $slot): ?>
                                    <tr><td class="p-2"><?= htmlspecialchars($slot['day']) ?> (<?= (int) $slot['day_order'] ?>)</td><td class="p-2"><?= htmlspecialchars($slot['period']) ?></td><td class="p-2">
                                        <button type="button" onclick="editSlot(<?= htmlspecialchars(json_encode(['id' => $slotId, 'day' => $slot['day'], 'day_order' => $slot['day_order'], 'period_index' => $slot['period_index']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>)" class="text-blue-700">Sửa</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Xóa khung giờ này? Chỉ xóa được khi không được lịch nào tham chiếu.')"><input type="hidden" name="action" value="slot_delete"><input type="hidden" name="slot_id" value="<?= htmlspecialchars($slotId) ?>"><button class="ml-2 text-rose-700">Xóa</button></form>
                                    </td></tr>
                                <?php endforeach; ?>
                                </tbody></table>
                            </div>
                        </article>

                        <article class="rounded-xl border border-slate-200 p-4">
                            <h3 class="mb-3 font-bold text-slate-800">Lịch bận giảng viên (ràng buộc bắt buộc)</h3>
                            <form id="unavailabilityForm" method="POST" class="mb-4 grid gap-2 sm:grid-cols-2">
                                <input type="hidden" name="action" value="unavailability_save"><input type="hidden" name="old_teacher_id" id="busy_old_teacher"><input type="hidden" name="old_slot_id" id="busy_old_slot">
                                <select name="teacher_id" id="busy_teacher" required class="rounded-lg border border-slate-300 px-3 py-2 text-xs"><?php foreach ($data['teachers'] as $teacherId => $teacher): ?><option value="<?= htmlspecialchars($teacherId) ?>"><?= htmlspecialchars($teacher['name']) ?></option><?php endforeach; ?></select>
                                <select name="slot_id" id="busy_slot" required class="rounded-lg border border-slate-300 px-3 py-2 text-xs"><?php foreach ($data['slots'] as $slotId => $slot): ?><option value="<?= htmlspecialchars($slotId) ?>"><?= htmlspecialchars($slot['day'] . ' · ' . $slot['period']) ?></option><?php endforeach; ?></select>
                                <input name="reason" id="busy_reason" required maxlength="200" placeholder="Lý do bận" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <div class="flex gap-2"><button id="busy_submit" class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-bold text-white">Thêm lịch bận</button><button type="button" onclick="resetUnavailabilityForm()" class="rounded-lg border px-3 py-2 text-xs">Mới</button></div>
                            </form>
                            <div class="max-h-60 overflow-auto">
                                <table class="w-full text-left text-xs"><thead><tr class="text-slate-500"><th class="p-2">Giảng viên / Khung giờ</th><th class="p-2">Lý do</th><th class="p-2">Thao tác</th></tr></thead><tbody class="divide-y">
                                <?php foreach ($data['unavailabilities'] as $busy): ?>
                                    <tr><td class="p-2"><?= htmlspecialchars($data['teachers'][$busy['teacher_id']]['name'] ?? $busy['teacher_id']) ?><div class="text-slate-400"><?= htmlspecialchars(($data['slots'][$busy['slot_id']]['day'] ?? '') . ' · ' . ($data['slots'][$busy['slot_id']]['period'] ?? '')) ?></div></td><td class="p-2"><?= htmlspecialchars($busy['reason']) ?></td><td class="p-2">
                                        <button type="button" onclick="editUnavailability(<?= htmlspecialchars(json_encode($busy, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>)" class="text-blue-700">Sửa</button>
                                        <form method="POST" class="inline"><input type="hidden" name="action" value="unavailability_delete"><input type="hidden" name="teacher_id" value="<?= htmlspecialchars($busy['teacher_id']) ?>"><input type="hidden" name="slot_id" value="<?= htmlspecialchars($busy['slot_id']) ?>"><button class="ml-2 text-rose-700">Xóa</button></form>
                                    </td></tr>
                                <?php endforeach; ?>
                                </tbody></table>
                            </div>
                        </article>
                    </div>
                </section>

                <section id="sub-plans" class="subtab-content hidden rounded-b-xl border border-t-0 border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <h2 class="text-lg font-black text-slate-900">So sánh và sử dụng phương án</h2>
                        <p class="mt-1 text-xs text-slate-500">Mỗi lần bấm “Tạo phương án”, hệ thống lưu lại một lịch để so sánh. Hãy ưu tiên phương án xếp được nhiều buổi, ít buổi chưa xếp và ít khung giờ mong muốn chưa đáp ứng. Chọn “Sử dụng” để áp dụng lịch.</p>
                        <p class="mt-2 text-[11px] leading-5 text-slate-500">
                            <strong>Thuật toán cơ bản:</strong> xét từng buổi lần lượt và dùng chỗ phù hợp đầu tiên.
                            <strong>Thuật toán cải thiện:</strong> xếp buổi khó trước và chọn phòng có sức chứa vừa đủ.
                            Buổi chưa xếp là buổi không tìm được lịch hợp lệ; ưu tiên chưa đạt là mong muốn mềm, không phải lỗi bắt buộc.
                        </p>
                    </div>
                    <?php $currentDataHash = scheduleDataFingerprint($data); ?>
                    <?php if ($schedulePlans): ?>
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full min-w-[760px] text-left text-xs">
                                <thead class="bg-slate-100 text-slate-600"><tr><th class="p-3">Phương án</th><th class="p-3">Cách xếp lịch</th><th class="p-3">Buổi đã xếp</th><th class="p-3">Buổi chưa xếp</th><th class="p-3">Mong muốn chưa đáp ứng</th><th class="p-3">Thao tác</th></tr></thead>
                                <tbody class="divide-y divide-slate-200">
                                <?php foreach ($schedulePlans as $plan): ?>
                                    <?php $planIsStale = $plan['data_hash'] !== $currentDataHash; ?>
                                    <?php $displayPlanName = str_replace(
                                        ['(Greedy First-Fit)', '(MRV + Best-Fit Room)'],
                                        ['(xếp lần lượt)', '(ưu tiên buổi khó, chọn phòng vừa đủ)'],
                                        $plan['name']
                                    ); ?>
                                    <?php
                                    $planAssignments = $plan['assignments'];
                                    usort($planAssignments, static function ($a, $b) use ($data): int {
                                        $slotA = $data['slots'][$a['slot_id']] ?? [];
                                        $slotB = $data['slots'][$b['slot_id']] ?? [];
                                        return [
                                            $slotA['day_order'] ?? 0,
                                            $slotA['period_index'] ?? 0,
                                            $a['lesson_id'],
                                        ] <=> [
                                            $slotB['day_order'] ?? 0,
                                            $slotB['period_index'] ?? 0,
                                            $b['lesson_id'],
                                        ];
                                    });
                                    ?>
                                    <tr class="<?= $plan['is_active'] ? 'bg-emerald-50' : '' ?>">
                                        <td class="p-3 font-bold"><?= htmlspecialchars($displayPlanName) ?><?= $plan['is_active'] ? ' · ĐANG ÁP DỤNG' : '' ?><?= $planIsStale ? ' · DỮ LIỆU ĐÃ THAY ĐỔI' : '' ?><div class="mt-1 text-slate-400"><?= htmlspecialchars($plan['created_at']) ?></div></td>
                                        <td class="p-3"><?= $plan['method'] === 'improved' ? 'Buổi khó trước, phòng vừa đủ' : 'Xếp lần lượt, chỗ phù hợp đầu tiên' ?></td>
                                        <td class="p-3 font-bold text-emerald-700"><?= (int) $plan['assigned_count'] ?> / <?= (int) $plan['total_lessons'] ?></td>
                                        <td class="p-3 text-rose-700"><?= (int) $plan['violations_count'] ?></td>
                                        <td class="p-3 text-amber-700"><?= (int) $plan['preference_violations'] ?></td>
                                        <td class="p-3">
                                            <?php if (!$plan['is_active']): ?>
                                                <form method="POST"><input type="hidden" name="action" value="activate_plan"><input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>"><button <?= $planIsStale ? 'disabled title="Dữ liệu đã thay đổi; tạo phương án mới để áp dụng"' : '' ?> class="rounded-lg bg-emerald-700 px-3 py-2 font-bold text-white disabled:cursor-not-allowed disabled:opacity-40"><?= $planIsStale ? 'Không còn phù hợp' : 'Sử dụng' ?></button></form>
                                            <?php else: ?><span class="font-bold text-emerald-700">Đang áp dụng</span><?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" class="px-3 pb-3">
                                            <details class="rounded-lg border border-slate-200 bg-slate-50">
                                                <summary class="cursor-pointer px-3 py-2 text-xs font-bold text-blue-700">
                                                    Xem lịch chi tiết của phương án này (<?= count($planAssignments) ?> buổi)
                                                </summary>
                                                <div class="overflow-x-auto border-t border-slate-200">
                                                    <table class="w-full min-w-[600px] text-left text-[11px]">
                                                        <thead class="bg-white text-slate-500">
                                                            <tr>
                                                                <th class="px-3 py-2">Buổi học</th>
                                                                <th class="px-3 py-2">Ngày / buổi</th>
                                                                <th class="px-3 py-2">Phòng</th>
                                                                <th class="px-3 py-2">Giảng viên</th>
                                                                <th class="px-3 py-2">Lớp</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-slate-200">
                                                            <?php foreach ($planAssignments as $assignment): ?>
                                                                <?php
                                                                $assignedLesson = $data['lessons'][$assignment['lesson_id']] ?? null;
                                                                $assignedSlot = $data['slots'][$assignment['slot_id']] ?? null;
                                                                if (!$assignedLesson || !$assignedSlot) {
                                                                    continue;
                                                                }
                                                                ?>
                                                                <tr>
                                                                    <td class="px-3 py-2 font-semibold text-slate-800">
                                                                        <?= htmlspecialchars($assignment['lesson_id'] . ' · ' . $assignedLesson['name']) ?>
                                                                        <?= !empty($assignment['is_locked']) ? ' · Đã khóa' : '' ?>
                                                                    </td>
                                                                    <td class="px-3 py-2"><?= htmlspecialchars($assignedSlot['day'] . ' · ' . $assignedSlot['period']) ?></td>
                                                                    <td class="px-3 py-2"><?= htmlspecialchars($data['rooms'][$assignment['room_id']]['name'] ?? $assignment['room_id']) ?></td>
                                                                    <td class="px-3 py-2"><?= htmlspecialchars($data['teachers'][$assignedLesson['teacher_id']]['name'] ?? $assignedLesson['teacher_id']) ?></td>
                                                                    <td class="px-3 py-2"><?= htmlspecialchars($data['classes'][$assignedLesson['class_id']]['name'] ?? $assignedLesson['class_id']) ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-slate-500">Chưa lưu phương án nào. Tạo phương án bằng nút trên bảng điều khiển.</p>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <?php if ($activeTab === 'admin'): ?>
                <section id="sub-requests" class="subtab-content hidden rounded-b-xl border border-t-0 border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h2 class="text-lg font-black text-slate-900">Đăng ký môn học từ giảng viên</h2>
                        <p class="mt-1 text-xs text-slate-500">Giảng viên chọn môn có sẵn và gửi yêu cầu. Chỉ sau khi Admin duyệt, hệ thống mới tạo lớp học phần và xếp lịch.</p>
                    </div>
                    <?php if ($lessonRequests): ?>
                        <div class="space-y-3">
                            <?php foreach ($lessonRequests as $request): ?>
                                <article class="flex flex-col justify-between gap-4 rounded-xl border <?= $request['status'] === 'pending' ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200 bg-slate-50/50' ?> p-4 lg:flex-row lg:items-center">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-bold text-slate-900"><?= htmlspecialchars($request['lesson_name']) ?></h3>
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold <?= $request['status'] === 'pending' ? 'bg-amber-100 text-amber-800' : ($request['status'] === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') ?>">
                                                <?= $request['status'] === 'pending' ? 'Đang chờ duyệt' : ($request['status'] === 'approved' ? 'Đã duyệt' : 'Đã từ chối') ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-600">
                                            Giảng viên: <strong><?= htmlspecialchars($request['teacher_name']) ?></strong>
                                            · Lớp: <strong><?= htmlspecialchars($request['class_name']) ?></strong>
                                            · <?= $request['required_type'] === 'LAB' ? 'Thực hành / Lab' : 'Lý thuyết' ?>
                                        </p>
                                        <p class="text-xs text-slate-600">
                                            Ngày/buổi mong muốn:
                                            <strong><?= htmlspecialchars(($data['slots'][$request['preferred_slot_id']]['day'] ?? 'Không xác định') . ' · ' . ($data['slots'][$request['preferred_slot_id']]['period'] ?? '')) ?></strong>
                                        </p>
                                        <p class="text-[11px] text-slate-400">Gửi lúc <?= htmlspecialchars($request['created_at']) ?></p>
                                        <?php if ($request['admin_note']): ?>
                                            <p class="text-xs text-slate-600">Ghi chú: <?= htmlspecialchars($request['admin_note']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <form method="POST" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                            <input type="hidden" name="action" value="lesson_request_review">
                                            <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                            <input type="text" name="admin_note" maxlength="500" placeholder="Ghi chú (không bắt buộc)" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                                            <button name="decision" value="approve" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                                                <i class="fa-solid fa-check mr-1"></i>Duyệt & xếp lịch
                                            </button>
                                            <button name="decision" value="reject" class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">
                                                Từ chối
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500">
                            Chưa có yêu cầu đăng ký môn học nào.
                        </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <?php if ($activeTab === 'admin'): ?>
                <section id="sub-unavailability-requests" class="subtab-content hidden rounded-b-xl border border-t-0 border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-5">
                        <h2 class="text-lg font-black text-slate-900">Yêu cầu đăng ký lịch bận</h2>
                        <p class="mt-1 text-xs text-slate-500">Lịch bận chỉ trở thành ràng buộc xếp lịch sau khi Admin duyệt.</p>
                    </div>
                    <?php if ($unavailabilityRequests): ?>
                        <div class="space-y-3">
                            <?php foreach ($unavailabilityRequests as $request): ?>
                                <?php $requestSlot = $data['slots'][$request['slot_id']] ?? null; ?>
                                <article class="flex flex-col justify-between gap-4 rounded-xl border <?= $request['status'] === 'pending' ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200 bg-slate-50/50' ?> p-4 lg:flex-row lg:items-center">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-bold text-slate-900"><?= htmlspecialchars($request['teacher_name']) ?></h3>
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold <?= $request['status'] === 'pending' ? 'bg-amber-100 text-amber-800' : ($request['status'] === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') ?>">
                                                <?= $request['status'] === 'pending' ? 'Đang chờ duyệt' : ($request['status'] === 'approved' ? 'Đã duyệt' : 'Đã từ chối') ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-600">
                                            Khung giờ: <strong><?= htmlspecialchars($requestSlot ? $requestSlot['day'] . ' · ' . $requestSlot['period'] : 'Không còn tồn tại') ?></strong>
                                            · Lý do: <?= htmlspecialchars($request['reason']) ?>
                                        </p>
                                        <p class="text-[11px] text-slate-400">Gửi lúc <?= htmlspecialchars($request['created_at']) ?></p>
                                        <?php if ($request['admin_note']): ?>
                                            <p class="text-xs text-slate-600">Ghi chú Admin: <?= htmlspecialchars($request['admin_note']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <form method="POST" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                            <input type="hidden" name="action" value="unavailability_request_review">
                                            <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                            <input type="text" name="admin_note" maxlength="500" placeholder="Ghi chú (không bắt buộc)" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                                            <button name="decision" value="approve" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">
                                                <i class="fa-solid fa-check mr-1"></i>Duyệt lịch bận
                                            </button>
                                            <button name="decision" value="reject" class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">
                                                Từ chối
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500">
                            Chưa có yêu cầu đăng ký lịch bận nào.
                        </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <!-- SUBTAB 1: THỜI KHÓA BIỂU TỔNG THỂ -->
                <div id="sub-grid" class="subtab-content bg-white rounded-b-xl border border-t-0 border-slate-200 p-4 shadow-sm overflow-hidden <?= $activeTab === 'schedule' ? 'rounded-t-xl border-t' : '' ?>">
                    <div class="flex justify-between items-center mb-3 text-xs text-slate-500">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></span> Buổi đã khóa</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-blue-100 border border-blue-300"></span> Phòng Lý thuyết (LT)</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-purple-100 border border-purple-300"></span> Phòng Lab Máy tính (LAB)</span>
                        </div>
                        <?php if ($activeTab === 'admin'): ?><div>
                            <i class="fa-solid fa-circle-info mr-1"></i> Bấm <i class="fa-solid fa-lock text-amber-500"></i> để Khóa/Mở khóa hoặc <i class="fa-solid fa-arrow-right-arrow-left text-blue-500"></i> để đổi lịch
                        </div><?php endif; ?>
                    </div>

                    <!-- Timetable Matrix Table -->
                    <div class="overflow-x-auto custom-scrollbar border border-slate-200 rounded-xl">
                        <?php $slotCountByDay = array_count_values(array_column($data['slots'], 'day')); ?>
                        <table class="w-full text-left text-xs border-collapse min-w-[900px]">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700 uppercase font-extrabold border-b border-slate-200 text-center">
                                    <th class="p-3 border-r border-slate-200 w-24">Ngày</th>
                                    <th class="p-3 border-r border-slate-200 w-36">Khung Giờ</th>
                                    <?php foreach ($data['rooms'] as $rid => $room): ?>
                                        <th class="p-3 border-r border-slate-200 last:border-r-0 min-w-[150px]">
                                            <div class="font-bold text-slate-900"><?= $room['name'] ?></div>
                                            <div class="text-[10px] font-normal mt-0.5 text-slate-500">
                                                <span class="px-1.5 py-0.2 rounded font-semibold <?= $room['type'] === 'LAB' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>">
                                                    <?= $room['type'] ?>
                                                </span>
                                                • Sức chứa: <strong><?= $room['capacity'] ?></strong>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php 
                                $currentDay = '';
                                foreach ($data['slots'] as $sid => $slot): 
                                ?>
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <?php if ($slot['day'] !== $currentDay): $currentDay = $slot['day']; ?>
                                            <td rowspan="<?= (int) ($slotCountByDay[$slot['day']] ?? 1) ?>" class="p-3 text-center font-extrabold bg-slate-50 border-r border-b border-slate-200 text-slate-800 align-middle">
                                                <div class="text-sm font-bold text-blue-900"><?= $slot['day'] ?></div>
                                                <div class="text-[10px] text-slate-400 font-normal"><?= (int) ($slotCountByDay[$slot['day']] ?? 1) ?> khung giờ</div>
                                            </td>
                                        <?php endif; ?>

                                        <td class="p-2.5 text-center font-semibold bg-slate-50/50 border-r border-slate-200 text-slate-700 <?= $slot['period_index'] === 2 ? 'border-t-4 border-t-slate-300' : '' ?>">
                                            <div class="font-bold text-slate-800 text-[11px]"><?= $slot['period'] ?></div>
                                            <span class="text-[10px] text-slate-400 font-mono">(<?= $sid ?>)</span>
                                        </td>

                                        <?php foreach ($data['rooms'] as $rid => $room): 
                                            $cell = $scheduleMatrix[$sid][$rid] ?? null;
                                        ?>
                                            <td class="p-2 border-r border-slate-200 last:border-r-0 align-top <?= $slot['period_index'] === 2 ? 'border-t-4 border-t-slate-300' : '' ?>">
                                                <?php if ($cell): ?>
                                                    <div class="p-2.5 rounded-xl border relative shadow-xs transition-all hover:shadow-md <?= $cell['is_locked'] ? 'bg-amber-50/90 border-amber-300 text-amber-950' : ($room['type'] === 'LAB' ? 'bg-purple-50/70 border-purple-200 text-purple-950' : 'bg-blue-50/70 border-blue-200 text-blue-950') ?>">
                                                        <div class="flex justify-between items-start gap-1">
                                                            <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-white/80 border border-slate-200">
                                                                <?= $cell['lesson']['id'] ?>
                                                            </span>

                                                            <?php if ($activeTab === 'admin'): ?>
                                                            <!-- Toggle Lock form -->
                                                            <div class="flex items-center gap-1">
                                                                <form method="POST" class="inline">
                                                                    <input type="hidden" name="action" value="toggle_lock">
                                                                    <input type="hidden" name="lesson_id" value="<?= $cell['lesson']['id'] ?>">
                                                                    <button type="submit" title="<?= $cell['is_locked'] ? 'Buổi đã KHÓA. Bấm để mở khóa' : 'Bấm để KHÓA buổi này' ?>" class="p-1 rounded hover:bg-white/80 transition-colors <?= $cell['is_locked'] ? 'text-amber-600 font-bold' : 'text-slate-400 hover:text-amber-500' ?>">
                                                                        <i class="fa-solid <?= $cell['is_locked'] ? 'fa-lock text-xs' : 'fa-lock-open text-xs' ?>"></i>
                                                                    </button>
                                                                </form>

                                                                <button type="button" onclick="openAdjustModal('<?= $cell['lesson']['id'] ?>', '<?= htmlspecialchars($cell['lesson']['name']) ?>', '<?= $sid ?>', '<?= $rid ?>', <?= $cell['is_locked'] ? 'true' : 'false' ?>)" title="Đổi phòng / Khung giờ" class="p-1 rounded text-slate-400 hover:text-blue-600 hover:bg-white/80 transition-colors">
                                                                    <i class="fa-solid fa-arrow-right-arrow-left text-xs"></i>
                                                                </button>
                                                            </div>
                                                            <?php elseif ($cell['is_locked']): ?>
                                                                <i class="fa-solid fa-lock text-amber-600 text-xs" title="Buổi đã khóa"></i>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="font-bold text-xs mt-1 leading-snug line-clamp-2">
                                                            <?= htmlspecialchars($cell['lesson']['name']) ?>
                                                        </div>

                                                        <div class="mt-2 space-y-0.5 text-[11px]">
                                                            <div class="flex items-center gap-1 text-slate-700">
                                                                <i class="fa-solid fa-chalkboard-user text-[10px] text-slate-400"></i>
                                                                <span class="truncate font-medium"><?= htmlspecialchars($cell['teacher']['name'] ?? $cell['lesson']['teacher_id']) ?></span>
                                                            </div>
                                                            <div class="flex items-center gap-1 text-slate-700">
                                                                <i class="fa-solid fa-users text-[10px] text-slate-400"></i>
                                                                <span class="truncate"><?= htmlspecialchars($cell['class']['name'] ?? $cell['lesson']['class_id']) ?> (<?= $cell['lesson']['size'] ?> SV)</span>
                                                            </div>
                                                        </div>

                                                        <?php if ($cell['is_locked']): ?>
                                                            <div class="mt-1.5 text-[10px] font-bold text-amber-700 flex items-center gap-1">
                                                                <i class="fa-solid fa-shield-halved text-[9px]"></i> Cố định đã khóa
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="h-full min-h-[70px] rounded-lg border border-dashed border-slate-200 flex items-center justify-center text-slate-300 text-[11px] hover:border-slate-300 hover:bg-slate-50 transition-colors">
                                                        <span>Trống</span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SUBTAB 3: BUỔI CHƯA XẾP & BẾ TẮC (PHÉP THỬ 4) -->
                <?php if ($activeTab === 'admin'): ?>
                <div id="sub-unassigned" class="subtab-content hidden bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 space-y-4 shadow-sm">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                            <span>Danh Sách Buổi Chưa Xếp Được (Phép thử bắt buộc 4)</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Dữ liệu gây bế tắc phải trả danh sách chưa xếp, không giấu buổi học hoặc báo lịch đầy đủ.</p>
                    </div>
                    <?php if (!empty($currentVersion['unassigned_lessons'])): ?>
                        <div class="space-y-3">
                            <?php foreach ($currentVersion['unassigned_lessons'] as $it): ?>
                                <div class="p-4 rounded-xl border border-rose-200 bg-rose-50/60 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 text-xs font-mono font-bold bg-rose-200 text-rose-800 rounded">
                                                <?= $it['lesson_id'] ?>
                                            </span>
                                            <h4 class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($it['lesson_name']) ?></h4>
                                        </div>
                                        <div class="text-xs text-slate-600 flex items-center gap-4">
                                            <span>Lớp: <strong><?= $it['class_id'] ?></strong></span>
                                            <span>Giảng viên: <strong><?= $it['teacher_id'] ?></strong></span>
                                        </div>
                                        <div class="text-xs text-rose-700 font-medium flex items-center gap-1.5 mt-1">
                                            <i class="fa-solid fa-circle-xmark"></i>
                                            <span>Nguyên nhân bế tắc: <strong><?= htmlspecialchars($it['reason']) ?></strong></span>
                                        </div>
                                    </div>

                                    <button type="button" onclick="openAdjustModal('<?= $it['lesson_id'] ?>', '<?= htmlspecialchars($it['lesson_name']) ?>', 'SL01', 'P101', false)" class="px-3.5 py-2 bg-white hover:bg-rose-100 text-rose-800 font-bold text-xs rounded-xl border border-rose-300 transition-colors shadow-xs">
                                        <i class="fa-solid fa-wrench mr-1"></i> Thử Xếp Thủ Công
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-xl">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-3xl mb-2"></i>
                            <p class="text-sm font-semibold text-slate-700">Tất cả các buổi học đều đã được xếp thành công!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
<?php endif; ?>
