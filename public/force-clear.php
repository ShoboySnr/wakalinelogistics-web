<?php
// Force clear all caches and rebuild

echo "<h1>Force Clearing All Caches...</h1>";

chdir(__DIR__ . '/..');

// Delete cached files manually
$cacheFiles = [
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes-v7.php',
    'bootstrap/cache/services.php',
    'bootstrap/cache/packages.php',
];

echo "<h2>Deleting cached files...</h2>";
foreach ($cacheFiles as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    if (file_exists($fullPath)) {
        unlink($fullPath);
        echo "<p style='color: green;'>✓ Deleted: {$file}</p>";
    } else {
        echo "<p style='color: orange;'>- Not found: {$file}</p>";
    }
}

// Clear Laravel caches
echo "<h2>Clearing Laravel caches...</h2>";
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$commands = [
    'clear-compiled',
    'cache:clear',
    'config:clear',
    'route:clear',
    'view:clear',
];

foreach ($commands as $command) {
    echo "<p>Running: php artisan {$command}</p>";
    try {
        $kernel->call($command);
        echo "<p style='color: green;'>✓ Success</p>";
    } catch (\Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2 style='color: green;'>✅ All Caches Cleared!</h2>";
echo "<p><strong>Now test these URLs:</strong></p>";
echo "<ul>";
echo "<li><a href='/client/dashboard'>Direct: /client/dashboard</a></li>";
echo "<li><a href='/client-dashboard-with-middleware'>Test: /client-dashboard-with-middleware</a></li>";
echo "<li><a href='/client-status-check'>Status: /client-status-check</a></li>";
echo "</ul>";
echo "<p style='color: red;'><strong>Important:</strong> Delete this file after use!</p>";
