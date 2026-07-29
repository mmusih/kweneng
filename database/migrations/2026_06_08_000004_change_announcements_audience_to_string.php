<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        /*
         * The old announcements.audience column is likely an ENUM that does not
         * include new targeting values such as form_level and class_group.
         * Convert it to VARCHAR so the future-proof announcement_targets table
         * can store detailed targets while announcements.audience stores the
         * general targeting mode.
         */
        DB::statement("ALTER TABLE announcements MODIFY audience VARCHAR(50) NOT NULL DEFAULT 'all'");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Keep this as string-safe rollback to avoid truncating newer audience values.
        DB::statement("ALTER TABLE announcements MODIFY audience VARCHAR(50) NOT NULL DEFAULT 'all'");
    }
};
