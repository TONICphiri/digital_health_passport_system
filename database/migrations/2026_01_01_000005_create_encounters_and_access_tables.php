<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One entry in the passport each time a health worker sees the holder.
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason');
            $table->string('diagnosis')->nullable();
            $table->text('treatment_summary')->nullable();
            $table->date('follow_up_on')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->decimal('weight', 5, 1)->nullable();
            $table->decimal('height', 5, 1)->nullable();
            $table->unsignedSmallInteger('systolic_pressure')->nullable();
            $table->unsignedSmallInteger('diastolic_pressure')->nullable();
            $table->unsignedSmallInteger('pulse_rate')->nullable();
            $table->unsignedTinyInteger('oxygen_saturation')->nullable();
            $table->dateTime('recorded_at');
            $table->timestamps();
            $table->index(['patient_id', 'recorded_at']);
            $table->index(['facility_id', 'recorded_at']);
        });

        // Medicines noted in the passport. The system does not stock or dispense.
        Schema::create('encounter_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('dosage', 100);
            $table->string('frequency', 100);
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->timestamps();
        });

        // Every time a passport is opened. A passport can only be read while one
        // of these is open for the health worker, and the holder can see the list.
        Schema::create('passport_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 30);
            $table->dateTime('opened_at');
            $table->dateTime('expires_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'patient_id', 'expires_at']);
            $table->index(['patient_id', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_accesses');
        Schema::dropIfExists('encounter_medications');
        Schema::dropIfExists('encounters');
    }
};
