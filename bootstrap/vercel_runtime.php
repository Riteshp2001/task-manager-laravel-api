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
$connection = getenv('DB_CONNECTION') ?: 'sqlite';
$database = getenv('DB_DATABASE') ?: '';

if ($connection === 'sqlite') {
    if (getenv('VERCEL') && is_file($bundledDatabase)) {
        if (! file_exists($runtimeDatabase) || filesize($runtimeDatabase) === 0) {
            copy($bundledDatabase, $runtimeDatabase);
        }
        $setRuntimeEnv('DB_DATABASE', $runtimeDatabase);
    } elseif ($database === '') {
        $setRuntimeEnv('DB_DATABASE', $bundledDatabase);
    }
} elseif (getenv('VERCEL')) {
    // Ensure the serverless container prepares the shared database once per cold start.
    $projectRoot = dirname(__DIR__);
    $phpBinary = PHP_BINARY;
    $artisan = $projectRoot.'/artisan';
    $bootstrapFlag = sys_get_temp_dir().'/task-manager-postgres-ready';

    if (file_exists($artisan) && ! file_exists($bootstrapFlag)) {
        $commands = [
            $phpBinary.' '.$artisan.' migrate --force 2>&1',
            $phpBinary.' '.$artisan.' db:seed --force 2>&1',
        ];

        foreach ($commands as $command) {
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                error_log(implode(PHP_EOL, $output));
                break;
            }
        }

        if ($returnCode === 0) {
            file_put_contents($bootstrapFlag, 'ready');
        }
    }
}
