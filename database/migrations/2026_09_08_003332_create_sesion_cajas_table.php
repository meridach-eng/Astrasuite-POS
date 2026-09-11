<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesion_cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->dateTime('fecha_apertura');
            $table->decimal('monto_apertura', 12, 2)->default(0);

            $table->dateTime('fecha_cierre')->nullable();
            $table->decimal('monto_esperado', 12, 2)->nullable();
            $table->decimal('monto_real', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();

            $table->string('estado', 20)->default('ABIERTA'); // ABIERTA, CERRADA
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesion_cajas');
    }
};