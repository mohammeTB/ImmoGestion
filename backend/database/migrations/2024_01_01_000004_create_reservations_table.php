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
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('nb_people');
            $table->enum('status', ['pending', 'accepted', 'failed', 'canceled', 'completed'])->default('pending');
            $table->decimal('total_price', 10, 2);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('proprietaire_amount', 10, 2)->default(0);
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
