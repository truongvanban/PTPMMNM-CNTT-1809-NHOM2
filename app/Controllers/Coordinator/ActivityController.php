<?php
/**
 * app/Controllers/Coordinator/ActivityController.php
 * Quản lý hoạt động tình nguyện của Điều phối viên
 */

declare(strict_types=1);

namespace App\Controllers\Coordinator;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityModel;
use App\Models\SkillModel;

class ActivityController extends Controller
{
    protected string $layout = 'coordinator';
    private ActivityModel $activityModel;

    public function __construct()
    {
        $this->activityModel = new ActivityModel();
    }

    public function index(): void
    {
        $coordId = (int)Session::userId();
        $status = $this->get('status', '');
        $keyword = trim($this->get('keyword', ''));
        $page = max(1, (int)$this->get('page', 1));
        $result = $this->activityModel->getByCoordinatorPage(
            $coordId,
            $status,
            $keyword,
            $page,
            $this->getPageSize(15)
        );
        $allActivities = $this->activityModel->getByCoordinator($coordId);

        $now = date('Y-m-d H:i:s');
        $nowTimestamp = strtotime($now);

        // Gán trạng thái hiệu lực cho hoạt động trong trang hiện tại (dùng hiển thị trên bảng).
        foreach ($result['data'] as &$activity) {
            $activity['effective_status'] = $this->activityModel->resolveActivityStatus($activity, $now);
        }
        unset($activity);

        $activityStats = [
            'total' => count($allActivities),
            'open' => 0,
            'draft' => 0,
            'closed' => 0,
            'ongoing' => 0,
            'upcoming' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'shifts' => 0,
            'registrations' => 0,
        ];
        // Tính thống kê Widget dựa trên trạng thái HIỆU LỰC (theo ngày thực tế) để khớp với danh sách hiển thị.
        foreach ($allActivities as &$activity) {
            $activity['effective_status'] = $this->activityModel->resolveActivityStatus($activity, $now);
            $statusKey = $activity['effective_status'];
            if (isset($activityStats[$statusKey])) {
                $activityStats[$statusKey]++;
            }
            // 'open' nhưng thời gian bắt đầu vẫn trong tương lai => sắp diễn ra.
            $dateStart = $activity['date_start'] ?? null;
            if ($statusKey === 'open' && $dateStart !== null && strtotime($dateStart) > $nowTimestamp) {
                $activityStats['upcoming']++;
            }
            $activityStats['shifts'] += (int)$activity['shift_count'];
            $activityStats['registrations'] += (int)$activity['reg_count'];
        }
        unset($activity);

        $this->render('coordinator.activities.index', [
            'title'      => 'Quản Lý Hoạt Động | VolunteerHub',
            'activities' => $result['data'],
            'total' => $result['total'],
            'page' => $result['current_page'],
            'lastPage' => $result['last_page'],
            'perPage' => $result['per_page'],
            'keyword' => $keyword,
            'status'     => $status,
            'coordinatorName' => Session::userName(),
            'activityStats' => $activityStats,
            'csrfToken'  => Session::generateCsrf(),
            'success'    => Session::getFlash('success'),
            'error'      => Session::getFlash('error'),
        ]);
    }

    public function createForm(): void
    {
        $this->render('coordinator.activities.create', [
            'title'     => 'Tạo Hoạt Động Mới | VolunteerHub',
            'categories' => ActivityModel::CATEGORIES,
            'skills'    => (new SkillModel())->getActive(),
            'csrfToken' => Session::generateCsrf(),
            'error'     => Session::getFlash('error'),
        ]);
    }

