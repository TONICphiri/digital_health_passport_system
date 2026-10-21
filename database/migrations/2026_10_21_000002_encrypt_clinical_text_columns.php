<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The clinical free text of a patient is now stored encrypted, so the values
 * that are already in the database are encrypted once. The diagnosis column is
 * widened first, because a cipher text is longer than the plain text it
 * replaces and would not fit a 255 character column.
 *
 * The migration is safe to run again: a value that is already a cipher text is
 * left alone.
 */
return new class extends Migration
{
    /** @var array<string, array<int, string>> */
    private array $columns = [
        'patients' => ['allergies', 'chronic_conditions', 'disabilities', 'health_notes'],
        'encounters' => ['diagnosis', 'treatment_summary'],
    ];

    public function up(): void
    {
        if (Schema::hasTable('encounters') && Schema::hasColumn('encounters', 'diagnosis')) {
            Schema::table('encounters', function (Blueprint $table) {
                $table->text('diagnosis')->nullable()->change();
            });
        }

        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $this->convert($table, $column, encrypt: true);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $this->convert($table, $column, encrypt: false);
            }
        }
    }

    /**
     * Encrypt or decrypt every value in one column, a page at a time.
     */
    private function convert(string $table, string $column, bool $encrypt): void
    {
        $encrypter = app('encrypter');

        DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $column, $encrypt, $encrypter) {
                foreach ($rows as $row) {
                    $value = (string) $row->{$column};

                    if ($encrypt ? $this->isEncrypted($value, $encrypter) : ! $this->isEncrypted($value, $encrypter)) {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([$column => $encrypt ? $encrypter->encrypt($value, false) : $encrypter->decrypt($value, false)]);
                }
            }, 'id');
    }

    /**
     * A cipher text is the base64 of a small json document. Plain text is not.
     */
    private function isEncrypted(string $value, \Illuminate\Contracts\Encryption\Encrypter $encrypter): bool
    {
        try {
            $encrypter->decrypt($value, false);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
