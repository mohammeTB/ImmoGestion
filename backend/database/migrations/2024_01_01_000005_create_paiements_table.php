<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->enum('methode', ['carte', 'paypal', 'stripe', 'autre']);
            $table->enum('statut', ['en_attente', 'paye', 'echoue', 'rembourse'])->default('en_attente');
            $table->string('transaction_id', 255)->nullable();
            $table->string('facture_url', 255)->nullable();
            $table->timestamp('paye_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
