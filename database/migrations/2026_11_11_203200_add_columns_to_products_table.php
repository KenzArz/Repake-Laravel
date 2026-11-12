<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignUuid('user_id')->after('id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->after('user_id')->constrained('categories')->nullOnDelete();
            $table->string('judul')->after('category_id');
            $table->string('slug')->unique()->after('judul');
            $table->unsignedBigInteger('harga')->after('slug');
            $table->string('kondisi', 50)->after('harga');
            $table->string('status', 20)->default('dijual')->after('kondisi');
            $table->text('deskripsi')->after('status');
            $table->string('kota')->after('deskripsi')->index();
            $table->string('patokan_lokasi')->nullable()->after('kota');
            $table->boolean('is_negotiable')->default(true)->after('patokan_lokasi');
            $table->softDeletes()->after('updated_at');

            // Listing "terbaru" global dan filter per kategori.
            $table->index(['status', 'created_at']);
            $table->index(['category_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'status', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['kota']);
            $table->dropSoftDeletes();
            $table->dropForeign(['category_id']);
            $table->dropForeign(['user_id']);
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'user_id', 'category_id', 'judul', 'slug', 'harga', 'kondisi',
                'status', 'deskripsi', 'kota', 'patokan_lokasi', 'is_negotiable',
            ]);
        });
    }
};
