    <!-- FOOTER -->
    <footer class="bg-white border-t border-slate-200 py-6 mt-12 text-slate-500 text-xs text-center">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-2">
            <div>© 2026 <strong>Đề tài 18</strong> - Xếp thời khóa biểu theo ràng buộc Giảng viên, Phòng & Lớp (PHP 8)</div>
            <div><?= count($data['teachers']) ?> Giảng viên • <?= count($data['rooms']) ?> Phòng học • <?= count($data['slots']) ?> Buổi/tuần • <?= count($data['lessons']) ?> Buổi học</div>
        </div>
    </footer>
