<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * First-run setup: a site that has no admin yet (a fresh git deploy after the migrations ran)
 * sends its first visitor to /setup to create one. Once an admin exists the screen is gone.
 *
 * Set SETUP_TOKEN in the environment to also require a secret on that screen, so nobody else
 * can claim the site between the deploy and your first visit.
 */
class FirstRunSetup
{
    private const CACHE_KEY = 'cms.setup.complete';

    public function enabled(): bool
    {
        return (bool) config('cms.first_run_setup');
    }

    /** True while setup is on and no admin account exists. Never throws (an unmigrated database is "not pending"). */
    public function isPending(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            if (Cache::get(self::CACHE_KEY) === true) {
                return false;
            }

            if (User::query()->where('is_admin', true)->exists()) {
                $this->markComplete();

                return false;
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function requiresToken(): bool
    {
        return filled(config('cms.setup_token'));
    }

    public function tokenMatches(?string $token): bool
    {
        return $this->requiresToken() && hash_equals((string) config('cms.setup_token'), (string) $token);
    }

    /** Remembers that an admin exists, so the check costs nothing on later requests. */
    public function markComplete(): void
    {
        try {
            Cache::forever(self::CACHE_KEY, true);
        } catch (Throwable) {
            // No cache store yet: the next request simply asks the database again.
        }
    }
}
