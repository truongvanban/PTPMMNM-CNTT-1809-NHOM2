/**
 * VolunteerHub - Custom JS
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Tự động khởi tạo Bootstrap Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 2. Tự động ẩn thông báo flash sau 5 giây
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // 3. Xử lý xác nhận trước các hành động quan trọng
    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        const confirmAction = function (e) {
            const message = this.getAttribute('data-confirm') || 'Bạn có chắc chắn muốn thực hiện hành động này?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        };

        if (element instanceof HTMLFormElement) {
            element.addEventListener('submit', confirmAction);
        } else {
            element.addEventListener('click', confirmAction);
        }
    });

    document.querySelectorAll('[data-select-all]').forEach(function (selectAll) {
        const group = selectAll.getAttribute('data-select-all');
        const items = document.querySelectorAll('[data-select-item="' + group + '"]');
        const count = document.querySelector('[data-selection-count="' + group + '"]');
        const submit = document.querySelector('[data-selection-submit="' + group + '"]');

        function updateSelection() {
            const selectedCount = document.querySelectorAll(
                '[data-select-item="' + group + '"]:checked'
            ).length;
            if (count) count.textContent = selectedCount;
            if (submit) submit.setAttribute('aria-label', 'Xóa ' + selectedCount + ' tài khoản đã chọn');

            selectAll.checked = items.length > 0 && selectedCount === items.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < items.length;
        }

        selectAll.addEventListener('change', function () {
            items.forEach(function (item) {
                item.checked = selectAll.checked;
            });
            updateSelection();
        });

        items.forEach(function (item) {
            item.addEventListener('change', updateSelection);
        });

        document.querySelectorAll('[data-clear-selection="' + group + '"]').forEach(function (clearButton) {
            clearButton.addEventListener('click', function () {
                items.forEach(function (item) {
                    item.checked = false;
                });
                updateSelection();
            });
        });

        updateSelection();
    });

    document.querySelectorAll('[data-selection-submit]').forEach(function (submitButton) {
        submitButton.addEventListener('click', function (event) {
            const group = submitButton.getAttribute('data-selection-submit');
            const selectedCount = document.querySelectorAll(
                '[data-select-item="' + group + '"]:checked'
            ).length;
            if (selectedCount === 0) {
                event.preventDefault();
                alert('Vui lòng chọn ít nhất một tài khoản để xóa.');
            }
        });
    });

    const activityForm = document.querySelector('[data-activity-create]');
    if (activityForm) {
        const titleInput = activityForm.querySelector('[data-preview-title]');
        const descriptionInput = activityForm.querySelector('[data-preview-description]');
        const locationInput = activityForm.querySelector('[data-preview-location]');
        const capacityInput = activityForm.querySelector('[data-preview-capacity]');
        const categoryInput = activityForm.querySelector('[data-preview-category]');
        const startDateInput = activityForm.querySelector('[data-preview-start-date]');
        const startTimeInput = activityForm.querySelector('[data-preview-start-time]');
        const endDateInput = activityForm.querySelector('[data-preview-end-date]');
        const endTimeInput = activityForm.querySelector('[data-preview-end-time]');

        const updateText = function (selector, value, fallback) {
            const output = activityForm.querySelector(selector);
            if (output) output.textContent = value.trim() || fallback;
        };

        const formatDateTime = function (dateValue, timeValue) {
            if (!dateValue || !timeValue) return '';
            const date = new Date(dateValue + 'T' + timeValue);
            if (Number.isNaN(date.getTime())) return '';
            return new Intl.DateTimeFormat('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hourCycle: 'h23'
            }).format(date);
        };

        const updateActivityPreview = function () {
            updateText('[data-preview-output-title]', titleInput.value, 'Tên hoạt động của bạn');
            updateText('[data-preview-output-description]', descriptionInput.value, 'Mô tả hoạt động sẽ xuất hiện tại đây.');
            updateText('[data-preview-output-location]', locationInput.value, 'Chưa có địa điểm');
            const capacity = Number.parseInt(capacityInput.value, 10);
            const capacityOutput = activityForm.querySelector('[data-preview-output-capacity]');
            capacityOutput.textContent = capacity > 0 ? capacity + ' tình nguyện viên' : 'Chưa giới hạn số lượng';

            const categoryOutput = activityForm.querySelector('[data-preview-output-category]');
            categoryOutput.textContent = categoryInput.value || 'Chưa chọn danh mục';
            const start = formatDateTime(startDateInput.value, startTimeInput.value);
            const end = formatDateTime(endDateInput.value, endTimeInput.value);
            const dateOutput = activityForm.querySelector('[data-preview-output-date]');
            dateOutput.textContent = start && end ? start + ' – ' + end : start || end || 'Chưa chọn thời gian';

            const titleCount = activityForm.querySelector('[data-title-count]');
            const descriptionCount = activityForm.querySelector('[data-description-count]');
            titleCount.textContent = titleInput.value.length;
            descriptionCount.textContent = descriptionInput.value.length;

            const selectedStatus = activityForm.querySelector('[data-preview-status]');
            const visibility = activityForm.querySelector('[data-preview-visibility]');
            const visibilityLabel = activityForm.querySelector('[data-preview-visibility-label]');
            const isPublic = selectedStatus && selectedStatus.value === 'open';
            visibility.classList.toggle('is-draft', !isPublic);
            visibilityLabel.textContent = isPublic ? 'Công khai' : 'Bản nháp';

            const selectedSkills = [...activityForm.querySelectorAll('[data-preview-skill]:checked')];
            const skillPlaceholder = activityForm.querySelector('[data-skill-placeholder]');
            if (skillPlaceholder) {
                skillPlaceholder.textContent = selectedSkills.length ? selectedSkills.length + ' kỹ năng đã chọn' : 'Chọn kỹ năng...';
            }
            const skillsPreview = activityForm.querySelector('[data-preview-skills]');
            skillsPreview.replaceChildren();
            selectedSkills.forEach(function (skill) {
                const tag = document.createElement('span');
                tag.textContent = skill.parentElement.querySelector('span').textContent;
                skillsPreview.appendChild(tag);
            });
            skillsPreview.hidden = selectedSkills.length === 0;
        };

        activityForm.querySelectorAll('input, textarea').forEach(function (field) {
            field.addEventListener('input', updateActivityPreview);
            field.addEventListener('change', updateActivityPreview);
        });
        categoryInput.addEventListener('change', updateActivityPreview);

        const statusInput = activityForm.querySelector('[data-preview-status]');
        const statusToggle = activityForm.querySelector('[data-status-toggle]');
        const statusText = activityForm.querySelector('[data-status-text]');
        statusToggle.addEventListener('change', function () {
            statusInput.value = statusToggle.checked ? 'open' : 'draft';
            statusText.textContent = statusToggle.checked ? 'Công khai' : 'Bản nháp';
            updateActivityPreview();
        });

        activityForm.querySelectorAll('[data-submit-status]').forEach(function (button) {
            button.addEventListener('click', function () {
                const isPublic = button.dataset.submitStatus === 'open';
                statusInput.value = isPublic ? 'open' : 'draft';
                statusToggle.checked = isPublic;
                statusText.textContent = isPublic ? 'Công khai' : 'Bản nháp';
                updateActivityPreview();
            });
        });

        const endDate = activityForm.querySelector('[data-preview-end-date]');
        const deadlineDate = activityForm.querySelector('#registration-deadline-date');
        const updateDateLimits = function () {
            endDate.min = startDateInput.value || '';
            deadlineDate.max = startDateInput.value || '';
        };
        startDateInput.addEventListener('change', updateDateLimits);
        updateDateLimits();

        const imageInput = activityForm.querySelector('[data-image-input]');
        const imageDropzone = activityForm.querySelector('[data-image-dropzone]');
        const previewImage = activityForm.querySelector('[data-preview-image]');
        const coverIcon = activityForm.querySelector('[data-preview-cover-icon]');
        const coverText = activityForm.querySelector('[data-preview-cover-text]');
        let currentImageUrl = '';
        const showImagePreview = function (file) {
            if (!file) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
                imageInput.value = '';
                alert('Ảnh phải có định dạng JPG, PNG hoặc WEBP và không quá 5 MB.');
                return;
            }
            if (currentImageUrl) URL.revokeObjectURL(currentImageUrl);
            currentImageUrl = URL.createObjectURL(file);
            previewImage.src = currentImageUrl;
            previewImage.hidden = false;
            coverIcon.hidden = true;
            coverText.hidden = true;
        };
        imageInput.addEventListener('change', function () {
            showImagePreview(imageInput.files[0]);
        });
        ['dragenter', 'dragover'].forEach(function (eventName) {
            imageDropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                imageDropzone.classList.add('is-dragging');
            });
        });
        ['dragleave', 'drop'].forEach(function (eventName) {
            imageDropzone.addEventListener(eventName, function (event) {
                event.preventDefault();
                imageDropzone.classList.remove('is-dragging');
            });
        });
        imageDropzone.addEventListener('drop', function (event) {
            const file = event.dataTransfer.files[0];
            if (file) {
                const transfer = new DataTransfer();
                transfer.items.add(file);
                imageInput.files = transfer.files;
                showImagePreview(file);
            }
        });

        const attachmentsInput = activityForm.querySelector('[data-attachments-input]');
        const attachmentsList = activityForm.querySelector('[data-attachments-list]');
        attachmentsInput.addEventListener('change', function () {
            const files = [...attachmentsInput.files];
            const totalSize = files.reduce(function (sum, file) { return sum + file.size; }, 0);
            const isValid = files.length <= 5
                && totalSize <= 10 * 1024 * 1024
                && files.every(function (file) {
                    return ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'].includes(file.type);
                });
            if (!isValid) {
                attachmentsInput.value = '';
                attachmentsList.replaceChildren();
                const previewAttachments = activityForm.querySelector('[data-preview-attachments]');
                previewAttachments.textContent = '';
                previewAttachments.hidden = true;
                alert('Chỉ nhận tối đa 5 tệp PDF/DOCX với tổng dung lượng không quá 10 MB.');
                return;
            }
            attachmentsList.replaceChildren();
            files.forEach(function (file) {
                const item = document.createElement('li');
                item.textContent = file.name + ' · ' + (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                attachmentsList.appendChild(item);
            });
            const previewAttachments = activityForm.querySelector('[data-preview-attachments]');
            previewAttachments.textContent = files.length ? files.length + ' tài liệu đính kèm' : '';
            previewAttachments.hidden = files.length === 0;
        });

        updateActivityPreview();
    }

    const sidebarToggle = document.querySelector('[data-coordinator-sidebar-toggle]');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            const isCollapsed = document.body.classList.toggle('coordinator-create-sidebar-collapsed');
            sidebarToggle.setAttribute('aria-expanded', String(!isCollapsed));
            sidebarToggle.setAttribute('aria-label', isCollapsed ? 'Mở rộng menu' : 'Thu gọn menu');
        });
    }

    // Quick Demo Login autofill
    document.querySelectorAll('[data-demo-email]').forEach(function (button) {
        button.addEventListener('click', function () {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            const email = this.getAttribute('data-demo-email');
            const pass = this.getAttribute('data-demo-pass');
            if (emailInput && passInput) {
                emailInput.value = email;
                passInput.value = pass;
                emailInput.style.transition = 'all 0.3s ease';
                emailInput.style.backgroundColor = '#ecfdf5';
                passInput.style.transition = 'all 0.3s ease';
                passInput.style.backgroundColor = '#ecfdf5';
                setTimeout(function () {
                    emailInput.style.backgroundColor = '';
                    passInput.style.backgroundColor = '';
                }, 600);
            }
        });
    });

    // Password visibility toggle
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            if (passwordInput && icon) {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    }

    // Smooth Navbar Scroll state
    const mainNavbar = document.getElementById('mainNavbar');
    if (mainNavbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 20) {
                mainNavbar.classList.add('scrolled');
            } else {
                mainNavbar.classList.remove('scrolled');
            }
        });
    }
});
