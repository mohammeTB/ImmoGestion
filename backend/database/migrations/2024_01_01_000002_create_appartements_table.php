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
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('capacity');
            $table->enum('type', ['appartement', 'villa', 'studio', 'maison', 'chambre']);
            $table->boolean('wifi')->default(false);
            $table->boolean('piscine')->default(false);
            $table->boolean('parking')->default(false);
            $table->boolean('climatisation')->default(false);
            $table->boolean('animals')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appartements');
    }
};
