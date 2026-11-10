<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
?>
<section class="activity-create">
    <div class="activity-create-heading">
        <a href="<?= $baseUrl ?>/coordinator/activities" class="btn btn-light activity-create-back">
            <i class="bi bi-arrow-left me-1"></i>Quay lại
        </a>
        <div class="activity-create-heading-icon"><i class="bi bi-plus-lg"></i></div>
        <div>
            <h1>Tạo hoạt động</h1>
            <p>Tổ chức một hoạt động ý nghĩa, kết nối cộng đồng</p>
        </div>
    </div>

    <form action="<?= $baseUrl ?>/coordinator/activities/create" method="POST" enctype="multipart/form-data" class="activity-create-form" data-activity-create>
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="status" value="open" data-preview-status>

        <div class="activity-create-grid">
            <div class="activity-create-fields">
                <section class="activity-create-panel">
                    <h2><i class="bi bi-info-circle-fill"></i>Thông tin cơ bản</h2>
                    <div class="activity-create-field">
                        <label for="activity-title">Tên hoạt động <span>*</span></label>
                        <input type="text" class="form-control" id="activity-title" name="title" maxlength="100"
                               placeholder="Nhập tên hoạt động..." required autofocus data-preview-title>
                        <small class="activity-create-counter"><span data-title-count>0</span>/100</small>
                    </div>
                    <div class="activity-create-field">
                        <label for="activity-description">Mô tả <span>*</span></label>
                        <textarea class="form-control" id="activity-description" name="description" rows="4" maxlength="1000"
                                  placeholder="Mô tả chi tiết về hoạt động..." required data-preview-description></textarea>
                        <small class="activity-create-counter"><span data-description-count>0</span>/1000</small>
                    </div>
                    <div class="activity-create-field mt-2">
                        <label for="activity-category">Danh mục <span>*</span></label>
                        <div class="activity-create-input-icon">
                            <i class="bi bi-heart-fill"></i>
                            <select class="form-select" id="activity-category" name="category" required data-preview-category>
                                <option value="">Chọn danh mục...</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="activity-create-panel">
                    <h2><i class="bi bi-calendar-event-fill"></i>Thời gian &amp; Địa điểm</h2>
                    <div class="row g-3">
                        <div class="col-md-6 activity-create-field">
                            <label for="activity-start">Thời gian bắt đầu <span>*</span></label>
                            <div class="activity-create-date-time">
                                <input type="date" class="form-control" id="activity-start" name="date_start_date" required data-preview-start-date>
                                <input type="time" class="form-control" name="date_start_time" value="08:00" required data-preview-start-time>
                            </div>
                        </div>
                        <div class="col-md-6 activity-create-field">
                            <label for="activity-end">Thời gian kết thúc <span>*</span></label>
                            <div class="activity-create-date-time">
                                <input type="date" class="form-control" id="activity-end" name="date_end_date" required data-preview-end-date>
                                <input type="time" class="form-control" name="date_end_time" value="11:00" required data-preview-end-time>
                            </div>
                        </div>
                    </div>
                    <div class="activity-create-field mt-3">
                        <label for="activity-location">Địa điểm <span>*</span></label>
                        <div class="activity-create-input-icon">
                            <i class="bi bi-geo-alt-fill"></i>
                            <input type="text" class="form-control" id="activity-location" name="location"
                                   placeholder="Nhập địa điểm tổ chức..." required data-preview-location>
                        </div>
                    </div>
                </section>

                <section class="activity-create-panel">
                    <h2><i class="bi bi-gear-fill"></i>Cài đặt hoạt động</h2>
                    <div class="row g-3 align-items-start">
                        <div class="col-md-6 activity-create-field">
                            <label for="activity-capacity">Số lượng TNV cần tuyển</label>
                            <div class="activity-create-input-icon">
                                <i class="bi bi-people-fill"></i>
                                <input type="number" class="form-control" id="activity-capacity" name="max_volunteers"
                                       min="1" placeholder="Ví dụ: 50" data-preview-capacity>
                            </div>
                        </div>
                        <div class="col-md-6 activity-create-field">
                            <label for="registration-deadline-date">Hạn đăng ký</label>
                            <div class="activity-create-date-time">
                                <input type="date" class="form-control" id="registration-deadline-date" name="registration_deadline_date">
                                <input type="time" class="form-control" name="registration_deadline_time">
                            </div>
                            <small class="text-muted">Để trống nếu không giới hạn.</small>
                        </div>
                    </div>
                    <div class="activity-create-field mt-3">
                        <label for="activity-skills">Kỹ năng yêu cầu <span class="text-muted fw-normal">(tùy chọn)</span></label>
                        <?php if (!empty($skills)): ?>
                            <details class="activity-create-skill-picker">
                                <summary id="activity-skills"><i class="bi bi-tags-fill"></i><span data-skill-placeholder>Chọn kỹ năng...</span><i class="bi bi-chevron-down ms-auto"></i></summary>
                                <div class="activity-create-skill-options">
                                    <?php foreach ($skills as $skill): ?>
                                        <label>
                                            <input type="checkbox" name="required_skills[]" value="<?= (int)$skill['id'] ?>" data-preview-skill>
                                            <span><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!empty($skill['category'])): ?><small><?= htmlspecialchars($skill['category'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        <?php else: ?>
                            <div class="activity-create-no-skills">Chưa có kỹ năng nào trong danh mục. Bạn vẫn có thể tạo hoạt động.</div>
                        <?php endif; ?>
                    </div>

                    <div class="activity-create-status mt-3">
                        <span class="activity-create-status-label">Trạng thái</span>
                        <label class="activity-create-switch">
                            <input type="checkbox" checked data-status-toggle>
                            <span class="activity-create-switch-slider"></span>
                            <span data-status-text>Công khai</span>
                        </label>
                    </div>

                    <div class="activity-create-upload-section mt-3">
                        <h3><i class="bi bi-image-fill"></i>Hình ảnh &amp; Tài liệu</h3>
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="activity-create-upload-zone" data-image-dropzone>
                                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-input>
                                    <span class="activity-create-upload-icon"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                                    <strong>Kéo thả ảnh vào đây hoặc</strong>
                                    <span class="btn btn-primary btn-sm">Chọn ảnh</span>
                                    <small>JPG, PNG, WEBP · tối đa 5 MB</small>
                                </label>
                            </div>
                            <div class="col-md-5">
                                <label for="activity-attachments" class="activity-create-attachment-label"><i class="bi bi-paperclip"></i>Tài liệu đính kèm <span>(tùy chọn)</span></label>
                                <input class="form-control activity-create-attachment-input" type="file" id="activity-attachments" name="attachments[]" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" multiple data-attachments-input>
                                <small class="text-muted">PDF, DOCX · tối đa 5 tệp, tổng 10 MB</small>
                                <ul class="activity-create-attachment-list" data-attachments-list></ul>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="activity-create-preview-column">
                <section class="activity-create-preview">
                    <div class="activity-create-preview-heading">
                        <h2><i class="bi bi-eye-fill"></i>Xem trước</h2>
                        <span class="activity-create-visibility" data-preview-visibility>
                            <i class="bi bi-circle-fill"></i><span data-preview-visibility-label>Công khai</span>
                        </span>
                    </div>
                    <div class="activity-create-cover">
                        <img src="" alt="Ảnh đại diện hoạt động" data-preview-image hidden>
                        <span data-preview-cover-icon><i class="bi bi-heart-pulse-fill"></i></span>
                        <i class="bi bi-stars activity-create-cover-star"></i>
                        <div data-preview-cover-text>Chung tay<br>vì cộng đồng</div>
                    </div>
                    <div class="activity-create-preview-body">
                        <div class="activity-create-preview-title-row">
                            <span class="activity-create-preview-heart"><i class="bi bi-heart-fill"></i></span>
                            <h3 data-preview-output-title>Tên hoạt động của bạn</h3>
                        </div>
                        <p class="activity-create-preview-description" data-preview-output-description>Mô tả hoạt động sẽ xuất hiện tại đây.</p>
                        <div class="activity-create-preview-meta">
                            <div><i class="bi bi-calendar-event"></i><span data-preview-output-date>Chưa chọn thời gian</span></div>
                            <div><i class="bi bi-geo-alt-fill"></i><span data-preview-output-location>Chưa có địa điểm</span></div>
                            <div><i class="bi bi-people-fill"></i><span data-preview-output-capacity>Chưa giới hạn số lượng</span></div>
                            <div><i class="bi bi-heart-fill"></i><span data-preview-output-category>Chưa chọn danh mục</span></div>
                        </div>
                        <div class="activity-create-preview-content">
                            <strong>Nội dung chính</strong>
                            <span><i class="bi bi-check-circle-fill"></i>Thông tin chương trình</span>
                            <span><i class="bi bi-check-circle-fill"></i>Thời gian và địa điểm cụ thể</span>
                            <span><i class="bi bi-check-circle-fill"></i>Cơ hội tham gia tình nguyện</span>
                            <div class="activity-create-preview-skills" data-preview-skills hidden></div>
                            <div class="activity-create-preview-attachments" data-preview-attachments hidden></div>
                        </div>
                        <button type="button" class="btn btn-primary w-100" disabled>
                            Xem hoạt động <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </section>
                <div class="activity-create-hint">
                    <i class="bi bi-lightbulb-fill"></i>
                    <span>Sau khi tạo hoạt động, bạn có thể tiếp tục thiết lập các ca trực và số lượng tình nguyện viên cho từng ca.</span>
                </div>
            </aside>
        </div>

        <div class="activity-create-actions">
            <button type="submit" class="btn btn-outline-primary activity-create-save-draft" data-submit-status="draft">
                <i class="bi bi-floppy me-2"></i>Lưu nháp
            </button>
            <button type="submit" class="btn btn-primary px-4" data-submit-status="open">
                <i class="bi bi-send-fill me-2"></i>Đăng hoạt động
            </button>
        </div>
    </form>
</section>
