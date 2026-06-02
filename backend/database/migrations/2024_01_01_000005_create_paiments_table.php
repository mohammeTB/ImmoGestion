<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->string('transaction_id', 255)->nullable()->unique();
            $table->decimal('price', 10, 2);
            $table->enum('type', ['carte', 'paypal', 'stripe', 'autre']);
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('facture_url', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiments');
    }
};
