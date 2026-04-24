<?php

$setRuntimeEnv = static function (string $key, string $value): void {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
};

$runtimeDefaults = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_CONFIG_CACHE' => sys_get_temp_dir().'/config.php',
    'APP_EVENTS_CACHE' => sys_get_temp_dir().'/events.php',
    'APP_PACKAGES_CACHE' => sys_get_temp_dir().'/packages.php',
    'APP_ROUTES_CACHE' => sys_get_temp_dir().'/routes.php',
    'APP_SERVICES_CACHE' => sys_get_temp_dir().'/services.php',
    'LOG_CHANNEL' => 'stderr',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'VIEW_COMPILED_PATH' => sys_get_temp_dir().'/views',
];

foreach ($runtimeDefaults as $key => $value) {
    if (getenv($key) === false || getenv($key) === '') {
        $setRuntimeEnv($key, $value);
    }
}

$viewPath = sys_get_temp_dir().'/views';

if (! is_dir($viewPath)) {
    mkdir($viewPath, 0777, true);
}

$vercelUrl = getenv('VERCEL_PROJECT_PRODUCTION_URL') ?: getenv('VERCEL_URL');

if ($vercelUrl && (getenv('APP_URL') === false || getenv('APP_URL') === '')) {
    $setRuntimeEnv('APP_URL', 'https://'.$vercelUrl);
}

$bundledDatabase = __DIR__.'/../database/database.sqlite';
$runtimeDatabase = sys_get_temp_dir().'/task-manager.sqlite';
$connection = getenv('DB_CONNECTION') ?: '';
$database = getenv('DB_DATABASE') ?: '';

if (($connection === '' || $connection === 'sqlite') && is_file($bundledDatabase)) {
    $shouldCopyDatabase = ! file_exists($runtimeDatabase)
        || filesize($runtimeDatabase) === 0
        || filemtime($bundledDatabase) > filemtime($runtimeDatabase);

    if ($shouldCopyDatabase) {
        copy($bundledDatabase, $runtimeDatabase);
    }

    if ($connection === '') {
        $setRuntimeEnv('DB_CONNECTION', 'sqlite');
    }

    if ($database === '') {
        $setRuntimeEnv('DB_DATABASE', $runtimeDatabase);
    }
}
