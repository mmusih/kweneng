<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcement_reads', function (Blueprint $table) {
            if (! Schema::hasColumn('announcement_reads', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('read_at');
            }

            if (! Schema::hasColumn('announcement_reads', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcement_reads', function (Blueprint $table) {
            if (Schema::hasColumn('announcement_reads', 'acknowledged_at')) {
                $table->dropColumn('acknowledged_at');
            }

            if (
                Schema::hasColumn('announcement_reads', 'created_at') &&
                Schema::hasColumn('announcement_reads', 'updated_at')
            ) {
                $table->dropTimestamps();
            }
        });
    }
};
