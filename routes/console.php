<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

// Menjalankan pengecekan EWS Kalibrasi setiap hari pukul 08:00 pagi
Schedule::command('ews:check-kalibrasi')->dailyAt('08:00');
