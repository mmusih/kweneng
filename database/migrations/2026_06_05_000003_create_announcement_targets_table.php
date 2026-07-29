<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcement_targets')) {
            Schema::create('announcement_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
                $table->string('target_type', 50);
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('target_value')->nullable();
                $table->timestamps();

                $table->index(['announcement_id', 'target_type']);
                $table->index(['target_type', 'target_id']);
                $table->index(['target_type', 'target_value']);
            });
        }

        Schema::table('announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('announcements', 'push_sent_at')) {
                $table->timestamp('push_sent_at')->nullable()->after('publish_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'push_sent_at')) {
                $table->dropColumn('push_sent_at');
            }
        });

        Schema::dropIfExists('announcement_targets');
    }
};
