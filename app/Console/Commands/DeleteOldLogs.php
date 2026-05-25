<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\UserLog;
use Illuminate\Console\Command;

class DeleteOldLogs extends Command
{
    protected $signature = 'logs:clean';
    protected $description = 'Delete old user activity logs';

    public function handle(): int
    {
        $days = Setting::getInt('log_retention_days', 90);
        $deleted = UserLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} log(s) older than {$days} day(s)");

        return self::SUCCESS;
    }
}
