<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_constraints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_setting_id')->constrained('tt_settings')->cascadeOnDelete();
            $table->string('kind', 60);
            $table->unsignedTinyInteger('weight')->default(100);
            $table->json('params')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tt_setting_id', 'kind'], 'tt_constraints_setting_kind_index');
        });

        Schema::create('tt_generation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_setting_id')->constrained('tt_settings')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('seed')->nullable();
            $table->unsignedInteger('placed_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('hard_violations')->default(0);
            $table->integer('soft_score')->default(0);
            $table->json('log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['tt_setting_id', 'status'], 'tt_generation_runs_setting_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_generation_runs');
        Schema::dropIfExists('tt_constraints');
    }
};
