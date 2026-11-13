<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generalisasi ulasan menjadi dua arah:
     * - `seller_id` -> `reviewee_id` (target bisa penjual maupun pembeli).
     * - `product_id` nullable (ulasan penjual->pembeli tidak terikat produk).
     * - `order_id` tidak lagi unik global, tetapi unik per (order, reviewer)
     *   agar kedua pihak boleh mengulas transaksi yang sama.
     */
    public function up(): void
    {
        // Unique index pada order_id dipakai oleh foreign key, jadi FK harus
        // dilepas dulu sebelum index-nya bisa dihapus.
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropUnique('reviews_order_id_unique');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->renameColumn('seller_id', 'reviewee_id');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->uuid('product_id')->nullable()->change();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['order_id', 'reviewer_id']);
        });

        // Pasang kembali FK order; composite unique di atas sudah menyediakan
        // index dengan order_id sebagai kolom paling kiri.
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropUnique(['order_id', 'reviewer_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->uuid('product_id')->nullable(false)->change();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->renameColumn('reviewee_id', 'seller_id');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }
};
