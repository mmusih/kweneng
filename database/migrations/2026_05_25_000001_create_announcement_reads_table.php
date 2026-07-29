<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('announcement_id');
            $table->timestamp('read_at')->useCurrent();

            $table->unique(['parent_id', 'announcement_id']);

            $table->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade');
            $table->foreign('announcement_id')->references('id')->on('announcements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
    }
};
