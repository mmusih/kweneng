<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','teacher','headmaster','student','parent','accounts_officer','librarian','office','register_officer','inventory') NOT NULL");
        }
    }

    public function down(): void
    {
        // Do not shrink the enum automatically: production data may already contain
        // one of the operational roles. Removing enum values would break those rows.
    }
};
