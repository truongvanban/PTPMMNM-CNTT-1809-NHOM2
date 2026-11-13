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
    $approvedCount = (new \App\Models\ReplacementModel())->autoApproveStartedShiftRequests();
    fwrite(STDOUT, "Automatically approved {$approvedCount} started-shift absence request(s).\n");
} catch (\Throwable $e) {
    error_log('[Auto Approve Started-Shift Absences] ' . $e->getMessage());
    fwrite(STDERR, "Failed to auto-approve started-shift absence requests. See the PHP error log for details.\n");
    exit(1);
}
