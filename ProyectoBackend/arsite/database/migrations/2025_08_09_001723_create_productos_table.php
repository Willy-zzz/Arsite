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
        Schema::create('productos', function (Blueprint $table) {
            $table->id('pro_id');
            $table->string('pro_nombre',25);
            $table->string('pro_imagen',255);
            $table->unsignedInteger('pro_orden')->nullable();
            $table->enum('pro_estatus',['Publicado','Guardado'])->default('Guardado');
            $table->foreignID('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
