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
        Schema::create('partners', function (Blueprint $table) {
            $table->id('par_id');
            $table->string('par_nombre',50);
            $table->string('par_logo',255);
            $table->date('par_fecha_publicacion')->nullable();
            $table->date('par_fecha_terminacion')->nullable();
            $table->unsignedInteger('par_orden')->nullable();
            $table->enum('par_estatus',['Publicado','Guardado'])->default('Guardado');
            $table->foreignID('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
