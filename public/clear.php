<?php

/**
 * Standalone Laravel Cache Clearer
 * Use this when Artisan routes are cached and blocking updates.
 */

// 1. Define paths
$rootPath = dirname(__DIR__);
$cachePath = $rootPath . '/bootstrap/cache';

echo "<h1>Laravel Emergency Cache Clear</h1>";

// 2. Clear bootstrap/cache files
$files = [
    'config.php',
    'routes-v7.php', // Laravel 7+ route cache
    'routes.php',    // Older route cache
    'services.php',
    'packages.php'
];

foreach ($files as $file) {
    $path = $cachePath . '/' . $file;
    if (file_exists($path)) {
        if (unlink($path)) {
            echo "<p style='color:green;'>DELETED: $file</p>";
        } else {
            echo "<p style='color:red;'>FAILED TO DELETE: $file (Check permissions)</p>";
        }
    } else {
        echo "<p style='color:gray;'>NOT FOUND: $file (Already clear)</p>";
    }
}

echo "<h3>Attempting to run Artisan commands...</h3>";
try {
    // Try to run artisan commands via shell if possible
    $output = shell_exec('php artisan optimize:clear 2>&1');
    echo "<pre>$output</pre>";
} catch (Exception $e) {
    echo "<p style='color:orange;'>Shell access restricted. Manual file deletion is usually enough.</p>";
}

echo "<hr><p>Now try visiting <b>/generate-report</b> again.</p>";
