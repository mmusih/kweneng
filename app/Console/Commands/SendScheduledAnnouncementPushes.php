<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Services\AudienceResolverService;
use App\Services\FirebaseNotificationService;
use Illuminate\Console\Command;

class SendScheduledAnnouncementPushes extends Command
{
    protected $signature = 'announcements:send-scheduled-push';
    protected $description = 'Send push notifications for scheduled published announcements whose publish time has arrived.';

    public function handle(
        FirebaseNotificationService $firebase,
        AudienceResolverService $audienceResolver
    ): int {
        $announcements = Announcement::with('targets')
            ->where('is_published', true)
            ->whereNull('push_sent_at')
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();

        foreach ($announcements as $announcement) {
            $parentIds = $audienceResolver->parentIdsForAnnouncement($announcement)->all();

            if (! empty($parentIds)) {
                $firebase->sendToParents(
                    $parentIds,
                    $announcement->type === 'urgent' ? 'Urgent School Notice' : 'New School Notice',
                    $announcement->title,
                    [
                        'type' => 'announcement',
                        'announcement_id' => (string) $announcement->id,
                        'screen' => 'announcements',
                    ]
                );
            }

            $announcement->forceFill(['push_sent_at' => now()])->save();
        }

        $this->info('Scheduled announcement pushes processed: ' . $announcements->count());

        return self::SUCCESS;
    }
}
