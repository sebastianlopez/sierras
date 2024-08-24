<?php

use App\Http\Controllers\integrateController;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Schedule::command('demo:cron')->everyTenMinutes();


