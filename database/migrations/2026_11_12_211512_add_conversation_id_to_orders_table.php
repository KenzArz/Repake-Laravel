<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hubungkan order ke percakapan yang melahirkannya agar transaksi bisa
     * ditandai "selesai" langsung dari ruang chat (pembeli).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('conversation_id')
                ->nullable()
                ->after('id')
                ->constrained('conversations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conversation_id');
        });
    }
};
