<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('numero_referencia', 50)->unique();
            $table->enum('tipo_ajuste', ['SOBRANTE', 'FALTANTE'])->default('FALTANTE');
            $table->date('fecha_ajuste');
            $table->decimal('costo_total', 12, 2)->default(0);
            $table->string('estado', 20)->default('COMPLETADA');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('detalle_ajustes_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ajuste_inventario_id')->constrained('ajustes_inventario')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->decimal('stock_sistema', 12, 2)->default(0);
            $table->decimal('stock_fisico', 12, 2)->default(0);
            $table->decimal('diferencia', 12, 2); // Positivo o Negativo
            $table->decimal('costo_unitario', 12, 4);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ajustes_inventario');
        Schema::dropIfExists('ajustes_inventario');
    }
};