    public function create(): void
    {
        $this->verifyCsrf();
        $coordId = (int)Session::userId();

        $postedTitle = $this->post('title', '');
        $postedDescription = $this->post('description', '');
        $postedCategory = $this->post('category', '');
        $postedLocation = $this->post('location', '');
        $title = is_string($postedTitle) ? trim($postedTitle) : '';
        $description = is_string($postedDescription) ? trim($postedDescription) : '';
        $category = is_string($postedCategory) ? trim($postedCategory) : '';
        $location = is_string($postedLocation) ? trim($postedLocation) : '';
        $dateStart   = $this->combineDateAndTime(
            $this->post('date_start_date', ''),
            $this->post('date_start_time', '')
        );
        $dateEnd     = $this->combineDateAndTime(
            $this->post('date_end_date', ''),
            $this->post('date_end_time', '')
        );
        $deadlineDate = $this->post('registration_deadline_date', '');
        $deadlineTime = $this->post('registration_deadline_time', '');
        if (is_string($deadlineDate) && $deadlineDate !== '' && $deadlineTime === '') {
            $deadlineTime = '23:59';
        }
        $deadline = ($deadlineDate === '' && $deadlineTime === '')
            ? null
            : $this->combineDateAndTime($deadlineDate, $deadlineTime);
        $maxVol      = (int)$this->post('max_volunteers', 0);
        $status      = $this->post('status', 'draft');

        if (
            $title === ''
            || $description === ''
            || !in_array($category, ActivityModel::CATEGORIES, true)
            || $location === ''
            || $dateStart === null
            || $dateEnd === null
            || ($deadlineDate !== '' || $deadlineTime !== '') && $deadline === null
        ) {
            $this->redirect('/coordinator/activities/create', ['error' => 'Vui lòng kiểm tra các trường bắt buộc và thời gian đã nhập.']);
            return;
        }

        if (strtotime($dateEnd) <= strtotime($dateStart) || ($deadline !== null && strtotime($deadline) > strtotime($dateStart))) {
            $this->redirect('/coordinator/activities/create', ['error' => 'Thời gian kết thúc phải sau thời gian bắt đầu.']);
            return;
        }

        if ($maxVol < 0 || !in_array($status, ['draft', 'open'], true)) {
            $this->redirect('/coordinator/activities/create', ['error' => 'Thông tin số lượng hoặc trạng thái không hợp lệ.']);
            return;
        }

        $skills = (new SkillModel())->getActive();
        $activeSkillIds = array_fill_keys(array_map('intval', array_column($skills, 'id')), true);
        $postedSkillIds = $this->post('required_skills', []);
        if (!is_array($postedSkillIds)) {
            $postedSkillIds = [];
        }
        $skillIds = [];
        foreach ($postedSkillIds as $postedSkillId) {
            $skillId = filter_var($postedSkillId, FILTER_VALIDATE_INT);
            if ($skillId === false || !isset($activeSkillIds[$skillId])) {
                $this->redirect('/coordinator/activities/create', ['error' => 'Danh sách kỹ năng yêu cầu không hợp lệ.']);
                return;
            }
            $skillIds[$skillId] = $skillId;
        }

        $savedFiles = [];
        try {
            $uploadDirectory = dirname(__DIR__, 3) . '/public/uploads/activities';
            $image = $this->saveUploadedFile(
                $_FILES['image'] ?? null,
                [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ],
                5 * 1024 * 1024,
                $uploadDirectory
            );
            if ($image !== null) {
                $savedFiles[] = $image['absolute_path'];
            }

            $attachments = $this->saveUploadedAttachments($uploadDirectory);
            foreach ($attachments as $attachment) {
                $savedFiles[] = $attachment['absolute_path'];
                unset($attachment['absolute_path']);
            }

            $actId = $this->activityModel->createWithDetails([
                'coordinator_id'        => $coordId,
                'title'                 => $title,
                'description'           => $description,
                'category'              => $category,
                'location'              => $location,
                'date_start'            => $dateStart,
                'date_end'              => $dateEnd,
                'registration_deadline' => $deadline,
                'status'                => $status,
                'max_volunteers'        => $maxVol > 0 ? $maxVol : null,
                'image_url'             => $image['file_path'] ?? null,
            ], array_values($skillIds), $attachments);
        } catch (\InvalidArgumentException $e) {
            foreach ($savedFiles as $savedFile) {
                if (is_file($savedFile)) {
                    unlink($savedFile);
                }
            }
            $this->redirect('/coordinator/activities/create', ['error' => $e->getMessage()]);
            return;
        } catch (\Throwable $e) {
            foreach ($savedFiles as $savedFile) {
                if (is_file($savedFile)) {
                    unlink($savedFile);
                }
            }
            error_log('[ActivityController] Không thể tạo hoạt động: ' . $e->getMessage());
            $this->redirect('/coordinator/activities/create', ['error' => 'Không thể lưu hoạt động. Vui lòng thử lại sau.']);
            return;
        }

        $successMessage = $status === 'draft'
            ? 'Đã lưu hoạt động dưới dạng bản nháp.'
            : 'Đã tạo hoạt động. Bạn có thể quản lý ca trực từ danh sách hoạt động.';
        $this->redirect('/coordinator/activities', ['success' => $successMessage]);
    }

