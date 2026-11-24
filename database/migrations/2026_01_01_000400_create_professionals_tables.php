<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name', 80);
            $table->string('color', 7)->default('#e11d48');
            $table->boolean('active')->default(true);
            $table->unsignedTinyInteger('commission_percent')->nullable(); // para liquidar en caja
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('professional_service', function (Blueprint $table) {
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();

            $table->primary(['professional_id', 'service_id']);
        });

        Schema::create('professional_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 1 = lunes … 7 = domingo (ISO)
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['professional_id', 'weekday']);
        });

        Schema::create('professional_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['professional_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_breaks');
        Schema::dropIfExists('professional_schedules');
        Schema::dropIfExists('professional_service');
        Schema::dropIfExists('professionals');
    }
};
