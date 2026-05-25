<?php

namespace App\Console\Commands;

use App\Models\RoomTemperature;
use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('temperature:cleanup {--days= : Number of days of temperature history to keep (overrides settings)}')]
#[Description('Delete old room temperature records')]
class CleanupRoomTemperatures extends Command
{
    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?? Setting::getInt('temp_retention_days', 7)));
        $cutoff = now()->subDays($days);

        $deleted = RoomTemperature::where('created_at', '<', $cutoff)->delete();

        if ($deleted > 0) {
            $this->info("Deleted {$deleted} temperature record(s) older than {$days} day(s)");
        } else {
            $this->info("No temperature records older than {$days} day(s)");
        }

        return Command::SUCCESS;
    }
}
