<?php

use App\Jobs\ReconcileAssistantKnowledgeJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ReconcileAssistantKnowledgeJob)
    ->hourly()
    ->withoutOverlapping()
    ->name('assistant-knowledge-reconcile');
