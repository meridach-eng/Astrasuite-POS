<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salidas_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('numero_referencia', 50)->unique();
            $table->enum('tipo_salida', ['USO_INTERNO', 'DAÑO', 'VENCIMIENTO', 'EXTRAVIO'])->default('USO_INTERNO');
            $table->date('fecha_salida');
            $table->decimal('costo_total', 12, 2)->default(0);
            $table->string('estado', 20)->default('COMPLETADA');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('detalle_salidas_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salida_inventario_id')->constrained('salidas_inventario')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->decimal('cantidad', 12, 2);
            $table->decimal('costo_unitario', 12, 4);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_salidas_inventario');
        Schema::dropIfExists('salidas_inventario');
    }
};