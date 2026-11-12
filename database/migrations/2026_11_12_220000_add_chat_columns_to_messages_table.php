<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Idempotency: satu kiriman klien hanya boleh menghasilkan satu baris,
            // walau request terkirim dua kali (double tap / retry jaringan).
            $table->string('client_id', 64)->nullable()->after('sender_id');
            $table->timestamp('delivered_at')->nullable()->after('read_at');

            $table->unique(['conversation_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropUnique(['conversation_id', 'client_id']);
            $table->dropColumn(['client_id', 'delivered_at']);
        });
    }
};
