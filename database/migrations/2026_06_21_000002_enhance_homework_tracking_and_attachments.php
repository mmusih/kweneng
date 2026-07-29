<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->makeHomeworkTotalMarksNullable();

        Schema::table('homeworks', function (Blueprint $table) {
            if (! Schema::hasColumn('homeworks', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('due_date');
            }

            if (! Schema::hasColumn('homeworks', 'attachment_original_name')) {
                $table->string('attachment_original_name')->nullable()->after('attachment_path');
            }

            if (! Schema::hasColumn('homeworks', 'attachment_mime')) {
                $table->string('attachment_mime', 120)->nullable()->after('attachment_original_name');
            }

            if (! Schema::hasColumn('homeworks', 'attachment_size')) {
                $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
            }
        });

        Schema::table('homework_marks', function (Blueprint $table) {
            if (! Schema::hasColumn('homework_marks', 'submission_status')) {
                $table->string('submission_status', 40)->default('submitted')->after('student_id');
                $table->index(['homework_id', 'submission_status'], 'homework_marks_homework_status_index');
            }

            if (! Schema::hasColumn('homework_marks', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable()->after('remarks');
            }

            if (! Schema::hasColumn('homework_marks', 'status_updated_by')) {
                $table->foreignId('status_updated_by')
                    ->nullable()
                    ->after('status_updated_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('homework_marks', function (Blueprint $table) {
            if (Schema::hasColumn('homework_marks', 'status_updated_by')) {
                $table->dropConstrainedForeignId('status_updated_by');
            }

            if (Schema::hasColumn('homework_marks', 'status_updated_at')) {
                $table->dropColumn('status_updated_at');
            }

            if (Schema::hasColumn('homework_marks', 'submission_status')) {
                $table->dropIndex('homework_marks_homework_status_index');
                $table->dropColumn('submission_status');
            }
        });

        Schema::table('homeworks', function (Blueprint $table) {
            foreach (['attachment_size', 'attachment_mime', 'attachment_original_name', 'attachment_path'] as $column) {
                if (Schema::hasColumn('homeworks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        $this->makeHomeworkTotalMarksRequired();
    }

    private function makeHomeworkTotalMarksNullable(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE homeworks MODIFY total_marks DECIMAL(8, 2) NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE homeworks ALTER COLUMN total_marks DROP NOT NULL');
        }
    }

    private function makeHomeworkTotalMarksRequired(): void
    {
        DB::table('homeworks')
            ->whereNull('total_marks')
            ->update(['total_marks' => 0]);

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE homeworks MODIFY total_marks DECIMAL(8, 2) NOT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE homeworks ALTER COLUMN total_marks SET NOT NULL');
        }
    }
};
