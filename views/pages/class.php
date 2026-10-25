<?php
$curClass = $data['classes'][$classFilterId] ?? reset($data['classes']);
            
            $cGrid = [];
            foreach (range(1, 2) as $p) {
                foreach ($data['days'] as $d) {
                    $cGrid[$p][$d] = null;
                }
            }
            if ($currentVersion) {
                foreach ($currentVersion['assignments'] as $a) {
                    $l = $data['lessons'][$a['lesson_id']] ?? null;
                    if ($l && $l['class_id'] === $curClass['id']) {
                        $slot = $data['slots'][$a['slot_id']] ?? null;
                        if ($slot) {
                            $cGrid[$slot['period_index']][$slot['day']] = [
                                'lesson' => $l,
                                'room' => $data['rooms'][$a['room_id']] ?? null,
                                'teacher' => $data['teachers'][$l['teacher_id']] ?? null,
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
                        <div class="w-14 h-14 rounded-2xl bg-violet-600 text-white flex items-center justify-center text-2xl font-black shadow-md shadow-violet-500/20">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-violet-600 uppercase tracking-wider">Cổng Lớp Học</span>
                                <span class="text-xs font-mono px-2 py-0.5 rounded bg-violet-50 text-violet-700 font-bold border border-violet-200"><?= $curClass['id'] ?></span>
                            </div>
                            <h1 class="text-2xl font-black text-slate-900 mt-0.5"><?= htmlspecialchars($curClass['name']) ?></h1>
                            <p class="text-xs text-slate-500">Ngành: <strong><?= $curClass['major'] ?></strong> • Sĩ số: <strong class="text-violet-700"><?= $curClass['size'] ?> sinh viên</strong></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <select onchange="window.location.href='class.php?class_id=' + this.value" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-700">
                            <?php foreach ($data['classes'] as $cid => $c): ?>
                                <option value="<?= $cid ?>" <?= $cid === $curClass['id'] ? 'selected' : '' ?>><?= $c['name'] ?> (<?= $cid ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button onclick="window.print()" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-print"></i> In Lịch / Xuất PDF
                        </button>
                    </div>
                </div>

                <!-- Lưới lịch tuần của lớp -->
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
                                            $c = $cGrid[$p][$day];
                                        ?>
                                            <td class="p-2 border-r border-slate-200 last:border-r-0 align-top min-w-[140px] h-28">
                                                <?php if ($c): ?>
                                                    <div class="h-full p-3 rounded-xl bg-violet-50/90 border border-violet-300 text-violet-950 flex flex-col justify-between shadow-xs">
                                                        <div>
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-white text-violet-800 border"><?= $c['lesson']['id'] ?></span>
                                                                <?php if ($c['is_locked']): ?><i class="fa-solid fa-lock text-amber-600 text-xs"></i><?php endif; ?>
                                                            </div>
                                                            <div class="font-black text-xs mt-1.5 line-clamp-2 leading-tight"><?= htmlspecialchars($c['lesson']['name']) ?></div>
                                                        </div>
                                                        <div class="mt-2 pt-2 border-t border-violet-200/60 text-[11px]">
                                                            <div class="font-semibold text-violet-900"><?= htmlspecialchars($c['teacher']['name'] ?? '') ?></div>
                                                            <div class="text-violet-700"><?= htmlspecialchars($c['room']['name'] ?? '') ?></div>
                                                        </div>
                                                    </div>
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
