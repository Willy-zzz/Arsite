<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('ban_titulo', 200)->change();
            $table->string('ban_subtitulo', 300)->change();
        });
    }

    public function down(): void {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('ban_titulo', 100)->change();
            $table->string('ban_subtitulo', 200)->change();
        });
    }
};