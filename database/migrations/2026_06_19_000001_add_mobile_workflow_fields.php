<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'parent_absence_notice_id')) {
                $table->foreignId('parent_absence_notice_id')
                    ->nullable()
                    ->after('term_id')
                    ->constrained('parent_absence_notices')
                    ->nullOnDelete();

                $table->index(['parent_absence_notice_id', 'attendance_date'], 'attendance_parent_notice_date_index');
            }

            if (! Schema::hasColumn('attendances', 'source')) {
                $table->string('source', 30)->default('teacher')->after('status');
            }
        });

        Schema::table('homeworks', function (Blueprint $table) {
            if (! Schema::hasColumn('homeworks', 'is_graded')) {
                $table->boolean('is_graded')->default(true)->after('description');
            }

            if (! Schema::hasColumn('homeworks', 'client_request_id')) {
                $table->uuid('client_request_id')->nullable()->unique()->after('is_graded');
            }

            if (! Schema::hasColumn('homeworks', 'image_disk')) {
                $table->string('image_disk', 30)->nullable()->after('due_date');
            }

            if (! Schema::hasColumn('homeworks', 'image_path')) {
                $table->string('image_path')->nullable()->after('image_disk');
            }

            if (! Schema::hasColumn('homeworks', 'image_original_name')) {
                $table->string('image_original_name')->nullable()->after('image_path');
            }

            if (! Schema::hasColumn('homeworks', 'image_mime_type')) {
                $table->string('image_mime_type', 100)->nullable()->after('image_original_name');
            }

            if (! Schema::hasColumn('homeworks', 'image_size')) {
                $table->unsignedBigInteger('image_size')->nullable()->after('image_mime_type');
            }

            if (! Schema::hasColumn('homeworks', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('image_size');
            }

            if (! Schema::hasColumn('homeworks', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            if (Schema::hasColumn('homeworks', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            foreach ([
                'published_at',
                'image_size',
                'image_mime_type',
                'image_original_name',
                'image_path',
                'image_disk',
                'client_request_id',
                'is_graded',
            ] as $column) {
                if (Schema::hasColumn('homeworks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'parent_absence_notice_id')) {
                $table->dropConstrainedForeignId('parent_absence_notice_id');
            }

            if (Schema::hasColumn('attendances', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
