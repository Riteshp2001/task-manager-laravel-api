<?php

use App\Services\TaskService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tasks:sync-overdue', function () {
    $updatedCount = app(TaskService::class)->syncAllOverdueTasks(true);

    $this->info("Marked {$updatedCount} task(s) as overdue.");
})->purpose('Sync overdue task statuses through the Django rules service');

Schedule::command('tasks:sync-overdue')->everyMinute();
