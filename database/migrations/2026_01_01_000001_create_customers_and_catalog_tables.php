<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('nik')->nullable();          // terenkripsi
            $table->text('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('sim_number')->nullable();   // terenkripsi
            $table->string('ktp_path')->nullable();   // disk privat
            $table->string('sim_path')->nullable();   // disk privat
            $table->timestamps();
        });

        Schema::create('motor_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('motorcycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('motor_category_id')->nullable()->constrained('motor_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('brand');
            $table->string('model')->nullable();
            $table->string('plate_number')->nullable()->unique();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedSmallInteger('cc');
            $table->string('transmission')->default('Manual');
            $table->unsignedTinyInteger('capacity')->default(2);
            $table->string('color')->nullable();
            $table->unsignedInteger('price_daily');
            $table->unsignedInteger('price_weekly')->nullable();
            $table->unsignedInteger('price_monthly')->nullable();
            $table->unsignedInteger('deposit')->default(0);
            $table->text('description')->nullable();
            $table->json('facilities')->nullable();
            $table->text('terms')->nullable();
            $table->string('status')->default('available')->index(); // available|maintenance|inactive
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_promo')->default(false);
            $table->timestamps();
        });

        Schema::create('motor_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('motorcycle_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('motor_images');
        Schema::dropIfExists('motorcycles');
        Schema::dropIfExists('motor_categories');
        Schema::dropIfExists('customers');
    }
};
