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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('business_type')->nullable();
            $table->string('operator_name')->nullable();
            $table->string('number')->unique();
            $table->string('oparetor_number')->unique();
            $table->string('project_name');
            $table->string('country_name')->nullable();
            $table->string('area_name')->nullable();
            $table->string('address')->nullable();

            // Referral info
            $table->string('referred_by_name')->nullable();
            $table->string('referred_by_number')->nullable();

            $table->enum('status', [
                'Running',
                'Not Using',
                'Closed',
                'Software Not Urgent',
                'Disappointed',
                'No Operator',
                'Another software choose',
                'Business Closed',
                'Not Happy',
                'Happy'
            ])->default('Running');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
