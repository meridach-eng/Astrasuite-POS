<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Referencias
            $table->string('numero_referencia', 50)->unique(); // Ej. PUR-2026-0001
            $table->string('tipo_comprobante', 20)->default('FACTURA');
            $table->string('serie_comprobante', 50)->nullable();
            $table->string('numero_comprobante', 50)->nullable();
            $table->date('fecha_compra')->default(now());

            // Estados
            $table->enum('estado_recepcion', ['RECIBIDO', 'PENDIENTE', 'ANULADO'])->default('RECIBIDO');
            $table->enum('estado_pago', ['PAGADO', 'PARCIAL', 'PENDIENTE'])->default('PAGADO');

            // Importes y Saldos
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('monto_pagado', 12, 2)->default(0);
            $table->decimal('saldo_pendiente', 12, 2)->default(0);

            // Observaciones y Auditoría de Anulación
            $table->text('observaciones')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->timestamp('anulado_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};