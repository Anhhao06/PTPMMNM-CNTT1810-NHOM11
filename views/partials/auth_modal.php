    <!-- MODAL: ĐĂNG NHẬP / ĐĂNG KÝ -->
    <div id="authModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[92vh] overflow-y-auto border border-slate-200">
            <div class="bg-gradient-to-r from-slate-950 via-blue-900 to-indigo-800 p-5 text-white flex justify-between items-center">
                <div>
                    <h3 class="font-black text-lg">Tài khoản hệ thống</h3>
                    <p class="mt-1 text-xs text-blue-100">Đăng nhập hoặc đăng ký cho giảng viên và lớp học</p>
                </div>
                <button onclick="document.getElementById('authModal').classList.add('hidden')" class="text-white/80 hover:text-white p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="grid gap-0 md:grid-cols-2">
                <section class="p-6 md:p-8">
                    <div class="mb-5">
                        <div class="text-xs font-bold uppercase tracking-widest text-blue-700">Chào mừng trở lại</div>
                        <h4 class="mt-1 text-2xl font-black text-slate-900">Đăng nhập</h4>
                    </div>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="login">
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Tên đăng nhập</label>
                            <input type="text" name="username" id="auth_user" required placeholder="Tên tài khoản" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Mật khẩu</label>
                            <input type="password" name="password" id="auth_pass" required placeholder="Nhập mật khẩu" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3.5 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-blue-700 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/20 transition hover:bg-blue-800">
                            Đăng nhập
                        </button>
                    </form>
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">Tài khoản dùng thử</div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <button type="button" onclick="quickFill('admin', '123')" class="rounded-lg border border-blue-200 bg-white px-3 py-2 font-bold text-blue-900 hover:bg-blue-50">Admin</button>
                            <button type="button" onclick="quickFill('gv01', '123')" class="rounded-lg border border-emerald-200 bg-white px-3 py-2 font-bold text-emerald-900 hover:bg-emerald-50">Giảng viên</button>
                            <button type="button" onclick="quickFill('lop01', '123')" class="rounded-lg border border-violet-200 bg-white px-3 py-2 font-bold text-violet-900 hover:bg-violet-50">Lớp học</button>
                        </div>
                    </div>
                </section>

                <section class="border-t border-slate-200 bg-slate-50 p-6 md:border-l md:border-t-0 md:p-8">
                    <div class="mb-5">
                        <div class="text-xs font-bold uppercase tracking-widest text-emerald-700">Thành viên mới</div>
                        <h4 class="mt-1 text-2xl font-black text-slate-900">Đăng ký</h4>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Chọn hồ sơ giảng viên hoặc lớp học đã có trong hệ thống để liên kết tài khoản.</p>
                    </div>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="register">
                        <div>
                            <label for="register_role" class="mb-1 block text-xs font-bold text-slate-700">Đăng ký với vai trò</label>
                            <select name="role" id="register_role" required onchange="updateRegistrationRole()" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm">
                                <option value="teacher" <?= ($_POST['role'] ?? 'teacher') === 'teacher' ? 'selected' : '' ?>>Giảng viên</option>
                                <option value="class" <?= ($_POST['role'] ?? '') === 'class' ? 'selected' : '' ?>>Đại diện lớp học</option>
                            </select>
                        </div>
                        <div id="register_teacher_field">
                            <label for="register_teacher_id" class="mb-1 block text-xs font-bold text-slate-700">Hồ sơ giảng viên</label>
                            <select name="teacher_ref_id" id="register_teacher_id" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm">
                                <?php foreach ($data['teachers'] as $teacherId => $teacher): ?>
                                    <option value="<?= htmlspecialchars($teacherId) ?>"><?= htmlspecialchars($teacher['name']) ?> (<?= htmlspecialchars($teacherId) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="register_class_field" class="hidden">
                            <label for="register_class_id" class="mb-1 block text-xs font-bold text-slate-700">Lớp học</label>
                            <select name="class_ref_id" id="register_class_id" disabled class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm">
                                <?php foreach ($data['classes'] as $classId => $class): ?>
                                    <option value="<?= htmlspecialchars($classId) ?>" <?= isset($registeredProfiles['class:' . $classId]) ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($class['name']) ?> (<?= htmlspecialchars($classId) ?>)<?= isset($registeredProfiles['class:' . $classId]) ? ' - đã có tài khoản' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Tên đăng nhập</label>
                            <input type="text" name="username" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9._-]+" autocomplete="username" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm" placeholder="Ví dụ: gv03 hoặc lop03">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Mật khẩu (ít nhất 6 ký tự)</label>
                            <input type="password" name="password" required minlength="6" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm" placeholder="Tạo mật khẩu">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-700">Nhập lại mật khẩu</label>
                            <input type="password" name="password_confirmation" required minlength="6" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm" placeholder="Xác nhận mật khẩu">
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-emerald-600 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/10 transition hover:bg-emerald-700">
                            Tạo tài khoản
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </div>

