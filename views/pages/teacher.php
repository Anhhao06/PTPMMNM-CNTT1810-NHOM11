<?php
$curTeacher = $data['teachers'][$teacherFilterId] ?? reset($data['teachers']);
            
            // Lập lưới lịch cho GV
            $tGrid = [];
            foreach (range(1, 2) as $p) {
                foreach ($data['days'] as $d) {
                    $tGrid[$p][$d] = null;
                }
            }
            // Đánh dấu bận
            foreach ($data['unavailabilities'] as $u) {
                if ($u['teacher_id'] === $curTeacher['id']) {
                    $slot = $data['slots'][$u['slot_id']] ?? null;
                    if ($slot) {
                        $tGrid[$slot['period_index']][$slot['day']] = ['is_busy' => true, 'reason' => $u['reason']];
                    }
                }
            }
            // Buổi dạy
            if ($currentVersion) {
                foreach ($currentVersion['assignments'] as $a) {
                    $l = $data['lessons'][$a['lesson_id']] ?? null;
                    if ($l && $l['teacher_id'] === $curTeacher['id']) {
                        $slot = $data['slots'][$a['slot_id']] ?? null;
                        if ($slot) {
                            $tGrid[$slot['period_index']][$slot['day']] = [
                                'is_busy' => false,
                                'lesson' => $l,
                                'room' => $data['rooms'][$a['room_id']] ?? null,
                                'class' => $data['classes'][$l['class_id']] ?? null,
                                'is_locked' => $a['is_locked']
                            ];
                        }
                    }
                }
            }
        ?>
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-2xl font-black shadow-md shadow-emerald-500/20">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Cổng Giảng Viên</span>
                                <span class="text-xs font-mono px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold border border-emerald-200"><?= $curTeacher['id'] ?></span>
                            </div>
                            <h1 class="text-2xl font-black text-slate-900 mt-0.5"><?= htmlspecialchars($curTeacher['name']) ?></h1>
                            <p class="text-xs text-slate-500">Bộ môn: <strong><?= htmlspecialchars($curTeacher['subject'] ?? '') ?></strong> • Email: <?= htmlspecialchars($curTeacher['email'] ?? '') ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <select onchange="window.location.href='teacher.php?teacher_id=' + this.value" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-700">
                            <?php foreach ($data['teachers'] as $tid => $t): ?>
                                <option value="<?= $tid ?>" <?= $tid === $curTeacher['id'] ? 'selected' : '' ?>><?= $t['name'] ?> (<?= $tid ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button onclick="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-print"></i> In Lịch / Xuất PDF
                        </button>
                    </div>
                </div>

                <?php if (($currentUser['role'] ?? '') === 'teacher' && $currentUser['ref_id'] === $curTeacher['id']): ?>
                    <section class="grid gap-5 rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm lg:grid-cols-[0.9fr_1.1fr]">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Đăng ký môn học</h2>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Chọn môn học, lớp và ngày/buổi mong muốn. Đây là khung giờ ưu tiên; hệ thống chỉ xếp vào khung khác nếu không thể đáp ứng và vẫn phải tuân thủ các ràng buộc bắt buộc. Môn chỉ được thêm vào thời khóa biểu sau khi Admin duyệt.</p>
                            <form method="POST" class="mt-4 space-y-3">
                                <input type="hidden" name="action" value="teacher_lesson_request">
                                <div>
                                    <label class="mb-1 block text-xs font-bold text-slate-700">Chọn môn học</label>
                                    <select name="subject_name" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                        <option value="" disabled selected>Chọn môn học trong danh mục</option>
                                        <?php foreach ($data['subjects'] as $subjectName => $subject): ?>
                                            <?php if ($subject['department'] === $curTeacher['subject']): ?>
                                                <option value="<?= htmlspecialchars($subjectName) ?>">
                                                    <?= htmlspecialchars($subjectName) ?> · <?= $subject['required_type'] === 'LAB' ? 'Thực hành / Lab' : 'Lý thuyết' ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold text-slate-700">Lớp học</label>
                                    <select name="class_id" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                        <?php foreach ($data['classes'] as $classId => $class): ?>
                                            <option value="<?= htmlspecialchars($classId) ?>"><?= htmlspecialchars($class['name']) ?> (<?= (int) $class['size'] ?> SV)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-bold text-slate-700">Ngày và buổi mong muốn</label>
                                    <select name="preferred_slot_id" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                        <option value="" disabled selected>Chọn ngày và buổi</option>
                                        <?php foreach ($data['slots'] as $slotId => $slot): ?>
                                            <option value="<?= htmlspecialchars($slotId) ?>"><?= htmlspecialchars($slot['day'] . ' · ' . $slot['period']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-700">
                                    <i class="fa-solid fa-paper-plane mr-1"></i>Gửi Admin duyệt
                                </button>
                            </form>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Môn đã đăng ký</h3>
                            <?php if ($teacherLessonRequests): ?>
                                <div class="mt-3 space-y-2">
                                    <?php foreach ($teacherLessonRequests as $request): ?>
                                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                            <div>
                                                <div class="text-xs font-bold text-slate-800"><?= htmlspecialchars($request['lesson_name']) ?></div>
                                                <div class="mt-0.5 text-[11px] text-slate-500"><?= htmlspecialchars($request['class_name']) ?> · <?= $request['required_type'] ?></div>
                                                <div class="mt-0.5 text-[11px] text-slate-500">Ngày/buổi mong muốn: <?= htmlspecialchars(($data['slots'][$request['preferred_slot_id']]['day'] ?? '') . ' · ' . ($data['slots'][$request['preferred_slot_id']]['period'] ?? '')) ?></div>
                                                <?php if ($request['admin_note']): ?><div class="mt-1 text-[11px] text-slate-600">Admin: <?= htmlspecialchars($request['admin_note']) ?></div><?php endif; ?>
                                            </div>
                                            <span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-bold <?= $request['status'] === 'pending' ? 'bg-amber-100 text-amber-800' : ($request['status'] === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') ?>">
                                                <?= $request['status'] === 'pending' ? 'Chờ Admin duyệt' : ($request['status'] === 'approved' ? 'Đã duyệt' : 'Từ chối') ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="mt-3 rounded-xl border border-dashed border-slate-200 p-5 text-center text-xs text-slate-500">Bạn chưa đăng ký môn học nào.</p>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if (($currentUser['role'] ?? '') === 'teacher' && $currentUser['ref_id'] === $curTeacher['id']): ?>
                    <section class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-black text-slate-900">Đăng ký lịch bận</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Chọn ngày/buổi và nhập lý do. Khung giờ chỉ được tính là lịch bận sau khi Admin duyệt.</p>
                        <form method="POST" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1.5fr_auto] sm:items-end">
                            <input type="hidden" name="action" value="teacher_unavailability_request">
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">Ngày và buổi bận</label>
                                <select name="slot_id" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                                    <option value="" disabled selected>Chọn ngày và buổi</option>
                                    <?php foreach ($data['slots'] as $slotId => $slot): ?>
                                        <option value="<?= htmlspecialchars($slotId) ?>"><?= htmlspecialchars($slot['day'] . ' · ' . $slot['period']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-700">Lý do bận</label>
                                <input type="text" name="reason" required maxlength="200" placeholder="Ví dụ: Công tác, họp chuyên môn" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                            </div>
                            <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-rose-700">
                                <i class="fa-solid fa-paper-plane mr-1"></i>Gửi Admin duyệt
                            </button>
                        </form>

                        <h3 class="mt-5 text-sm font-bold text-slate-800">Các yêu cầu lịch bận của tôi</h3>
                        <?php if ($teacherUnavailabilityRequests): ?>
                            <div class="mt-3 space-y-2">
                                <?php foreach ($teacherUnavailabilityRequests as $request): ?>
                                    <?php $requestSlot = $data['slots'][$request['slot_id']] ?? null; ?>
                                    <div class="flex flex-col justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 sm:flex-row sm:items-center">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800">
                                                <?= htmlspecialchars($requestSlot ? $requestSlot['day'] . ' · ' . $requestSlot['period'] : 'Khung giờ không còn tồn tại') ?>
                                            </div>
                                            <div class="mt-0.5 text-[11px] text-slate-500"><?= htmlspecialchars($request['reason']) ?></div>
                                            <?php if ($request['admin_note']): ?>
                                                <div class="mt-1 text-[11px] text-slate-600">Admin: <?= htmlspecialchars($request['admin_note']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-bold <?= $request['status'] === 'pending' ? 'bg-amber-100 text-amber-800' : ($request['status'] === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') ?>">
                                            <?= $request['status'] === 'pending' ? 'Chờ duyệt' : ($request['status'] === 'approved' ? 'Đã duyệt · Đang bận' : 'Đã từ chối') ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="mt-3 rounded-xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-500">Bạn chưa gửi yêu cầu lịch bận nào.</p>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <!-- Lưới lịch tuần GV -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar border border-slate-200 rounded-xl">
                        <table class="w-full text-left text-xs border-collapse min-w-[700px]">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700 uppercase font-extrabold border-b border-slate-200 text-center">
                                    <th class="p-3 border-r border-slate-200 w-36">Ca / Giờ</th>
                                    <?php foreach ($data['days'] as $day): ?>
                                        <th class="p-3 border-r border-slate-200 last:border-r-0 font-bold"><?= $day ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php foreach (range(1, 2) as $p): ?>
                                    <tr class="hover:bg-slate-50/50 <?= $p === 2 ? 'border-t-4 border-t-slate-300' : '' ?>">
                                        <td class="p-3 bg-slate-50 border-r border-slate-200 font-bold text-center">
                                            <div class="text-slate-900 font-extrabold"><?= $data['periods'][$p] ?></div>
                                        </td>
                                        <?php foreach ($data['days'] as $day): 
                                            $c = $tGrid[$p][$day];
                                        ?>
                                            <td class="p-2 border-r border-slate-200 last:border-r-0 align-top min-w-[140px] h-28">
                                                <?php if ($c): ?>
                                                    <?php if (!empty($c['is_busy'])): ?>
                                                        <div class="h-full p-3 rounded-xl bg-rose-50/90 border border-rose-200 text-rose-800 flex flex-col justify-center items-center text-center">
                                                            <i class="fa-solid fa-ban text-rose-500 text-base mb-1"></i>
                                                            <div class="font-bold text-[11px] uppercase">Lịch Bận</div>
                                                            <div class="text-[10px] text-rose-600 mt-0.5"><?= htmlspecialchars($c['reason']) ?></div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="h-full p-3 rounded-xl bg-emerald-50/90 border border-emerald-300 text-emerald-950 flex flex-col justify-between shadow-xs">
                                                            <div>
                                                                <div class="flex items-center justify-between">
                                                                    <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-white text-emerald-800 border"><?= $c['lesson']['id'] ?></span>
                                                                    <?php if ($c['is_locked']): ?><i class="fa-solid fa-lock text-amber-600 text-xs"></i><?php endif; ?>
                                                                </div>
                                                                <div class="font-black text-xs mt-1.5 line-clamp-2 leading-tight"><?= htmlspecialchars($c['lesson']['name']) ?></div>
                                                            </div>
                                                            <div class="mt-2 pt-2 border-t border-emerald-200/60 text-[11px]">
                                                                <div class="font-semibold text-emerald-800"><?= htmlspecialchars($c['class']['name'] ?? $c['lesson']['class_id']) ?></div>
                                                                <div class="text-emerald-700"><?= htmlspecialchars($c['room']['name'] ?? '') ?></div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div class="h-full rounded-lg border border-dashed border-slate-200 flex items-center justify-center text-slate-300 text-xs">Trống</div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
