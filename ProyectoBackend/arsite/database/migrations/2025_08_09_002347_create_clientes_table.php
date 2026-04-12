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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id('cli_id');
            $table->string('cli_nombre',60);
            $table->string('cli_logo',255);
            $table->date('cli_fecha_publicacion')->nullable();
            $table->date('cli_fecha_terminacion')->nullable();
            $table->unsignedInteger('cli_orden')->nullable();
            $table->enum('cli_estatus',['Publicado', 'Guardado'])->default('Guardado');
            $table->foreignID('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
