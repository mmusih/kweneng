<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            if (! Schema::hasColumn('homeworks', 'attachment_deleted_at')) {
                $table->timestamp('attachment_deleted_at')->nullable()->after('published_at');
            }

            if (! Schema::hasColumn('homeworks', 'attachment_deleted_by')) {
                $table->foreignId('attachment_deleted_by')
                    ->nullable()
                    ->after('attachment_deleted_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('homeworks', 'attachment_deleted_reason')) {
                $table->string('attachment_deleted_reason', 80)->nullable()->after('attachment_deleted_by');
            }

            if (! Schema::hasColumn('homeworks', 'attachment_storage_released_bytes')) {
                $table->unsignedBigInteger('attachment_storage_released_bytes')->nullable()->after('attachment_deleted_reason');
            }

            if (Schema::hasColumn('homeworks', 'term_id')) {
                $table->index(['term_id', 'deleted_at'], 'homeworks_term_deleted_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            if (Schema::hasColumn('homeworks', 'term_id') && Schema::hasColumn('homeworks', 'deleted_at')) {
                $table->dropIndex('homeworks_term_deleted_index');
            }

            if (Schema::hasColumn('homeworks', 'attachment_deleted_by')) {
                $table->dropConstrainedForeignId('attachment_deleted_by');
            }

            foreach ([
                'attachment_storage_released_bytes',
                'attachment_deleted_reason',
                'attachment_deleted_at',
            ] as $column) {
                if (Schema::hasColumn('homeworks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
