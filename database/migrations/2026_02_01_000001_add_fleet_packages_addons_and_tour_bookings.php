<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Armada: satu baris = satu tipe/model, jumlah unit di kolom stock (mis. 70 trail, 120 jeep).
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->string('vehicle_type')->default('trail')->index(); // trail|jeep
            $table->unsignedSmallInteger('stock')->default(1);
        });

        Schema::create('rental_packages', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_type')->default('jeep');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('includes')->nullable();
            $table->unsignedInteger('price');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->string('applies_to')->default('both'); // rental|tour|both
            $table->string('per')->default('person');      // person|booking
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->constrained('rental_packages')->nullOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedSmallInteger('people')->nullable();
            $table->json('addons')->nullable();
            $table->unsignedInteger('addons_total')->default(0);
            $table->string('source')->default('website'); // website|whatsapp|instagram|walk_in
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('forfeited')->default(false);
            $table->unsignedTinyInteger('rescheduled_count')->default(0);
        });

        Schema::table('rental_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantity')->default(1);
        });

        Schema::table('tour_packages', function (Blueprint $table) {
            $table->boolean('own_motor')->default(false); // paket bawa motor pribadi
        });

        Schema::create('tour_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('tour_package_id')->constrained()->restrictOnDelete();
            $table->foreignId('tour_schedule_id')->nullable()->constrained('tour_schedules')->nullOnDelete();
            $table->string('trip_type')->default('open'); // open|private
            $table->date('trip_date');
            $table->unsignedSmallInteger('people');
            $table->unsignedInteger('price_per_person');
            $table->unsignedBigInteger('subtotal');
            $table->json('addons')->nullable();
            $table->unsignedInteger('addons_total')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('contact_name');
            $table->string('contact_phone', 30);
            $table->text('notes')->nullable();
            $table->string('status')->default('waiting_payment')->index();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('proof_path')->nullable();
            $table->text('payment_note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancelled_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('forfeited')->default(false);
            $table->unsignedTinyInteger('rescheduled_count')->default(0);
            $table->string('source')->default('website');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_bookings');

        Schema::table('tour_packages', fn (Blueprint $t) => $t->dropColumn('own_motor'));
        Schema::table('rental_items', fn (Blueprint $t) => $t->dropColumn('quantity'));
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
            $table->dropColumn(['quantity', 'people', 'addons', 'addons_total', 'source', 'cancelled_at', 'forfeited', 'rescheduled_count']);
        });

        Schema::dropIfExists('addons');
        Schema::dropIfExists('rental_packages');
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->dropIndex(['vehicle_type']);
            $table->dropColumn(['vehicle_type', 'stock']);
        });
    }
};
