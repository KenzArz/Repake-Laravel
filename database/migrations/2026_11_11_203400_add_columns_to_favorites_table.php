<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->after('user_id')->constrained('products')->restrictOnDelete();
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'product_id']);
        });
    }
};
