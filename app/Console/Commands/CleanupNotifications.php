<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\Notification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notification:cleanup')]
#[Description('Delete old notifications')]
class CleanupNotifications extends Command
{
    public function handle(): int
    {
        $days = AppSetting::retentionDays(AppSetting::NOTIFICATION_RETENTION_DAYS);
        $deleted = Notification::where('created_at', '<', now()->subDays($days))->delete();

        if ($deleted > 0) {
            $this->info("Deleted {$deleted} old notification(s) older than {$days} day(s)");
        } else {
            $this->info("No old notifications older than {$days} day(s)");
        }

        return self::SUCCESS;
    }
}
