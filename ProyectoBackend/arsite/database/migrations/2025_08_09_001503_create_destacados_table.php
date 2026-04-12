<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('destacados', function (Blueprint $table) {
            $table->id('des_id');
            $table->string('des_titulo', 50);
            $table->string('des_subtitulo', 80);
            $table->string('des_texto_boton', 40);
            $table->string('des_enlace_boton',255);
            $table->string('des_imagen',255);
            $table->date('des_fecha_publicacion')->nullable();
            $table->date('des_fecha_terminacion')->nullable();
            $table->unsignedInteger('des_orden')->nullable();
            $table->enum('des_estatus',['Publicado','Guardado'])->default('Guardado');
            $table->foreignID('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destacados');
    }
};
