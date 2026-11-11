<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('firebase_uid')->nullable()->unique()->after('id');
            $table->string('username')->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('username');
            $table->string('phone')->nullable()->after('avatar');
            $table->string('kota')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('kota');
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
            $table->dropUnique(['firebase_uid']);
            $table->dropUnique(['username']);
            $table->dropColumn(['firebase_uid', 'username', 'avatar', 'phone', 'kota', 'bio']);
        });
    }
};
