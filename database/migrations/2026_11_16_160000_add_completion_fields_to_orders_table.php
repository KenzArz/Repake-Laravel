<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan penyelesaian & pembatalan transaksi untuk fitur "Selesaikan"
     * dan riwayat transaksi. `completion_type` mencatat apakah stok habis
     * atau masih tersedia saat transaksi diselesaikan; `cancelled_by` dan
     * `cancel_reason` merekam alasan pembatalan agar notifikasi & riwayat
     * tidak menyesatkan.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('completion_type', 20)->nullable()->after('status');
            $table->string('cancelled_by', 20)->nullable()->after('completion_type');
            $table->string('cancel_reason', 50)->nullable()->after('cancelled_by');

            // Riwayat & notifikasi sering menanyakan status produk aktif.
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'status']);
            $table->dropColumn(['completion_type', 'cancelled_by', 'cancel_reason']);
        });
    }
};