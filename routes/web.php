<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;

Route::get('/', function () {
    return view('welcome');
});

// Run every day at 8am IST (2:30am UTC)
Schedule::command('jobs:fetch')->dailyAt('08:00')->timezone('Asia/Kolkata');