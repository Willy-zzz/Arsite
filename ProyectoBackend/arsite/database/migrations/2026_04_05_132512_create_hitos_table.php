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
        Schema::create('hitos', function (Blueprint $table) {
            $table->id('hit_id');
            $table->string('hit_titulo', 150);  
            $table->text('hit_descripcion')->nullable();
            $table->string('hit_imagen', 255)->nullable();
            $table->string('hit_categoria', 50)->nullable();
            $table->date('hit_fecha')->nullable();
            $table->unsignedInteger('hit_orden')->nullable();
            $table->enum('hit_estatus', ['Publicado', 'Guardado'])->default('Guardado');
            $table->foreignID('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hitos');
    }
};
