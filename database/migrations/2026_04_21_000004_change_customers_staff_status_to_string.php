<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('staff_status', 50)->default('New')->change();
        });
    }

    public function down(): void
    {
        // Best-effort rollback to the original enum list from the initial migration.
        Schema::table('customers', function (Blueprint $table) {
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
            ])->default('New')->change();
        });
    }
};

