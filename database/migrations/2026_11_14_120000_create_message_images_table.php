<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['message_id', 'urutan']);
        });

        // Pindahkan gambar lama (satu per pesan) ke tabel baru agar semua
        // pesan memakai satu sumber render yang sama.
        DB::table('messages')
            ->whereNotNull('image')
            ->orderBy('id')
            ->chunkById(200, function ($messages): void {
                $now = now();

                $rows = $messages->map(fn ($message) => [
                    'message_id' => $message->id,
                    'path' => $message->image,
                    'urutan' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('message_images')->insert($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_images');
    }
};
