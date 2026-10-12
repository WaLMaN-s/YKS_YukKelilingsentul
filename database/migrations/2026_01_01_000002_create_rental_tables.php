<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('bank'); // bank|qris|cash
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percent'); // percent|fixed
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_transaction')->default(0);
            $table->unsignedInteger('max_discount')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('used')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('promo_id')->nullable()->constrained('promos')->nullOnDelete();
            $table->string('renter_name');
            $table->string('renter_phone', 30);
            $table->string('renter_email');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->unsignedInteger('extra_fee')->default(0);
            $table->unsignedInteger('deposit')->default(0);
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });

        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('motorcycle_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('price_per_day');
            $table->unsignedSmallInteger('days');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('rental_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->unique()->constrained()->cascadeOnDelete();
            $table->dateTime('returned_at');
            $table->string('condition')->default('baik');   // baik|ringan|berat
            $table->string('fuel_level')->default('full');
            $table->unsignedInteger('late_hours')->default(0);
            $table->unsignedInteger('late_fee')->default(0);
            $table->text('damage_description')->nullable();
            $table->unsignedInteger('damage_fee')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('proof_path')->nullable();      // disk privat
            $table->string('status')->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('promo_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('discount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_usages');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('rental_returns');
        Schema::dropIfExists('rental_items');
        Schema::dropIfExists('rentals');
        Schema::dropIfExists('promos');
        Schema::dropIfExists('payment_methods');
    }
};
