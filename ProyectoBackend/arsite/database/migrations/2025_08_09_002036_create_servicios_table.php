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
        Schema::create('servicios', function (Blueprint $table) {
            $table->id('ser_id');
            $table->string('ser_titulo', 100);
            $table->text('ser_descripcion');
            $table->string('ser_imagen')->nullable();
            $table->unsignedInteger('ser_orden')->default(0);
            $table->enum('ser_estatus',['Publicado','Guardado'])->default('Guardado');
            $table->date('ser_fecha_publicacion')->nullable();
            $table->date('ser_fecha_terminacion')->nullable();
            $table->foreignID('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
