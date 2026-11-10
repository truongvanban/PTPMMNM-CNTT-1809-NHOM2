<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

\App\Core\Autoloader::register();

try {
    $removedCount = (new \App\Models\AssignmentModel())->removeExpiredUnconfirmed();
    fwrite(STDOUT, "Removed {$removedCount} unconfirmed assignment(s).\n");
} catch (\Throwable $e) {
    error_log('[Expire Unconfirmed Assignments] ' . $e->getMessage());
    fwrite(STDERR, "Failed to expire unconfirmed assignments. See the PHP error log for details.\n");
    exit(1);
}
