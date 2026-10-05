<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('passport_number', 30)->unique();
            $table->string('qr_token', 64)->unique();
            $table->string('national_id', 20)->nullable()->unique();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->date('date_of_birth');
            $table->string('sex', 10);
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->string('traditional_authority', 100)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('physical_address')->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->text('disabilities')->nullable();
            $table->text('health_notes')->nullable();
            $table->foreignId('mother_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->timestamp('separated_from_mother_at')->nullable();
            $table->foreignId('registered_facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });

        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->string('relationship', 50);
            $table->string('phone', 30);
            $table->string('physical_address')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->after('facility_id')->unique()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patient_id');
        });
        Schema::dropIfExists('emergency_contacts');
        Schema::dropIfExists('patients');
    }
};
