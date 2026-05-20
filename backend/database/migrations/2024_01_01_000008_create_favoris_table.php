<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favoris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('locataire_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('appartement_id')->constrained('appartements')->cascadeOnDelete();
            $table->unique(['locataire_id', 'appartement_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favoris');
    }
};
