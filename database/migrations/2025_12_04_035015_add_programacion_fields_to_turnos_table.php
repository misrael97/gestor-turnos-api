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
        Schema::table('turnos', function (Blueprint $table) {
            $table->boolean('programado')->default(false)->after('cola');
            $table->date('fecha_programada')->nullable()->after('programado');
            $table->time('hora_programada')->nullable()->after('fecha_programada');
            $table->string('tipo', 50)->default('presencial')->after('hora_programada'); // 'presencial' o 'online'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn(['programado', 'fecha_programada', 'hora_programada', 'tipo']);
        });
    }
};
