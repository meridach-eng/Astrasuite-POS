<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_caja_id')->constrained('sesion_cajas')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('numero_ticket', 50)->unique(); // Ej: POS-20260908-001
            $table->dateTime('fecha_venta')->useCurrent();
            $table->date('fecha_vencimiento')->nullable(); // Para créditos y FCAM SAT

            // Modalidad y Estados
            $table->enum('tipo_venta', ['CONTADO', 'CREDITO'])->default('CONTADO');
            $table->enum('estado', ['COMPLETADA', 'PENDIENTE', 'ANULADA'])->default('COMPLETADA');
            $table->enum('estado_pago', ['PAGADO', 'PARCIAL', 'CREDITO'])->default('PAGADO');

            // Importes
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('impuesto', 12, 2)->default(0); // Para IVA SAT
            $table->decimal('total', 12, 2)->default(0);

            // Control de saldo y vuelto
            $table->decimal('monto_pagado', 12, 2)->default(0);
            $table->decimal('saldo_pendiente', 12, 2)->default(0);
            $table->decimal('cambio_entregado', 12, 2)->default(0);

            // FEL SAT (Guatemala) - Preparación
            $table->string('tipo_dte', 20)->default('FACT'); // FACT, FCAM, NCRE
            $table->string('fel_uuid', 100)->nullable();
            $table->string('fel_serie', 50)->nullable();
            $table->string('fel_numero', 50)->nullable();

            $table->text('notas')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->timestamp('anulado_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};