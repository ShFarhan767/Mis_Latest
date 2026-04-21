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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->nullable(); // ✅ this is needed
            $table->string('shop_name')->nullable();
            $table->string('title'); // ✅ Added title column
            $table->text('details')->nullable();
            $table->date('start_date')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('status', [
                'New',
                'Assigned',
                'Pending',
                'Working',
                'Complete',
                'Approved',
                'Cancelled',
                'Reissue',
                'Staff',
            ])->default('New');
            $table->enum('staff_decision', ['Pending', 'Approved', 'Declined' ,'Declined Trash'])->default('Pending');
            $table->text('decline_note')->nullable();
            $table->text('approve_note')->nullable();
            $table->text('reissue_comment')->nullable();
            $table->text('complete_note')->nullable();
            $table->text('cancelled_note')->nullable();
            $table->text('approved_note')->nullable();
            $table->text('declined_trash_note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
