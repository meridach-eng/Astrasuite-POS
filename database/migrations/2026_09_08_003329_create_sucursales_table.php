<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Central, Xela, Zona 1
            $table->string('codigo_establecimiento_sat', 10)->default('1'); // Código SAT obligatorio para FEL
            $table->string('direccion')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};