<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcement_reads')) {
            Schema::create('announcement_reads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('announcement_id');
                $table->unsignedBigInteger('parent_id');
                $table->timestamp('read_at')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();

                $table->unique(['announcement_id', 'parent_id']);
                $table->index('announcement_id');
                $table->index('parent_id');

                $table->foreign('announcement_id')
                    ->references('id')
                    ->on('announcements')
                    ->cascadeOnDelete();

                // parent_id is intentionally not constrained here because
                // different installs may use parents, parent_models, or another
                // table name for ParentModel. The app already validates access
                // through the authenticated user's parent relationship.
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
    }
};
