<?php
$baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
$monthStart = new DateTimeImmutable($selectedMonth . '-01');
$monthEnd = $monthStart->modify('last day of this month');
$calendarStart = $monthStart->modify('monday this week');
$calendarEnd = $monthEnd->modify('sunday this week');
$previousMonth = $monthStart->modify('-1 month')->format('Y-m');
$nextMonth = $monthStart->modify('+1 month')->format('Y-m');
$monthLabels = [
    1 => 'Tháng Một', 2 => 'Tháng Hai', 3 => 'Tháng Ba', 4 => 'Tháng Tư',
    5 => 'Tháng Năm', 6 => 'Tháng Sáu', 7 => 'Tháng Bảy', 8 => 'Tháng Tám',
    9 => 'Tháng Chín', 10 => 'Tháng Mười', 11 => 'Tháng Mười Một', 12 => 'Tháng Mười Hai',
];
$assignmentsByDate = [];
$monthAssignmentCount = 0;
$monthEventCount = 0;
$monthConfirmedCount = 0;
$monthDates = [];
foreach ($assignments as $assignment) {
    $date = $assignment['shift_date'];
    $assignmentsByDate[$date][] = $assignment;
    if (substr($date, 0, 7) === $selectedMonth) {
        $monthEventCount++;
        if ($assignment['assignment_status'] !== 'removed') {
            $monthAssignmentCount++;
            $monthDates[$date] = true;
            if (in_array(($assignment['attendance_status'] ?? ''), ['confirmed', 'completed'], true)) {
                $monthConfirmedCount++;
            }
        }
    }
}
$monthDayCount = count($monthDates);
$weekdays = ['Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ Nhật'];
?>
<section class="volunteer-schedule">
    <header class="volunteer-schedule-hero">
        <div>
            <span class="volunteer-schedule-eyebrow"><i class="bi bi-calendar-heart me-1"></i> KẾ HOẠCH CỦA BẠN</span>
            <h1 class="h3 fw-bold mb-2">Lịch phân công cá nhân</h1>
            <p class="mb-0">Mọi ca trực đã được phân công, gói gọn trong một lịch trình dễ theo dõi.</p>
        </div>
        <a href="<?= $baseUrl ?>/volunteer/replacement" class="btn btn-light volunteer-schedule-report">
            <i class="bi bi-person-exclamation me-2"></i>Báo vắng ca trực
        </a>
    </header>

    <div class="volunteer-schedule-stats" aria-label="Tổng quan lịch tháng">
        <article class="volunteer-schedule-stat">
            <span class="volunteer-schedule-stat-icon"><i class="bi bi-calendar2-check"></i></span>
            <div><strong><?= $monthAssignmentCount ?></strong><span>Ca trong tháng</span></div>
        </article>
        <article class="volunteer-schedule-stat">
            <span class="volunteer-schedule-stat-icon is-blue"><i class="bi bi-calendar2-week"></i></span>
            <div><strong><?= $monthDayCount ?></strong><span>Ngày có ca</span></div>
        </article>
        <article class="volunteer-schedule-stat">
            <span class="volunteer-schedule-stat-icon is-amber"><i class="bi bi-check2-circle"></i></span>
            <div><strong><?= $monthConfirmedCount ?></strong><span>Đã xác nhận</span></div>
        </article>
    </div>

    <div class="card border-0 shadow-sm rounded-4 volunteer-calendar">
        <div class="volunteer-calendar-toolbar">
            <div>
                <span class="volunteer-calendar-month">
                    <?= $monthLabels[(int)$monthStart->format('n')] ?> <?= $monthStart->format('Y') ?>
                </span>
                <span class="volunteer-calendar-caption"><?= $monthAssignmentCount ?> ca trực · <?= $monthDayCount ?> ngày</span>
            </div>
            <nav class="volunteer-calendar-navigation" aria-label="Điều hướng lịch">
                <a class="btn btn-outline-secondary btn-sm"
                   href="<?= $baseUrl ?>/volunteer/schedule?month=<?= urlencode($previousMonth) ?>"
                   aria-label="Tháng trước"><i class="bi bi-chevron-left"></i><span class="ms-1 d-none d-sm-inline">Tháng trước</span></a>
                <a class="btn btn-success btn-sm"
                   href="<?= $baseUrl ?>/volunteer/schedule?month=<?= date('Y-m') ?>"><i class="bi bi-calendar-day me-1"></i>Hôm nay</a>
                <a class="btn btn-outline-secondary btn-sm"
                   href="<?= $baseUrl ?>/volunteer/schedule?month=<?= urlencode($nextMonth) ?>"
                   aria-label="Tháng sau"><span class="me-1 d-none d-sm-inline">Tháng sau</span><i class="bi bi-chevron-right"></i></a>
            </nav>
        </div>

        <?php if ($monthEventCount === 0): ?>
            <div class="volunteer-schedule-empty">
                <span><i class="bi bi-calendar2-plus"></i></span>
                <div>
                    <strong><?= empty($assignments) ? 'Bạn chưa có ca trực nào' : 'Tháng này chưa có ca trực' ?></strong>
                    <p class="mb-0"><?= empty($assignments) ? 'Khám phá và đăng ký ca trực phù hợp để bắt đầu hành trình tình nguyện.' : 'Các ca đã được phân công ở tháng khác vẫn được giữ trong lịch của bạn.' ?></p>
                </div>
                <?php if (empty($assignments)): ?>
                    <a class="btn btn-success btn-sm" href="<?= $baseUrl ?>/volunteer/shifts">Tìm ca trực</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="volunteer-calendar-scroll" role="region" aria-label="Lịch phân công tháng <?= $monthStart->format('m/Y') ?>" tabindex="0">
            <div class="volunteer-calendar-grid">
                <?php foreach ($weekdays as $weekday): ?>
                    <div class="volunteer-calendar-weekday"><?= $weekday ?></div>
                <?php endforeach; ?>

                <?php for ($day = $calendarStart; $day <= $calendarEnd; $day = $day->modify('+1 day')): ?>
                    <?php
                    $dateKey = $day->format('Y-m-d');
                    $isCurrentMonth = $day->format('Y-m') === $selectedMonth;
                    $isToday = $dateKey === date('Y-m-d');
                    $dayAssignments = $assignmentsByDate[$dateKey] ?? [];
                    ?>
                    <div class="volunteer-calendar-day <?= $isCurrentMonth ? '' : 'is-outside-month' ?> <?= $isToday ? 'is-current-day' : '' ?> <?= $dayAssignments ? 'has-events' : '' ?>">
                        <div class="volunteer-calendar-day-heading">
                            <span class="volunteer-calendar-day-number <?= $isToday ? 'is-today' : '' ?>">
                                <?= $day->format('j') ?>
                            </span>
                            <?php if ($isToday): ?><span class="volunteer-calendar-today-label">Hôm nay</span><?php endif; ?>
                        </div>
                        <?php foreach ($dayAssignments as $asgn): ?>
                            <?php
                            $replacementStatus = $asgn['replacement_status'] ?? '';
                            $attendanceStatus = $asgn['attendance_status'] ?? '';
                            $isAssignmentActive = in_array($asgn['assignment_status'], ['assigned', 'confirmed'], true);
                            $canRespondToAssignment = $isAssignmentActive && $attendanceStatus !== 'completed';
                            ?>
                            <article class="volunteer-calendar-event <?= $asgn['assignment_status'] === 'removed' ? 'is-removed' : '' ?>">
                                <div class="volunteer-calendar-event-time"><i class="bi bi-clock me-1"></i><?= substr($asgn['time_start'], 0, 5) ?>–<?= substr($asgn['time_end'], 0, 5) ?></div>
                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($asgn['shift_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted volunteer-calendar-activity"><?= htmlspecialchars($asgn['activity_title'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if (!empty($asgn['activity_location'])): ?>
                                    <div class="small text-muted volunteer-calendar-location">
                                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($asgn['activity_location'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($asgn['assignment_status'] === 'removed'): ?>
                                    <span class="badge bg-secondary mt-2">Đã gỡ phân công</span>
                                <?php elseif ($replacementStatus === 'pending'): ?>
                                    <span class="badge bg-warning-soft text-warning mt-2">Đang chờ duyệt báo vắng</span>
                                <?php elseif ($replacementStatus === 'rejected'): ?>
                                    <span class="badge bg-danger-soft text-danger mt-2">Báo vắng bị từ chối</span>
                                    <?php if (!empty($asgn['replacement_coordinator_note'])): ?>
                                        <div class="small text-muted mt-1">
                                            <?= nl2br(htmlspecialchars($asgn['replacement_coordinator_note'], ENT_QUOTES, 'UTF-8')) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif ($attendanceStatus === 'completed'): ?>
                                    <span class="badge bg-primary-subtle text-primary mt-2">Đã hoàn thành</span>
                                <?php elseif ($attendanceStatus === 'confirmed'): ?>
                                    <span class="badge bg-success-soft text-success mt-2">Đã xác nhận</span>
                                <?php elseif ($isAssignmentActive): ?>
                                    <span class="badge bg-primary-subtle text-primary mt-2">Đã phân công</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary mt-2"><?= htmlspecialchars($asgn['assignment_status'] ?: 'Chưa xác định', ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                <?php if (!empty($asgn['replacement_reason'])): ?>
                                    <div class="small text-muted mt-1">
                                        <strong>Lý do báo vắng:</strong>
                                        <?= nl2br(htmlspecialchars($asgn['replacement_reason'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($replacementStatus === 'rejected'): ?>
                                    <?php if (!empty($asgn['coordinator_email'])): ?>
                                        <a class="d-block small mt-2"
                                           href="mailto:<?= rawurlencode($asgn['coordinator_email']) ?>?subject=<?= rawurlencode('Hỗ trợ ca trực ' . $asgn['shift_name']) ?>">
                                            <i class="bi bi-envelope me-1"></i>Liên hệ điều phối viên
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($isAssignmentActive): ?>
                                        <a href="<?= $baseUrl ?>/volunteer/replacement" class="d-inline-block small mt-1">Gửi lại báo vắng</a>
                                    <?php endif; ?>
                                <?php elseif ($replacementStatus !== 'pending' && $attendanceStatus !== 'confirmed' && empty($asgn['replacement_request_id']) && $canRespondToAssignment): ?>
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        <form action="<?= $baseUrl ?>/volunteer/confirm" method="POST" class="m-0">
                                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="assignment_id" value="<?= (int)$asgn['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-sm py-1 px-2">
                                                <i class="bi bi-check-lg me-1"></i>Xác nhận
                                            </button>
                                        </form>
                                        <a href="<?= $baseUrl ?>/volunteer/replacement" class="btn btn-outline-danger btn-sm py-1 px-2">Báo vắng</a>
                                    </div>
                                <?php elseif ($canRespondToAssignment && $attendanceStatus === 'confirmed' && $replacementStatus !== 'pending' && $replacementStatus !== 'approved'): ?>
                                    <a href="<?= $baseUrl ?>/volunteer/replacement" class="d-inline-block small mt-2">Báo bận đột xuất</a>
                                <?php endif; ?>
                                <?php if (!empty($asgn['coordinator_name'])): ?>
                                    <div class="small text-muted mt-2"><?= htmlspecialchars($asgn['coordinator_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                                <?php if (!empty($asgn['coordinator_email'])): ?>
                                    <a class="d-block small text-break" href="mailto:<?= htmlspecialchars($asgn['coordinator_email'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($asgn['coordinator_email'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($asgn['coordinator_phone'])): ?>
                                    <a class="d-block small" href="tel:<?= htmlspecialchars($asgn['coordinator_phone'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($asgn['coordinator_phone'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</section>
