<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cabecera de Venta a Cuotas
        Schema::create('ventas_cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();

            $table->string('numero_referencia', 50)->unique(); // Ej: INS-2026-00001
            $table->date('fecha_inicio');

            // Valores monetarios
            $table->decimal('precio_base', 12, 2)->default(0); // Suma de subtotales de productos
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('envio_otros', 12, 2)->default(0);
            $table->decimal('porcentaje_interes', 5, 2)->default(0);
            $table->decimal('monto_interes', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Enganche y saldo
            $table->decimal('enganche', 12, 2)->default(0);
            $table->decimal('saldo_financiar', 12, 2)->default(0);
            $table->decimal('total_pagado', 12, 2)->default(0);
            $table->decimal('saldo_pendiente', 12, 2)->default(0);

            // Configuración de Cuotas
            $table->unsignedInteger('numero_cuotas')->default(1);
            $table->unsignedInteger('dias_intervalo')->default(30); // 15 quincenal, 30 mensual
            $table->string('metodo_pago_enganche', 50)->nullable(); // EFECTIVO, TRANSFERENCIA, etc.

            // Estados y Auditoría
            $table->enum('estado', ['ACTIVO', 'LIQUIDADO', 'ANULADO'])->default('ACTIVO');
            $table->text('notas')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->timestamp('anulado_at')->nullable();

            $table->timestamps();
        });

        // 2. Detalle de Productos en la Venta a Cuotas
        Schema::create('detalle_venta_cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_cuota_id')->constrained('ventas_cuotas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();

            $table->decimal('cantidad', 12, 2)->default(1);
            $table->decimal('precio_unitario', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);

            $table->timestamps();
        });

        // 3. Cronograma de Cuotas Programadas
        Schema::create('cuotas_cobro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_cuota_id')->constrained('ventas_cuotas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            $table->unsignedInteger('numero_cuota'); // 1, 2, 3...
            $table->decimal('monto_cuota', 12, 2);
            $table->date('fecha_vencimiento');

            // Control de cobro
            $table->decimal('monto_pagado', 12, 2)->default(0);
            $table->decimal('saldo_pendiente', 12, 2);
            $table->date('fecha_ultimo_pago')->nullable();

            $table->enum('estado', ['PENDIENTE', 'PARCIAL', 'PAGADO', 'ANULADO'])->default('PENDIENTE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuotas_cobro');
        Schema::dropIfExists('detalle_venta_cuotas');
        Schema::dropIfExists('ventas_cuotas');
    }
};