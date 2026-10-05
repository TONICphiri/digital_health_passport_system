<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('protects_against');
            $table->unsignedTinyInteger('total_doses');
            $table->unsignedSmallInteger('days_between_doses')->nullable();
            $table->string('recommended_age', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vaccine_id')->constrained()->restrictOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('administered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('dose_number');
            $table->date('administered_on');
            $table->string('batch_number', 50)->nullable();
            $table->date('next_dose_due_on')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['patient_id', 'vaccine_id', 'dose_number']);
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20);
            $table->string('title', 150);
            $table->text('message');
            $table->date('due_on')->index();
            $table->unsignedSmallInteger('repeat_every_days')->nullable();
            $table->boolean('is_confidential')->default(false);
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('last_sent_at')->nullable();
            $table->nullableMorphs('source');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('vaccinations');
        Schema::dropIfExists('vaccines');
    }
};
