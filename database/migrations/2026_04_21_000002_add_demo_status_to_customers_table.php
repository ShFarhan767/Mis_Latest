<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('demo_status', ['Pending', 'Done'])->nullable()->after('demo_presenter_id');
            $table->dateTime('demo_done_at')->nullable()->after('demo_status');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['demo_status', 'demo_done_at']);
        });
    }
};

