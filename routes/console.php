<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('rental:remind')->dailyAt('08:00');
