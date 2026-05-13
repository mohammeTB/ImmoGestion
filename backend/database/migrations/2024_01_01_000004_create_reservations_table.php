<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appartement_id')->constrained('appartements')->cascadeOnDelete();
            $table->foreignId('locataire_id')->constrained('users')->cascadeOnDelete();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->unsignedTinyInteger('nb_personnes');
            $table->unsignedTinyInteger('nb_nuits');
            $table->enum('statut', ['en_attente', 'acceptee', 'refusee', 'annulee', 'terminee'])->default('en_attente');
            $table->decimal('prix_total', 10, 2);
            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded'
            ])->default('pending');
            $table->string('reference',50)->unique();
            $table->timestamps();
            $table->timestamp('confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
