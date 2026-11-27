<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mencatat siapa yang menyelesaikan transaksi. Diperlukan karena pembeli
     * kini juga bisa mengonfirmasi penerimaan barang, sehingga riwayat dan
     * notifikasi bisa membedakan penyelesaian oleh penjual vs pembeli.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('completed_by', 20)->nullable()->after('completion_type');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('completed_by');
        });
    }
};
