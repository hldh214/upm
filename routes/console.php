<?php

use Illuminate\Support\Facades\Schedule;

// Run crawler every four hours at 25 minutes past the hour
Schedule::command('upm:crawl')->everyFourHours(25);
