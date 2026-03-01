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
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('mobile')->unique();
                $table->string('email')->nullable()->unique();
                $table->string('designation')->nullable();
                $table->string('password');
                $table->string('code', 4); // 4-digit generated code
                $table->enum('status', ['Running', 'Suspend', 'Disable'])->default('Running');
                $table->string('role')->default('user');
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
