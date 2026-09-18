<?php

namespace App\Console\Commands;

use App\Services\BrokenImageCleanupService;
use Illuminate\Console\Command;

/**
 * Manual/admin escape hatch. Normal operation no longer needs this run on a
 * cron: the dashboard's own auto-refresh reload triggers the same check
 * (see EmbyWebhookController::cleanupWebhooks()). Use this when you want to
 * force an immediate full sweep, e.g. right after a batch of items broke.
 */
class CleanupBrokenImages extends Command
{
    protected $signature = 'webhooks:cleanup-broken-images';

    protected $description = 'Force an immediate re-check of every stored poster/backdrop URL and remove entries whose image is no longer reachable';

    public function handle(BrokenImageCleanupService $service): int
    {
        $removed = $service->run(limit: null, ignoreRecheckWindow: true);

        if ($removed > 0) {
            $this->info("Removed {$removed} webhook entr" . ($removed === 1 ? 'y' : 'ies') . " with unreachable images.");
        } else {
            $this->info('No broken images found.');
        }

        return self::SUCCESS;
    }
}
