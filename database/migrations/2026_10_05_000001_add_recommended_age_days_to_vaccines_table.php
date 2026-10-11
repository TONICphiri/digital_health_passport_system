<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vaccines', function (Blueprint $table) {
            $table->unsignedSmallInteger('recommended_age_days')->nullable()->after('recommended_age');
        });
    }

    public function down(): void
    {
        Schema::table('vaccines', function (Blueprint $table) {
            $table->dropColumn('recommended_age_days');
        });
    }
};
