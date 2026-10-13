<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_packages', function (Blueprint $table) {
            $table->json('excludes')->nullable()->after('facilities'); // daftar "belum termasuk"
            $table->unsignedSmallInteger('min_participants')->default(1)->after('quota'); // ketentuan minimal peserta
        });
    }

    public function down(): void
    {
        Schema::table('tour_packages', function (Blueprint $table) {
            $table->dropColumn(['excludes', 'min_participants']);
        });
    }
};
