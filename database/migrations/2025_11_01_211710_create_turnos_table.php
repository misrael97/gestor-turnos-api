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
    Schema::create('turnos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('negocio_id')->constrained('negocios')->onDelete('cascade');
    $table->string('estado')->default('espera');
    $table->timestamp('hora_inicio')->nullable();
    $table->timestamp('hora_fin')->nullable();
    $table->timestamps();
});
}



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};
