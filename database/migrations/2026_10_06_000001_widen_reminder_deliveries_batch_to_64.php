<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A skipped delivery has a batch of "none-" followed by a uuid, which is
        // 41 characters in total and did not fit the original 40 column.
        Schema::table('reminder_deliveries', function (Blueprint $table) {
            $table->string('batch', 64)->change();
        });
    }

    public function down(): void
    {
        Schema::table('reminder_deliveries', function (Blueprint $table) {
            $table->string('batch', 40)->change();
        });
    }
};
