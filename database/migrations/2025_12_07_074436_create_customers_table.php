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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('email')->nullable();
            $table->string('shop_type')->nullable();
            $table->string('country_name')->nullable(); // comma separated
            $table->string('locations')->nullable(); // comma separated
            $table->string('lead_source')->nullable();
            $table->string('interest_level')->nullable();
            $table->json('service_type')->nullable();
            $table->text('feature_need')->nullable();
            $table->text('our_commitment')->nullable();
            $table->string('offer_connect')->nullable();
            $table->text('client_behaviour')->nullable();
            $table->enum('status', ['New','Contacted','In Progress','Completed','Cancelled','Final Client'])->default('New');
            $table->enum('staff_status', [
                'New',
                'Interested',
                'Serious Interested',
                'Call For Demo',
                'Need To Show Demo',
                'Need Direct Meeting',
                'Future',
                'Unwanted',
                'Cancelled'
            ])->default('New');
            $table->date('last_contact_date')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->text('last_discuss_note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_staff_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
