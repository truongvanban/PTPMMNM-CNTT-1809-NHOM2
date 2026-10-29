<?php
/**
 * app/Core/Controller.php
 * Base Controller - render view, redirect, helpers
 */

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected string $layout = 'main';

    /**
     * Render view với layout
     */
    protected function render(string $view, array $data = [], ?string $layout = null): void
    {
        extract($data);
        $layout = $layout ?? $this->layout;
        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View không tồn tại: $viewFile");
        }

        // Capture view content vào buffer
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render với layout
        $layoutFile = VIEW_PATH . '/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Redirect
     */
    protected function redirect(string $path, array $flash = []): void
    {
        foreach ($flash as $key => $msg) {
            Session::flash($key, $msg);
        }
        $base = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
        header("Location: $base$path");
        exit;
    }

    /**
     * JSON response
     */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Escape output để chống XSS
     */
    protected function e(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Lấy dữ liệu POST đã sanitize
     */
    protected function post(string $key, mixed $default = ''): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Lấy dữ liệu GET đã sanitize
     */
    protected function get(string $key, mixed $default = ''): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function getPageSize(int $default = 15): int
    {
        $value = $this->get('per_page', '');
        if ($value === 'all') {
            return 0;
        }

        if (!is_string($value) && !is_int($value)) {
            return $default;
        }

        $pageSize = filter_var($value, FILTER_VALIDATE_INT);
        return in_array($pageSize, [5, 10, 15, 20], true) ? $pageSize : $default;
    }

    /**
     * Kiểm tra CSRF token từ POST
     */
    protected function verifyCsrf(): void
    {
        $token = $this->post('_csrf_token', '');
        if (!is_string($token) || !Session::verifyCsrf($token)) {
            Session::flash('error', 'Phiên làm việc hết hạn hoặc yêu cầu không hợp lệ. Vui lòng thử lại.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }

    /**
     * Lấy URL hiện tại
     */
    protected function url(string $path = ''): string
    {
        $base = rtrim(getenv('APP_URL') ?: 'http://localhost/VolunteerHub/public', '/');
        return $base . $path;
    }

    /**
     * Kiểm tra quyền sở hữu dữ liệu (để chống thay đổi ID URL)
     */
    protected function requireOwnership(int $ownerId): void
    {
        $userId = Session::userId();
        if ($userId !== $ownerId && !Session::hasRole('admin') && !Session::hasRole('coordinator')) {
            $this->redirect('/403');
        }
    }
}
