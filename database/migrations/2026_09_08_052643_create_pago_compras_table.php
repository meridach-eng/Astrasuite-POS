<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->enum('metodo_pago', ['EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'CHEQUE', 'OTRO'])->default('EFECTIVO');
            $table->decimal('monto', 12, 2);
            $table->string('referencia_pago', 100)->nullable(); // No. cheque, transferencia, boleta
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_compras');
    }
};