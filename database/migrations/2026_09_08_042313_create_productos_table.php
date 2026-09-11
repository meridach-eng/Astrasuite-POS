<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['BIEN', 'SERVICIO'])->default('BIEN');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();

            $table->string('codigo_interno', 50)->nullable()->unique(); // Ej: LAC-001, SERV-01
            $table->string('codigo_barras', 50)->nullable()->unique(); // Lector de barras / EAN-13
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();

            // Costos, márgenes y precios (AVCO base)
            $table->decimal('precio_compra', 12, 4)->default(0);
            $table->decimal('margen_utilidad', 8, 2)->default(0);
            $table->decimal('precio_venta', 12, 2)->default(0);
            $table->decimal('precio_mayorista', 12, 2)->nullable();

            // Inventario y Lotes (PEPS)
            $table->decimal('stock_minimo', 10, 2)->default(5);
            $table->boolean('maneja_lotes')->default(false);
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};