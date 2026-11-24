<?php

use Illuminate\Support\Facades\Schedule;

// Libera los horarios de reservas online que no pagaron la seña a tiempo
Schedule::command('bookings:release-expired')->everyMinute()->withoutOverlapping();
