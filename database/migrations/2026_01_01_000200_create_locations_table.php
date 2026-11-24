<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('location_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 1 = lunes … 7 = domingo (ISO)
            $table->boolean('active')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();

            $table->unique(['location_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_hours');
        Schema::dropIfExists('locations');
    }
};
