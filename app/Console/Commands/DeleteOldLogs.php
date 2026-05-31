<?php

namespace App\Console\Commands;

use App\Models\UserLog;
use Illuminate\Console\Command;

class DeleteOldLogs extends Command
{
    protected $signature = 'logs:clean';
    protected $description = 'Delete old user activity logs';

    private const RETENTION_DAYS = 30;

    public function handle(): int
    {
        $days = self::RETENTION_DAYS;
        $deleted = UserLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} log(s) older than {$days} day(s)");

        return self::SUCCESS;
    }
}
