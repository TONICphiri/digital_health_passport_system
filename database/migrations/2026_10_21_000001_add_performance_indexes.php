<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for the queries that run on every dashboard load and on
 * every passport page. They match the filter columns in the order the code
 * uses them, so the database can use a range scan instead of a full table
 * scan as the passport grows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passport_accesses', function (Blueprint $table) {
            // Facility dashboard: accesses at this facility today.
            $table->index(['facility_id', 'opened_at'], 'passport_accesses_facility_opened_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            // Facility audit page and dashboard activity feed.
            $table->index(['facility_id', 'created_at'], 'audit_logs_facility_created_index');
        });

        Schema::table('visit_stages', function (Blueprint $table) {
            // Health worker dashboard: stages recorded by this user today.
            $table->index(['recorded_by', 'recorded_at'], 'visit_stages_recorder_recorded_index');
        });

        Schema::table('patients', function (Blueprint $table) {
            // Facility dashboard: passports issued this month.
            $table->index(['registered_facility_id', 'created_at'], 'patients_facility_created_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            // The bell polls unread notifications for one user.
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_unread_index');
        });
    }

    public function down(): void
    {
        Schema::table('passport_accesses', function (Blueprint $table) {
            $table->dropIndex('passport_accesses_facility_opened_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_facility_created_index');
        });

        Schema::table('visit_stages', function (Blueprint $table) {
            $table->dropIndex('visit_stages_recorder_recorded_index');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_facility_created_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_unread_index');
        });
    }
};
