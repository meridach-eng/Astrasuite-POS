<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_negocio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_comercial');
            $table->string('razon_social');
            $table->string('nit', 20)->default('CF');
            $table->string('regimen_impuestos')->default('Pequeño Contribuyente 5%');
            $table->string('direccion')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('sitio_web')->nullable();
            $table->string('logo')->nullable();

            // Configuración Felplex FEL
            $table->boolean('fel_habilitado')->default(false);
            $table->string('felplex_id')->nullable();
            $table->text('felplex_api_key')->nullable();
            $table->string('felplex_url')->default('https://api.felplex.com');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_negocio');
    }
};