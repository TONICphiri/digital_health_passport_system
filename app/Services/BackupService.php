<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\User;
use App\Notifications\BackupNotification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Makes and restores copies of the database.
 *
 * A backup is one file. The first line describes it. Every other line holds a
 * block of rows from one table, compressed and encrypted with the application
 * key, so the file is useless to anyone who does not also hold that key. The
 * last line marks the end, which lets a cut off file be recognised.
 *
 * Because it only reads and writes rows through the database layer, it works
 * on any database the application supports and needs no command line tools.
 */
class BackupService
{
    public const FORMAT = 'dhp-backup-1';

    public function __construct(private readonly SettingService $settings)
    {
    }

    public function run(string $trigger, ?User $by = null): Backup
    {
        $disk = Storage::disk(config('health_passport.backup.disk'));
        $directory = config('health_passport.backup.directory');
        $filename = 'dhp-'.now()->format('Ymd-His').'.dhpbak';
        $path = "{$directory}/{$filename}";

        $backup = Backup::create(['status' => 'running', 'trigger' => $trigger, 'created_by' => $by?->id]);

        try {
            $disk->makeDirectory($directory);
            $this->write($disk->path($path));

            $backup->update([
                'filename' => $filename,
                'size_bytes' => $disk->size($path),
                'status' => Backup::COMPLETED,
            ]);

            $this->removeExpired();
        } catch (Throwable $exception) {
            report($exception);
            $disk->delete($path);

            $backup->update(['status' => Backup::FAILED, 'error' => mb_substr($exception->getMessage(), 0, 500)]);
        }

        $this->notifyAdministrators($backup->fresh());

        return $backup->fresh();
    }

    /**
     * Replaces the data in the database with the contents of a backup file.
     * The database must already have the same structure, so run the migrations first.
     *
     * @return array<string, int> Rows restored for each table.
     */
    public function restore(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'rb');

        if (! $handle) {
            throw new RuntimeException('The backup file could not be opened.');
        }

        $meta = json_decode((string) fgets($handle), true);

        if (($meta['format'] ?? null) !== self::FORMAT) {
            fclose($handle);
            throw new RuntimeException('This is not a backup file made by this system.');
        }

        $restored = [];
        $finished = false;
        $tables = array_values(array_filter($meta['tables'] ?? [], fn ($table) => Schema::hasTable($table)));

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($handle, $tables, &$restored, &$finished) {
                if (DB::getDriverName() === 'sqlite') {
                    // SQLite cannot switch foreign keys off inside a transaction, but it can postpone the check.
                    DB::statement('PRAGMA defer_foreign_keys = ON');
                }

                // Everything is cleared first, so a cascading delete can never remove restored rows.
                foreach ($tables as $table) {
                    DB::table($table)->delete();
                }

                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);

                    if ($line === '') {
                        continue;
                    }

                    if (str_starts_with($line, '{')) {
                        $finished = (json_decode($line, true)['end'] ?? false) === true;

                        continue;
                    }

                    $block = json_decode(gzuncompress(Crypt::decryptString($line)), true, 512, JSON_THROW_ON_ERROR);
                    $table = $block['table'];

                    if (! in_array($table, $tables, true)) {
                        continue;
                    }

                    if ($block['rows'] !== []) {
                        DB::table($table)->insert($block['rows']);
                    }

                    $restored[$table] = ($restored[$table] ?? 0) + count($block['rows']);
                }

                if (! $finished) {
                    throw new RuntimeException('The backup file is incomplete, so nothing was restored.');
                }
            });
        } finally {
            fclose($handle);
            Schema::enableForeignKeyConstraints();
        }

        $this->settings->flush();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $restored;
    }

    public function path(Backup $backup): string
    {
        return config('health_passport.backup.directory').'/'.$backup->filename;
    }

    public function delete(Backup $backup): void
    {
        if ($backup->filename) {
            Storage::disk(config('health_passport.backup.disk'))->delete($this->path($backup));
        }

        $backup->delete();
    }

    private function write(string $file): void
    {
        $handle = fopen($file, 'wb');

        if (! $handle) {
            throw new RuntimeException('The backup file could not be created. Check that the storage folder is writable.');
        }

        try {
            $skip = config('health_passport.backup.skip_tables');
            $chunk = (int) config('health_passport.backup.chunk_rows');
            // Only this system's own tables are saved. A shared database may hold tables
            // from other projects, and those are neither ours to copy nor safe to read.
            $owned = $this->ownTables();
            $existing = collect(Schema::getTables())->pluck('name');
            $tables = $existing->filter(fn ($name) => in_array($name, $owned, true))->reject(fn ($name) => in_array($name, $skip, true))->sort()->values();
            $ignored = $existing->diff($tables)->reject(fn ($name) => in_array($name, $skip, true))->sort()->values();
            $total = 0;

            fwrite($handle, json_encode([
                'format' => self::FORMAT,
                'created_at' => now()->toIso8601String(),
                'application' => config('app.name'),
                'tables' => $tables->all(),
                'ignored_tables' => $ignored->all(),
            ])."\n");

            foreach ($tables as $table) {
                $rows = [];

                foreach (DB::table($table)->cursor() as $row) {
                    $rows[] = (array) $row;
                    $total++;

                    if (count($rows) >= $chunk) {
                        $this->writeBlock($handle, $table, $rows);
                        $rows = [];
                    }
                }

                if ($rows !== [] || ! DB::table($table)->exists()) {
                    $this->writeBlock($handle, $table, $rows);
                }
            }

            fwrite($handle, json_encode(['end' => true, 'rows' => $total])."\n");
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  resource  $handle
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function writeBlock($handle, string $table, array $rows): void
    {
        $json = json_encode(['table' => $table, 'rows' => $rows], JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);

        fwrite($handle, Crypt::encryptString(gzcompress($json, 6))."\n");
    }

    /**
     * The tables created by this application's migrations.
     *
     * @return array<int, string>
     */
    private function ownTables(): array
    {
        return collect(glob(database_path('migrations/*.php')) ?: [])
            ->flatMap(function (string $file) {
                preg_match_all("/Schema::create\(\s*['\"]([^'\"]+)['\"]/", (string) file_get_contents($file), $matches);

                return $matches[1];
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Deletes copies older than the retention period, always keeping the three newest.
     */
    private function removeExpired(): void
    {
        $days = max(1, (int) $this->settings->get('backup_retention_days', '30'));

        $keep = Backup::query()->where('status', Backup::COMPLETED)->latest('id')->limit(3)->pluck('id');

        Backup::query()
            ->where('created_at', '<', now()->subDays($days))
            ->whereNotIn('id', $keep)
            ->get()
            ->each(fn (Backup $old) => $this->delete($old));
    }

    private function notifyAdministrators(Backup $backup): void
    {
        try {
            $administrators = User::query()->active()->role(\App\Enums\RoleName::SystemAdmin->value)->get();

            Notification::send($administrators, new BackupNotification($backup));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}