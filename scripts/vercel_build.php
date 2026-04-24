<?php

$isVercel = getenv('VERCEL');

if (! $isVercel) {
    return;
}

$projectRoot = dirname(__DIR__);
$databasePath = getenv('DB_DATABASE') ?: '/tmp/database.sqlite';
$connection = getenv('DB_CONNECTION') ?: 'sqlite';

chdir($projectRoot);

if ($connection === 'sqlite' && ! file_exists($databasePath)) {
    touch($databasePath);
}

$phpBinary = escapeshellarg(PHP_BINARY);
$artisan = escapeshellarg($projectRoot.'/artisan');
$commands = [
    "{$phpBinary} {$artisan} migrate --force",
    "{$phpBinary} {$artisan} db:seed --force",
];

foreach ($commands as $command) {
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        exit($exitCode);
    }
}