    private function combineDateAndTime(mixed $date, mixed $time): ?string
    {
        if (!is_string($date) || !is_string($time) || $date === '' || $time === '') {
            return null;
        }
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
        $errors = \DateTimeImmutable::getLastErrors();
        if (
            $parsed === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $parsed->format('Y-m-d H:i') !== $date . ' ' . $time
        ) {
            return null;
        }
        return $parsed->format('Y-m-d H:i:s');
    }

    private function saveUploadedFile(
        mixed $upload,
        array $allowedMimes,
        int $maxSize,
        string $uploadDirectory
    ): ?array {
        if ($upload === null || !is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Tệp tải lên bị lỗi. Vui lòng chọn lại tệp.');
        }
        if (!isset($upload['tmp_name'], $upload['size'], $upload['name']) || !is_uploaded_file($upload['tmp_name'])) {
            throw new \InvalidArgumentException('Tệp tải lên không hợp lệ.');
        }
        if ((int)$upload['size'] <= 0 || (int)$upload['size'] > $maxSize) {
            throw new \InvalidArgumentException('Kích thước tệp vượt quá giới hạn cho phép.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($upload['tmp_name']);
        if (!is_string($mimeType) || !isset($allowedMimes[$mimeType])) {
            throw new \InvalidArgumentException('Định dạng tệp không được hỗ trợ.');
        }

        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
            throw new \RuntimeException('Không thể tạo thư mục lưu tệp hoạt động.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $allowedMimes[$mimeType];
        $absolutePath = $uploadDirectory . '/' . $filename;
        if (!move_uploaded_file($upload['tmp_name'], $absolutePath)) {
            throw new \RuntimeException('Không thể lưu tệp tải lên.');
        }

        return [
            'absolute_path' => $absolutePath,
            'file_path' => '/uploads/activities/' . $filename,
            'original_name' => basename(str_replace('\\', '/', (string)$upload['name'])),
            'mime_type' => $mimeType,
            'file_size' => (int)$upload['size'],
        ];
    }

    private function saveUploadedAttachments(string $uploadDirectory): array
    {
        $uploads = $_FILES['attachments'] ?? null;
        if ($uploads === null || !is_array($uploads) || !isset($uploads['name'], $uploads['tmp_name'], $uploads['error'], $uploads['size'])) {
            return [];
        }

        $fileCount = count($uploads['name']);
        if ($fileCount > 5) {
            throw new \InvalidArgumentException('Bạn chỉ có thể đính kèm tối đa 5 tệp.');
        }

        $attachments = [];
        try {
            $totalSize = 0;
            foreach ($uploads['name'] as $index => $name) {
                if (($uploads['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $upload = [
                    'name' => $name,
                    'tmp_name' => $uploads['tmp_name'][$index] ?? '',
                    'error' => $uploads['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $uploads['size'][$index] ?? 0,
                ];
                $attachment = $this->saveUploadedFile(
                    $upload,
                    [
                        'application/pdf' => 'pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    ],
                    10 * 1024 * 1024,
                    $uploadDirectory
                );
                if ($attachment !== null) {
                    $totalSize += $attachment['file_size'];
                    if ($totalSize > 10 * 1024 * 1024) {
                        if (is_file($attachment['absolute_path'])) {
                            unlink($attachment['absolute_path']);
                        }
                        throw new \InvalidArgumentException('Tổng dung lượng tài liệu đính kèm không được vượt quá 10 MB.');
                    }
                    $attachments[] = $attachment;
                }
            }
        } catch (\Throwable $e) {
            foreach ($attachments as $attachment) {
                if (is_file($attachment['absolute_path'])) {
                    unlink($attachment['absolute_path']);
                }
            }
            throw $e;
        }
        return $attachments;
    }

    public function editForm(string $id): void
    {
        $actId = (int)$id;
        $coordId = (int)Session::userId();
        $activity = $this->activityModel->findById($actId);

        if (!$activity || ((int)$activity['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }
        // Hoạt động đã qua ngày kết thúc → chỉnh sửa.
        $now = date('Y-m-d H:i:s');
        if ($this->activityModel->isActivityEnded($activity, $now)) {
            $this->redirect('/coordinator/activities', ['error' => 'Hoạt động đã kết thúc, không thể chỉnh sửa']);
            return;
        }

        $this->render('coordinator.activities.edit', [
            'title'     => 'Chỉnh Sửa Hoạt Động | VolunteerHub',
            'activity'  => $activity,
            'csrfToken' => Session::generateCsrf(),
            'error'     => Session::getFlash('error'),
            'success'   => Session::getFlash('success'),
        ]);
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $actId = (int)$id;
        $coordId = (int)Session::userId();
        $activity = $this->activityModel->findById($actId);

        if (!$activity || ((int)$activity['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }
        // Hoạt động đã qua ngày kết thúc → cập nhật.
        $now = date('Y-m-d H:i:s');
        if ($this->activityModel->isActivityEnded($activity, $now)) {
            $this->redirect("/coordinator/activities/{$actId}/edit", ['error' => 'Hoạt động đã kết thúc, không thể cập nhật']);
            return;
        }

        $title       = trim($this->post('title', ''));
        $description = trim($this->post('description', ''));
        $location    = trim($this->post('location', ''));
        $dateStart   = $this->post('date_start', '');
        $dateEnd     = $this->post('date_end', '');
        $deadline    = $this->post('registration_deadline', '');
        $maxVol      = (int)$this->post('max_volunteers', 0);
        $status      = $this->post('status', $activity['status']);

        if (empty($title) || empty($dateStart) || empty($dateEnd)) {
            $this->redirect("/coordinator/activities/{$actId}/edit", ['error' => 'Vui lòng điền đủ các thông tin bắt buộc.']);
            return;
        }

        if (strtotime($dateEnd) <= strtotime($dateStart)) {
            $this->redirect("/coordinator/activities/{$actId}/edit", ['error' => 'Thời gian kết thúc phải sau thời gian bắt đầu.']);
            return;
        }

        // Không cho phép mở hoạt động nếu chưa có ít nhất 1 ca trực.
        if ($status === 'open' && $this->activityModel->countShifts($actId) === 0) {
            $this->redirect("/coordinator/activities/{$actId}/edit", [
                'error' => 'Không thể mở hoạt động này vì chưa có ca trực nào. Vui lòng thêm ít nhất 1 ca trực trước khi chuyển sang trạng thái "Đang mở".',
            ]);
            return;
        }

        $this->activityModel->update($actId, [
            'title'                 => $title,
            'description'           => $description,
            'location'              => $location,
            'date_start'            => $dateStart,
            'date_end'              => $dateEnd,
            'registration_deadline' => $deadline ?: null,
            'status'                => $status,
            'max_volunteers'        => $maxVol > 0 ? $maxVol : null,
        ]);

        $this->redirect("/coordinator/activities", ['success' => 'Cập nhật hoạt động thành công!']);
    }

    public function updateStatus(string $id): void
    {
        $this->verifyCsrf();
        $actId = (int)$id;
        $coordId = (int)Session::userId();
        $activity = $this->activityModel->findById($actId);

        if (!$activity || ((int)$activity['coordinator_id'] !== $coordId && !Session::hasRole('admin'))) {
            $this->redirect('/403');
            return;
        }
        // Hoạt động đã qua ngày kết thúc → thay đổi trạng thái.
        $now = date('Y-m-d H:i:s');
        if ($this->activityModel->isActivityEnded($activity, $now)) {
            $this->redirect('/coordinator/activities', ['error' => 'Hoạt động đã kết thúc, không thể thay đổi trạng thái']);
            return;
        }

        $status = $this->post('status', '');
        $allowed = ['draft', 'open', 'closed', 'ongoing', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            $this->redirect('/coordinator/activities', ['error' => 'Trạng thái không hợp lệ.']);
            return;
        }
        // Không cho phép mở hoạt động nếu chưa có ít nhất 1 ca trực.
        if ($status === 'open' && $this->activityModel->countShifts($actId) === 0) {
            $this->redirect('/coordinator/activities', [
                'error' => 'Không thể mở hoạt động này vì chưa có ca trực nào. Vui lòng thêm ít nhất 1 ca trực trước khi chuyển sang trạng thái "Đang mở".',
            ]);
            return;
        }
        $this->activityModel->updateStatus($actId, $status);
        $this->redirect('/coordinator/activities', ['success' => "Đã chuyển trạng thái hoạt động sang: $status"]);
    }
}
