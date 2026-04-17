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
        if (!Schema::hasColumn('clientes', 'cli_orden')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->unsignedInteger('cli_orden')->nullable()->after('cli_fecha_terminacion');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('clientes', 'cli_orden')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropColumn('cli_orden');
            });
        }
    }
};
