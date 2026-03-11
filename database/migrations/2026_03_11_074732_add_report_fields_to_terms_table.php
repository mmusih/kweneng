<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->string('report_title')->nullable()->after('status');
            $table->text('report_footer_note')->nullable()->after('report_title');
            $table->text('report_office_note')->nullable()->after('report_footer_note');
            $table->text('report_extra_note')->nullable()->after('report_office_note');
        });
    }

    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn([
                'report_title',
                'report_footer_note',
                'report_office_note',
                'report_extra_note',
            ]);
        });
    }
};
