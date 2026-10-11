<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One staged visit. A visit is started at reception and locked once the
        // pharmacist has handed over every prescribed medicine.
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->restrictOnDelete();
            $table->string('reason')->nullable();
            $table->string('status', 20)->default('in_progress');
            $table->date('follow_up_on')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('started_at');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('locked_at')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'started_at']);
            $table->index(['facility_id', 'started_at']);
            $table->index('status');
        });

        // One row for each stage. The first recorder is kept; an edit records
        // who changed it and when.
        Schema::create('visit_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recorded_as_position', 20)->nullable();
            $table->text('summary')->nullable();
            $table->json('payload')->nullable();
            $table->dateTime('recorded_at');
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('edited_at')->nullable();
            $table->timestamps();
            $table->unique(['visit_id', 'type']);
        });

        // Vital signs. Every reading is kept, so a correction credits both the
        // first recorder and the person who corrected it. The newest value for
        // a type is the one shown.
        Schema::create('visit_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('value', 30);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recorded_as_position', 20)->nullable();
            $table->dateTime('recorded_at');
            $table->timestamps();
            $table->index(['visit_id', 'type', 'recorded_at']);
        });

        // A medicine prescribed by the doctor, marked handed over by the
        // pharmacist. The system does not stock or dispense anything else.
        Schema::create('visit_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('dosage', 100);
            $table->string('frequency', 100);
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->foreignId('prescribed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('prescribed_at')->nullable();
            $table->boolean('dispensed')->default(false);
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('dispensed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_medications');
        Schema::dropIfExists('visit_observations');
        Schema::dropIfExists('visit_stages');
        Schema::dropIfExists('visits');
    }
};
