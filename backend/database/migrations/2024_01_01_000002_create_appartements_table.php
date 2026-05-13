<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appartements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proprietaire_id')->constrained('users')->cascadeOnDelete();
            $table->string('titre', 150);
            $table->text('description')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('pays', 100)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->decimal('prix_par_nuit', 10, 2);
            $table->integer('capacite');
            $table->enum('type_logement', ['appartement', 'villa', 'studio', 'maison', 'chambre']);
            $table->boolean('wifi')->default(false);
            $table->boolean('piscine')->default(false);
            $table->boolean('parking')->default(false);
            $table->boolean('climatisation')->default(false);
            $table->boolean('animaux_acceptes')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appartements');
    }
};
