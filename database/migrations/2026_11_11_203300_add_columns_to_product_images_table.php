<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->foreignUuid('product_id')->after('id')->constrained('products')->cascadeOnDelete();
            $table->string('path')->after('product_id');
            $table->boolean('is_cover')->default(false)->after('path');
            $table->unsignedTinyInteger('urutan')->default(0)->after('is_cover');
            $table->index(['product_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'urutan']);
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'path', 'is_cover', 'urutan']);
        });
    }
};
