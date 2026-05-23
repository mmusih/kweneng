<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_parent_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');

            // The parent invite code printed on the login slip
            $table->string('code', 12)->unique();

            // Whether this code has been consumed (parent registered with it)
            $table->boolean('used')->default(false);

            // Code expires 48 hours after generation
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index(['code', 'used', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_parent_codes');
    }
};
