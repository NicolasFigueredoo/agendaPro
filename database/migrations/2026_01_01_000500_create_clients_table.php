<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_number')->nullable()->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->date('birth_date')->nullable();
            $table->string('dni', 20)->nullable()->index();
            $table->string('gender', 20)->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable()->index(); // normalizado: +549XXXXXXXXXX
            $table->string('address')->nullable();
            $table->string('province', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
