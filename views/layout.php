<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Xếp Thời Khóa Biểu </title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eff6ff', 100: '#dbeafe', 600: '#2563eb', 700: '#1d4ed8' }
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="<?= defined('APP_PAGE') && APP_PAGE === 'admin' ? 'bg-slate-100 text-slate-800 min-h-screen flex flex-col' : 'bg-slate-50 text-slate-800 min-h-screen flex flex-col' ?>">

    <!-- Top Navigation Bar -->
    <header class="<?= defined('APP_PAGE') && APP_PAGE === 'admin' ? 'bg-slate-950 border-b border-slate-800 sticky top-0 z-50 shadow-lg shadow-slate-900/10' : 'bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm' ?>">
        <div class="<?= defined('APP_PAGE') && APP_PAGE === 'admin' ? 'max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8' ?>">
            <div class="flex justify-between items-center h-16">
                
                <!-- Logo & Title -->
                <div class="flex items-center space-x-3">
                    <?php if (defined('APP_PAGE') && APP_PAGE === 'admin'): ?>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-400 to-blue-600 flex items-center justify-center text-white shadow-md shadow-cyan-500/20">
                            <i class="fa-solid fa-gauge-high text-lg"></i>
                        </div>
                        <div>
                            <a href="admin.php" class="text-base font-extrabold tracking-tight text-white">TRUNG TÂM QUẢN TRỊ</a>       
                        </div>
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                            <i class="fa-solid fa-calendar-days text-xl"></i>
                        </div>
                        <div>
                            <a href="index.php" class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                <span>Thời Khóa Biểu</span>
                            </a>
                            <p class="text-xs text-slate-500 hidden sm:block">Xếp thời khóa biểu theo ràng buộc Giảng viên, Phòng & Lớp</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Role Switch Navigation -->
                <?php if (!(defined('APP_PAGE') && APP_PAGE === 'admin')): ?>
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="index.php" class="px-3.5 py-2 text-sm font-medium rounded-lg transition-colors <?= (defined('APP_PAGE') && APP_PAGE === 'schedule') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-calendar-days mr-1.5 text-blue-600"></i> Thời khóa biểu
                    </a>
                    <a href="admin.php" class="px-3.5 py-2 text-sm font-medium rounded-lg transition-colors <?= (defined('APP_PAGE') && APP_PAGE === 'admin') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-sliders mr-1.5 text-blue-600"></i> Người xếp lịch (Admin)
                    </a>
                    <a href="teacher.php" class="px-3.5 py-2 text-sm font-medium rounded-lg transition-colors <?= (defined('APP_PAGE') && APP_PAGE === 'teacher') ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-slate-600 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-chalkboard-user mr-1.5 text-emerald-600"></i> Lịch Giảng viên
                    </a>
                    <a href="class.php" class="px-3.5 py-2 text-sm font-medium rounded-lg transition-colors <?= (defined('APP_PAGE') && APP_PAGE === 'class') ? 'bg-violet-50 text-violet-700 font-bold' : 'text-slate-600 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-graduation-cap mr-1.5 text-violet-600"></i> Lịch Lớp học
                    </a>
                </nav>
                <?php endif; ?>

                <!-- Auth Status -->
                <div class="flex items-center space-x-3">
                    <?php if (defined('APP_PAGE') && APP_PAGE === 'admin'): ?>
                        <a href="index.php" class="hidden sm:inline-flex items-center gap-2 rounded-xl border border-slate-700 px-3 py-2 text-xs font-bold text-slate-200 transition hover:border-cyan-400 hover:text-white">
                            <i class="fa-solid fa-arrow-up-right-from-square text-cyan-300"></i> Xem trang chính
                        </a>
                        <?php if ($currentUser): ?>
                            <div class="hidden sm:block text-right">
                                <div class="text-xs font-bold text-white"><?= htmlspecialchars($currentUser['name']) ?></div>
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-cyan-300">Quản trị viên</div>
                            </div>
                            <a href="?action=logout" title="Đăng xuất" class="p-2 text-slate-400 hover:text-rose-300 hover:bg-slate-800 rounded-lg transition-colors">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            </a>
                        <?php else: ?>
                            <button onclick="document.getElementById('authModal').classList.remove('hidden')" class="px-4 py-2 text-sm font-semibold text-slate-950 bg-cyan-300 hover:bg-cyan-200 rounded-xl transition-all">
                                Đăng nhập Admin
                            </button>
                        <?php endif; ?>
                    <?php elseif ($currentUser): ?>
                        <div class="flex items-center space-x-2.5 bg-slate-100/90 py-1.5 px-3 rounded-xl border border-slate-200">
                            <div class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs uppercase">
                                <?= substr($currentUser['role'], 0, 2) ?>
                            </div>
                            <div class="text-left hidden sm:block">
                                <div class="text-xs font-bold text-slate-800 leading-tight"><?= htmlspecialchars($currentUser['name']) ?></div>
                                <div class="text-[10px] text-slate-500 uppercase font-semibold"><?= htmlspecialchars($currentUser['role']) ?></div>
                            </div>
                        </div>
                        <a href="?action=logout" title="Đăng xuất" class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </a>
                    <?php else: ?>
                        <button onclick="document.getElementById('authModal').classList.remove('hidden')" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-all shadow-blue-500/20">
                            Đăng nhập / Đăng ký
                        </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </header>

    <!-- Flash message -->
    <?php if ($message): ?>
        <div class="<?= defined('APP_PAGE') && APP_PAGE === 'admin' ? 'max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full' : 'max-w-7xl mx-auto px-4 mt-4 w-full' ?>">
            <div class="p-4 rounded-xl border text-sm font-semibold flex items-center justify-between <?= $messageType === 'danger' ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800' ?>">
                <div class="flex items-center gap-2">
                    <i class="fa-solid <?= $messageType === 'danger' ? 'fa-circle-xmark text-rose-600' : 'fa-circle-check text-emerald-600' ?> text-lg"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Container -->
    <div class="<?= defined('APP_PAGE') && APP_PAGE === 'admin' && $viewRole === 'admin' ? 'flex-1 w-full max-w-screen-2xl mx-auto flex gap-6 px-4 sm:px-6 lg:px-8 py-6' : 'flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6' ?>">
        <?php if (defined('APP_PAGE') && APP_PAGE === 'admin' && $viewRole === 'admin'): ?>
            <aside class="hidden lg:flex w-60 shrink-0 flex-col self-stretch rounded-2xl bg-slate-900 p-4 text-slate-300 shadow-xl shadow-slate-900/10">
                <div class="mb-3 rounded-xl border border-cyan-400/20 bg-cyan-400/10 px-3 py-3">
                    <div class="flex items-center gap-2 text-xs font-bold text-cyan-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span> Hệ thống đang hoạt động
                    </div>
                    <div class="mt-1 text-[10px] text-slate-400"><?= count($data['lessons']) ?> buổi học · <?= count($data['slots']) ?> buổi/tuần</div>
                </div>
                <button onclick="openSubTab('sub-grid')" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-white transition hover:bg-slate-800">
                    <i class="fa-solid fa-table-cells w-4 text-center text-cyan-300"></i> Tổng quan lịch
                </button>
                <button onclick="openSubTab('sub-lessons')" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition hover:bg-slate-800 hover:text-white">
                    <i class="fa-solid fa-book-open w-4 text-center text-blue-300"></i> Quản lý môn học
                </button>
                <button onclick="openSubTab('sub-classes')" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition hover:bg-slate-800 hover:text-white">
                    <i class="fa-solid fa-users-rectangle w-4 text-center text-violet-300"></i> Quản lý lớp học
                </button>
                <button onclick="openSubTab('sub-requests')" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition hover:bg-slate-800 hover:text-white">
                    <span class="flex items-center gap-3"><i class="fa-solid fa-inbox w-4 text-center text-amber-300"></i> Đăng ký giảng viên</span>
                    <span class="rounded-full bg-amber-400/15 px-2 py-0.5 text-[10px] text-amber-200"><?= $pendingLessonRequestCount ?></span>
                </button>
                <button onclick="openSubTab('sub-unassigned')" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition hover:bg-slate-800 hover:text-white">
                    <span class="flex items-center gap-3"><i class="fa-solid fa-triangle-exclamation w-4 text-center text-rose-300"></i> Lịch chưa xếp</span>
                    <span class="rounded-full bg-rose-400/15 px-2 py-0.5 text-[10px] text-rose-200"><?= count($currentVersion['unassigned_lessons']) ?></span>
                </button>
                <div class="mt-5 border-t border-slate-800 pt-4">
                    <a href="index.php" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-400 transition hover:bg-slate-800 hover:text-white">
                        <i class="fa-solid fa-arrow-left w-4 text-center"></i> Quay về trang chính
                    </a>
                </div>
            </aside>
        <?php endif; ?>
        <main class="min-w-0 flex-1">

        <?php
        $activeTab = defined('APP_PAGE') ? APP_PAGE : ($_GET['tab'] ?? 'schedule');
        if ($activeTab === 'admin' || $activeTab === 'schedule') {
            require __DIR__ . '/pages/admin.php';
        } elseif ($activeTab === 'teacher') {
            require __DIR__ . '/pages/teacher.php';
        } else {
            require __DIR__ . '/pages/class.php';
        }
        ?>
        </main>
    </div>

    <?php require __DIR__ . '/partials/footer.php'; ?>
    <?php require __DIR__ . '/partials/auth_modal.php'; ?>
    <?php require __DIR__ . '/partials/adjust_modal.php'; ?>
    <?php require __DIR__ . '/partials/scripts.php'; ?>
