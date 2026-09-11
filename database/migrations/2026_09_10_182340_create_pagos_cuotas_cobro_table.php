<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_cuotas_cobro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuota_cobro_id')->constrained('cuotas_cobro')->cascadeOnDelete();
            $table->foreignId('venta_cuota_id')->constrained('ventas_cuotas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sesion_caja_id')->nullable()->constrained('sesion_cajas')->nullOnDelete();

            $table->decimal('monto', 12, 2);
            $table->enum('metodo_pago', ['EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'CHEQUE', 'OTRO'])->default('EFECTIVO');
            $table->date('fecha_pago');
            $table->string('referencia', 100)->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_cuotas_cobro');
    }
};