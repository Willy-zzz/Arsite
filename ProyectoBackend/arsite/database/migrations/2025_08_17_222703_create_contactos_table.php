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
        Schema::create('contactos', function (Blueprint $table) {
            $table->id('con_id');
            $table->string('con_nombre', 100);
            $table->string('con_email', 150);
            $table->string('con_telefono', 20)->nullable();
            $table->string('con_asunto', 200)->nullable();
            $table->text('con_mensaje');
            $table->string('con_empresa', 100)->nullable();
            $table->enum('con_estado', ['Nuevo', 'Leido', 'Respondido', 'Archivado'])->default('Nuevo');
            $table->string('con_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contactos');
    }
};
