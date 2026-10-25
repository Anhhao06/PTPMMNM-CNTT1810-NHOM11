    <!-- MODAL: ĐIỀU CHỈNH LỊCH & BÁO XUNG ĐỘT (CHỨC NĂNG 4) -->
    <div id="adjustModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-5 text-white flex justify-between items-center">
                <div>
                    <h3 class="font-black text-base">Điều Chỉnh Lịch & Kiểm Tra Xung Đột</h3>
                    <p class="text-blue-100 text-xs">Kiểm tra tức thì các điều kiện bắt buộc</p>
                </div>
                <button onclick="document.getElementById('adjustModal').classList.add('hidden')" class="text-white/80 hover:text-white p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form method="POST" class="p-5 space-y-4">
                <input type="hidden" name="action" value="manual_adjust">
                <input type="hidden" name="lesson_id" id="modal_lesson_id" value="">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Buổi học</label>
                    <div id="modal_lesson_name" class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 font-bold text-xs text-slate-800"></div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Khung giờ mới</label>
                        <select name="new_slot_id" id="modal_slot_id" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold">
                            <?php foreach ($data['slots'] as $sid => $s): ?>
                                <option value="<?= $sid ?>"><?= $s['day'] ?> - <?= $s['period'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Phòng học mới</label>
                        <select name="new_room_id" id="modal_room_id" class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold">
                            <?php foreach ($data['rooms'] as $rid => $r): ?>
                                <option value="<?= $rid ?>"><?= $r['name'] ?> (<?= $r['type'] ?> - <?= $r['capacity'] ?> chỗ)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('adjustModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700">Hủy</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md">Xác nhận chuyển</button>
                </div>
            </form>
        </div>
    </div>

