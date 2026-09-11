<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();

            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_unitario', 12, 4);
            $table->decimal('subtotal', 12, 2);

            // Control de lotes y vencimiento
            $table->string('numero_lote', 100)->nullable();
            $table->date('fecha_vencimiento')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_compras');
    }
};