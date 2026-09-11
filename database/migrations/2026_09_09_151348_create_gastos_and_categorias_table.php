<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gasto_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_referencia');
            $table->date('fecha');
            $table->foreignId('gasto_categoria_id')->constrained('gasto_categorias')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->decimal('monto', 12, 2);
            $table->string('metodo_pago')->default('Efectivo');
            $table->boolean('pagado')->default(true); // Control de cuentas por cobrar/pagar
            $table->text('descripcion')->nullable();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
        Schema::dropIfExists('gasto_categorias');
    }
};