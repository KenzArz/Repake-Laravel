<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')->after('id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->after('conversation_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable()->after('sender_id');
            $table->string('image')->nullable()->after('body');
            $table->string('type', 20)->default('text')->after('image');
            $table->unsignedBigInteger('offer_amount')->nullable()->after('type');
            $table->string('offer_status', 20)->nullable()->after('offer_amount');
            $table->timestamp('read_at')->nullable()->after('offer_status');

            // Polling chat memakai WHERE conversation_id = ? AND id > ?.
            $table->index(['conversation_id', 'id']);
            $table->index(['sender_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['sender_id', 'read_at']);
            $table->dropIndex(['conversation_id', 'id']);
            $table->dropForeign(['sender_id']);
            $table->dropForeign(['conversation_id']);
            $table->dropColumn([
                'conversation_id', 'sender_id', 'body', 'image', 'type',
                'offer_amount', 'offer_status', 'read_at',
            ]);
        });
    }
};
