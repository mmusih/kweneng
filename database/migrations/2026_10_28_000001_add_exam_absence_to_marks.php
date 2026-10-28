<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('marks', function (Blueprint $table) {
            $table->boolean('midterm_did_not_write')->default(false);
            $table->boolean('endterm_did_not_write')->default(false);
        });
    }
    public function down(): void
    {
        Schema::table('marks', fn (Blueprint $table) => $table->dropColumn(['midterm_did_not_write', 'endterm_did_not_write']));
    }
};
