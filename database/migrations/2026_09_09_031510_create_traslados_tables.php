<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traslados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_origen_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('sucursal_destino_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('numero_referencia', 50)->unique();
            $table->date('fecha_traslado');
            $table->string('estado', 20)->default('ENVIADO'); // ENVIADO, RECIBIDO, ANULADO
            $table->text('nota_remitente')->nullable();
            $table->text('nota_receptor')->nullable();
            $table->timestamps();
        });

        Schema::create('detalle_traslados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traslado_id')->constrained('traslados')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->decimal('cantidad', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_traslados');
        Schema::dropIfExists('traslados');
    }
};