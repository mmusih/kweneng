<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->string('status')->default('open'); // open, locked, closed
            $table->index('status');
        });
        
        // Backfill existing records - assume active = open, inactive = closed
        DB::table('academic_years')->where('active', true)->update(['status' => 'open']);
        DB::table('academic_years')->where('active', false)->update(['status' => 'closed']);
    }

    public function down()
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
