<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->string('status')->default('active'); // active, finalized, locked
            $table->index('status');
        });
        
        // Backfill existing records - assume locked = true means locked, false means active
        DB::table('terms')->where('locked', true)->update(['status' => 'locked']);
        DB::table('terms')->where('locked', false)->update(['status' => 'active']);
    }

    public function down()
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
