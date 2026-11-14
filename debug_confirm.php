<?php
require_once __DIR__ . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/config/app.php';
\App\Core\Session::start();
\App\Models\Database::initialize();

$service = new \App\Services\AssignmentService();

echo 'Testing confirmRun flow...' . PHP_EOL;

// List all active runs
$db = \App\Models\Database::getInstance();
$stmt = $db->query("SELECT ar.id, ar.activity_id, ar.status, ar.input_snapshot FROM assignment_runs ar ORDER BY ar.id DESC LIMIT 20");
$stmt->execute();
$runs = $stmt->fetchAll();
echo 'Total runs: ' . count($runs) . PHP_EOL;

foreach ($runs as $run) {
    echo PHP_EOL . '--- Run ID: ' . $run['id'] . ' ---' . PHP_EOL;
    echo 'Activity: ' . $run['activity_id'] . PHP_EOL;
    echo 'Status: ' . $run['status'] . PHP_EOL;
    
    if ($run['status'] === 'preview') {
        // Simulate confirmation
        try {
            // Check the data
            $inputSnapshot = json_decode($run['input_snapshot'], true);
            echo 'Input snapshot keys: ' . implode(', ', array_keys($inputSnapshot ?? [])) . PHP_EOL;
            
            if (isset($inputSnapshot['shifts'])) {
                echo 'Number of shifts in snapshot: ' . count($inputSnapshot['shifts']) . PHP_EOL;
                echo 'Shift IDs: ' . implode(', ', array_column($inputSnapshot['shifts'], 'id')) . PHP_EOL;
            }
            
            if (isset($inputSnapshot['registrations'])) {
                $regCount = count($inputSnapshot['registrations']);
                echo 'Number of registration shifts in snapshot: ' . $regCount . PHP_EOL;
                foreach ($inputSnapshot['registrations'] as $shiftId => $users) {
                    echo "  Shift $shiftId: " . count($users) . " users" . PHP_EOL;
                }
            }
            
            if (isset($inputSnapshot['volunteers'])) {
                echo 'Number of volunteers: ' . count($inputSnapshot['volunteers']) . PHP_EOL;
            }
            
            // Check current approved registrations
            $currentRegistrations = $service->getApprovedRegistrations((int)$run['activity_id'], true);
            echo 'Current approved registration shifts: ' . count($currentRegistrations) . PHP_EOL;
            
            if (count($currentRegistrations) !== count($inputSnapshot['registrations'] ?? [])) {
                echo 'REGISTRATION MISMATCH!' . PHP_EOL;
            }
        } catch (Exception $e) {
            echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
            echo 'TRACE: ' . $e->getTraceAsString() . PHP_EOL;
        }
    }
}
