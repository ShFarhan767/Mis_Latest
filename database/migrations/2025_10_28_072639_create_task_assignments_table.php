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
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete(); // only role=employee
            $table->date('start_date'); // default: now()
            $table->date('end_date')->nullable();
            $table->integer('committed_hours')->nullable();
            $table->enum('status', [
                'New', 'Assigned', 'Pending', 'Working', 'Complete', 'Approved', 'Cancelled', 'Reissue', 'Staff',
            ])->default('New');
            $table->text('reissue_comment')->nullable();
            $table->text('complete_note')->nullable();
            $table->text('cancelled_note')->nullable();
            $table->text('approved_note')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete(); // admin who assigned
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_assignments');
    }
};
