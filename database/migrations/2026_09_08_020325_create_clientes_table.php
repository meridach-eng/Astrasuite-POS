<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            // Datos Fiscales / Facturación FEL
            $table->string('tipo_documento', 10)->default('NIT'); // NIT, CUI (DPI), PASAPORTE
            $table->string('numero_documento', 30)->default('CF')->index();
            $table->string('nombre'); // Nombre o Razón Social registrada en SAT
            $table->string('nombre_comercial')->nullable();
            $table->string('direccion')->default('CIUDAD');

            // Contacto y Foto / Logo
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable(); // Correo para envío de factura electrónica (PDF/XML)
            $table->string('imagen')->nullable(); // Foto o logo del cliente

            // Condiciones comerciales
            $table->decimal('limite_credito', 12, 2)->default(0);
            $table->integer('dias_credito')->default(0);
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};