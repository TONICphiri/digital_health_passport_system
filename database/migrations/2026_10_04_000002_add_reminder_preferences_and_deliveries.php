<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What the passport holder wants to receive. Empty means everything.
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('must_change_password');
        });

        // One row for each channel a reminder was sent through, so staff can
        // see whether the holder was actually reached.
        Schema::create('reminder_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->constrained()->cascadeOnDelete();
            $table->string('batch', 40)->index();
            $table->string('channel', 10);
            $table->string('status', 10);
            $table->string('detail')->nullable();
            $table->timestamps();
            $table->index(['reminder_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_deliveries');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
