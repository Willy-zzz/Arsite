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
        Schema::create('banners', function (Blueprint $table) {
            $table->id('ban_id');
            $table->string('ban_titulo', 100);
            $table->string('ban_subtitulo', 200);
            $table->string('ban_texto_boton',50)->default('Saber más');
            $table->string('ban_enlace_boton', 255);
            $table->string('ban_imagen',255);
            $table->date('ban_fecha_publicacion')->nullable();
            $table->date('ban_fecha_terminacion')->nullable();
            $table->unsignedInteger('ban_orden')->nullable();
            $table->enum('ban_estatus', ['Publicado', 'Guardado'])->default('Guardado');
            $table->foreignID('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
