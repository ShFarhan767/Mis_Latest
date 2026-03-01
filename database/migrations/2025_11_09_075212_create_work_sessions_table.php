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
        Schema::create('work_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_assignment_id')->constrained()->onDelete('cascade');
                $table->timestamp('start_time')->nullable();
                $table->timestamp('stop_time')->nullable();
                $table->integer('duration_minutes')->default(0); // auto-calc stop-start
                $table->string('duration_display')->nullable();
                $table->enum('status', ['active', 'stopped'])->default('active');
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_sessions');
    }
};
