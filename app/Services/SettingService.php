<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Reads and writes system settings stored in the database. Values are cached
 * and the cache is cleared whenever a setting changes.
 */
class SettingService
{
    private const CACHE_KEY = 'system-settings';

    /**
     * @return Collection<string, string|null>
     */
    public function all(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                if (! Schema::hasTable('settings')) {
                    return collect();
                }

                return Setting::query()->pluck('value', 'key');
            } catch (Throwable) {
                return collect();
            }
        });
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()->get($key, $default);
    }

    /**
     * Settings stored as one item per line, returned as a clean list.
     *
     * @return array<int, string>
     */
    public function list(string $key): array
    {
        $value = (string) $this->get($key, '');

        return collect(preg_split('/\r\n|\r|\n/', $value) ?: [])
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->where('key', $key)->update(['value' => $value]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function countryName(): string
    {
        return (string) $this->get('country_name', '');
    }

    public function issuingAuthority(): string
    {
        return (string) $this->get('issuing_authority', '');
    }

    public function systemName(): string
    {
        return $this->get('system_name', config('app.name'));
    }

    public function passportPrefix(): string
    {
        return strtoupper($this->get('passport_number_prefix', 'MW'));
    }

    public function childSeparationAge(): int
    {
        return (int) $this->get('child_separation_age', '18');
    }

    /**
     * Number of characters on a National ID card.
     */
    public function nationalIdLength(): int
    {
        return max(1, (int) $this->get('national_id_length', '8'));
    }

    /**
     * Hours a visit note typed without a connection is kept on the device.
     */
    public function offlineDraftHours(): int
    {
        return max(1, (int) $this->get('offline_draft_hours', '24'));
    }

    /**
     * How long a scanned passport stays open for a health worker.
     */
    public function passportSessionMinutes(): int
    {
        return max(5, (int) $this->get('passport_session_minutes', '30'));
    }
}
