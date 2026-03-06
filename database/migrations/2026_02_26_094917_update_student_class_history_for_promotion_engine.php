<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('student_class_history', function (Blueprint $table) {
            // Add missing columns for promotion engine
            $table->foreignId('term_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('exited_at')->nullable();
            $table->string('status')->default('active'); // active, promoted, repeated, transferred, graduated
            $table->text('remarks')->nullable();
            
            // Add indexes for performance
            $table->index(['student_id', 'status']);
            $table->index(['class_id', 'status']);
            $table->index(['academic_year_id', 'status']);
        });
        
        // Backfill existing records
        DB::table('student_class_history')->whereNull('enrolled_at')->update([
            'enrolled_at' => now(),
            'status' => 'active'
        ]);
    }

    public function down()
    {
        Schema::table('student_class_history', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'status']);
            $table->dropIndex(['class_id', 'status']);
            $table->dropIndex(['academic_year_id', 'status']);
            
            $table->dropColumn(['term_id', 'enrolled_at', 'exited_at', 'status', 'remarks']);
        });
    }
};
