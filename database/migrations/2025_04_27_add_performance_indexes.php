<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add indexes for common queries to improve performance
     */
    public function up(): void
    {
        // Customer table - frequently searched/filtered columns
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->index('assigned_staff_id');
                $table->index('demo_presenter_id');
                $table->index('staff_status');
                $table->index('status');
                $table->index('created_by');
                $table->index('created_at');
            });
        }

        // CustomerDemoNote - for unread count queries
        if (Schema::hasTable('customer_demo_notes')) {
            Schema::table('customer_demo_notes', function (Blueprint $table) {
                $table->index('customer_id');
                $table->index('user_id');
                $table->index(['customer_id', 'user_id']);
            });
        }

        // CustomerDemoNoteRead - for tracking reads
        if (Schema::hasTable('customer_demo_note_reads')) {
            Schema::table('customer_demo_note_reads', function (Blueprint $table) {
                $table->index(['customer_id', 'user_id']);
            });
        }

        // CustomerHistory - for history tracking
        if (Schema::hasTable('customer_histories')) {
            Schema::table('customer_histories', function (Blueprint $table) {
                $table->index('customer_id');
                $table->index('staff_id');
                $table->index(['customer_id', 'staff_id']);
            });
        }

        // CustomerHistoryRead - for tracking reads
        if (Schema::hasTable('customer_history_reads')) {
            Schema::table('customer_history_reads', function (Blueprint $table) {
                $table->index(['customer_id', 'user_id']);
            });
        }

        // Area table indexes
        if (Schema::hasTable('areas')) {
            Schema::table('areas', function (Blueprint $table) {
                $table->index('status');
                $table->index('created_by');
            });
        }

        // BusinessType table indexes
        if (Schema::hasTable('business_types')) {
            Schema::table('business_types', function (Blueprint $table) {
                $table->index('status');
            });
        }

        // Client table indexes
        if (Schema::hasTable('clients')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->index('status');
                $table->index('area_name');
            });
        }

        // User table indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('role');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all added indexes
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex(['assigned_staff_id']);
                $table->dropIndex(['demo_presenter_id']);
                $table->dropIndex(['staff_status']);
                $table->dropIndex(['status']);
                $table->dropIndex(['created_by']);
                $table->dropIndex(['created_at']);
            });
        }

        if (Schema::hasTable('customer_demo_notes')) {
            Schema::table('customer_demo_notes', function (Blueprint $table) {
                $table->dropIndex(['customer_id']);
                $table->dropIndex(['user_id']);
                $table->dropIndex(['customer_id', 'user_id']);
            });
        }

        if (Schema::hasTable('customer_demo_note_reads')) {
            Schema::table('customer_demo_note_reads', function (Blueprint $table) {
                $table->dropIndex(['customer_id', 'user_id']);
            });
        }

        if (Schema::hasTable('customer_histories')) {
            Schema::table('customer_histories', function (Blueprint $table) {
                $table->dropIndex(['customer_id']);
                $table->dropIndex(['staff_id']);
                $table->dropIndex(['customer_id', 'staff_id']);
            });
        }

        if (Schema::hasTable('customer_history_reads')) {
            Schema::table('customer_history_reads', function (Blueprint $table) {
                $table->dropIndex(['customer_id', 'user_id']);
            });
        }

        if (Schema::hasTable('areas')) {
            Schema::table('areas', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropIndex(['created_by']);
            });
        }

        if (Schema::hasTable('business_types')) {
            Schema::table('business_types', function (Blueprint $table) {
                $table->dropIndex(['status']);
            });
        }

        if (Schema::hasTable('clients')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropIndex(['area_name']);
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['role']);
            });
        }
    }
};
