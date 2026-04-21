<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('staff','admin','employee','demo_presenter') NOT NULL DEFAULT 'employee'");
            return;
        }

        if ($driver === 'sqlite') {
            // SQLite doesn't support altering ENUM columns; role is effectively stored as TEXT.
            return;
        }

        // For other drivers, you may need a driver-specific migration.
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('staff','admin','employee') NOT NULL DEFAULT 'employee'");
            return;
        }

        if ($driver === 'sqlite') {
            return;
        }
    }
};
