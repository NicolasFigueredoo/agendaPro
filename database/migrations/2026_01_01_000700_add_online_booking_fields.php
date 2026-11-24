<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professionals', function (Blueprint $table) {
            $table->boolean('accepts_online')->default(false)->after('active');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('payment_status'); // manual | online
            $table->dateTime('hold_expires_at')->nullable()->after('source');         // reserva online esperando la seña
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['source', 'hold_expires_at']);
        });

        Schema::table('professionals', function (Blueprint $table) {
            $table->dropColumn('accepts_online');
        });
    }
};
