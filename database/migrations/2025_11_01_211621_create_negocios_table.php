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
    Schema::create('negocios', function (Blueprint $table) {
    $table->id();
    $table->string('nombre');
    $table->string('direccion')->nullable();
    $table->string('telefono')->nullable();
    $table->string('horario')->nullable();
    $table->foreignId('agente_id')->nullable()->constrained('users'); // Agente responsable
    $table->timestamps();
});

}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};
