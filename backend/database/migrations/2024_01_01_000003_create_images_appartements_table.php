<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images_appartements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appartement_id')->constrained('appartements')->cascadeOnDelete();
            $table->string('image_url', 255);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images_appartements');
    }
};
