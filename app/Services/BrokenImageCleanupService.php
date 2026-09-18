<?php

namespace App\Services;

use App\Models\EmbyWebhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Proactively re-validates stored poster/backdrop URLs instead of relying solely
 * on a visitor's browser reporting an onerror. Driven by the dashboard's own
 * auto-refresh reload (see EmbyWebhookController::cleanupWebhooks()) so it needs
 * no cron/scheduler on the host.
 */
class BrokenImageCleanupService
{
    /**
     * @param int|null $limit Cap how many candidates to check this run. Null = no cap
     *                        (used by the manual `webhooks:cleanup-broken-images` command).
     * @param bool $ignoreRecheckWindow Check every candidate regardless of when it was
     *                                  last checked. Only for the manual command.
     */
    public function run(?int $limit = null, bool $ignoreRecheckWindow = false): int
    {
        $recheckMinutes = (int) config('services.webhook.image_recheck_minutes', 15);
        $strikesThreshold = (int) config('services.webhook.image_check_strikes', 3);
        $cutoff = now()->subMinutes($recheckMinutes)->toIso8601String();

        $query = EmbyWebhook::query()
            ->where(function ($q) {
                $q->whereNotNull('metadata->poster_url')
                    ->orWhereNotNull('metadata->backdrop_url');
            });

        if (! $ignoreRecheckWindow) {
            $query->where(function ($q) use ($cutoff) {
                $q->whereNull('metadata->image_checked_at')
                    ->orWhere('metadata->image_checked_at', '<', $cutoff);
            });
        }

        if ($limit) {
            $query->limit($limit);
        }

        $candidates = $query->get(['id', 'uuid', 'item_name', 'metadata']);
        $removed = 0;

        foreach ($candidates as $webhook) {
            $metadata = $webhook->metadata ?? [];
            $url = $metadata['poster_url'] ?? $metadata['backdrop_url'] ?? null;

            if (! $url) {
                continue;
            }

            if ($this->isImageReachable($url)) {
                unset($metadata['image_check_strikes']);
                $metadata['image_checked_at'] = now()->toIso8601String();
                $webhook->update(['metadata' => $metadata]);
                continue;
            }

            $strikes = ($metadata['image_check_strikes'] ?? 0) + 1;

            if ($strikes >= $strikesThreshold) {
                Log::info('Cleanup: removing webhook after repeated unreachable image checks', [
                    'uuid' => $webhook->uuid,
                    'item_name' => $webhook->item_name,
                    'url' => $url,
                    'strikes' => $strikes,
                ]);
                $webhook->delete();
                $removed++;
                continue;
            }

            $metadata['image_check_strikes'] = $strikes;
            $metadata['image_checked_at'] = now()->toIso8601String();
            $webhook->update(['metadata' => $metadata]);
        }

        return $removed;
    }

    private function isImageReachable(string $url): bool
    {
        try {
            $response = Http::timeout(5)->head($url);

            if ($response->successful()) {
                return true;
            }

            // Some servers (e.g. Emby) don't support HEAD on image endpoints; fall back to GET.
            if (in_array($response->status(), [405, 501])) {
                return Http::timeout(8)->get($url)->successful();
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
