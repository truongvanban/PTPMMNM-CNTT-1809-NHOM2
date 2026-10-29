<?php
/**
 * app/Core/Session.php
 * Quản lý session an toàn
 */

declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        $name = getenv('SESSION_NAME') ?: 'VH_SESSION';
        $lifetime = (int)(getenv('SESSION_LIFETIME') ?: 7200);

        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,     // Chặn JS đọc cookie
            'samesite' => 'Lax',
        ]);
        session_start();

        // Regenerate session ID mỗi 30 phút để giảm session fixation
        if (!isset($_SESSION['_last_regenerated'])) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerated'] = time();
        } elseif (time() - $_SESSION['_last_regenerated'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerated'] = time();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /**
     * Flash message - hiển thị một lần rồi xoá
     */
    public static function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        $msg = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $msg;
    }

    /**
     * CSRF Token
     */
    public static function generateCsrf(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function verifyCsrf(string $token): bool
    {
        $stored = $_SESSION['_csrf_token'] ?? '';
        return hash_equals($stored, $token);
    }

    /**
     * Xác thực đăng nhập
     */
    public static function login(array $user): void
    {
        session_regenerate_id(true); // Đổi session ID sau đăng nhập
        $_SESSION['_last_regenerated'] = time();
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_roles'] = array_filter(explode(',', $user['role_names'] ?? ''));
    }

    public static function refreshUser(array $user): void
    {
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_roles'] = array_filter(explode(',', $user['role_names'] ?? ''));
    }

    public static function invalidateLogin(): void
    {
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_email'],
            $_SESSION['user_name'],
            $_SESSION['user_roles'],
            $_SESSION['_csrf_token']
        );
        session_regenerate_id(true);
        $_SESSION['_last_regenerated'] = time();
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function userName(): string
    {
        return $_SESSION['user_name'] ?? '';
    }

    public static function userRoles(): array
    {
        return $_SESSION['user_roles'] ?? [];
    }

    public static function hasRole(string $role): bool
    {
        return in_array($role, self::userRoles(), true);
    }

    public static function logout(): void
    {
        self::destroy();
    }
}
