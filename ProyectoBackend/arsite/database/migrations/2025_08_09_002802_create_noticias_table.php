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
        Schema::create('noticias', function (Blueprint $table) {
            $table->id('not_id');
            $table->string('not_titulo', 100);
            $table->string('not_subtitulo', 300)->nullable();
            $table->text('not_descripcion');
            $table->string('not_portada', 255);
            $table->string('not_imagen', 255)->nullable();
            $table->string('not_video', 255)->nullable();
            $table->dateTime('not_publicacion')->nullable();
            $table->enum('not_estatus', ['Publicado', 'Guardado'])->default('Guardado');
            $table->foreignID('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('noticias');
    }
};
