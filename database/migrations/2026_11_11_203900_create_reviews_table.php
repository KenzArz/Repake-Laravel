<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUuid('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('seller_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('komentar')->nullable();
            $table->timestamps();

            // Agregat rating per penjual di halaman profil.
            $table->index(['seller_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
