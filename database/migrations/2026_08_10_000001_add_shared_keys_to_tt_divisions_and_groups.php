<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_divisions', function (Blueprint $table) {
            $table->uuid('shared_key')->nullable()->after('name')->index();
            $table->unique(['class_id', 'shared_key'], 'tt_divisions_class_shared_unique');
        });

        Schema::table('tt_groups', function (Blueprint $table) {
            $table->uuid('shared_key')->nullable()->after('name')->index();
            $table->unique(['tt_division_id', 'shared_key'], 'tt_groups_division_shared_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tt_groups', function (Blueprint $table) {
            $table->dropUnique('tt_groups_division_shared_unique');
            $table->dropColumn('shared_key');
        });

        Schema::table('tt_divisions', function (Blueprint $table) {
            $table->dropUnique('tt_divisions_class_shared_unique');
            $table->dropColumn('shared_key');
        });
    }
};
