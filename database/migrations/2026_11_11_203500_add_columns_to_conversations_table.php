<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('buyer_id')->after('id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->after('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->after('seller_id')->constrained('products')->restrictOnDelete();
            $table->timestamp('last_message_at')->nullable()->after('product_id');
            $table->unique(['buyer_id', 'seller_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['buyer_id', 'seller_id', 'product_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['seller_id']);
            $table->dropForeign(['buyer_id']);
            $table->dropColumn(['buyer_id', 'seller_id', 'product_id', 'last_message_at']);
        });
    }
};
