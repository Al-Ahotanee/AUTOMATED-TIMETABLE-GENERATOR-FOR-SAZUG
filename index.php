<?php
require_once __DIR__ . '/SystemCore.php';

$core = new SystemCore();

// Enforce authentication before loading the application interface
$core->requireAuth();

// If authenticated, load the SPA dashboard
// (This file will be created in Phase 5)
if (file_exists(__DIR__ . '/dashboard.php')) {
    require_once __DIR__ . '/dashboard.php';
} else {
    echo "<h2>Installation in progress...</h2>";
    echo "<p>System Core and Database are active. Please proceed to Phase 5 to generate the dashboard.php file.</p>";
}
?>
