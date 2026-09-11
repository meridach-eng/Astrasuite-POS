<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('numero_referencia', 50)->unique();
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->decimal('subtotal_general', 12, 2)->default(0);
            $table->string('descuento', 50)->nullable(); // Soporta montos fijos o porcentajes ej. "10%" o "50.00"
            $table->decimal('costo_total', 12, 2)->default(0); // Gran total final
            $table->string('estado', 20)->default('PENDIENTE'); // PENDIENTE, APROBADA, FACTURADA, EXPIRADA, ANULADA
            $table->text('observaciones')->nullable(); // Notas adicionales
            $table->timestamps();
        });

        Schema::create('detalle_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 12, 4);
            $table->decimal('subtotal', 12, 2);
            $table->text('descripcion')->nullable(); // Descripción o nota específica del renglón
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_cotizaciones');
        Schema::dropIfExists('cotizaciones');
    }
};