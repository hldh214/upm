<?php

use Illuminate\Support\Facades\Schedule;

// Run crawler daily at 4:00 AM
Schedule::command('upm:crawl')->dailyAt('04:00');
