<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_payment_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();                  // para las páginas públicas de retorno
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);                        // sena | total
            $table->unsignedInteger('amount');
            $table->string('preference_id')->nullable();
            $table->text('init_point')->nullable();
            $table->string('status', 20)->default('pending');  // pending | paid | rejected | expired | cancelled
            $table->dateTime('expires_at')->nullable();
            $table->string('mp_payment_id', 40)->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_payment_links');
    }
};
