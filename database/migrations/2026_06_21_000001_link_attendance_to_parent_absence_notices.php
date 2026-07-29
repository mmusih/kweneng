<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'parent_absence_notice_id')) {
                $table->foreignId('parent_absence_notice_id')
                    ->nullable()
                    ->after('remarks')
                    ->constrained('parent_absence_notices')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('attendances', 'recorded_from_parent_notice')) {
                $table->boolean('recorded_from_parent_notice')
                    ->default(false)
                    ->after('parent_absence_notice_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'parent_absence_notice_id')) {
                $table->dropConstrainedForeignId('parent_absence_notice_id');
            }

            if (Schema::hasColumn('attendances', 'recorded_from_parent_notice')) {
                $table->dropColumn('recorded_from_parent_notice');
            }
        });
    }
};
