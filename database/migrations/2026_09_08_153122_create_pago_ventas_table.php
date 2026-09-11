<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('sesion_caja_id')->nullable()->constrained('sesion_cajas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('metodo_pago', [
                'EFECTIVO',
                'TARJETA',
                'TRANSFERENCIA',
                'CHEQUE',
                'OTRO',
            ])->default('EFECTIVO');

            $table->decimal('monto', 12, 2); // Lo que realmente ingresa al arqueo (descontando vuelto)
            $table->decimal('recibido', 12, 2)->nullable(); // Lo entregado físicamente por el cliente
            $table->decimal('cambio', 12, 2)->nullable();   // Vuelto entregado
            $table->string('referencia_pago', 100)->nullable(); // No. voucher POS, boleta, autorización

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_ventas');
    }
};