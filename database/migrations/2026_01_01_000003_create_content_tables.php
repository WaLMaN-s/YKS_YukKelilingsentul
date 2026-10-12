<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('price');
            $table->string('duration')->default('1 Hari');
            $table->string('destination')->nullable();
            $table->text('description')->nullable();
            $table->json('facilities')->nullable();
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('quota')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tour_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_package_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('quota')->default(10);
            $table->unsignedSmallInteger('booked')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('motorcycle_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status')->default('pending')->index(); // pending|approved|rejected
            $table->timestamps();
        });

        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image');
            $table->string('category')->default('motor'); // motor|perjalanan|wisata|customer
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('galleries');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('tour_schedules');
        Schema::dropIfExists('tour_packages');
    }
};
