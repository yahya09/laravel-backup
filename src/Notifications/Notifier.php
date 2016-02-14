<?php

namespace Spatie\Backup\Notifications;

use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatus;
use Throwable;

class Notifier
{
    /** @var array */
    protected $config;

    public function __construct()
    {
        $this->subject = config('laravel-backup.backup.name').' backups';
    }

    public function backupWasSuccessful()
    {
        $this->sendNotification(
            'whenBackupWasSuccessful',
            $this->subject,
            'Successfully took a new backup',
            BaseSender::TYPE_SUCCESS
        );
    }

    public function backupHasFailed(Throwable $error)
    {
        $this->sendNotification(
            'whenBackupWasFailed',
            "{$this->subject} : error",
            "Failed to backup because: {$error->getMessage()}",
            BaseSender::TYPE_ERROR
        );
    }

    public function cleanupWasSuccessFul(BackupDestination $backupDestination)
    {
        $this->sendNotification(
            'whenCleanupWasSuccessful',
            $this->subject,
            "Successfully cleaned up the backups on {$backupDestination->getFilesystemType()}-filesystem",
            BaseSender::TYPE_SUCCESS
        );
    }

    public function cleanupHasFailed(Throwable $error)
    {
        $this->sendNotification(
            'whencleanupHasFailed',
            "{$this->subject} : error",
            "Failed to cleanup the backup because: {$error->getMessage()}",
            BaseSender::TYPE_ERROR
        );
    }

    public function healthyBackupWasFound(BackupDestinationStatus $backupDestinationStatus)
    {
        $this->sendNotification(
            'whenHealthyBackupWasFound',
            "Healty backup found for {$backupDestinationStatus->getBackupName()} on {$backupDestinationStatus->getFilesystemName()}-filesystem",
            "Backups on filesystem {$backupDestinationStatus->getFilesystemName()} are ok",
            BaseSender::TYPE_SUCCESS
        );
    }

    /**
     * @param \Spatie\Backup\Tasks\Monitor\BackupDestinationStatus $backupDestinationStatus
     */
    public function unHealthyBackupWasFound(BackupDestinationStatus $backupDestinationStatus)
    {
        $this->sendNotification(
            'whenUnHealthyBackupWasFound',
            "Unhealthy backup found for {$backupDestinationStatus->getBackupName()} on {$backupDestinationStatus->getFilesystemName()}-filesystem",
            UnhealthyBackupMessage::createForBackupDestinationStatus($backupDestinationStatus),
            BaseSender::TYPE_ERROR
        );
    }

    protected function sendNotification(string $eventName, string $subject, string $message, string $type)
    {
        $senderNames = config("laravel-backup.notifications.events.{$eventName}");

        collect($senderNames)
            ->map(function (string $senderName) {
                $className = '\\Spatie\\Backup\\Notifications\\Senders\\'.ucfirst($senderName);

                return app($className);
            })
            ->each(function (SendsNotifications $sender) use ($subject, $message, $type) {
                $sender
                    ->setSubject($subject)
                    ->setMessage($message)
                    ->setType($type)
                    ->send();
            });
    }
}
