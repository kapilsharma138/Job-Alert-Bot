<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;

Route::get('/', function () {
    return view('welcome');
});

// Run every day at 8am IST (2:30am UTC)
Schedule::command('jobs:fetch')->dailyAt('08:00')->timezone('Asia/Kolkata');

// Weekly digest every Monday at 9am IST
Schedule::command('jobs:digest')->weeklyOn(1, '09:00')->timezone('Asia/Kolkata');