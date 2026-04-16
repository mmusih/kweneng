<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_borrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_copy_id')->constrained('book_copies')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('issued_at');
            $table->date('due_at');
            $table->date('returned_at')->nullable();
            $table->enum('status', ['borrowed', 'returned', 'overdue', 'lost'])->default('borrowed');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['teacher_id', 'status']);
            $table->index(['due_at', 'status']);
            $table->index(['book_copy_id', 'status']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE library_borrowings
                ADD CONSTRAINT chk_library_borrowings_one_borrower
                CHECK (
                    (student_id IS NOT NULL AND teacher_id IS NULL)
                    OR
                    (student_id IS NULL AND teacher_id IS NOT NULL)
                )
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('library_borrowings');
    }
};
