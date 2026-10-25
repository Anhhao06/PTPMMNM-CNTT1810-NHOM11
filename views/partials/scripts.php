    <script>
        function openSubTab(tabId) {
            document.querySelectorAll('.subtab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.subtab-btn').forEach(el => {
                el.classList.remove('border-blue-600', 'text-blue-600');
                el.classList.add('border-transparent', 'text-slate-500');
            });
            document.getElementById(tabId).classList.remove('hidden');
            const activeBtn = document.getElementById('btn-' + tabId);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent', 'text-slate-500');
                activeBtn.classList.add('border-blue-600', 'text-blue-600');
            }
        }

        function openAdjustModal(lessonId, lessonName, currentSlot, currentRoom, isLocked) {
            if (isLocked) {
                alert('Buổi học này đang bị KHÓA! Hãy mở khóa trước khi đổi lịch.');
                return;
            }
            document.getElementById('modal_lesson_id').value = lessonId;
            document.getElementById('modal_lesson_name').innerText = lessonId + ' - ' + lessonName;
            document.getElementById('modal_slot_id').value = currentSlot;
            document.getElementById('modal_room_id').value = currentRoom;
            document.getElementById('adjustModal').classList.remove('hidden');
        }

        function resetLessonForm() {
            const form = document.getElementById('lessonForm');
            if (!form) return;
            form.reset();
            document.getElementById('lesson_form_id').value = '';
            document.getElementById('lesson_form_preferred_slot').value = '';
            updateLessonType();
            document.getElementById('lesson_form_submit').innerHTML = '<i class="fa-solid fa-calendar-plus mr-1"></i> Lưu và xếp lịch';
        }

        function updateLessonType() {
            const subject = document.getElementById('lesson_form_name');
            const selectedOption = subject.options[subject.selectedIndex];
            const requiredType = selectedOption ? selectedOption.dataset.requiredType : '';
            document.getElementById('lesson_form_type').value = requiredType;
            document.getElementById('lesson_form_type_display').value = requiredType;
        }

        function editLesson(lesson) {
            document.getElementById('lesson_form_id').value = lesson.id;
            document.getElementById('lesson_form_name').value = lesson.name;
            updateLessonType();
            document.getElementById('lesson_form_teacher').value = lesson.teacher_id;
            document.getElementById('lesson_form_class').value = lesson.class_id;
            document.getElementById('lesson_form_preferred_slot').value = lesson.preferred_slot || '';
            document.getElementById('lesson_form_submit').innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i> Lưu thay đổi và xếp lại';
            document.getElementById('lessonForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
            document.getElementById('lesson_form_name').focus();
        }

        function resetClassForm() {
            const form = document.getElementById('classForm');
            if (!form) return;
            form.reset();
            document.getElementById('class_form_id').value = '';
            document.getElementById('class_form_submit').innerHTML = '<i class="fa-solid fa-plus mr-1"></i> Thêm lớp';
        }

        function editClass(classInfo) {
            document.getElementById('class_form_id').value = classInfo.id;
            document.getElementById('class_form_name').value = classInfo.name;
            document.getElementById('class_form_major').value = classInfo.major;
            document.getElementById('class_form_size').value = classInfo.size;
            document.getElementById('class_form_submit').innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i> Lưu thay đổi';
            document.getElementById('classForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
            document.getElementById('class_form_name').focus();
        }

        function editTeacher(teacher) {
            document.getElementById('teacher_form_id').value = teacher.id;
            document.getElementById('teacher_form_name').value = teacher.name;
            document.getElementById('teacher_form_email').value = teacher.email;
            document.getElementById('teacher_form_subject').value = teacher.subject;
            document.getElementById('teacher_form_submit').textContent = 'Lưu giảng viên';
            document.getElementById('teacherForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function editRoom(room) {
            document.getElementById('room_form_id').value = room.id;
            document.getElementById('room_form_name').value = room.name;
            document.getElementById('room_form_capacity').value = room.capacity;
            document.getElementById('room_form_type').value = room.type;
            document.getElementById('room_form_submit').textContent = 'Lưu phòng';
            document.getElementById('roomForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function editSlot(slot) {
            document.getElementById('slot_form_id').value = slot.id;
            document.getElementById('slot_form_day_name').value = slot.day;
            document.getElementById('slot_form_day_order').value = slot.day_order;
            document.getElementById('slot_form_period').value = slot.period_index;
            document.getElementById('slot_form_submit').textContent = 'Lưu khung giờ';
            document.getElementById('slotForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function editUnavailability(item) {
            document.getElementById('busy_teacher').value = item.teacher_id;
            document.getElementById('busy_slot').value = item.slot_id;
            document.getElementById('busy_reason').value = item.reason;
            document.getElementById('busy_old_teacher').value = item.teacher_id;
            document.getElementById('busy_old_slot').value = item.slot_id;
            document.getElementById('busy_submit').textContent = 'Lưu lịch bận';
            document.getElementById('unavailabilityForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function resetResourceForm(formId, idField, submitId, label) {
            document.getElementById(formId).reset();
            document.getElementById(idField).value = '';
            document.getElementById(submitId).textContent = label;
        }

        function resetUnavailabilityForm() {
            document.getElementById('unavailabilityForm').reset();
            document.getElementById('busy_old_teacher').value = '';
            document.getElementById('busy_old_slot').value = '';
            document.getElementById('busy_submit').textContent = 'Thêm lịch bận';
        }

        function quickFill(user, pass) {
            document.getElementById('auth_user').value = user;
            document.getElementById('auth_pass').value = pass;
        }

        function updateRegistrationRole() {
            const role = document.getElementById('register_role').value;
            const teacherField = document.getElementById('register_teacher_field');
            const classField = document.getElementById('register_class_field');
            const teacherSelect = document.getElementById('register_teacher_id');
            const classSelect = document.getElementById('register_class_id');
            const isTeacher = role === 'teacher';

            teacherField.classList.toggle('hidden', !isTeacher);
            classField.classList.toggle('hidden', isTeacher);
            teacherSelect.disabled = !isTeacher;
            classSelect.disabled = isTeacher;
        }

        updateRegistrationRole();

        <?php if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && in_array($_POST['action'] ?? '', ['login', 'register'], true) && $messageType === 'danger'): ?>
        document.getElementById('authModal').classList.remove('hidden');
        <?php endif; ?>
    </script>
</body>
</html>
