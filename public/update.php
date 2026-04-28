<?php
// This file helps update the application on cPanel
// Visit: https://app.wakalinelogistics.com/update.php

echo "<h1>Updating Application...</h1>";

// Change to the base directory
chdir(__DIR__ . '/..');

echo "<p>Current directory: " . getcwd() . "</p>";

// Run composer dump-autoload
echo "<h2>Running composer dump-autoload...</h2>";
putenv('HOME=' . __DIR__ . '/..');
putenv('COMPOSER_HOME=' . __DIR__ . '/../.composer');
exec('composer dump-autoload -o 2>&1', $output, $return);
echo "<pre>" . implode("\n", $output) . "</pre>";
echo "<p>Return code: " . $return . "</p>";

// Alternative: Manually regenerate autoload files
if ($return !== 0) {
    echo "<p style='color: orange;'>Composer failed, trying alternative method...</p>";
    require __DIR__.'/../vendor/autoload.php';
    
    // Force Laravel to regenerate class maps
    echo "<h2>Regenerating Laravel class maps...</h2>";
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->call('clear-compiled');
    echo "<p style='color: green;'>Class maps regenerated!</p>";
}

// Clear all caches
echo "<h2>Clearing caches...</h2>";
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$commands = [
    'config:clear',
    'route:clear',
    'view:clear',
    'cache:clear',
];

foreach ($commands as $command) {
    echo "<p>Running: php artisan {$command}</p>";
    $kernel->call($command);
}

// Re-cache routes for production
echo "<h2>Re-caching routes...</h2>";
$kernel->call('route:cache');
echo "<p style='color: green;'>Routes cached successfully!</p>";

echo "<h2 style='color: green;'>✅ Update Complete!</h2>";
echo "<p><a href='/client'>Go to Client Login</a></p>";
echo "<p><a href='/client/dashboard'>Go to Client Dashboard</a></p>";
echo "<p><strong>Important:</strong> Delete this file after use for security!</p>";
