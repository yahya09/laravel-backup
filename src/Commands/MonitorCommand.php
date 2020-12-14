<?php

namespace Spatie\Backup\Commands;

use Spatie\Backup\Events\HealthyBackupWasFoundEvent;
use Spatie\Backup\Events\UnhealthyBackupWasFoundEvent;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;

class MonitorCommand extends BaseCommand
{
    /** @var string */
    protected $signature = 'backup:monitor';

    /** @var string */
    protected $description = 'Monitor the health of all backups.';

    public function handle()
    {
        $hasError = false;

        $statuses = BackupDestinationStatusFactory::createForMonitorConfig(config('backup.monitor_backups'));

        foreach ($statuses as $backupDestinationStatus) {
            $diskName = $backupDestinationStatus->backupDestination()->diskName();

            if ($backupDestinationStatus->isHealthy()) {
                $this->info("The backups on {$diskName} are considered healthy.");
                event(new HealthyBackupWasFoundEvent($backupDestinationStatus));
            } else {
                $hasError = true;
                $this->error("The backups on {$diskName} are considered unhealthy!");
                event(new UnhealthyBackupWasFoundEvent($backupDestinationStatus));
            }
        }

        if ($hasError) {
            return 1;
        }
    }
}
