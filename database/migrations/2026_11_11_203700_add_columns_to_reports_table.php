<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('product_id')->after('id')->constrained('products')->restrictOnDelete();
            $table->foreignId('reporter_id')->after('product_id')->constrained('users')->cascadeOnDelete();
            $table->string('alasan', 50)->after('reporter_id');
            $table->text('keterangan')->after('alasan');
            $table->string('bukti')->nullable()->after('keterangan');
            $table->string('status', 20)->default('pending')->after('bukti');
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['reporter_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn([
                'product_id', 'reporter_id', 'alasan', 'keterangan',
                'bukti', 'status', 'reviewed_by', 'reviewed_at',
            ]);
        });
    }
};
