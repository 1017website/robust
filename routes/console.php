<?php

use App\Services\TemporaryUpload;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('uploads:prune', function () {
    $this->info(app(TemporaryUpload::class)->prune().' temporary uploads deleted.');
})->purpose('Delete expired temporary uploads');

Schedule::command('uploads:prune')->hourly();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
